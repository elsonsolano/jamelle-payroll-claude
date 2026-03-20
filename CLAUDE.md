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

**Domain:** Multi-branch employee payroll management. Staff manually enter their own DTR (daily time record) via a mobile-friendly portal. Admins manage payroll computation.

### Two-Role System

There is a single `users` table with a `role` column (`admin` | `staff`):

- **Admin** — full access to the admin panel (branches, employees, payroll, DTR view, holidays)
- **Staff** — access only to the staff portal (`/staff/*`); linked to an `Employee` record via `users.employee_id`

Key `users` columns beyond the standard Laravel auth fields:
- `employee_id` (nullable FK) — links a staff user to their employee record
- `role` — `admin` or `staff`
- `can_approve_ot` (bool) — whether this staff user can approve overtime requests
- `must_change_password` (bool) — forces password change on next login (default `true` for new staff accounts)
- `signature` (longText) — base64 PNG of drawn signature, set during first-login onboarding

Staff accounts are created by the admin on the employee show page. Default password is `password`; staff are forced to change it on first login, then prompted to draw their signature via canvas.

### Key Models & Relationships

- `Branch` → has many `Employee`s, `PayrollCutoff`s; defines default `work_start_time`/`work_end_time`
- `Employee` → belongs to `Branch`; has one `User`; has many `Dtr`, `EmployeeSchedule`, `EmployeeStandingDeduction`, `PayrollEntry`
- `User` → belongs to `Employee` (staff only); admin users have `employee_id = null`
- `EmployeeSchedule` → weekly schedule per employee with `rest_days` (array) and optional custom work hours; the most recent schedule with `week_start_date <= date` is used for DTR computation
- `EmployeeStandingDeduction` → recurring deductions (SSS, PhilHealth, PagIBIG, loan, cash_advance, uniform, other); `active` flag; `cutoff_period` (`both` | `first` | `second`) controls which semi-monthly cutoff it applies to — determined by `end_date`: day ≤ 15 = `first` (payday 15th), day > 15 = `second` (payday 30th/31st). Cutoff pattern: payday 15th covers ~30th prev month → 13th; payday 30th/31st covers 14th → 29th.
- `PayrollCutoff` → belongs to `Branch`; has many `PayrollEntry`; status: `draft` → `processing` → `finalized`
- `Holiday` → date (unique), name, type (`regular` | `special_non_working` | `special_working`); managed via calendar UI at `/holidays`
- `PayrollEntry` → has many `PayrollDeduction`; stores computed: basic_pay, overtime_pay, holiday_pay, late_deduction, undertime_deduction, gross_pay, total_deductions, net_pay
- `Dtr` → belongs to `Employee`; key extra columns: `source` (`device`|`manual`), `ot_status` (`none`|`pending`|`approved`|`rejected`), `ot_end_time`, `ot_approved_by` (FK to users), `ot_approved_at`, `ot_rejection_reason`
- `TimemarkLog` → audit trail of DaysCamera API fetch operations (timemark feature is hidden from the UI but the code remains)

Employees have `salary_type` (daily/monthly), `employee_code` (unique), and `timemark_id` (nullable). Non-branch employees (drivers, area managers, etc.) are assigned to the **Head Office** branch.

### DTR Computation (`app/Services/DtrComputationService.php`)

All manually entered DTRs go through `DtrComputationService::compute(Employee, date, time_in, am_out, pm_in, time_out, ?float $otHours)` which returns:
- `total_hours` — time_in→time_out minus break (am_out→pm_in)
- `overtime_hours` — directly from the `$otHours` input (staff enter hours, not end time)
- `late_mins` — minutes after scheduled `work_start_time`
- `undertime_mins` — minutes before scheduled `work_end_time`
- `is_rest_day` — derived from the employee's schedule `rest_days` array

Note: in the UI, `am_out` is labelled **Start Break** and `pm_in` is labelled **End Break**.

`ot_end_time` is computed and stored (`time_out + ot_hours`) for display in the approval page, but staff input OT as a number of hours.

`DtrComputationService::getOtApprovers(Employee, User $submitter)` returns the collection of users who should be notified/can approve a given OT submission (see OT Approval Hierarchy below).

### Payroll Computation (`app/Services/PayrollComputationService.php`)

Single entry point: `computeEntry(PayrollCutoff, Employee): PayrollEntry` — uses `updateOrCreate` to avoid duplicates.

- **Daily** employees: basic pay = days_worked × daily_rate; late/undertime deducted from pay
- **Monthly** employees: basic pay = monthly_rate / 2 (semi-monthly); no absence deductions
- Overtime multipliers: **1.25x** regular days, **1.30x** rest days
- Active `EmployeeStandingDeduction` records matching the cutoff's period are copied as `PayrollDeduction` line items on each entry

**Philippine Holiday Pay Rules (DOLE)** — applied per `Holiday` records in the cutoff period:

| Holiday Type | Not Worked | Worked | OT Multiplier |
|---|---|---|---|
| `regular` | Daily: +100% daily rate added to basic pay; Monthly: no extra | +100% premium → `holiday_pay` | 2.60× hourly |
| `special_non_working` | No pay (no work no pay) | +30% premium → `holiday_pay` | 1.69× hourly |
| `special_working` | No pay | Normal rate (no premium) | 1.25×/1.30× |

For monthly employees the premium is calculated using `monthly_rate / 22` as the daily equivalent.

Payroll uses `dtr.overtime_hours` directly. If a DTR has `ot_status = pending`, overtime IS included in payroll but a flag is visible on the payroll entry — OT is not withheld pending approval.

### OT Approval Hierarchy

Three-tier hierarchy enforced in `DtrComputationService::getOtApprovers()` and `Staff\OtApprovalController`:

1. **Regular staff** (not `can_approve_ot`) → approved by any `can_approve_ot` user in the same branch
2. **Branch-level approver** (`can_approve_ot`, non-Head-Office branch) → approved by `can_approve_ot` users in Head Office
3. **Head Office approver** (`can_approve_ot`, Head Office branch) → approved by admin users (`role = admin`)

Rejection resets `overtime_hours = 0` on the DTR. Staff can re-submit OT after rejection by editing the DTR.

### In-App Notifications

Uses Laravel's database notification channel (`notifications` table). Three notification classes in `app/Notifications/`:
- `OtSubmitted` — sent to approvers when staff submits OT
- `OtApproved` — sent to the staff member when their OT is approved
- `OtRejected` — sent to the staff member when their OT is rejected (includes rejection reason)

Unread count is shown in the staff portal top bar bell icon.

### Middleware

Registered in `bootstrap/app.php` as aliases and also appended to the `web` group:

- `admin` (`RequireAdmin`) — 403 if not `role = admin`; applied to all admin panel routes
- `staff` (`RequireStaff`) — 403 if not `role = staff`; applied to all `/staff/*` routes
- `EnsurePasswordChanged` — redirects to `/change-password` if `must_change_password = true`; runs on every web request
- `EnsureSignatureSet` — redirects to `/setup-signature` if staff user has no signature; runs on every web request

The last two run globally (appended to web group) so they intercept after auth, before any controller. Both check their respective exempt routes to avoid redirect loops.

### Routes Structure

`/` redirects to login. `routes/web.php` is split into three sections:

**First-login flows** (auth only, no role restriction):
- `GET/POST /change-password` — force password change
- `GET/POST /setup-signature` — signature canvas onboarding

**Staff portal** (`auth` + `staff` middleware, prefix `/staff`, name prefix `staff.`):
- `staff.dashboard` — home with recent DTRs and stats
- `staff.dtr.*` — index, create, store, edit, update (edit blocked if DTR is in a finalized payroll)
- `staff.ot-approvals.*` — index, approve, reject (access not middleware-gated, enforced inside controller)
- `staff.notifications.*` — index, mark-read

**Admin panel** (`auth` + `admin` middleware):
- All existing admin routes: branches, employees, payroll cutoffs/entries, DTR (read-only view), holidays
- `employees/{employee}/account` (POST create, PATCH update, POST reset-password) — staff account management

### Frontend

Two layouts:
- `x-app-layout` (`AppLayout.php` → `layouts/app.blade.php`) — desktop sidebar layout for admin
- `x-staff-layout` (`StaffLayout.php` → `layouts/staff.blade.php`) — mobile-first layout with bottom nav bar for staff portal; max-width constrained, sticky top bar, fixed bottom nav

Alpine.js handles interactivity inline (no Vue/React). PDF payslips via `barryvdh/laravel-dompdf`.

The timemark fetch button is hidden from the admin sidebar (commented out in `layouts/app.blade.php`) but the underlying code (`FetchAttendanceJob`, `TimemarkController`, `timemark.*` routes) is intact.

### Attendance Integration (`app/Jobs/FetchAttendanceJob.php`)

Queued job (`ShouldQueue`) that fetches from DaysCamera timemark device API:
- Paginated fetch (pageSize=20) with 200ms throttle between pages
- API state codes: 0=time_in, 1=am_out, 2=pm_in, 3=time_out
- SSL verification controlled by `TIMEMARK_VERIFY_SSL` env var (set to `false` locally on Windows/WAMP; defaults to `true` in production)

Queue uses database driver. Manual: `php artisan queue:listen --tries=1 --timeout=0`

### Railway Deployment

Config files: `nixpacks.toml` (build), `railway.json` (deploy), and `start.sh` (runtime entrypoint). Key behaviours:
- `bootstrap/cache` and `storage/` dirs are gitignored, so `nixpacks.toml` creates them with `mkdir -p` before `composer install`
- Build phase: installs deps, runs `npm run build`, caches routes and views only — **config cache is NOT done at build time**; `php artisan config:cache` runs inside `start.sh` at container startup
- Production server: **nginx + PHP-FPM** via `start.sh`; configs written to `/tmp` at startup substituting `$PORT`
- Migration ordering: `payroll_deductions` timestamp bumped to `142153` so it runs after `payroll_entries` (FK dependency)
- Queue worker runs as a **separate Railway service** with start command: `php artisan queue:listen --tries=1 --timeout=0`
- `AdminUserSeeder` runs on every deploy (inside `start.sh`); idempotent via `firstOrCreate`
- `bootstrap/app.php` sets `trustProxies(at: '*')` for Railway's HTTPS load balancer
- `public/hot` is gitignored — never commit it
- Required env vars: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `DB_*` (use public proxy host/port, not `mysql.railway.internal`), `LOG_CHANNEL=stderr`, `TIMEMARK_VERIFY_SSL=true`

### Database & Seeding

MySQL with database-backed sessions, cache, and notifications. All foreign keys cascade on delete.

Seeders (run in order via `DatabaseSeeder`):
- `BranchSeeder` — Head Office, Abreeza, SM Lanang, SM Ecoland, NCCC
- `AdminUserSeeder` — admin user (`admin@payroll.test`), runs on every deploy
- `PayrollCutoffSeeder` — 6 semi-monthly cutoffs per branch going back ~3 months; idempotent via `firstOrCreate`
- `EmployeeSeeder` — 36 real employees; management staff seeded with `rate = 0` (configure manually)
