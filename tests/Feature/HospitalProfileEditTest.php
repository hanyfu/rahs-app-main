<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use App\Models\Atoll;
use App\Models\HospitalContact;
use App\Models\Island;
use App\Models\Profile;
use App\Models\RolePermission;
use App\Models\UserRole;
use Illuminate\Support\Str;
use Tests\TestCase;

class HospitalProfileEditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RolePermission::where('permission_key', 'edit_hospital_profiles')->update([
            'supervisor_access' => true,
            'coordinator_access' => true,
            'staff_access' => true,
        ]);
    }

    private function makeStaffUser(): AuthUser
    {
        $user = AuthUser::create([
            'email' => 'staff-'.Str::uuid().'@example.com',
            'password_hash' => bcrypt('password123'),
        ]);
        $id = $user->id;

        Profile::create([
            'id' => $id,
            'email' => $user->email,
            'first_name' => 'Staff',
            'last_name' => 'User',
            'designation' => 'Test',
        ]);
        UserRole::create(['user_id' => $id, 'role' => 'staff']);

        return $user;
    }

    public function test_staff_without_assignment_cannot_save_a_profile(): void
    {
        $staff = $this->makeStaffUser();

        $this->actingAs($staff)
            ->postJson('/api/hospital-profiles', [
                'hospital_contact_id' => null,
                'no_of_beds' => 12,
                'grade' => 'B',
                'population' => 1500,
            ])->assertStatus(422)
            ->assertJsonValidationErrors('island_id');

        $this->assertDatabaseMissing('hospital_profiles', [
            'no_of_beds' => 12,
        ]);
    }

    public function test_staff_with_assignment_can_save_island_profile(): void
    {
        $staff = $this->makeStaffUser();
        $island = Island::query()->whereNull('assigned_staff_id')->first() ?? Island::first();
        $island->update(['assigned_staff_id' => $staff->id, 'status' => 'active']);

        $this->actingAs($staff)
            ->postJson('/api/hospital-profiles', [
                'hospital_contact_id' => null,
                'no_of_beds' => 8,
                'population' => 900,
            ])->assertStatus(201);

        $this->assertDatabaseHas('hospital_profiles', [
            'hospital_contact_id' => null,
            'island_id' => $island->id,
            'no_of_beds' => 8,
        ]);
    }

    public function test_staff_cannot_save_profile_for_another_island(): void
    {
        $staff = $this->makeStaffUser();
        $island = Island::first();
        $island->update(['assigned_staff_id' => $staff->id, 'status' => 'active']);

        $other = Island::where('id', '!=', $island->id)->first();
        $contact = HospitalContact::create([
            'hospital_name' => 'Other Atoll Hospital',
            'island_id' => $other->id,
            'manager_name' => 'Manager',
            'contact_number' => '1234567',
            'status' => 'active',
        ]);

        $this->actingAs($staff)
            ->postJson('/api/hospital-profiles', [
                'hospital_contact_id' => $contact->id,
                'no_of_beds' => 5,
            ])->assertStatus(422);

        $this->assertDatabaseMissing('hospital_profiles', ['hospital_contact_id' => $contact->id]);
    }

    public function test_non_staff_requires_contact_for_profile_save(): void
    {
        $supervisor = AuthUser::where('email', 'supervisor@rahs.mv')->first();

        $this->actingAs($supervisor)
            ->postJson('/api/hospital-profiles', [
                'hospital_contact_id' => null,
                'no_of_beds' => 3,
            ])->assertStatus(422);

        $this->assertDatabaseMissing('hospital_profiles', ['no_of_beds' => 3]);
    }

    public function test_supervisor_can_only_edit_hospitals_in_assigned_atolls(): void
    {
        $supervisor = AuthUser::where('email', 'supervisor@rahs.mv')->firstOrFail();
        $assignedAtoll = Atoll::query()->firstOrFail();
        $otherAtoll = Atoll::query()->whereKeyNot($assignedAtoll->id)->firstOrFail();
        $assignedAtoll->update(['supervisor_id' => $supervisor->id]);
        $otherAtoll->update(['supervisor_id' => null]);

        $assignedIsland = Island::query()->where('atoll_id', $assignedAtoll->id)->firstOrFail();
        $otherIsland = Island::query()->where('atoll_id', $otherAtoll->id)->firstOrFail();

        $this->actingAs($supervisor)
            ->postJson('/api/hospital-profiles', [
                'hospital_contact_id' => $assignedIsland->id,
                'no_of_beds' => 21,
            ])->assertCreated();

        $this->actingAs($supervisor)
            ->postJson('/api/hospital-profiles', [
                'hospital_contact_id' => $otherIsland->id,
                'no_of_beds' => 22,
            ])->assertStatus(422)
            ->assertJsonValidationErrors('island_id');

        $this->assertDatabaseHas('hospital_profiles', ['island_id' => $assignedIsland->id, 'no_of_beds' => 21]);
        $this->assertDatabaseMissing('hospital_profiles', ['island_id' => $otherIsland->id, 'no_of_beds' => 22]);
    }

    public function test_staff_dashboard_renders_profile_without_assignment(): void
    {
        $staff = $this->makeStaffUser();

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Hospital Profile')
            ->assertDontSee('Edit profile');
    }

    public function test_island_without_contact_appears_in_directory_and_syncs_dashboard_edits(): void
    {
        $staff = $this->makeStaffUser();
        $dhiddhoo = Island::where('name', 'Dhiddhoo')->firstOrFail();
        $dhiddhoo->update(['assigned_staff_id' => $staff->id, 'status' => 'active']);

        // Staff updates their island profile from the dashboard (no contact linked).
        $this->actingAs($staff)
            ->postJson('/api/hospital-profiles', [
                'hospital_contact_id' => null,
                'no_of_beds' => 42,
                'population' => 3000,
                'grade' => 'A',
            ])->assertStatus(201);

        // The island facility (with no contact) is listed in the directory.
        $this->actingAs($staff)
            ->get('/hospitals')
            ->assertOk()
            ->assertSee('Dhiddhoo Health Facility');

        // Opening the island facility shows the dashboard-updated profile.
        $this->actingAs($staff)
            ->getJson('/api/hospital-profiles/'.$dhiddhoo->id)
            ->assertOk()
            ->assertJsonPath('island_id', $dhiddhoo->id)
            ->assertJsonPath('no_of_beds', 42)
            ->assertJsonPath('population', 3000)
            ->assertJsonPath('grade', 'A');
    }

    public function test_dashboard_profile_syncs_to_contact_linked_profile(): void
    {
        $staff = $this->makeStaffUser();
        $maleCity = Island::where('name', 'Male City')->firstOrFail();
        $maleCity->update(['assigned_staff_id' => $staff->id, 'status' => 'active']);
        $contact = HospitalContact::where('island_id', $maleCity->id)->firstOrFail();

        // Staff edits their hospital details on the dashboard (contact-linked).
        $this->actingAs($staff)
            ->postJson('/api/hospital-profiles', [
                'hospital_contact_id' => $contact->id,
                'no_of_beds' => 120,
                'staff_medicine' => 8,
            ])->assertStatus(201);

        // Others opening the same facility from the directory see the updates.
        $this->actingAs($staff)
            ->getJson('/api/hospital-profiles/'.$contact->id)
            ->assertOk()
            ->assertJsonPath('hospital_contact_id', $contact->id)
            ->assertJsonPath('no_of_beds', 120)
            ->assertJsonPath('staff_medicine', 8);
    }

    public function test_profile_save_rejects_invalid_numeric_values(): void
    {
        $staff = $this->makeStaffUser();
        $maleCity = Island::where('name', 'Male City')->firstOrFail();
        $maleCity->update(['assigned_staff_id' => $staff->id, 'status' => 'active']);
        $contact = HospitalContact::where('island_id', $maleCity->id)->firstOrFail();

        $this->actingAs($staff)
            ->postJson('/api/hospital-profiles', [
                'hospital_contact_id' => $contact->id,
                'no_of_beds' => -5,
                'population' => 999999999999,
            ])->assertStatus(422);

        $this->assertDatabaseMissing('hospital_profiles', ['no_of_beds' => -5]);
    }

    public function test_admin_directory_shows_coverage_and_profile_preview(): void
    {
        $admin = $this->makeStaffUser();
        UserRole::where('user_id', $admin->id)->update(['role' => 'admin']);

        $staff = $this->makeStaffUser();
        $dhiddhoo = Island::where('name', 'Dhiddhoo')->firstOrFail();
        $dhiddhoo->update(['assigned_staff_id' => $staff->id, 'status' => 'active']);

        // A fresh profile counts toward coverage and shows a table preview.
        $this->actingAs($staff)
            ->postJson('/api/hospital-profiles', [
                'hospital_contact_id' => null,
                'no_of_beds' => 42,
                'grade' => 'b',
                'population' => 3000,
            ])->assertStatus(201);

        $this->actingAs($admin)
            ->get('/hospitals')
            ->assertOk()
            ->assertSee('Hospital profile coverage')
            ->assertSee('Dhiddhoo Health Facility')
            ->assertSee('"beds":42', false)
            ->assertSee('"grade":"B"', false)
            ->assertSee('"population":3000', false);
    }

    public function test_hospital_directory_component_payload_is_not_truncated(): void
    {
        $admin = $this->makeStaffUser();
        UserRole::where('user_id', $admin->id)->update(['role' => 'admin']);

        $content = $this->actingAs($admin)->get('/hospitals')->assertOk()->getContent();

        $document = new \DOMDocument();
        @$document->loadHTML($content);
        $nodes = (new \DOMXPath($document))->query('//*[@x-data and starts-with(@x-data, "hospitalsPage(")]');

        $this->assertCount(1, $nodes);
        $expression = $nodes->item(0)->getAttribute('x-data');
        $this->assertStringContainsString('role: "admin"', $expression);
        $this->assertStringContainsString('canManageHospitals: true', $expression);
        $this->assertStringContainsString('canEditProfiles: true', $expression);
        $this->assertStringEndsWith('})', $expression);
    }

    public function test_contacts_export_includes_island_facilities(): void
    {
        $admin = $this->makeStaffUser();
        UserRole::where('user_id', $admin->id)->update(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/api/reports/export/hospital-contacts')
            ->assertOk()
            ->assertSee('Dhiddhoo Health Facility');
    }

    public function test_admin_can_bulk_import_hospital_contacts(): void
    {
        $admin = $this->makeStaffUser();
        UserRole::where('user_id', $admin->id)->update(['role' => 'admin']);
        $island = Island::firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/hospital-contacts/import', [
                'contacts' => [
                    [
                        'hospital_name' => 'CSV Regional Hospital',
                        'island_id' => $island->id,
                        'manager_name' => 'Aminath Example',
                        'contact_number' => '7000001',
                        'contact_designation' => 'Senior Administrator, Health Services',
                    ],
                    [
                        'hospital_name' => 'CSV Health Centre',
                        'island_id' => null,
                        'manager_name' => 'Ahmed Example',
                        'contact_number' => '7000002',
                        'contact_designation' => 'Administrator',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'imported' => 2]);

        $this->assertDatabaseHas('hospital_contacts', [
            'hospital_name' => 'CSV Regional Hospital',
            'island_id' => $island->id,
            'contact_designation' => 'Senior Administrator, Health Services',
        ]);
        $this->assertDatabaseHas('hospital_contacts', [
            'hospital_name' => 'CSV Health Centre',
            'island_id' => null,
        ]);
    }
}
