<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $renames = [
            'PAG-IBIG LOAN' => 'Pag-ibig Loan',
            'SSS LOAN'      => 'SSS Loan',
            'SAVINGS'       => 'Savings',
            'RETIREMENT PAY' => 'Retirement Pay',
        ];

        foreach ($renames as $old => $new) {
            DB::table('payroll_entry_variable_deductions')
                ->where('description', $old)
                ->update(['description' => $new]);
        }
    }

    public function down(): void
    {
        $renames = [
            'Pag-ibig Loan'  => 'PAG-IBIG LOAN',
            'SSS Loan'       => 'SSS LOAN',
            'Savings'        => 'SAVINGS',
            'Retirement Pay' => 'RETIREMENT PAY',
        ];

        foreach ($renames as $old => $new) {
            DB::table('payroll_entry_variable_deductions')
                ->where('description', $old)
                ->update(['description' => $new]);
        }
    }
};
