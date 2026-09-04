<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $labels = [
            'Senior Registered' => 'Senior Registered Nurses',
            'Registered' => 'Registered Nurses',
            'Enrolled' => 'Enrolled Nurses',
        ];

        foreach ($labels as $old => $new) {
            DB::table('critical_staff_leaves')->where('staff_category', $old)->update(['staff_category' => $new]);
            DB::table('critical_staff_availability_setup')->where('staff_category', $old)->update(['staff_category' => $new]);
        }
    }

    public function down(): void
    {
        $labels = [
            'Senior Registered Nurses' => 'Senior Registered',
            'Registered Nurses' => 'Registered',
            'Enrolled Nurses' => 'Enrolled',
        ];

        foreach ($labels as $new => $old) {
            DB::table('critical_staff_leaves')->where('staff_category', $new)->update(['staff_category' => $old]);
            DB::table('critical_staff_availability_setup')->where('staff_category', $new)->update(['staff_category' => $old]);
        }
    }
};
