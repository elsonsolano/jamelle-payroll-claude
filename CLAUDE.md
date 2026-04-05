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

## Pre-deploy Checklist

Before every `git push`, simulate the Railway production environment locally to catch issues before they go live:

```bash
# 1. Cache config and routes (mirrors what Railway does at startup)
php artisan config:cache && php artisan route:cache

# 2. Reload the app in the browser and verify nothing is broken

# 3. Restore normal local dev state
php artisan config:clear && php artisan route:clear
```

**Why this matters:** Railway runs `config:cache` at container startup (`start.sh`). After that, `env()` calls in application code return `null` — only `config()` works. Any code that uses `env()` outside of `config/*.php` files will silently fail in production.

**Rules to follow:**
- Never use `env()` in application code (controllers, models, providers, middleware). Only use it inside `config/*.php` files.
- Always use `config('app.env')`, `config('app.debug')`, etc. in application code.
- Exception: `bootstrap/app.php` runs before the config service is available — use `getenv()` there, not `env()` or `config()`.

## Architecture

**Stack:** Laravel 13 (PHP 8.3+), MySQL, Blade + Tailwind CSS + Alpine.js, Vite

**Domain:** Multi-branch employee payroll management. Staff manually enter their own DTR (daily time record) via a mobile-friendly portal. Admins manage payroll computation.

### Two-Role System

There is a single `users` table with a `role` column (`admin` | `staff`):

- **Admin** — access to the admin panel; further subdivided by `permissions` (see below)
- **Staff** — access only to the staff portal (`/staff/*`); linked to an `Employee` record via `users.employee_id`

Key `users` columns beyond the standard Laravel auth fields:
- `employee_id` (nullable FK) — links a staff user to their employee record
- `role` — `admin` or `staff`
- `permissions` (JSON, nullable) — `null` = super admin (full access); explicit array = limited admin with named permissions (e.g. `['schedules']`)
- `can_approve_ot` (bool) — whether this staff user can approve overtime requests
- `must_change_password` (bool) — forces password change on next login (default `true` for new staff accounts)
- `signature` (longText) — base64 PNG of drawn signature, set during first-login onboarding

Staff accounts are created by the admin on the employee show page. Default password is `password`; staff are forced to change it on first login, then prompted to draw their signature via canvas.

### Admin Permission System

Admin users are either **super admins** (`permissions = null`) or **limited admins** (`permissions = ['schedules', ...]`). All existing admins have `permissions = null` (backward compatible).

`User` model helpers:
- `isSuperAdmin(): bool` — `role === 'admin' && permissions === null`
- `hasPermission(string $permission): bool` — true for super admins, or if `$permission` is in the permissions array

`RequireSuperAdmin` middleware (`super-admin` alias) — aborts 403 if `!isSuperAdmin()`. Applied to all sensitive admin routes (payroll, DTR, holidays, employee CRUD, branches, etc.).

Currently defined permissions: `schedules` — can access schedule uploads and manage employee schedules.

**Schedule manager (limited admin with `schedules`):** Can access `/schedule-uploads/*` and `/employees/{employee}/schedules`. On `employees.index`, gets a simplified view (`schedule-manager-index.blade.php`) showing only name, branch, position, and a "Manage Schedule" link — no salary, gov IDs, or other sensitive data. The back button in `employees/{employee}/schedules` routes to `employees.index` for limited admins (not `employees.show` which is super-admin only).

Admin sidebar is role-aware: sensitive nav items (Branches, Admin Users, DTR, Payroll, Holidays) only render for `isSuperAdmin()`; Schedules link renders for `hasPermission('schedules')`.

### Key Models & Relationships

- `Branch` → has many `Employee`s, `PayrollCutoff`s; has `work_start_time`/`work_end_time` columns but these are **not used for DTR computation** — only informational
- `Employee` → belongs to `Branch`; has one `User`; has many `Dtr`, `EmployeeSchedule`, `DailySchedule`, `EmployeeStandingDeduction`, `EmployeeAllowance`, `PayrollEntry`; has `nickname` (nullable) — short name used for matching names in schedule uploads (e.g. "Eddie", "AJ"); has `birthday` (date, nullable); has `hired_date` (date, nullable) — column is named `hired_date`, not `date_hired`
- `User` → belongs to `Employee` (staff only); has many `PushSubscription`s; admin users have `employee_id = null`
- `EmployeeSchedule` → weekly repeating schedule per employee with `rest_days` (array) and optional custom work hours; used as fallback when no `DailySchedule` exists for a date; managed at `/employees/{employee}/schedules`
- `DailySchedule` → date-specific schedule per employee (`date`, `work_start_time`, `work_end_time`, `is_day_off`, `assigned_branch_id`, `notes`); **takes priority over `EmployeeSchedule`** in DTR computation; unique on `(employee_id, date)`; belongs to `ScheduleUpload`; also visible and inline-editable on the `/employees/{employee}/schedules` page
- `ScheduleUpload` → tracks each schedule image import (branch, uploader, label, `ai_response` JSON, status: `pending`→`review`→`applied`)
- `EmployeeStandingDeduction` → recurring deductions (SSS, PhilHealth, PagIBIG, loan, cash_advance, uniform, other); `active` flag; `cutoff_period` (`both` | `first` | `second`) controls which semi-monthly cutoff it applies to — determined by `end_date`: day ≤ 15 = `first` (payday 15th), day > 15 = `second` (payday 30th/31st). Cutoff pattern: payday 15th covers ~30th prev month → 13th; payday 30th/31st covers 14th → 29th.
- `EmployeeAllowance` → per-employee daily allowance (`daily_amount`, `description`, `active`); applied as `daily_amount × working_days` during payroll computation
- `PayrollCutoff` → belongs to `Branch`; has many `PayrollEntry`; status: `draft` → `processing` → `finalized` → `voided`; `void_reason` (nullable text) stores the reason when voided. Voided cutoffs show a red banner on their show page and cannot have payroll regenerated, but **DTRs within a voided cutoff's date range become editable again** (only `finalized` status blocks DTR editing)
- `Holiday` → date (unique), name, type (`regular` | `special_non_working` | `special_working`); managed via calendar UI at `/holidays`
- `PayrollEntry` → has many `PayrollDeduction`, `PayrollEntryRefund`, `PayrollEntryVariableDeduction`; stores computed pay figures (basic_pay, overtime_pay, holiday_pay, allowance_pay, gross_pay, total_deductions, net_pay) plus summary columns: `working_days` (decimal 8,4 — truncated to 2dp), `total_hours_worked` (capped at 8h/day), `total_overtime_hours`; `late_deduction` and `undertime_deduction` columns exist but are always 0 (kept for schema compatibility)
- `PayrollEntryRefund` → manual refunds added per payroll entry (`description`, `amount`); **preserved** when payroll is regenerated
- `PayrollEntryVariableDeduction` → manual one-off deductions per payroll entry (`description`, `amount`); **preserved** when payroll is regenerated
- `Dtr` → belongs to `Employee`; key extra columns: `source` (`device`|`manual`), `ot_status` (`none`|`pending`|`approved`|`rejected`), `ot_end_time`, `ot_approved_by` (FK to users), `ot_approved_at`, `ot_rejection_reason`
- `TimemarkLog` → audit trail of DaysCamera API fetch operations (timemark feature is hidden from the UI but the code remains)
- `PushSubscription` → stores Web Push device subscriptions per user (`endpoint`, `endpoint_hash` SHA256 for unique index, `p256dh_key`, `auth_token`); one row per browser/device; used by `minishlink/web-push` to send push notifications

Employees have `salary_type` (daily/monthly), `employee_code` (unique), and `timemark_id` (nullable). Non-branch employees (drivers, area managers, etc.) are assigned to the **Head Office** branch.

### DTR Computation (`app/Services/DtrComputationService.php`)

All manually entered DTRs go through `DtrComputationService::compute(Employee, date, time_in, am_out, pm_in, time_out, ?float $otHours)` which returns:
- `total_hours` — time_in→time_out minus break (am_out→pm_in)
- `overtime_hours` — directly from the `$otHours` input (staff enter hours, not end time)
- `late_mins` — minutes after `work_start_time`; **0 if no schedule set**
- `undertime_mins` — minutes before `work_end_time`; **0 if no schedule set**
- `is_rest_day` — true if `DailySchedule.is_day_off`, or derived from `EmployeeSchedule.rest_days` (defaults to `['Sunday']`)

**Schedule resolution order:** `DailySchedule` for the exact date is checked first; if none exists, falls back to the most recent `EmployeeSchedule` with `week_start_date <= date`.

**Important:** `Branch.work_start_time` / `Branch.work_end_time` are never used for late/undertime computation.

**DTR recomputation on schedule save:** Whenever a `DailySchedule` is created or updated — either via `EmployeeScheduleController::storeDaily()`/`updateDaily()` or via `ScheduleUploadController::apply()` — any existing `Dtr` for that employee+date is immediately recomputed (`late_mins`, `undertime_mins`, `is_rest_day` updated). This ensures the admin `/dtr` page reflects the correct late data without waiting for payroll regeneration.

Note: in the UI, `am_out` is labelled **Start Break** and `pm_in` is labelled **End Break**.

`ot_end_time` is computed and stored (`time_out + ot_hours`) for display in the approval page, but staff input OT as a number of hours in **0.25 increments** (minimum 0.25 = 15 minutes).

`DtrComputationService::getOtApprovers(Employee, User $submitter)` returns the collection of users who should be notified/can approve a given OT submission (see OT Approval Hierarchy below).

### Payroll Computation (`app/Services/PayrollComputationService.php`)

Single entry point: `computeEntry(PayrollCutoff, Employee): PayrollEntry` — uses `firstOrNew` to avoid duplicates.

**Before computing payroll, `computeEntry` re-runs `DtrComputationService` on every DTR in the period and saves the updated values.** This means regenerating payroll always picks up schedule changes automatically.

- **Daily** employees: billable hours per DTR are capped at 8. `working_days = floor(sum(billableHours) / 8 × 100) / 100` (truncated to 2 decimal places, matching Excel). `basic_pay = working_days × daily_rate`. Unworked regular holidays add a full `daily_rate` to `basic_pay` on top. `total_hours_worked` and `total_overtime_hours` are stored separately; both are capped/summed from DTR values. Reduced hours from late arrival or early departure naturally produce fewer working_days and lower pay — there are no separate late or undertime deductions.
- **Monthly** employees: `basic_pay = monthly_rate / 2` (semi-monthly flat); no hour-based deductions. `working_days` = count of DTRs with `time_in`.
- Overtime multipliers: **1.30×** both regular and rest days (internal rule; DOLE standard is 1.25× regular but company uses 1.30× across the board)
- `allowance_pay = sum(active EmployeeAllowance.daily_amount) × working_days`
- `gross_pay = basic_pay + overtime_pay + holiday_pay + allowance_pay`
- Active `EmployeeStandingDeduction` records matching the cutoff's period are copied as `PayrollDeduction` line items.
- On **first generation**, active `EmployeeStandingDeduction` records are auto-copied as `PayrollDeduction` line items. On **regeneration**, all existing `PayrollDeduction`, `PayrollEntryVariableDeduction`, and `PayrollEntryRefund` records are preserved — only the pay figures (basic, overtime, holiday, allowance, gross, net) are recalculated.
- `net_pay = gross_pay − total_deductions + total_refunds`

**Philippine Holiday Pay Rules (DOLE)** — applied per `Holiday` records in the cutoff period:

| Holiday Type | Not Worked | Worked | OT Multiplier |
|---|---|---|---|
| `regular` | Daily: +100% daily_rate added to basic_pay; Monthly: no extra | +100% of `hours × hourly_rate` → `holiday_pay` | 2.60× hourly |
| `special_non_working` | No pay (no work no pay) | +30% of `hours × hourly_rate` → `holiday_pay` | 1.69× hourly |
| `special_working` | No pay | Normal rate (no premium) | 1.25×/1.30× |

For monthly employees the premium is calculated using `monthly_rate / 22` as the daily equivalent.

Payroll uses `dtr.overtime_hours` directly. If a DTR has `ot_status = pending`, overtime IS included in payroll but a flag is visible on the payroll entry — OT is not withheld pending approval.

### Schedule Upload Flow (`app/Services/ScheduleParserService.php`)

Admin imports a date-specific schedule via `/schedule-uploads`:
1. Admin uploads schedule image to **claude.ai** (free) using the prompt shown on the import page
2. Pastes the resulting JSON into the import form along with the selected branch
3. `ScheduleParserService::matchEmployees()` matches names in the JSON against `Employee.nickname` (first) then `Employee.first_name` (fallback) for the selected branch
4. Review screen shows all parsed rows; unmatched names (red) can be manually assigned via dropdown
5. On apply, `DailySchedule` records are upserted (unique on `employee_id + date`); if an unmatched name was manually assigned and the employee has no nickname yet, the schedule name is **automatically saved as `Employee.nickname`** for future auto-matching

`DailySchedule.assigned_branch_id` — if a name has `branch_override` (e.g. `"ABR"`), `resolveBranch()` does a case-insensitive `LIKE` match against branch names to resolve the ID. This means the employee is working at another branch that day but their DTR is still filed under their home branch.

`DailySchedule.notes` stores annotations like `"OT1HR"` — informational only, no auto-DTR creation.

### OT Approval Hierarchy

Three-tier hierarchy enforced in `DtrComputationService::getOtApprovers()` and `Staff\OtApprovalController`:

1. **Regular staff** (not `can_approve_ot`) → approved by any `can_approve_ot` user in the same branch
2. **Branch-level approver** (`can_approve_ot`, non-Head-Office branch) → approved by `can_approve_ot` users in Head Office
3. **Head Office approver** (`can_approve_ot`, Head Office branch) → approved by admin users (`role = admin`)

Rejection resets `overtime_hours = 0` on the DTR. Staff can re-submit OT after rejection by editing the DTR.

Admins can also approve/reject OT directly from the admin `/dtr` page via `DtrController::approveOt()` / `rejectOt()` — these bypass the hierarchy and work on any pending DTR. Pending OT rows are highlighted amber on the index; a "Pending OT only" checkbox filter is available.

### In-App Notifications

Uses Laravel's database notification channel (`notifications` table). Three notification classes in `app/Notifications/`:
- `OtSubmitted` — sent to approvers when staff submits OT
- `OtApproved` — sent to the staff member when their OT is approved (includes `approver_name`)
- `OtRejected` — sent to the staff member when their OT is rejected (includes rejection reason and `approver_name`)

Unread count is shown in the staff portal top bar bell icon.

### Admin Impersonation

Admins can log in as any staff employee via `POST /employees/{employee}/impersonate` (`employees.impersonate`), handled by `ImpersonationController::impersonate()`. The original admin's user ID and return URL are stored in `session('impersonator_id')` and `session('impersonator_return_url')`. The staff layout shows an amber banner during impersonation with a "Return to Admin" button that posts to `POST /impersonation/exit` (`impersonation.exit`).

`EnsurePasswordChanged` and `EnsureSignatureSet` both check `session()->has('impersonator_id')` and skip their forced redirects during impersonation so the admin sees the real staff portal without interruption.

### Middleware

Registered in `bootstrap/app.php` as aliases and also appended to the `web` group:

- `admin` (`RequireAdmin`) — 403 if not `role = admin`; applied to all admin panel routes
- `super-admin` (`RequireSuperAdmin`) — 403 if not `isSuperAdmin()`; applied to sensitive admin sub-routes (payroll, DTR, holidays, employee CRUD, branches, admin-users, etc.)
- `staff` (`RequireStaff`) — 403 if not `role = staff`; applied to all `/staff/*` routes
- `EnsurePasswordChanged` — redirects to `/change-password` if `must_change_password = true`; skipped during impersonation
- `EnsureSignatureSet` — redirects to `/setup-signature` if staff user has no signature; skipped during impersonation

The last two run globally (appended to web group) so they intercept after auth, before any controller. Both check their respective exempt routes to avoid redirect loops.

### Routes Structure

`/` redirects to login. `routes/web.php` is split into three sections:

**First-login flows** (auth only, no role restriction):
- `GET/POST /change-password` — force password change
- `GET/POST /setup-signature` — signature canvas onboarding

**Staff portal** (`auth` + `staff` middleware, prefix `/staff`, name prefix `staff.`):
- `staff.dashboard` — home with quote of the day, today's schedule snippet, OT approvals card (approvers only), Today's DTR card, recent DTRs
- `staff.dtr.*` — index (shows pending OT amber banner + amber dot on nav icon), create, store, edit, update (edit blocked if DTR is in a **finalized** payroll; voided cutoffs do not block)
- `POST staff.dtr.log-event` — single-event logging from the dashboard (Time In / Start Break / End Break / Time Out); uses `firstOrNew` to create or update today's DTR; handles OT submission when event is `time_out`; must be declared **before** the `{dtr}` resource to avoid route conflicts
- `staff.ot-approvals.*` — index, approve, reject (access not middleware-gated, enforced inside controller)
- `staff.notifications.*` — index, mark-read
- `staff.profile` — read-only employee profile page (contact info, gov IDs, emergency contact); contains the Logout button (Logout was removed from the bottom nav)
- `staff.schedule` — 6-week schedule grid + 30-day upcoming list; resolves `DailySchedule` first then falls back to `EmployeeSchedule` (same logic as DTR computation)

**Auth-only** (no role restriction, accessible during impersonation):
- `POST /impersonation/exit` (`impersonation.exit`) — restores admin session, clears impersonation session keys

**Admin panel** (`auth` + `admin` middleware) — split into two tiers:

*Accessible to all admins (super + limited):*
- `GET employees` (`employees.index`) — super admins get full employee table; limited admins get `schedule-manager-index.blade.php` (name, branch, position, "Manage Schedule" link only)
- `employees/{employee}/schedules` + `employees/{employee}/daily-schedules` — schedule management
- `schedule-uploads.*` — schedule import: index (with delete), create, store, review, apply (`ScheduleUploadController`); `DELETE schedule-uploads/{schedule}` — soft-deletes the upload record; underlying `DailySchedule` rows use `nullOnDelete()` so they are **preserved** after upload deletion; review page has per-row trash-icon delete (`assignments.splice(idx, 1)`), dropdown syncing (changing one row's employee syncs all rows with the same name), and a **List/Grid toggle** — grid view shows employees as rows and dates as columns (Alpine computed getters `uniqueDates`, `uniqueNames`, `getCell(name, date)`)

*Super admin only* (`super-admin` middleware):
- Branches: `branches.*`
- Admin users: `admin-users.*` (`AdminUserController`; route model binding uses `adminUser` key); create/edit forms have Access Level radio (`is_super_admin` = `1`/`0`) + permissions checkboxes (shown when Limited Admin selected via Alpine)
- Employee full management: show, create, store, edit, update, destroy, import
- `employees/{employee}/account` — staff account management
- `employees/{employee}/deductions`, `employees/{employee}/allowances`
- `POST employees/{employee}/impersonate` — log in as staff; disabled if no `User` record
- Payroll: `payroll/cutoffs/*` (CRUD, generate, void, unvoid, entries, PDF)
- DTR: `dtr` index/show, `dtr/{dtr}/approve-ot`, `dtr/{dtr}/reject-ot`
- Holidays: `holidays.*`
- Timemark: `timemark.*`
- `GET/POST utilities/truncate-schedules` — deletes all `daily_schedules` and `schedule_uploads` rows; uses `SET FOREIGN_KEY_CHECKS=0` + `DELETE`
- `GET/POST utilities/test-push` — sends a test push notification to all subscribed devices; inline route closure with title + body form

Excel column order (0-indexed): `0`=EE#, `2`=First Name, `3`=Middle Name, `4`=Last Name, `5`=Date Hired, `6`=Position, `7`=Birthdate, `8`=Email, `9`=Mobile, `10`=TIN, `11`=SSS, `12`=PhilHealth, `13`=Pag-IBIG, `14`=Basic Pay (monthly), `15`=Allowance (unused), `16`=Daily Rate, `17`=Branch. Columns `1` (Full Name) and `15` (Allowance) are ignored. Date fields accept `YYYY-MM-DD`, `d-M-Y` (e.g. `28-Oct-1987`), or Excel date serials.

### PWA & Push Notifications

The staff portal is a PWA. Files: `public/manifest.json`, `public/sw.js`, `public/images/icons/icon-192.png`, `public/images/icons/icon-512.png`. App name is **"Jamelle Payroll"**, theme color green (`#22c55e`), `start_url` = `/staff/dashboard`.

**Install banners** (in `layouts/staff.blade.php`, pure JS):
- **Android** — captures `beforeinstallprompt`, shows a green banner with an Install button; disappears after install or via `appinstalled` event
- **iOS** — detects iOS Safari + not standalone, shows a blue banner with Share → "Add to Home Screen" instructions; dismissible via `localStorage('pwa-ios-dismissed')`
- **Neither banner** shows when `display-mode: standalone` (already installed)

**Notification permission** (in `layouts/staff.blade.php`):
- **Android** — `Notification.requestPermission()` is called automatically (allowed without gesture)
- **iOS standalone** — shows a separate amber "Enable Notifications" banner; permission is only requested on button tap (iOS requires a user gesture; auto-request is silently ignored); dismissible via `localStorage('notif-banner-dismissed')`

**Push subscription flow:** On permission grant → `navigator.serviceWorker.ready` (not `register().then()` — SW must be fully active) → `pushManager.subscribe()` with VAPID public key → `POST /push-subscriptions` → saved to `push_subscriptions` table. Uses `endpoint_hash` (SHA256) as the unique key to handle `updateOrCreate` on TEXT endpoint columns (MySQL can't index TEXT directly).

**Sending push:** Uses `minishlink/web-push`. VAPID config in `config/services.php` under `services.vapid`. To send, create a `WebPush` instance with VAPID config, queue notifications via `Subscription::create(['endpoint' => ..., 'keys' => ['p256dh' => ..., 'auth' => ...]])`, then call `flush()`. See `routes/web.php` `utilities/test-push` POST handler for a working example. For a command-based example, see `app/Console/Commands/SendTimeInReminders.php`.

**Scheduled push reminders** (`routes/console.php`, both run `everyFiveMinutes()`):
- `notifications:time-in-reminders` — fires only in the 07:00–07:05 window; skips rest days and staff who already timed in; uses daily cache key per user to prevent duplicates
- `notifications:clock-out-reminders` — fires 15 min before each employee's `work_end_time` (schedule resolution: `DailySchedule` → `EmployeeSchedule` → fallback 9:00 PM); skips rest days and staff who already clocked out

**iOS Web Push requirement:** iOS 16.4+ only, and only when running as an installed PWA (Add to Home Screen). Does not work in Safari browser tabs or in Chrome/Firefox on iOS.

### Frontend

Two layouts:
- `x-app-layout` (`AppLayout.php` → `layouts/app.blade.php`) — desktop sidebar layout for admin; includes "Powered by Futuristech.ph" footer
- `x-staff-layout` (`StaffLayout.php` → `layouts/staff.blade.php`) — mobile-first layout with bottom nav bar for staff portal; max-width constrained, sticky top bar, fixed bottom nav (no footer — intentionally omitted to avoid conflict with the bottom nav)

**Staff dashboard Today's DTR card:** Shows 4 event rows (Time In, Start Break, End Break, Time Out) with timemark color coding (green/amber/orange/blue). Each row is either logged (checkmark), next-to-log (colored dot + "Tap to Log" button), or locked (gray). Tapping opens an Alpine.js bottom sheet with a native `<input type="time">` and a 12-hour preview rendered below it via `toLocaleTimeString`. Time Out row includes an optional OT section. `<input type="time">` always renders in 24-hour format on Android/Brave regardless of locale — the 12-hour preview below the input is the workaround.

**Staff dashboard order:** Quote of the Day → Today's Schedule snippet (tappable, links to `staff.schedule`) → OT Approvals Waiting card (approvers only) → Today's DTR card → Recent DTRs. Pending OT count and unread notifications were removed from the dashboard; pending OT is shown as an amber banner on the DTR index instead.

**Staff dashboard daily quote:** `DashboardController::dailyQuote()` picks from a hardcoded array of 30 quotes using `today()->dayOfYear % count($quotes)` — same quote all day for all staff, changes each morning.

**Staff bottom nav:** Home, DTR, Schedule, Profile. Approvals tab removed (approvers use the dashboard card). DTR icon shows an amber dot when the employee has pending OT requests — computed inline in the layout via `Auth::user()->employee?->dtrs()->where('ot_status', 'pending')->count()`. Logout is inside the Profile page.

**Employee index filter persistence:** `EmployeeController` saves the last-used branch/status/search filters to `session('employee_filters')` and restores them on a bare index visit. Append `?clear=1` to reset.

**Alpine.js + Vite timing:** Vite loads Alpine as a deferred ES module (`type="module"`). This means `@push('scripts')` / `@stack('scripts')` content executes **after** Alpine has already initialized and evaluated all `x-data` attributes — so any Alpine component function defined via `@push` will be undefined when Alpine looks for it. **Always define Alpine component functions in a `<script>` tag placed directly before the `x-data` element** in the Blade template, not in a pushed stack.

Alpine.js handles interactivity inline (no Vue/React). PDF payslips via `barryvdh/laravel-dompdf`. Excel import via `phpoffice/phpspreadsheet`.

**DOMPDF fonts:** Currently uses DejaVu Sans (bundled with DOMPDF); the ₱ peso sign is rendered as `PHP` prefix because DejaVu Sans UFM lacks the glyph. `register-fonts.php` (untracked) is a dev utility script for experimenting with registering Noto Sans (which supports ₱ via U+20B1) — run it manually with `php register-fonts.php` after placing `NotoSans-Regular.ttf` and `NotoSans-Bold.ttf` in `storage/fonts/`. The `storage/fonts/*.json` files are DOMPDF font cache files (auto-generated, gitignored).

The timemark fetch button is hidden from the admin sidebar (commented out in `layouts/app.blade.php`) but the underlying code (`FetchAttendanceJob`, `TimemarkController`, `timemark.*` routes) is intact.

### Attendance Integration (`app/Jobs/FetchAttendanceJob.php`)

Queued job (`ShouldQueue`) that fetches from DaysCamera timemark device API:
- Paginated fetch (pageSize=20) with 200ms throttle between pages
- API state codes: 0=time_in, 1=am_out, 2=pm_in, 3=time_out
- SSL verification controlled by `TIMEMARK_VERIFY_SSL` env var (set to `false` locally on Windows/WAMP; defaults to `true` in production)

Queue uses database driver. Manual: `php artisan queue:listen --tries=1 --timeout=0`

### Railway Deployment

Config files: `nixpacks.toml` (build), `railway.json` (web service deploy), `railway-cron.json` (cron service deploy), and `start.sh` (runtime entrypoint). Key behaviours:
- `bootstrap/cache` and `storage/` dirs are gitignored, so `nixpacks.toml` creates them with `mkdir -p` before `composer install`
- Build phase: installs deps, runs `npm run build`, caches routes and views only — **config cache is NOT done at build time**; `php artisan config:cache` runs inside `start.sh` at container startup
- Production server: **nginx + PHP-FPM** via `start.sh`; configs written to `/tmp` at startup substituting `$PORT`
- Migration ordering: `payroll_deductions` timestamp bumped to `142153` so it runs after `payroll_entries` (FK dependency)
- Queue worker runs as a **separate Railway service** with start command: `php artisan queue:listen --tries=1 --timeout=0`
- Cron service runs as a **separate Railway service** using `railway-cron.json` (set via Settings → Config-as-code in Railway dashboard); start command: `php artisan config:cache && php artisan schedule:run`; cron schedule: `*/5 * * * *`. The cron service must have the same env vars as the web service (DB, APP_KEY, VAPID, etc.) — Railway does not share variables between services automatically.
- `AdminUserSeeder` runs on every deploy (inside `start.sh`); idempotent via `firstOrCreate`
- `bootstrap/app.php` sets `trustProxies(at: '*')` only when `APP_ENV=production` — calling it unconditionally causes a Symfony `IpUtils::checkIp4` crash on local (no REMOTE_ADDR)
- `public/hot` is gitignored — never commit it
- Required env vars: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `DB_*` (use public proxy host/port, not `mysql.railway.internal`), `LOG_CHANNEL=stderr`, `TIMEMARK_VERIFY_SSL=true`, `ANTHROPIC_API_KEY` (not currently used in production — schedule import uses manual JSON paste), `ANTHROPIC_VERIFY_SSL=true`, `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`

### Database & Seeding

MySQL with database-backed sessions, cache, and notifications. All foreign keys cascade on delete.

**MySQL `TRUNCATE` with FK constraints:** `TRUNCATE TABLE` fails in production even when truncating the child table first, because MySQL checks FK constraints before executing. Use `DELETE` with FK checks disabled instead:
```php
DB::statement('SET FOREIGN_KEY_CHECKS=0');
DB::table('child_table')->delete();
DB::table('parent_table')->delete();
DB::statement('SET FOREIGN_KEY_CHECKS=1');
```

Seeders (run in order via `DatabaseSeeder`):
- `BranchSeeder` — Head Office, Abreeza, SM Lanang, SM Ecoland, NCCC
- `AdminUserSeeder` — admin user (`admin@payroll.test`), runs on every deploy
- `PayrollCutoffSeeder` — 6 semi-monthly cutoffs per branch going back ~3 months; idempotent via `firstOrCreate`
- `EmployeeSeeder` — 36 real employees; management staff seeded with `rate = 0` (configure manually)

### Testing

Only scaffolded Laravel Breeze auth tests exist (`tests/Feature/Auth/`). There are no domain-specific tests for payroll, DTR, or OT computation yet. When adding tests, use `php artisan test --filter=TestClassName` to run a single test class.
