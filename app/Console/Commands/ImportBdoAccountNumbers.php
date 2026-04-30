<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ImportBdoAccountNumbers extends Command
{
    protected $signature = 'employees:import-bdo-accounts
                            {--dry-run : Show what would change without saving}
                            {--force : Overwrite existing BDO account numbers}';

    protected $description = 'Import BDO account numbers into employee records by matching employee names';

    /**
     * @var array<int, array{account: string, name: string}>
     */
    private array $accounts = [
        ['account' => '010470174269', 'name' => 'Ellaine Jame S. Berza'],
        ['account' => '010470174277', 'name' => 'Flordelyn D. Lipardo'],
        ['account' => '010470173734', 'name' => 'Menirose A. Cartil'],
        ['account' => '010470172525', 'name' => 'Rachelle Lyn B. Alisna'],
        ['account' => '010470172274', 'name' => 'Ervin S. Soberano'],
        ['account' => '010470172576', 'name' => 'Elson James B. Solano'],
        ['account' => '010470174234', 'name' => 'Jezel Rose S. Solano'],
        ['account' => '010470172479', 'name' => 'Jocelyn P. Sinco'],
        ['account' => '010470172428', 'name' => 'Janet B. Perez'],
        ['account' => '010470173769', 'name' => 'Trisha Kaye R. Pascua'],
        ['account' => '010470172312', 'name' => 'Via T. Omaque'],
        ['account' => '010470172339', 'name' => 'Vanessa T. Omaque'],
        ['account' => '010470172517', 'name' => 'Elizabeth S. Narguada'],
        ['account' => '010470172452', 'name' => 'Mariah Carmella Mosqueda'],
        ['account' => '010470173726', 'name' => 'Mona Liza C. Leones'],
        ['account' => '010470174412', 'name' => 'Anjelica A. Cutillar'],
        ['account' => '010470174471', 'name' => 'Ashlee Jane N. Bughao'],
        ['account' => '010470172363', 'name' => 'Kissie Argoncillo'],
        ['account' => '010470172282', 'name' => 'Aime N. Ajero'],
        ['account' => '010470172541', 'name' => 'Norenz John Taylaran'],
        ['account' => '010470172355', 'name' => 'Rhadaen M. Tibulan'],
        ['account' => '010470174358', 'name' => 'Jhannie M. Suazo'],
        ['account' => '010470174528', 'name' => 'John Michael M. Colina'],
        ['account' => '010470172606', 'name' => 'Jomar L. Razonable'],
        ['account' => '010470172533', 'name' => 'Archie Pimentel'],
        ['account' => '010470172398', 'name' => 'Christian Dave L. Orcajo'],
        ['account' => '010470174226', 'name' => 'Anajean A. Jumarito'],
        ['account' => '010470174404', 'name' => 'Raven Keith B. Ingutan'],
        ['account' => '010470172460', 'name' => 'Peter Saimon M. Ignacio'],
        ['account' => '010470172568', 'name' => 'Joshua De Asis'],
        ['account' => '010470174374', 'name' => 'Edward M. Canada'],
        ['account' => '010470174293', 'name' => 'Joshua C. Casia'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $employees = Employee::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $updated = 0;
        $skipped = [];
        $unmatched = [];
        $ambiguous = [];

        foreach ($this->accounts as $row) {
            $matches = $this->matchingEmployees($employees, $row['name']);

            if ($matches->isEmpty()) {
                $unmatched[] = [$row['account'], $row['name']];
                continue;
            }

            if ($matches->count() > 1) {
                $ambiguous[] = [
                    $row['account'],
                    $row['name'],
                    $matches->map(fn(Employee $employee) => "{$employee->id}: {$employee->full_name}")->implode(', '),
                ];
                continue;
            }

            /** @var Employee $employee */
            $employee = $matches->first();

            if ($employee->bdo_account_number && ! $force) {
                $skipped[] = [
                    $employee->id,
                    $employee->full_name,
                    $employee->bdo_account_number,
                    $row['account'],
                ];
                continue;
            }

            $updated++;

            if (! $dryRun) {
                $employee->bdo_account_number = $row['account'];
                $employee->save();
            }

            $this->line(sprintf(
                '%s %s -> %s',
                $dryRun ? '[DRY RUN]' : 'Updated',
                $employee->full_name,
                $row['account'],
            ));
        }

        $this->newLine();
        $this->info(($dryRun ? "{$updated} employee(s) would be updated." : "{$updated} employee(s) updated."));

        if ($skipped !== []) {
            $this->warn(count($skipped) . ' employee(s) skipped because they already have a BDO account number. Use --force to overwrite.');
            $this->table(['ID', 'Employee', 'Existing BDO', 'Import BDO'], $skipped);
        }

        if ($unmatched !== []) {
            $this->error(count($unmatched) . ' account row(s) were not matched.');
            $this->table(['BDO Account', 'Import Name'], $unmatched);
        }

        if ($ambiguous !== []) {
            $this->error(count($ambiguous) . ' account row(s) matched multiple employees.');
            $this->table(['BDO Account', 'Import Name', 'Possible Employees'], $ambiguous);
        }

        return ($unmatched === [] && $ambiguous === []) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param Collection<int, Employee> $employees
     *
     * @return Collection<int, Employee>
     */
    private function matchingEmployees(Collection $employees, string $importName): Collection
    {
        $normalizedImportName = $this->normalizeName($importName);
        $normalizedImportNameWithoutMiddleInitials = $this->normalizeNameWithoutMiddleInitials($importName);

        return $employees
            ->filter(function (Employee $employee) use ($normalizedImportName) {
                return $this->normalizeName($employee->full_name) === $normalizedImportName;
            })
            ->whenEmpty(function () use ($employees, $normalizedImportNameWithoutMiddleInitials) {
                return $employees->filter(function (Employee $employee) use ($normalizedImportNameWithoutMiddleInitials) {
                    return $this->normalizeNameWithoutMiddleInitials($employee->full_name) === $normalizedImportNameWithoutMiddleInitials;
                });
            });
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->replaceMatches('/[^a-z\s]/', ' ')
            ->squish()
            ->toString();
    }

    private function normalizeNameWithoutMiddleInitials(string $name): string
    {
        return Str::of($this->normalizeName($name))
            ->explode(' ')
            ->reject(fn(string $part) => strlen($part) === 1)
            ->implode(' ');
    }
}
