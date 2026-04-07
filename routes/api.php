<?php

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/employees/check', function (Request $request) {
    $code = $request->query('code');

    if (blank($code)) {
        return response()->json(['error' => 'The code parameter is required.'], 422);
    }

    $exists = Employee::where('employee_code', $code)->exists();

    return response()->json(
        $exists
            ? ['exists' => true, 'employee_code' => $code]
            : ['exists' => false]
    );
});
