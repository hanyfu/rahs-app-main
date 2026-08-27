<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hospital_profiles', 'hospital_contact_id')) {
            Schema::table('hospital_profiles', function (Blueprint $table) {
                $table->foreignUuid('hospital_contact_id')
                    ->nullable()
                    ->unique()
                    ->after('id')
                    ->constrained('hospital_contacts')
                    ->cascadeOnDelete();
            });
        }

        DB::table('hospital_profiles')
            ->whereNull('hospital_contact_id')
            ->whereNotNull('island_id')
            ->orderBy('created_at')
            ->each(function (object $profile): void {
                $contactId = DB::table('hospital_contacts')
                    ->where('island_id', $profile->island_id)
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                    ->orderByDesc('created_at')
                    ->value('id');

                if ($contactId) {
                    DB::table('hospital_profiles')
                        ->where('id', $profile->id)
                        ->update(['hospital_contact_id' => $contactId]);
                }
            });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE hospital_profiles DROP CONSTRAINT IF EXISTS hospital_profiles_island_id_unique');
            DB::statement('ALTER TABLE hospital_profiles DROP CONSTRAINT IF EXISTS hospital_profiles_island_id_key');
        } elseif (DB::getDriverName() !== 'sqlite') {
            Schema::table('hospital_profiles', function (Blueprint $table) {
                $table->dropUnique(['island_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hospital_profiles', 'hospital_contact_id')) {
            Schema::table('hospital_profiles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('hospital_contact_id');
            });
        }
    }
};
