<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\PayrollCutoffController;
use App\Http\Controllers\PayrollEntryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TimemarkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Branches
    Route::resource('branches', BranchController::class);

    // Employees
    Route::resource('employees', EmployeeController::class);

    // Employee Schedules & Deductions (nested under employee)
    Route::prefix('employees/{employee}')->name('employees.')->group(function () {
        Route::get('schedules', [EmployeeScheduleController::class, 'index'])->name('schedules.index');
        Route::post('schedules', [EmployeeScheduleController::class, 'store'])->name('schedules.store');
        Route::put('schedules/{schedule}', [EmployeeScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('schedules/{schedule}', [EmployeeScheduleController::class, 'destroy'])->name('schedules.destroy');

        Route::get('deductions', [\App\Http\Controllers\EmployeeStandingDeductionController::class, 'index'])->name('deductions.index');
        Route::post('deductions', [\App\Http\Controllers\EmployeeStandingDeductionController::class, 'store'])->name('deductions.store');
        Route::put('deductions/{deduction}', [\App\Http\Controllers\EmployeeStandingDeductionController::class, 'update'])->name('deductions.update');
        Route::patch('deductions/{deduction}/toggle', [\App\Http\Controllers\EmployeeStandingDeductionController::class, 'toggle'])->name('deductions.toggle');
        Route::delete('deductions/{deduction}', [\App\Http\Controllers\EmployeeStandingDeductionController::class, 'destroy'])->name('deductions.destroy');
    });

    // Payroll Cutoffs
    Route::resource('payroll/cutoffs', PayrollCutoffController::class)->names([
        'index'   => 'payroll.cutoffs.index',
        'create'  => 'payroll.cutoffs.create',
        'store'   => 'payroll.cutoffs.store',
        'show'    => 'payroll.cutoffs.show',
        'edit'    => 'payroll.cutoffs.edit',
        'update'  => 'payroll.cutoffs.update',
        'destroy' => 'payroll.cutoffs.destroy',
    ]);

    // Payroll Entries (nested under cutoff)
    Route::prefix('payroll/cutoffs/{cutoff}')->name('payroll.cutoffs.')->group(function () {
        Route::get('entries', [PayrollEntryController::class, 'index'])->name('entries.index');
        Route::get('entries/{entry}', [PayrollEntryController::class, 'show'])->name('entries.show');
        Route::get('entries/{entry}/pdf', [PayrollEntryController::class, 'pdf'])->name('entries.pdf');
        Route::post('generate', [PayrollEntryController::class, 'generate'])->name('generate');
    });

    // DTR
    Route::get('dtr', [DtrController::class, 'index'])->name('dtr.index');
    Route::get('dtr/{dtr}', [DtrController::class, 'show'])->name('dtr.show');

    // Timemark
    Route::post('timemark/fetch', [TimemarkController::class, 'fetch'])->name('timemark.fetch');
    Route::get('timemark/logs', [TimemarkController::class, 'index'])->name('timemark.logs');
});

require __DIR__.'/auth.php';
