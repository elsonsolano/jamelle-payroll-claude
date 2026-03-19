# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies and run migrations
composer run setup

# Start all dev services (PHP server, queue listener, Vite)
composer run dev

# Run tests
composer run test
# or
php artisan test

# Run a single test
php artisan test --filter=TestClassName

# Code formatting
./vendor/bin/pint

# Build frontend assets
npm run build
```

## Architecture

**Stack:** Laravel 13 (PHP 8.3+), MySQL, Blade + Tailwind CSS + Alpine.js, Vite

**Domain:** Multi-branch employee payroll management with timemark device integration for attendance tracking.

### Key Models & Relationships

- `Branch` → has many `Employee`s, `PayrollCutoff`s; defines default `work_start_time`/`work_end_time`
- `Employee` → belongs to `Branch`; has many `Dtr`, `EmployeeSchedule`, `EmployeeStandingDeduction`, `PayrollEntry`
- `EmployeeSchedule` → weekly schedule per employee with `rest_days` (array) and optional custom work hours
- `EmployeeStandingDeduction` → recurring deductions (SSS, PhilHealth, PagIBIG, loan, cash_advance, uniform, other); `active` flag; `cutoff_period` (`both` | `first` | `second`) controls which semi-monthly cutoff it applies to — determined by `end_date`: day ≤ 15 = `first` (payday 15th), day > 15 = `second` (payday 30th/31st). Cutoff pattern: payday 15th covers ~30th prev month → 13th; payday 30th/31st covers 14th → 29th.
- `PayrollCutoff` → belongs to `Branch`; has many `PayrollEntry`; status: `draft` → `processing` → `finalized`
- `PayrollEntry` → has many `PayrollDeduction`; stores computed: basic_pay, overtime_pay, late_deduction, undertime_deduction, gross_pay, total_deductions, net_pay
- `TimemarkLog` → audit trail of DaysCamera API fetch operations per employee

Employees have `salary_type` (daily/monthly), `employee_code` (unique), and `timemark_id` (nullable, unique when set, maps to timemark device). Non-branch employees (drivers, area managers, etc.) are assigned to the **Head Office** branch.

### Payroll Computation (`app/Services/PayrollComputationService.php`)

Single entry point: `computeEntry(PayrollCutoff, Employee): PayrollEntry` — uses `updateOrCreate` to avoid duplicates.

- **Daily** employees: basic pay = days_worked × daily_rate; late/undertime deducted from pay
- **Monthly** employees: basic pay = monthly_rate / 2 (semi-monthly); no absence deductions
- Overtime multipliers: **1.25x** regular days, **1.30x** rest days
- Active `EmployeeStandingDeduction` records matching the cutoff's period are copied as `PayrollDeduction` line items on each entry

### Attendance Integration (`app/Jobs/FetchAttendanceJob.php`)

Queued job (`ShouldQueue`) that fetches from DaysCamera timemark device API:
- Paginated fetch (pageSize=20) with 200ms throttle between pages
- API state codes: 0=time_in, 1=am_out, 2=pm_in, 3=time_out
- AM/PM time disambiguation: checks `hour < 12` and compares with previous `time_in`
- Logs to `TimemarkLog` with success/failed status and record counts
- SSL verification controlled by `TIMEMARK_VERIFY_SSL` env var (set to `false` locally on Windows/WAMP to bypass cURL cert errors; defaults to `true` in production)

Queue uses database driver. `composer run dev` starts the listener automatically. Manual: `php artisan queue:listen --tries=1 --timeout=0`

### Scheduled Attendance Fetching (`app/Console/Commands/FetchAttendanceCommand.php`)

`php artisan attendance:fetch` dispatches `FetchAttendanceJob` for all active employees with a `timemark_id`. Defaults to yesterday's date range. Accepts `--date-from` and `--date-to` options for manual runs. Registered in `routes/console.php` to run daily at midnight via the Laravel scheduler. Requires the OS crontab entry: `* * * * * php artisan schedule:run`

### Routes Structure

All routes in `routes/web.php`, protected by `auth` middleware:

- `branches.*` — CRUD
- `employees.*` — CRUD + show; nested: `employees/{employee}/schedules`, `employees/{employee}/deductions` (with `toggle` action)
- `payroll/cutoffs.*` — CRUD + show; nested: `payroll/cutoffs/{cutoff}/entries` (index, show, pdf); `POST payroll/cutoffs/{cutoff}/generate` triggers computation
- `dtr` — index/show with filtering by employee, branch, date range, cutoff
- `timemark/fetch` (POST) — dispatches `FetchAttendanceJob`; `timemark/logs` — fetch history

### Frontend

Blade templates in `resources/views/` organized by feature. Alpine.js handles interactivity inline (no Vue/React). PDF payslips via `barryvdh/laravel-dompdf` — filename: `payslip-{employee-slug}-{start_date}.pdf`.

### Railway Deployment

Config files: `nixpacks.toml` (build), `railway.json` (deploy), and `start.sh` (runtime entrypoint). Key behaviours:
- `bootstrap/cache` and `storage/` dirs are gitignored, so `nixpacks.toml` creates them with `mkdir -p` before `composer install` (required for Laravel's `package:discover` post-install hook)
- Build phase: installs deps, runs `npm run build`, caches routes and views only — **config cache is NOT done at build time** (DB env vars are not available then); `php artisan config:cache` runs inside `start.sh` at container startup
- Production server: **nginx + PHP-FPM** via `start.sh` (replaces `php artisan serve` which is single-threaded). `start.sh` writes nginx and php-fpm configs to `/tmp` at startup, substituting `$PORT`
- `nixpacks.toml` includes `nginx` in `nixPkgs`; all nginx fastcgi params are inlined in `start.sh` (no external `fastcgi_params` file needed)
- Migration ordering: migrations with identical timestamps sort alphabetically — `payroll_deductions` must run after `payroll_entries` (FK dependency), so its timestamp was bumped to `142153`
- Queue worker runs as a **separate Railway service** from the same repo with start command: `php artisan queue:listen --tries=1 --timeout=0`
- Required env vars on Railway: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `DB_HOST/PORT/DATABASE/USERNAME/PASSWORD` (use public proxy host/port from MySQL service Settings → Networking, not the internal `mysql.railway.internal`), `LOG_CHANNEL=stderr`, `TIMEMARK_VERIFY_SSL=true`

### Database & Seeding

MySQL with database-backed sessions and cache. All foreign keys cascade on delete. Unique constraints: `employee_code`, `timemark_id` (nullable), `(payroll_cutoff_id, employee_id)`, `(employee_id, date)` for DTRs.

Seeders (run in order via `DatabaseSeeder`):
- `BranchSeeder` — Head Office, Abreeza, SM Lanang, SM Ecoland, NCCC
- `AdminUserSeeder` — admin user (`admin@payroll.test`)
- `PayrollCutoffSeeder` — generates 6 semi-monthly cutoffs per branch going back ~3 months from today; idempotent via `firstOrCreate`
- `EmployeeSeeder` — 36 real employees from the company directory; maps "Ayala Abreeza" → "Abreeza" branch; management staff with no known rate seeded as `monthly` with `rate = 0` (configure manually)
