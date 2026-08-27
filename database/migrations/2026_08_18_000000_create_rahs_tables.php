<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('name');
                $table->text('color')->nullable();
                $table->text('status')->default('active');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('user_departments')) {
            Schema::create('user_departments', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('name');
                $table->text('description')->nullable();
                $table->text('color')->nullable();
                $table->text('status')->default('active');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('profiles')) {
            Schema::create('profiles', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('email')->unique();
                $table->text('first_name');
                $table->text('last_name');
                $table->text('avatar_url')->nullable();
                $table->text('designation')->nullable();
                $table->text('contact_no')->nullable();
                $table->uuid('department_id')->nullable()->references('id')->on('departments');
                $table->uuid('user_department_id')->nullable()->references('id')->on('user_departments');
                $table->uuid('manager_id')->nullable()->references('id')->on('profiles');
                $table->text('status')->default('active');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('user_id')->unique()->nullable()->references('id')->on('profiles')->cascadeOnDelete();
                $table->text('role')->default('staff');
            });
        }

        if (! Schema::hasTable('atolls')) {
            Schema::create('atolls', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('name');
                $table->uuid('coordinator_id')->nullable()->references('id')->on('profiles');
                $table->uuid('supervisor_id')->nullable()->references('id')->on('profiles');
                $table->text('status')->default('active');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('islands')) {
            Schema::create('islands', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('atoll_id')->nullable()->references('id')->on('atolls')->cascadeOnDelete();
                $table->text('name');
                $table->uuid('assigned_staff_id')->nullable()->references('id')->on('profiles');
                $table->text('status')->default('active');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('title');
                $table->text('creator_description')->nullable();
                $table->text('completion_description')->nullable();
                $table->text('status')->default('pending');
                $table->text('priority')->default('medium');
                $table->uuid('assigned_by')->nullable()->references('id')->on('profiles');
                $table->uuid('assigned_to')->nullable()->references('id')->on('profiles');
                $table->uuid('department_id')->nullable()->references('id')->on('departments');
                $table->uuid('island_id')->nullable()->references('id')->on('islands');
                $table->text('due_date')->nullable();
                $table->boolean('archived')->default(false);
                $table->jsonb('task_types')->nullable();
                $table->text('attachment_url')->nullable();
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('task_comments')) {
            Schema::create('task_comments', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('task_id')->nullable()->references('id')->on('tasks')->cascadeOnDelete();
                $table->uuid('user_id')->nullable()->references('id')->on('profiles');
                $table->text('content');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('task_activities')) {
            Schema::create('task_activities', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('task_id')->nullable()->references('id')->on('tasks')->cascadeOnDelete();
                $table->uuid('user_id')->nullable()->references('id')->on('profiles');
                $table->text('action');
                $table->text('field_name')->nullable();
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('user_id')->nullable()->references('id')->on('profiles')->cascadeOnDelete();
                $table->uuid('task_id')->nullable()->references('id')->on('tasks')->nullOnDelete();
                $table->text('title');
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('auth_users')) {
            Schema::create('auth_users', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('email')->unique();
                $table->text('password_hash');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('hospital_contacts')) {
            Schema::create('hospital_contacts', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('hospital_name');
                $table->uuid('island_id')->nullable()->references('id')->on('islands');
                $table->text('manager_name');
                $table->text('contact_number');
                $table->text('contact_designation')->nullable();
                $table->text('notes')->nullable();
                $table->text('status')->default('active');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('call_logs')) {
            Schema::create('call_logs', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('task_id')->nullable()->references('id')->on('tasks')->cascadeOnDelete();
                $table->uuid('user_id')->nullable()->references('id')->on('profiles');
                $table->text('contact_name');
                $table->text('contact_phone')->nullable();
                $table->text('notes')->nullable();
                $table->text('call_date');
                $table->text('attachment_url')->nullable();
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('scheduled_reports')) {
            Schema::create('scheduled_reports', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('user_id')->nullable()->references('id')->on('profiles');
                $table->text('name');
                $table->jsonb('recipients')->nullable();
                $table->text('frequency');
                $table->integer('day_of_week')->nullable();
                $table->integer('day_of_month')->nullable();
                $table->text('time_of_day');
                $table->jsonb('filters')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('last_sent_at')->nullable();
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('hospital_profiles')) {
            Schema::create('hospital_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->foreignUuid('hospital_contact_id')->unique()->nullable()->constrained('hospital_contacts')->cascadeOnDelete();
                $table->uuid('island_id')->nullable()->references('id')->on('islands')->cascadeOnDelete();
                $table->integer('no_of_beds')->default(0);
                $table->text('grade')->nullable();
                $table->integer('population')->default(0);
                $table->integer('avg_outpatient_per_day')->default(0);
                $table->integer('avg_inpatient_per_month')->default(0);
                $table->integer('staff_physiotherapy')->default(0);
                $table->integer('staff_dermatology')->default(0);
                $table->integer('staff_ortho')->default(0);
                $table->integer('staff_medicine')->default(0);
                $table->integer('staff_surgeon')->default(0);
                $table->integer('staff_gynaecology')->default(0);
                $table->integer('staff_paediatrician')->default(0);
                $table->integer('staff_ent')->default(0);
                $table->integer('staff_dental')->default(0);
                $table->integer('staff_ophthalmology')->default(0);
                $table->integer('staff_psychology')->default(0);
                $table->integer('staff_radiology')->default(0);
                $table->integer('staff_anesthesiologist')->default(0);
                $table->integer('staff_medical_officer')->default(0);
                $table->integer('staff_psychiatrist')->default(0);
                $table->integer('nurses_clinical')->default(0);
                $table->integer('nurses_senior_registered')->default(0);
                $table->integer('nurses_registered')->default(0);
                $table->integer('nurses_enrolled')->default(0);
                $table->integer('admin_officers_senior')->default(0);
                $table->integer('admin_officers')->default(0);
                $table->integer('customer_service')->default(0);
                $table->integer('drivers')->default(0);
                $table->integer('lab_tech')->default(0);
                $table->integer('other_staffs')->default(0);
                $table->integer('ambulance_running_condition')->default(0);
                $table->integer('ambulance_total')->default(0);
                $table->boolean('lab_service_available')->default(false);
                $table->boolean('poct_available')->default(false);
                $table->boolean('launch_boat_service')->default(false);
                $table->boolean('operation_theatre_service')->default(false);
                $table->boolean('emergency_room_service')->default(false);
                $table->boolean('radiology_service')->default(false);
                $table->boolean('public_health_unit_service')->default(false);
                $table->text('medical_consumables_status')->nullable();
                $table->text('laboratory_reagents_status')->nullable();
                $table->text('life_saving_drugs_status')->nullable();
                $table->text('sto_pharmacy_status')->nullable();
                $table->boolean('sterilization_service')->default(false);
                $table->text('staff_status')->nullable();
                $table->text('building_status')->nullable();
                $table->text('project_information')->nullable();
                $table->text('other_information')->nullable();
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('permission_key')->unique();
                $table->text('permission_name');
                $table->text('permission_description')->nullable();
                $table->text('category');
                $table->boolean('admin_access')->default(false);
                $table->boolean('supervisor_access')->default(false);
                $table->boolean('coordinator_access')->default(false);
                $table->boolean('staff_access')->default(false);
                $table->boolean('user_access')->default(false);
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('important_contacts')) {
            Schema::create('important_contacts', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('name');
                $table->text('title');
                $table->text('organization')->nullable();
                $table->text('phone_primary');
                $table->text('phone_secondary')->nullable();
                $table->text('email')->nullable();
                $table->text('notes')->nullable();
                $table->integer('priority')->default(100);
                $table->text('status')->default('active');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('critical_staff_leaves')) {
            Schema::create('critical_staff_leaves', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('staff_name');
                $table->text('staff_id');
                $table->text('staff_category');
                $table->text('department_unit');
                $table->uuid('assigned_coordinator')->nullable()->references('id')->on('profiles');
                $table->uuid('direct_supervisor')->nullable()->references('id')->on('profiles');
                $table->text('leave_type');
                $table->date('leave_start_date');
                $table->date('leave_end_date');
                $table->integer('number_of_leave_days')->default(1);
                $table->text('shift_affected')->nullable();
                $table->text('reason_for_leave')->nullable();
                $table->text('contact_during_leave')->nullable();
                $table->text('replacement_staff')->nullable();
                $table->text('handover_notes')->nullable();
                $table->text('critical_level')->default('low');
                $table->text('urgency')->default('normal');
                $table->text('approval_status')->default('submitted');
                $table->uuid('reviewed_by')->nullable()->references('id')->on('profiles');
                $table->text('remarks')->nullable();
                $table->uuid('created_by')->nullable()->references('id')->on('profiles');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        if (! Schema::hasTable('critical_staff_availability_setup')) {
            Schema::create('critical_staff_availability_setup', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('department_unit');
                $table->text('staff_category');
                $table->text('shift');
                $table->integer('total_active_staff')->default(0);
                $table->integer('required_minimum_staff')->default(0);
                $table->uuid('coordinator_responsible')->nullable()->references('id')->on('profiles');
                $table->text('status')->default('active');
                $table->timestampTz('created_at')->default(DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            });
        }

        $indexes = [
            'idx_profiles_department_id' => 'CREATE INDEX IF NOT EXISTS idx_profiles_department_id ON profiles(department_id)',
            'idx_profiles_user_department_id' => 'CREATE INDEX IF NOT EXISTS idx_profiles_user_department_id ON profiles(user_department_id)',
            'idx_profiles_manager_id' => 'CREATE INDEX IF NOT EXISTS idx_profiles_manager_id ON profiles(manager_id)',
            'idx_atolls_coordinator_id' => 'CREATE INDEX IF NOT EXISTS idx_atolls_coordinator_id ON atolls(coordinator_id)',
            'idx_atolls_supervisor_id' => 'CREATE INDEX IF NOT EXISTS idx_atolls_supervisor_id ON atolls(supervisor_id)',
            'idx_islands_atoll_id' => 'CREATE INDEX IF NOT EXISTS idx_islands_atoll_id ON islands(atoll_id)',
            'idx_islands_assigned_staff_id' => 'CREATE INDEX IF NOT EXISTS idx_islands_assigned_staff_id ON islands(assigned_staff_id)',
            'idx_tasks_assigned_by' => 'CREATE INDEX IF NOT EXISTS idx_tasks_assigned_by ON tasks(assigned_by)',
            'idx_tasks_assigned_to' => 'CREATE INDEX IF NOT EXISTS idx_tasks_assigned_to ON tasks(assigned_to)',
            'idx_tasks_department_id' => 'CREATE INDEX IF NOT EXISTS idx_tasks_department_id ON tasks(department_id)',
            'idx_tasks_island_id' => 'CREATE INDEX IF NOT EXISTS idx_tasks_island_id ON tasks(island_id)',
            'idx_tasks_status' => 'CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status)',
            'idx_task_comments_task_id' => 'CREATE INDEX IF NOT EXISTS idx_task_comments_task_id ON task_comments(task_id)',
            'idx_task_activities_task_id' => 'CREATE INDEX IF NOT EXISTS idx_task_activities_task_id ON task_activities(task_id)',
            'idx_notifications_user_id' => 'CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)',
            'idx_hospital_contacts_island_id' => 'CREATE INDEX IF NOT EXISTS idx_hospital_contacts_island_id ON hospital_contacts(island_id)',
            'idx_call_logs_task_id' => 'CREATE INDEX IF NOT EXISTS idx_call_logs_task_id ON call_logs(task_id)',
            'idx_scheduled_reports_user_id' => 'CREATE INDEX IF NOT EXISTS idx_scheduled_reports_user_id ON scheduled_reports(user_id)',
            'idx_hospital_profiles_island_id' => 'CREATE INDEX IF NOT EXISTS idx_hospital_profiles_island_id ON hospital_profiles(island_id)',
            'idx_important_contacts_status_priority' => 'CREATE INDEX IF NOT EXISTS idx_important_contacts_status_priority ON important_contacts(status, priority, name)',
            'idx_critical_staff_leaves_dates' => 'CREATE INDEX IF NOT EXISTS idx_critical_staff_leaves_dates ON critical_staff_leaves(leave_start_date, leave_end_date, approval_status)',
            'idx_critical_staff_leaves_department' => 'CREATE INDEX IF NOT EXISTS idx_critical_staff_leaves_department ON critical_staff_leaves(department_unit, staff_category)',
            'idx_critical_staff_availability_scope' => 'CREATE INDEX IF NOT EXISTS idx_critical_staff_availability_scope ON critical_staff_availability_setup(department_unit, staff_category, shift, status)',
            'idx_cs_leave_scope' => 'CREATE INDEX IF NOT EXISTS idx_cs_leave_scope ON critical_staff_leaves(department_unit, staff_category, approval_status)',
        ];

        foreach ($indexes as $name => $sql) {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        // Intentionally not dropping existing tables to protect data.
    }
};
