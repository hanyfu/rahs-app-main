<?php

namespace Tests\Feature;

use App\Models\Atoll;
use App\Models\AuthUser;
use App\Models\CriticalStaffAvailabilitySetup;
use App\Models\CriticalStaffLeave;
use App\Models\HospitalContact;
use App\Models\HospitalProfile;
use App\Models\Island;
use App\Models\Profile;
use App\Models\UserRole;
use App\Services\LeaveService;
use Illuminate\Support\Str;
use Tests\TestCase;

class CriticalStaffLeaveWorkflowTest extends TestCase
{
    private function makeUser(string $role): AuthUser
    {
        $user = AuthUser::create([
            'email' => $role.'-'.Str::uuid().'@example.com',
            'password_hash' => bcrypt('password123'),
        ]);
        Profile::create([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'status' => 'active',
        ]);
        UserRole::create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    private function hospitalFor(Island $island, string $name, int $nurses = 2): HospitalProfile
    {
        $contact = HospitalContact::create([
            'hospital_name' => $name,
            'island_id' => $island->id,
            'manager_name' => 'Manager',
            'contact_number' => '7000000',
            'status' => 'active',
        ]);

        return HospitalProfile::create([
            'hospital_contact_id' => $contact->id,
            'island_id' => $island->id,
            'nurses_clinical' => $nurses,
        ]);
    }

    public function test_island_assignee_creates_hospital_linked_leave_with_automatic_officers(): void
    {
        $staff = $this->makeUser('staff');
        $coordinator = $this->makeUser('coordinator');
        $supervisor = $this->makeUser('supervisor');
        $atoll = Atoll::first();
        $atoll->update(['coordinator_id' => $coordinator->id, 'supervisor_id' => $supervisor->id]);
        $island = Island::where('atoll_id', $atoll->id)->first();
        $island->update(['assigned_staff_id' => $staff->id]);
        $hospital = $this->hospitalFor($island, 'Assigned Hospital');

        $this->actingAs($staff)->postJson('/api/leaves', [
            'hospital_profile_id' => $hospital->id,
            'staff_name' => 'Aminath Shareefa',
            'staff_category' => 'Clinical Nurses',
            'leave_type' => 'annual',
            'leave_start_date' => '2026-09-10',
            'leave_end_date' => '2026-09-12',
            'shift_affected' => 'All shifts',
        ])->assertCreated()
            ->assertJsonPath('hospital_profile_id', $hospital->id)
            ->assertJsonPath('island_id', $island->id)
            ->assertJsonPath('assigned_coordinator', $coordinator->id)
            ->assertJsonPath('direct_supervisor', $supervisor->id)
            ->assertJsonPath('number_of_leave_days', 3);
    }

    public function test_assignee_cannot_create_leave_for_another_hospital(): void
    {
        $staff = $this->makeUser('staff');
        $atoll = Atoll::first();
        $islands = Island::where('atoll_id', $atoll->id)->take(2)->get();
        if ($islands->count() < 2) {
            $this->markTestSkipped('Seeder needs two islands in an atoll.');
        }
        $islands[0]->update(['assigned_staff_id' => $staff->id]);
        $hospital = $this->hospitalFor($islands[1], 'Other Hospital');

        $this->actingAs($staff)->postJson('/api/leaves', [
            'hospital_profile_id' => $hospital->id,
            'staff_name' => 'Other Person',
            'staff_category' => 'Clinical Nurses',
            'leave_type' => 'annual',
            'leave_start_date' => '2026-09-10',
            'leave_end_date' => '2026-09-10',
        ])->assertForbidden();
    }

    public function test_shortage_uses_hospital_profile_staff_total_and_overlapping_leave(): void
    {
        $staff = $this->makeUser('staff');
        $atoll = Atoll::first();
        $island = Island::where('atoll_id', $atoll->id)->first();
        $island->update(['assigned_staff_id' => $staff->id]);
        $hospital = $this->hospitalFor($island, 'Risk Hospital', 2);
        CriticalStaffAvailabilitySetup::create([
            'hospital_profile_id' => $hospital->id,
            'department_unit' => 'Risk Hospital',
            'staff_category' => 'Clinical Nurses',
            'shift' => 'All shifts',
            'total_active_staff' => 2,
            'required_minimum_staff' => 2,
            'status' => 'active',
        ]);
        $leave = CriticalStaffLeave::create([
            'hospital_profile_id' => $hospital->id,
            'island_id' => $island->id,
            'staff_name' => 'Critical Nurse',
            'staff_id' => 'N-1',
            'staff_category' => 'Clinical Nurses',
            'department_unit' => 'Risk Hospital',
            'leave_type' => 'annual',
            'leave_start_date' => '2026-09-10',
            'leave_end_date' => '2026-09-12',
            'number_of_leave_days' => 3,
            'shift_affected' => 'All shifts',
            'approval_status' => 'submitted',
            'created_by' => $staff->id,
        ]);

        $risk = (new LeaveService)->checkLeaveShortageRisk($leave);

        $this->assertTrue($risk['shortage']);
        $this->assertSame(2, $risk['total']);
        $this->assertSame(1, $risk['onLeave']);
        $this->assertSame(1, $risk['remaining']);
    }
}
