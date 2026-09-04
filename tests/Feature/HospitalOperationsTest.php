<?php

namespace Tests\Feature;

use App\Models\Atoll;
use App\Models\AuthUser;
use App\Models\EmergencyIncident;
use App\Models\HospitalContact;
use App\Models\HospitalProfile;
use App\Models\Island;
use App\Models\Profile;
use App\Models\PushSubscription;
use App\Models\Task;
use App\Models\EquipmentFault;
use App\Models\TransportAsset;
use App\Models\UserRole;
use Illuminate\Support\Str;
use Tests\TestCase;

class HospitalOperationsTest extends TestCase
{
    private function user(string $role): AuthUser
    {
        $user=AuthUser::create(['email'=>$role.'-'.Str::uuid().'@example.com','password_hash'=>bcrypt('Password123')]);
        Profile::create(['id'=>$user->id,'email'=>$user->email,'first_name'=>ucfirst($role),'last_name'=>'Operator','status'=>'active']);
        UserRole::create(['user_id'=>$user->id,'role'=>$role]);
        return $user;
    }

    private function hospital(Island $island,string $name): HospitalProfile
    {
        $contact=HospitalContact::create(['hospital_name'=>$name,'island_id'=>$island->id,'manager_name'=>'Manager','contact_number'=>'7000000','status'=>'active']);
        return HospitalProfile::create(['hospital_contact_id'=>$contact->id,'island_id'=>$island->id]);
    }

    public function test_emergency_creates_scoped_incident_without_automatic_task(): void
    {
        $staff=$this->user('staff'); $coordinator=$this->user('coordinator'); $supervisor=$this->user('supervisor');
        $atoll=Atoll::first(); $atoll->update(['coordinator_id'=>$coordinator->id,'supervisor_id'=>$supervisor->id]);
        $island=Island::where('atoll_id',$atoll->id)->first(); $island->update(['assigned_staff_id'=>$staff->id]);
        $hospital=$this->hospital($island,'Emergency Hospital');

        $response=$this->actingAs($staff)->postJson('/api/operations/incidents',['hospital_profile_id'=>$hospital->id,'title'=>'Oxygen system failure','description'=>'Central oxygen supply is unavailable.','severity'=>'critical']);

        $response->assertCreated()->assertJsonPath('hospital_profile_id',$hospital->id)->assertJsonPath('severity','critical');
        $incident=EmergencyIncident::firstOrFail();
        $this->assertNull($incident->task_id);
    }

    public function test_staff_cannot_write_operations_for_another_island(): void
    {
        $staff=$this->user('staff'); $islands=Island::take(2)->get();
        if($islands->count()<2)$this->markTestSkipped('Two islands required');
        $islands[0]->update(['assigned_staff_id'=>$staff->id]); $hospital=$this->hospital($islands[1],'Other Hospital');
        $this->actingAs($staff)->postJson('/api/operations/transport',['hospital_profile_id'=>$hospital->id,'type'=>'ambulance','name'=>'A-1','status'=>'operational'])->assertForbidden();
    }

    public function test_operations_page_and_executive_pdf_are_available(): void
    {
        $admin=$this->user('admin'); $island=Island::first(); $this->hospital($island,'Report Hospital');
        $this->actingAs($admin)->get('/hospital-operations')->assertOk()->assertSee('Hospital Operations');
        $this->actingAs($admin)->get('/hospital-operations/executive-report.pdf')->assertOk()->assertHeader('content-type','application/pdf');
    }

    public function test_authenticated_user_can_register_a_push_device(): void
    {
        $staff=$this->user('staff');
        $endpoint='https://push.example.test/subscriptions/'.Str::uuid();

        $this->actingAs($staff)->postJson('/api/push-subscriptions',[
            'endpoint'=>$endpoint,
            'keys'=>['p256dh'=>str_repeat('p',64),'auth'=>str_repeat('a',24)],
        ])->assertCreated()->assertJsonPath('user_id',$staff->id);

        $this->assertSame(hash('sha256',$endpoint),PushSubscription::firstOrFail()->endpoint_hash);
    }

    public function test_high_risk_record_uses_optional_linked_task_without_status_sync(): void
    {
        $admin=$this->user('admin'); $island=Island::first(); $hospital=$this->hospital($island,'Linked Work Hospital');
        $this->actingAs($admin)->postJson('/api/operations/faults',['hospital_profile_id'=>$hospital->id,'equipment_name'=>'Ventilator','severity'=>'critical','description'=>'Fails startup self test'])->assertCreated();
        $fault=EquipmentFault::firstOrFail();
        $this->assertNull($fault->task_id);

        $this->actingAs($admin)->postJson('/api/operations/action-task',['type'=>'fault','id'=>$fault->id])->assertCreated()->assertJsonPath('already_existed',false);
        $fault->refresh();
        $this->assertDatabaseHas('tasks',['id'=>$fault->task_id,'priority'=>'urgent']);
        $this->actingAs($admin)->postJson('/api/operations/action-task',['type'=>'fault','id'=>$fault->id])->assertOk()->assertJsonPath('already_existed',true);
        $this->assertSame(1,Task::whereKey($fault->task_id)->count());

        $this->actingAs($admin)->patchJson('/api/tasks/'.$fault->task_id,['status'=>'completed'])->assertOk();
        $this->assertSame('reported',$fault->fresh()->status);
    }

    public function test_unavailable_fleet_record_can_be_promoted_to_action_task(): void
    {
        $admin=$this->user('admin'); $island=Island::first(); $hospital=$this->hospital($island,'Fleet Hospital');
        $asset=TransportAsset::create(['hospital_profile_id'=>$hospital->id,'type'=>'ambulance','name'=>'AMB-1','status'=>'unavailable','updated_by'=>$admin->id]);
        $this->actingAs($admin)->postJson('/api/operations/action-task',['type'=>'transport','id'=>$asset->id])->assertCreated()->assertJsonPath('already_existed',false);
        $this->assertNotNull($asset->fresh()->task_id);
    }
}
