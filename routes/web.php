<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\EmployeeAllowanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\PayrollCutoffController;
use App\Http\Controllers\PayrollEntryController;
use App\Http\Controllers\PayrollEntryRefundController;
use App\Http\Controllers\PayrollEntryVariableDeductionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleUploadController;
use App\Http\Controllers\SetupSignatureController;
use App\Http\Controllers\TimemarkController;
use App\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Route;

Route::get("/", function () { return redirect()->route("login"); });
Route::get("/health", function () { return response()->json(["status" => "ok"]); });

Route::middleware("auth")->group(function () {
    Route::get("/change-password", [ChangePasswordController::class, "show"])->name("password.change");
    Route::post("/change-password", [ChangePasswordController::class, "update"])->name("password.change.update");
    Route::get("/setup-signature", [SetupSignatureController::class, "show"])->name("signature.setup");
    Route::post("/setup-signature", [SetupSignatureController::class, "store"])->name("signature.setup.store");
    Route::post("/impersonation/exit", [ImpersonationController::class, "exit"])->name("impersonation.exit");
    Route::post("/push-subscriptions", [\App\Http\Controllers\PushSubscriptionController::class, "store"])->name("push-subscriptions.store");
});

Route::middleware(["auth", "staff"])->prefix("staff")->name("staff.")->group(function () {
    Route::get("/dashboard", [\App\Http\Controllers\Staff\DashboardController::class, "index"])->name("dashboard");
    Route::get("/dtr", [\App\Http\Controllers\Staff\DtrController::class, "index"])->name("dtr.index");
    Route::get("/dtr/create", [\App\Http\Controllers\Staff\DtrController::class, "create"])->name("dtr.create");
    Route::post("/dtr/log-event", [\App\Http\Controllers\Staff\DtrController::class, "logEvent"])->name("dtr.log-event");
    Route::post("/dtr", [\App\Http\Controllers\Staff\DtrController::class, "store"])->name("dtr.store");
    Route::get("/dtr/{dtr}/edit", [\App\Http\Controllers\Staff\DtrController::class, "edit"])->name("dtr.edit");
    Route::put("/dtr/{dtr}", [\App\Http\Controllers\Staff\DtrController::class, "update"])->name("dtr.update");
    Route::get("/ot-approvals", [\App\Http\Controllers\Staff\OtApprovalController::class, "index"])->name("ot-approvals.index");
    Route::post("/ot-approvals/{dtr}/approve", [\App\Http\Controllers\Staff\OtApprovalController::class, "approve"])->name("ot-approvals.approve");
    Route::post("/ot-approvals/{dtr}/reject", [\App\Http\Controllers\Staff\OtApprovalController::class, "reject"])->name("ot-approvals.reject");
    Route::get("/notifications", [\App\Http\Controllers\Staff\NotificationController::class, "index"])->name("notifications.index");
    Route::post("/notifications/mark-read", [\App\Http\Controllers\Staff\NotificationController::class, "markAllRead"])->name("notifications.mark-read");
    Route::get("/profile", [\App\Http\Controllers\Staff\ProfileController::class, "index"])->name("profile");
    Route::post("/profile/signature", [\App\Http\Controllers\Staff\ProfileController::class, "updateSignature"])->name("profile.signature");
    Route::get("/schedule", [\App\Http\Controllers\Staff\ScheduleController::class, "index"])->name("schedule");
    Route::get("/payslips", [\App\Http\Controllers\Staff\PayslipController::class, "index"])->name("payslips.index");
    Route::get("/payslips/{entry}", [\App\Http\Controllers\Staff\PayslipController::class, "show"])->name("payslips.show");
    Route::get("/payslips/{entry}/pdf", [\App\Http\Controllers\Staff\PayslipController::class, "downloadPdf"])->name("payslips.pdf");
    Route::post("/payslips/{entry}/acknowledge", [\App\Http\Controllers\Staff\PayslipController::class, "acknowledge"])->name("payslips.acknowledge");
});

Route::middleware(["auth", "admin"])->group(function () {
    Route::get("/dashboard", [\App\Http\Controllers\DashboardController::class, "index"])->middleware("verified")->name("dashboard");
    Route::get("/profile", [ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile", [ProfileController::class, "update"])->name("profile.update");
    Route::delete("/profile", [ProfileController::class, "destroy"])->name("profile.destroy");

    // --- Accessible to all admins (super + limited) ---

    // Employees: index accessible to all admins (schedule managers get a simplified view)
    Route::get("employees", [EmployeeController::class, "index"])->name("employees.index");

    // Employee schedules & daily schedules
    Route::prefix("employees/{employee}")->name("employees.")->group(function () {
        Route::get("schedules", [EmployeeScheduleController::class, "index"])->name("schedules.index");
        Route::post("schedules/default", [EmployeeScheduleController::class, "saveDefault"])->name("schedules.saveDefault");
        Route::delete("schedules/default", [EmployeeScheduleController::class, "destroyDefault"])->name("schedules.destroyDefault");
        Route::post("schedules", [EmployeeScheduleController::class, "store"])->name("schedules.store");
        Route::put("schedules/{schedule}", [EmployeeScheduleController::class, "update"])->name("schedules.update");
        Route::delete("schedules/{schedule}", [EmployeeScheduleController::class, "destroy"])->name("schedules.destroy");
        Route::post("daily-schedules", [EmployeeScheduleController::class, "storeDaily"])->name("daily-schedules.store");
        Route::put("daily-schedules/{daily}", [EmployeeScheduleController::class, "updateDaily"])->name("daily-schedules.update");
        Route::delete("daily-schedules/{daily}", [EmployeeScheduleController::class, "destroyDaily"])->name("daily-schedules.destroy");
    });

    // Schedule uploads
    Route::get("schedule-uploads", [ScheduleUploadController::class, "index"])->name("schedule-uploads.index");
    Route::get("schedule-uploads/create", [ScheduleUploadController::class, "create"])->name("schedule-uploads.create");
    Route::post("schedule-uploads", [ScheduleUploadController::class, "store"])->name("schedule-uploads.store");
    Route::get("schedule-uploads/{schedule}/review", [ScheduleUploadController::class, "review"])->name("schedule-uploads.review");
    Route::post("schedule-uploads/{schedule}/apply", [ScheduleUploadController::class, "apply"])->name("schedule-uploads.apply");
    Route::post("schedule-uploads/{schedule}/assign-name", [ScheduleUploadController::class, "assignName"])->name("schedule-uploads.assign-name");
    Route::delete("schedule-uploads/{schedule}", [ScheduleUploadController::class, "destroy"])->name("schedule-uploads.destroy");

    // --- Super admin only ---
    Route::middleware("super-admin")->group(function () {
        Route::resource("admin-users", AdminUserController::class)->except(['show']);
        Route::resource("branches", BranchController::class);

        // Employee full management
        Route::get("employees/{employee}", [EmployeeController::class, "show"])->name("employees.show");
        Route::get("employees/create", [EmployeeController::class, "create"])->name("employees.create");
        Route::post("employees", [EmployeeController::class, "store"])->name("employees.store");
        Route::get("employees/{employee}/edit", [EmployeeController::class, "edit"])->name("employees.edit");
        Route::put("employees/{employee}", [EmployeeController::class, "update"])->name("employees.update");
        Route::delete("employees/{employee}", [EmployeeController::class, "destroy"])->name("employees.destroy");
        Route::get("employees/import/template", [\App\Http\Controllers\EmployeeImportController::class, "template"])->name("employees.import.template");
        Route::post("employees/import", [\App\Http\Controllers\EmployeeImportController::class, "import"])->name("employees.import");

        // Employee account, deductions, allowances, impersonation
        Route::prefix("employees/{employee}")->name("employees.")->group(function () {
            Route::post("account", [EmployeeController::class, "createAccount"])->name("account.create");
            Route::patch("account", [EmployeeController::class, "updateAccount"])->name("account.update");
            Route::post("account/reset-password", [EmployeeController::class, "resetPassword"])->name("account.reset-password");
            Route::post("account/change-password", [EmployeeController::class, "changePassword"])->name("account.change-password");
            Route::post("impersonate", [ImpersonationController::class, "impersonate"])->name("impersonate");
            Route::get("deductions", [\App\Http\Controllers\EmployeeStandingDeductionController::class, "index"])->name("deductions.index");
            Route::post("deductions", [\App\Http\Controllers\EmployeeStandingDeductionController::class, "store"])->name("deductions.store");
            Route::put("deductions/{deduction}", [\App\Http\Controllers\EmployeeStandingDeductionController::class, "update"])->name("deductions.update");
            Route::patch("deductions/{deduction}/toggle", [\App\Http\Controllers\EmployeeStandingDeductionController::class, "toggle"])->name("deductions.toggle");
            Route::delete("deductions/{deduction}", [\App\Http\Controllers\EmployeeStandingDeductionController::class, "destroy"])->name("deductions.destroy");
            Route::get("allowances", [EmployeeAllowanceController::class, "index"])->name("allowances.index");
            Route::post("allowances", [EmployeeAllowanceController::class, "store"])->name("allowances.store");
            Route::put("allowances/{allowance}", [EmployeeAllowanceController::class, "update"])->name("allowances.update");
            Route::patch("allowances/{allowance}/toggle", [EmployeeAllowanceController::class, "toggle"])->name("allowances.toggle");
            Route::delete("allowances/{allowance}", [EmployeeAllowanceController::class, "destroy"])->name("allowances.destroy");
        });

        // Payroll
        Route::resource("payroll/cutoffs", PayrollCutoffController::class)->names([
            "index" => "payroll.cutoffs.index", "create" => "payroll.cutoffs.create",
            "store" => "payroll.cutoffs.store", "show" => "payroll.cutoffs.show",
            "edit" => "payroll.cutoffs.edit", "update" => "payroll.cutoffs.update",
            "destroy" => "payroll.cutoffs.destroy",
        ]);
        Route::prefix("payroll/cutoffs/{cutoff}")->name("payroll.cutoffs.")->group(function () {
            Route::get("entries", [PayrollEntryController::class, "index"])->name("entries.index");
            Route::get("entries/{entry}", [PayrollEntryController::class, "show"])->name("entries.show");
            Route::get("entries/{entry}/pdf", [PayrollEntryController::class, "pdf"])->name("entries.pdf");
            Route::post("entries/{entry}/refunds", [PayrollEntryRefundController::class, "store"])->name("entries.refunds.store");
            Route::delete("entries/{entry}/refunds/{refund}", [PayrollEntryRefundController::class, "destroy"])->name("entries.refunds.destroy");
            Route::post("entries/{entry}/variable-deductions", [PayrollEntryVariableDeductionController::class, "store"])->name("entries.variable-deductions.store");
            Route::patch("entries/{entry}/variable-deductions/{variableDeduction}", [PayrollEntryVariableDeductionController::class, "update"])->name("entries.variable-deductions.update");
            Route::delete("entries/{entry}/variable-deductions/{variableDeduction}", [PayrollEntryVariableDeductionController::class, "destroy"])->name("entries.variable-deductions.destroy");
            Route::post("generate", [PayrollEntryController::class, "generate"])->name("generate");
            Route::post("finalize", [PayrollEntryController::class, "finalize"])->name("finalize");
            Route::get("bdo-export", [PayrollCutoffController::class, "bdoExport"])->name("bdo-export");
            Route::post("void", [PayrollCutoffController::class, "void"])->name("void");
            Route::post("unvoid", [PayrollCutoffController::class, "unvoid"])->name("unvoid");
        });

        // DTR admin view
        Route::get("dtr", [DtrController::class, "index"])->name("dtr.index");
        Route::get("dtr/export", [DtrController::class, "export"])->name("dtr.export");
        Route::get("dtr/export-excel", [DtrController::class, "exportExcel"])->name("dtr.export-excel");
        Route::get("dtr/{dtr}/edit", [DtrController::class, "edit"])->name("dtr.edit");
        Route::put("dtr/{dtr}", [DtrController::class, "update"])->name("dtr.update");
        Route::get("dtr/{dtr}", [DtrController::class, "show"])->name("dtr.show");
        Route::post("dtr/{dtr}/approve-ot", [DtrController::class, "approveOt"])->name("dtr.approve-ot");
        Route::post("dtr/{dtr}/reject-ot", [DtrController::class, "rejectOt"])->name("dtr.reject-ot");

        // Reports
        Route::get("reports/lates", [\App\Http\Controllers\ReportsController::class, "lates"])->name("reports.lates");
        Route::get("reports/overtime", [\App\Http\Controllers\ReportsController::class, "overtime"])->name("reports.overtime");

        // Holidays
        Route::get("holidays", [HolidayController::class, "index"])->name("holidays.index");
        Route::post("holidays", [HolidayController::class, "store"])->name("holidays.store");
        Route::put("holidays/{holiday}", [HolidayController::class, "update"])->name("holidays.update");
        Route::delete("holidays/{holiday}", [HolidayController::class, "destroy"])->name("holidays.destroy");

        // Timemark
        Route::post("timemark/fetch", [TimemarkController::class, "fetch"])->name("timemark.fetch");
        Route::get("timemark/logs", [TimemarkController::class, "index"])->name("timemark.logs");

        // Adminer — database UI; CSRF excluded in bootstrap/app.php
        Route::any("admin/adminer", \App\Http\Controllers\AdminerController::class)->name("adminer");

        // Utilities
        Route::get("utilities/test-push", function () {
            $count = \App\Models\PushSubscription::count();
            return response('
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Test Push Notification</title>
                    <style>
                        body { font-family: sans-serif; padding: 2rem; max-width: 480px; }
                        label { display: block; margin-bottom: .25rem; font-size: .875rem; color: #374151; font-weight: 500; }
                        input, textarea { width: 100%; box-sizing: border-box; border: 1px solid #d1d5db; border-radius: 6px; padding: .5rem .75rem; font-size: 1rem; margin-bottom: 1rem; }
                        textarea { resize: vertical; }
                        button { background: #16a34a; color: white; padding: .6rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; }
                        button:hover { background: #15803d; }
                        .meta { color: #6b7280; font-size: .875rem; margin-bottom: 1.5rem; }
                    </style>
                </head>
                <body>
                    <h2 style="margin-bottom:.5rem">Test Push Notification</h2>
                    <p class="meta">' . $count . ' device(s) subscribed</p>
                    <form method="POST">
                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                        <label>Title</label>
                        <input type="text" name="title" value="Jamelle Payroll" required>
                        <label>Message</label>
                        <textarea name="body" rows="4" required placeholder="Enter your test message..."></textarea>
                        <button type="submit">Send to All Devices</button>
                    </form>
                </body>
                </html>
            ');
        })->name("utilities.test-push");

        Route::post("utilities/test-push", function (\Illuminate\Http\Request $request) {
            $request->validate([
                'title' => 'required|string|max:100',
                'body'  => 'required|string|max:500',
            ]);

            $subscriptions = \App\Models\PushSubscription::all();

            if ($subscriptions->isEmpty()) {
                return back()->with('error', 'No subscribed devices found.');
            }

            $webPush = new \Minishlink\WebPush\WebPush([
                'VAPID' => [
                    'subject'    => config('services.vapid.subject'),
                    'publicKey'  => config('services.vapid.public_key'),
                    'privateKey' => config('services.vapid.private_key'),
                ],
            ]);

            $payload = json_encode([
                'title' => $request->input('title'),
                'body'  => $request->input('body'),
                'url'   => '/staff/dashboard',
            ]);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    \Minishlink\WebPush\Subscription::create([
                        'endpoint'        => $sub->endpoint,
                        'keys'            => [
                            'p256dh' => $sub->p256dh_key,
                            'auth'   => $sub->auth_token,
                        ],
                    ]),
                    $payload
                );
            }

            $sent = 0;
            $failed = 0;
            foreach ($webPush->flush() as $report) {
                $report->isSuccess() ? $sent++ : $failed++;
            }

            return response('
                <!DOCTYPE html><html><head><meta charset="utf-8"><title>Done</title>
                <style>body{font-family:sans-serif;padding:2rem;max-width:480px} a{color:#16a34a}</style>
                </head><body>
                <h2>Done</h2>
                <p>Sent: <strong>' . $sent . '</strong> &nbsp; Failed: <strong>' . $failed . '</strong></p>
                <a href="' . route('utilities.test-push') . '">&larr; Send another</a>
                </body></html>
            ');
        });

        Route::get("utilities/truncate-schedules", function () {
            return response('
                <form method="POST" style="font-family:sans-serif;padding:2rem;max-width:400px">
                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                    <h2 style="margin-bottom:1rem">Truncate Schedule Data</h2>
                    <p style="margin-bottom:1.5rem;color:#555">This will permanently delete all rows in <strong>schedule_uploads</strong> and <strong>daily_schedules</strong>. This cannot be undone.</p>
                    <button type="submit" style="background:#dc2626;color:white;padding:.6rem 1.5rem;border:none;border-radius:6px;cursor:pointer;font-size:1rem">
                        Yes, delete everything
                    </button>
                </form>
            ');
        })->name("utilities.truncate-schedules");
        Route::post("utilities/truncate-schedules", function () {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
            \Illuminate\Support\Facades\DB::table('daily_schedules')->delete();
            \Illuminate\Support\Facades\DB::table('schedule_uploads')->delete();
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return redirect()->route('schedule-uploads.index')
                ->with('success', 'All schedule uploads and daily schedules have been deleted.');
        });
    });
});

require __DIR__."/auth.php";
