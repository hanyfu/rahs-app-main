<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['equipment_faults', 'transport_assets', 'tracked_expiry_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignUuid('task_id')->nullable()->after('hospital_profile_id')->constrained('tasks')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['equipment_faults', 'transport_assets', 'tracked_expiry_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('task_id');
            });
        }
    }
};
