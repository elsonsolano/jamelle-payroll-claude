<?php

namespace App\Jobs;

use App\Models\Dtr;
use App\Models\Employee;
use App\Models\TimemarkLog;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchAttendanceJob implements ShouldQueue
{
    use Queueable;

    public int    $pageSize = 20;
    public string $dateFrom;
    public string $dateTo;

    public function __construct(
        public Employee $employee,
        string $dateFrom,
        string $dateTo
    ) {
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
    }

    public function handle(): void
    {
        $employee  = $this->employee;
        $deviceId  = $employee->timemark_id;
        $startTs   = Carbon::createFromFormat('Y-m-d', $this->dateFrom)->startOfDay()->timestamp;
        $endTs     = Carbon::createFromFormat('Y-m-d', $this->dateTo)->endOfDay()->timestamp;
        $url       = 'https://app.dayscamera.com/next/attendance/record/query/plain';
        $page      = 1;
        $cutFrom   = Carbon::createFromFormat('Y-m-d', $this->dateFrom);
        $cutTo     = Carbon::createFromFormat('Y-m-d', $this->dateTo);
        $totalFetched = 0;

        Log::info("FetchAttendanceJob: Processing employee ID {$employee->id} ({$employee->full_name}), device: {$deviceId}, range: {$this->dateFrom} to {$this->dateTo}");

        try {
            do {
                $response = Http::withOptions(['verify' => config('services.timemark.verify_ssl', true)])
                    ->post($url, [
                    'device_id'      => $deviceId,
                    'startTimestamp' => $startTs,
                    'endTimestamp'   => $endTs,
                    'page'           => $page,
                    'pageSize'       => $this->pageSize,
                ]);

                if (! $response->successful()) {
                    throw new \Exception("API request failed with status: {$response->status()}");
                }

                $json = $response->json();
                if (! isset($json['data']['records']) || ! is_array($json['data']['records'])) {
                    throw new \Exception('Unexpected response structure: data.records missing or invalid');
                }

                $records = $json['data']['records'];
                Log::info("FetchAttendanceJob: Received " . count($records) . " records from API for page {$page}");

                usort($records, fn($a, $b) => $a['state'] <=> $b['state']);

                foreach ($records as $r) {
                    $dateStr = Carbon::createFromFormat('Y/m/d', $r['date'])->format('Y-m-d');
                    $date    = Carbon::parse($dateStr);

                    // Filter to cutoff range
                    if (! $date->isSameDay($cutFrom) && ! $date->isSameDay($cutTo) && ! $date->between($cutFrom, $cutTo)) {
                        continue;
                    }

                    [$h, $m] = array_pad(explode(':', $r['time']), 2, '00');
                    $hour     = (int) $h;
                    $minute   = (int) $m;
                    $state    = (int) $r['state'];

                    // Fetch existing time_in for comparison
                    $existing = Dtr::where('employee_id', $employee->id)->where('date', $dateStr)->first();
                    $inTotal  = null;
                    if ($existing && $existing->time_in) {
                        [$inH, $inM] = array_pad(explode(':', substr($existing->time_in, 0, 5)), 2, '00');
                        $inTotal = ((int) $inH) * 60 + ((int) $inM);
                    }
                    $rawTotal = $hour * 60 + $minute;

                    // AM/PM disambiguation
                    if ($hour < 12) {
                        switch ($state) {
                            case 0: // time_in: if before 8 AM, treat as PM
                                if ($hour < 8) {
                                    $hour += 12;
                                }
                                break;
                            case 1: // am_out
                            case 2: // pm_in
                                if ($inTotal !== null && $rawTotal < $inTotal) {
                                    $hour += 12;
                                }
                                break;
                            case 3: // time_out always PM
                                $hour += 12;
                                break;
                        }
                    }

                    $time = sprintf('%02d:%02d:00', $hour, $minute);

                    $match   = ['employee_id' => $employee->id, 'date' => $dateStr];
                    $payload = [
                        'employee_id'    => $employee->id,
                        'date'           => $dateStr,
                        'late_mins'      => $existing->late_mins ?? 0,
                        'undertime_mins' => $existing->undertime_mins ?? 0,
                        'status'         => 'Approved',
                    ];

                    switch ($state) {
                        case 0:
                            $payload['time_in'] = $time;
                            break;
                        case 1:
                            $payload['am_out'] = $time;
                            break;
                        case 2:
                            $payload['pm_in'] = $time;
                            break;
                        case 3:
                            $payload['time_out']      = $time;
                            $totalHours               = (float) ($r['workingHours'] ?? 0);
                            $payload['total_hours']   = $totalHours;
                            $payload['overtime_hours'] = max(0, $totalHours - 8);
                            break;
                    }

                    Dtr::updateOrCreate($match, $payload);
                    $totalFetched++;
                }

                $page++;
                usleep(200_000);
            } while (count($records));

            TimemarkLog::create([
                'employee_id'    => $employee->id,
                'device_id'      => $deviceId,
                'fetched_at'     => now(),
                'status'         => 'success',
                'records_fetched' => $totalFetched,
                'notes'          => "Fetched range: {$this->dateFrom} to {$this->dateTo}",
            ]);

            Log::info("FetchAttendanceJob: Completed for employee {$employee->id}, total records processed: {$totalFetched}");

        } catch (\Exception $e) {
            Log::error('FetchAttendanceJob error: ' . $e->getMessage(), [
                'employee_id' => $employee->id,
                'device_id'   => $deviceId,
                'page'        => $page,
            ]);

            TimemarkLog::create([
                'employee_id'    => $employee->id,
                'device_id'      => $deviceId,
                'fetched_at'     => now(),
                'status'         => 'failed',
                'records_fetched' => $totalFetched,
                'notes'          => "Error: {$e->getMessage()}",
            ]);
        }
    }
}
