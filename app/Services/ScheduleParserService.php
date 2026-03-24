<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class ScheduleParserService
{
    /**
     * Send image to Claude Vision API and get parsed schedule JSON.
     */
    public function parseImage(string $imageBase64, string $mimeType): array
    {
        $prompt = <<<'PROMPT'
You are parsing a work schedule image. Extract all schedule data and return it as JSON only — no explanation, no markdown, just raw JSON.

The table has:
- DATE column (e.g. "Mar 23")
- DAY column (e.g. "Mon")
- Shift columns with time range headers (e.g. "8-5", "8:30-5:30", "12-9", "1-10", "2-11")
- DAY-OFF column for employees with no work that day

Rules:
- Shift times like "8-5" mean 8:00 AM to 5:00 PM. Convert all shifts to 24-hour HH:MM format.
- Shifts ending in a small number (e.g. 5, 5:30, 9, 10, 11) where the end hour is less than the start are PM times. Example: "8-5" = 08:00-17:00, "12-9" = 12:00-21:00, "1-10" = 13:00-22:00, "2-11" = 14:00-23:00.
- Multiple employee names in a cell are separated by "/" — treat each as a separate assignment.
- A name with "(ABR)" annotation (e.g. "Mona(ABR)") means branch_override = "ABR", name = "Mona".
- A name with an OT annotation like "(OT1HR)" means notes = "OT1HR", name = "Mariah".
- A DAY-OFF entry of "-" means nobody has the day off — skip it.
- Use the year from the schedule header (e.g. "MARCH 2026" → year 2026). If no year shown, infer from context.
- Dates like "Mar 23" with year 2026 → "2026-03-23".

Return this exact JSON structure:
{
  "month": "March 2026",
  "rows": [
    {
      "date": "2026-03-23",
      "day": "Mon",
      "assignments": [
        {
          "name": "Eddie",
          "work_start_time": "08:00",
          "work_end_time": "17:00",
          "is_day_off": false,
          "branch_override": null,
          "notes": null
        },
        {
          "name": "Kaye",
          "work_start_time": null,
          "work_end_time": null,
          "is_day_off": true,
          "branch_override": null,
          "notes": null
        }
      ]
    }
  ]
}
PROMPT;

        $response = Http::withOptions(['verify' => env('ANTHROPIC_VERIFY_SSL', true)])
            ->withHeaders([
                'x-api-key'         => env('ANTHROPIC_API_KEY'),
                'anthropic-version' => '2023-06-01',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 4096,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mimeType,
                                'data'       => $imageBase64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Claude API error: ' . $response->body());
        }

        $content = $response->json('content.0.text') ?? '';

        // Strip any accidental markdown fences
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);

        $parsed = json_decode(trim($content), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse AI response as JSON: ' . $content);
        }

        return $parsed;
    }

    /**
     * Try to match each assignment name to an Employee record.
     * Returns the parsed array with employee_id filled in where matched.
     */
    public function matchEmployees(array $parsed, int $branchId): array
    {
        $employees = Employee::where('branch_id', $branchId)
            ->where('active', true)
            ->get(['id', 'first_name', 'nickname']);

        // Build lookup: lowercase name => employee_id
        $lookup = [];
        foreach ($employees as $emp) {
            if ($emp->nickname) {
                $lookup[strtolower(trim($emp->nickname))] = $emp->id;
            }
            $lookup[strtolower(trim($emp->first_name))] = $emp->id;
        }

        foreach ($parsed['rows'] as &$row) {
            foreach ($row['assignments'] as &$assignment) {
                $key = strtolower(trim($assignment['name']));
                $assignment['employee_id'] = $lookup[$key] ?? null;
            }
        }

        // Collect all unmatched names for the review screen warning
        $unmatched = [];
        foreach ($parsed['rows'] as $row) {
            foreach ($row['assignments'] as $assignment) {
                if (is_null($assignment['employee_id'])) {
                    $unmatched[] = $assignment['name'];
                }
            }
        }
        $parsed['unmatched_names'] = array_values(array_unique($unmatched));

        return $parsed;
    }

    /**
     * Resolve a branch override abbreviation (e.g. "ABR") to a branch_id.
     */
    public function resolveBranch(string $abbreviation): ?int
    {
        $branch = Branch::all()->first(function ($b) use ($abbreviation) {
            return stripos($b->name, $abbreviation) !== false;
        });

        return $branch?->id;
    }
}
