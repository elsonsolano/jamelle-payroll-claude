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

# Recompute DTR hours, late, undertime, and rest-day flag for all records
php artisan dtr:recompute

# Recompute with filters (all options are optional and combinable)
php artisan dtr:recompute --employee=13 --branch=3 --from=2026-03-30 --to=2026-04-13
php artisan dtr:recompute --dry-run   # preview without saving

# Recompute one payroll cutoff
php artisan payroll:recompute 7 --mode=default --dry-run
php artisan payroll:recompute 7 --mode=sheet --dry-run
php artisan payroll:recompute 7 --mode=sheet
```

> **After importing a Railway DB dump locally**, always run `php artisan dtr:recompute` — the production DB may have DTR `total_hours` values computed with older code or before schedules were set up.

## Pre-deploy Checklist

Before every `git push`, simulate the Railway production environment locally:

```bash
php artisan config:cache && php artisan route:cache
# reload app in browser and verify
php artisan config:clear && php artisan route:clear
```

**Why this matters:** Railway runs `config:cache` at container startup (`start.sh`). After that, `env()` calls in application code return `null` — only `config()` works.

**Rules:**
- Never use `env()` in application code (controllers, models, providers, middleware). Only inside `config/*.php` files.
- Always use `config('app.env')`, `config('app.debug')`, etc. in application code.
- Exception: `bootstrap/app.php` runs before config is available — use `getenv()` there, not `env()` or `config()`.

## Architecture

**Stack:** Laravel 13 (PHP 8.3+), MySQL, Blade + Tailwind CSS + Alpine.js, Vite

**Domain:** Multi-branch employee payroll management. Staff manually enter their own DTR (daily time record) via a mobile-friendly portal. Admins manage payroll computation.

### Roles & Permissions

Single `users` table with `role` (`admin` | `staff`). Admin users are either **super admins** (`permissions = null`) or **limited admins** (`permissions = ['schedules', ...]`). All existing admins have `permissions = null` (backward compatible).

- `isSuperAdmin()` — `role === 'admin' && permissions === null`
- `hasPermission($p)` — true for super admins, or if `$p` is in the permissions array
- `super-admin` middleware alias → 403 if `!isSuperAdmin()`
- `can_approve_ot` (bool on users) — controls OT approval access
- `must_change_password` (bool) — forces password change on next login; default `true` for new staff

Currently defined permissions: `schedules`.

### Key Model Gotchas

- `Branch.work_start_time` / `work_end_time` are **not used for DTR computation** — informational only
- `Employee` column is `hired_date`, not `date_hired`
- `Employee.nickname` — used for schedule upload name matching (takes priority over `first_name`)
- `PayrollCutoff` status: `draft → processing → finalized → voided`. **Only `finalized` blocks staff DTR editing** — `processing` is a deliberate preview state where payroll is visible but DTRs remain editable.
- `PayrollEntry.late_deduction` and `undertime_deduction` always 0 — kept for schema compatibility only
- `PayrollEntry.acknowledged_at` is **reset to null whenever payroll is regenerated**
- `PayrollEntryRefund` and `PayrollEntryVariableDeduction` are **preserved** on regeneration (only pay figures recalculate)
- `EmployeeStandingDeduction.cutoff_period`: `end_date` day ≤ 15 = `first` (payday 15th); day > 15 = `second` (payday 30th/31st)

### DTR Computation (`app/Services/DtrComputationService.php`)

**Schedule resolution:** `DailySchedule` for exact date first; falls back to most recent `EmployeeSchedule` with `week_start_date <= date`. `Branch.work_start_time` / `work_end_time` are never used.

**Schedule-aware hour boundaries:** Early arrival not credited (`effective_start = max(actual_in, scheduled_start)`); staying late not credited past schedule end.

**Break deduction rules (company policy):**
- Break logged (`am_out` + `pm_in`): deduct `max(actual_break_mins, 60)` — minimum 1 hour always
- No break logged, shift > 5 hours: force-deduct 60 mins
- No break logged, shift ≤ 5 hours: no deduction

**Billable hours:** `min(total_hours, 8.0)` — capped at 8h/day.

**Overnight shifts:** Time values are bare strings with no date component. When `time_out <= time_in`, service adds one day to `time_out`. Same logic for break end and scheduled `work_end_time`.

**UI labels:** `am_out` = "Start Break", `pm_in` = "End Break".

**OT input:** Staff enter hours in 0.25 increments (not end time). `ot_end_time` is computed and stored for display.

### Payroll Computation (`app/Services/PayrollComputationService.php`)

**`computeEntry` re-runs `DtrComputationService` on every DTR in the period before computing pay** — regenerating payroll always picks up schedule changes automatically.

- **Daily:** `working_days = floor(sum(billableHours) / 8 × 100) / 100` (truncated, not rounded). `basic_pay = working_days × daily_rate`.
- **Monthly:** `basic_pay = monthly_rate / 2`. No hour-based deductions.
- **OT multiplier: 1.30×** for both regular and rest days (company rule; DOLE standard is 1.25× regular but company uses 1.30× across the board).
- First generation: standing deductions auto-copied as `PayrollDeduction`. Regeneration: all deductions/refunds/variable deductions preserved — only pay figures recalculate.
- `ot_status = pending` DTRs **are included in payroll** (not withheld pending approval).
- **Sheet mode** (default for Generate/Regenerate flow): rounds `working_days` to 2dp instead of truncating; holiday billable hours `>= 7.95` treated as `8.0`.

**Philippine Holiday Pay Rules (DOLE):**

| Holiday Type | Not Worked | Worked | OT Multiplier |
|---|---|---|---|
| `regular` | Daily: +100% daily_rate to basic_pay; Monthly: no extra | +100% of `hours × hourly_rate` → `holiday_pay` | 2.60× |
| `special_non_working` | No pay | +30% of `hours × hourly_rate` → `holiday_pay` | 1.69× |
| `special_working` | No pay | Normal rate (no premium) | 1.30× |

Monthly holiday premium uses `monthly_rate / 22` as daily equivalent.

### Schedule Upload Gotchas

- On apply, if an unmatched name was manually assigned and the employee has no nickname yet, the schedule name is **automatically saved as `Employee.nickname`** for future auto-matching.
- `DailySchedule.assigned_branch_id` — a `branch_override` in the JSON (e.g. `"ABR"`) is resolved via case-insensitive `LIKE` match against branch names. The employee's DTR is still filed under their home branch.
- **Shift cap on apply:** If `work_end_time − work_start_time > 9 hours`, `work_end_time` is silently capped to `start + 9h` and an OT note is appended to `notes`. The review page shows an amber warning badge on affected rows.

### OT Approval Hierarchy

1. **Regular staff** → approved by `can_approve_ot` users in same branch
2. **Branch-level approver** (non-Head-Office) → approved by `can_approve_ot` users in Head Office
3. **Head Office approver** → approved by admin users (`role = admin`)

Rejection resets `overtime_hours = 0` on the DTR. Staff can re-submit after rejection.

### Routing Gotchas

- `POST staff.dtr.log-event` must be declared **before** the `{dtr}` resource route to avoid conflicts
- `dtr/export` and `dtr/export-excel` must be declared **before** `dtr/{dtr}` for the same reason

### Middleware Gotchas

- `EnsurePasswordChanged` and `EnsureSignatureSet` run globally (appended to web group). Both skip during impersonation via `session()->has('impersonator_id')`.
- `bootstrap/app.php` sets `trustProxies(at: '*')` only when `APP_ENV=production` — calling it unconditionally causes a Symfony `IpUtils::checkIp4` crash locally (no `REMOTE_ADDR`).

### Frontend Gotchas

**Alpine.js + Vite timing:** Vite loads Alpine as a deferred ES module (`type="module"`). `@push('scripts')` / `@stack('scripts')` executes **after** Alpine has already initialized — any Alpine component function defined via `@push` will be undefined when Alpine looks for it. **Always define Alpine component functions in a `<script>` tag placed directly before the `x-data` element.**

**Tippy.js tooltips:** Use `data-tippy-content="..."` with `tippy('[data-tippy-content]', { delay: 0 })`. Do not use native `title=""` — it has a ~1s browser delay.

**DOMPDF images:** Always embed as base64 — file path `src` attributes can fail depending on server config. Pattern: `'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/logo.png')))`. Wrap in `file_exists()`.

**DOMPDF fonts:** Uses DejaVu Sans (bundled). The ₱ peso sign renders as `PHP` prefix — DejaVu Sans UFM lacks the glyph. No fix applied yet.

### PWA & Push Notifications

- **Push subscription:** Use `navigator.serviceWorker.ready` (not `register().then()`) — the SW must be fully active before subscribing.
- **`endpoint_hash`** (SHA256 of endpoint) is used as the unique key because MySQL cannot index TEXT columns directly.
- **iOS Web Push:** iOS 16.4+ only, and only when running as an installed PWA (Add to Home Screen). Does not work in Safari browser tabs.

### Attendance Integration (Timemark)

Feature is hidden from the UI (sidebar button commented out) but code remains intact (`FetchAttendanceJob`, `TimemarkController`, `timemark.*` routes). `TIMEMARK_VERIFY_SSL` env var — set to `false` locally on Windows/WAMP, `true` in production.

### Railway Deployment

- **`config:cache` runs in `start.sh` at container startup**, not at build time — this is why `env()` in app code silently returns `null` in production.
- Migration ordering: `payroll_deductions` timestamp bumped to `142153` to run after `payroll_entries` (FK dependency).
- `public/hot` is gitignored — never commit it.
- Cron service must have the same env vars as the web service — Railway does not share variables between services automatically.
- Required env vars: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `DB_*`, `LOG_CHANNEL=stderr`, `TIMEMARK_VERIFY_SSL=true`, `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`

### Database

**`TRUNCATE` with FK constraints fails** even on child tables. Use `DELETE` with FK checks disabled:
```php
DB::statement('SET FOREIGN_KEY_CHECKS=0');
DB::table('child_table')->delete();
DB::table('parent_table')->delete();
DB::statement('SET FOREIGN_KEY_CHECKS=1');
```

### Testing

Only scaffolded Laravel Breeze auth tests exist (`tests/Feature/Auth/`). No domain-specific tests yet. Run a single test: `php artisan test --filter=TestClassName`.
