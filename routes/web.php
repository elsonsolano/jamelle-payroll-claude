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
use Illuminate\Support\Facades\Route;

Route::get("/", function () { return redirect()->route("login"); });
Route::get("/health", function () { return response()->json(["status" => "ok"]); });

Route::middleware("auth")->group(function () {
    Route::get("/change-password", [ChangePasswordController::class, "show"])->name("password.change");
    Route::post("/change-password", [ChangePasswordController::class, "update"])->name("password.change.update");
    Route::get("/setup-signature", [SetupSignatureController::class, "show"])->name("signature.setup");
    Route::post("/setup-signature", [SetupSignatureController::class, "store"])->name("signature.setup.store");
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
});

Route::middleware(["auth", "admin"])->group(function () {
    Route::get("/dashboard", [\App\Http\Controllers\DashboardController::class, "index"])->middleware("verified")->name("dashboard");
    Route::get("/profile", [ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile", [ProfileController::class, "update"])->name("profile.update");
    Route::delete("/profile", [ProfileController::class, "destroy"])->name("profile.destroy");
    Route::resource("admin-users", AdminUserController::class)->except(['show']);
    Route::resource("branches", BranchController::class);
    Route::get("employees/import/template", [\App\Http\Controllers\EmployeeImportController::class, "template"])->name("employees.import.template");
    Route::post("employees/import", [\App\Http\Controllers\EmployeeImportController::class, "import"])->name("employees.import");
    Route::resource("employees", EmployeeController::class);
    Route::prefix("employees/{employee}")->name("employees.")->group(function () {
        Route::post("account", [EmployeeController::class, "createAccount"])->name("account.create");
        Route::patch("account", [EmployeeController::class, "updateAccount"])->name("account.update");
        Route::post("account/reset-password", [EmployeeController::class, "resetPassword"])->name("account.reset-password");
        Route::get("schedules", [EmployeeScheduleController::class, "index"])->name("schedules.index");
        Route::post("schedules", [EmployeeScheduleController::class, "store"])->name("schedules.store");
        Route::put("schedules/{schedule}", [EmployeeScheduleController::class, "update"])->name("schedules.update");
        Route::delete("schedules/{schedule}", [EmployeeScheduleController::class, "destroy"])->name("schedules.destroy");
        Route::post("daily-schedules", [EmployeeScheduleController::class, "storeDaily"])->name("daily-schedules.store");
        Route::put("daily-schedules/{daily}", [EmployeeScheduleController::class, "updateDaily"])->name("daily-schedules.update");
        Route::delete("daily-schedules/{daily}", [EmployeeScheduleController::class, "destroyDaily"])->name("daily-schedules.destroy");
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
        Route::delete("entries/{entry}/variable-deductions/{variableDeduction}", [PayrollEntryVariableDeductionController::class, "destroy"])->name("entries.variable-deductions.destroy");
        Route::post("generate", [PayrollEntryController::class, "generate"])->name("generate");
        Route::post("void", [PayrollCutoffController::class, "void"])->name("void");
        Route::post("unvoid", [PayrollCutoffController::class, "unvoid"])->name("unvoid");
    });
    Route::get("dtr", [DtrController::class, "index"])->name("dtr.index");
    Route::get("dtr/{dtr}", [DtrController::class, "show"])->name("dtr.show");
    Route::post("dtr/{dtr}/approve-ot", [DtrController::class, "approveOt"])->name("dtr.approve-ot");
    Route::post("dtr/{dtr}/reject-ot", [DtrController::class, "rejectOt"])->name("dtr.reject-ot");
    Route::post("timemark/fetch", [TimemarkController::class, "fetch"])->name("timemark.fetch");
    Route::get("timemark/logs", [TimemarkController::class, "index"])->name("timemark.logs");
    Route::get("schedule-uploads", [ScheduleUploadController::class, "index"])->name("schedule-uploads.index");
    Route::get("schedule-uploads/create", [ScheduleUploadController::class, "create"])->name("schedule-uploads.create");
    Route::post("schedule-uploads", [ScheduleUploadController::class, "store"])->name("schedule-uploads.store");
    Route::get("schedule-uploads/{schedule}/review", [ScheduleUploadController::class, "review"])->name("schedule-uploads.review");
    Route::post("schedule-uploads/{schedule}/apply", [ScheduleUploadController::class, "apply"])->name("schedule-uploads.apply");
    Route::post("schedule-uploads/{schedule}/assign-name", [ScheduleUploadController::class, "assignName"])->name("schedule-uploads.assign-name");
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

    Route::get("holidays", [HolidayController::class, "index"])->name("holidays.index");
    Route::post("holidays", [HolidayController::class, "store"])->name("holidays.store");
    Route::put("holidays/{holiday}", [HolidayController::class, "update"])->name("holidays.update");
    Route::delete("holidays/{holiday}", [HolidayController::class, "destroy"])->name("holidays.destroy");
});

require __DIR__."/auth.php";
