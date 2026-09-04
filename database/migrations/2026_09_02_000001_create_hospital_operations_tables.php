<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_profile_id')->constrained('hospital_profiles')->cascadeOnDelete();
            $table->foreignUuid('island_id')->nullable()->constrained('islands')->nullOnDelete();
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('severity')->default('high');
            $table->string('status')->default('active');
            $table->string('attachment_url')->nullable();
            $table->timestampTz('acknowledged_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('profiles')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['hospital_profile_id', 'status', 'severity']);
        });

        Schema::create('equipment_faults', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_profile_id')->constrained('hospital_profiles')->cascadeOnDelete();
            $table->string('equipment_name');
            $table->string('asset_tag')->nullable();
            $table->string('category')->nullable();
            $table->string('severity')->default('medium');
            $table->text('description');
            $table->string('photo_url')->nullable();
            $table->string('status')->default('reported');
            $table->date('expected_return_date')->nullable();
            $table->text('repair_notes')->nullable();
            $table->json('maintenance_history')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('profiles')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('profiles')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['hospital_profile_id', 'status', 'severity']);
        });

        Schema::create('transport_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_profile_id')->constrained('hospital_profiles')->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->string('status')->default('operational');
            $table->text('unavailable_reason')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('profiles')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['hospital_profile_id', 'type', 'status']);
        });

        Schema::create('tracked_expiry_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_profile_id')->constrained('hospital_profiles')->cascadeOnDelete();
            $table->string('item_type');
            $table->string('name');
            $table->string('reference_number')->nullable();
            $table->date('expiry_date');
            $table->unsignedInteger('warning_days')->default(30);
            $table->decimal('quantity', 12, 2)->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->string('document_url')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('profiles')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['hospital_profile_id', 'expiry_date', 'status']);
        });

        Schema::create('hospital_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_profile_id')->constrained('hospital_profiles')->cascadeOnDelete();
            $table->string('category');
            $table->string('title');
            $table->string('version')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_url');
            $table->text('notes')->nullable();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('profiles')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['hospital_profile_id', 'category', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_documents');
        Schema::dropIfExists('tracked_expiry_items');
        Schema::dropIfExists('transport_assets');
        Schema::dropIfExists('equipment_faults');
        Schema::dropIfExists('emergency_incidents');
    }
};
