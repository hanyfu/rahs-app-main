<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('critical_staff_leaves', function (Blueprint $table) {
            $table->uuid('hospital_profile_id')->nullable()->after('department_unit')->references('id')->on('hospital_profiles')->nullOnDelete();
            $table->uuid('island_id')->nullable()->after('hospital_profile_id')->references('id')->on('islands')->nullOnDelete();
        });

        Schema::table('critical_staff_availability_setup', function (Blueprint $table) {
            $table->uuid('hospital_profile_id')->nullable()->after('department_unit')->references('id')->on('hospital_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('critical_staff_availability_setup', fn (Blueprint $table) => $table->dropConstrainedForeignId('hospital_profile_id'));
        Schema::table('critical_staff_leaves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('island_id');
            $table->dropConstrainedForeignId('hospital_profile_id');
        });
    }
};
