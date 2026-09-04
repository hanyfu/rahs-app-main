<?php

namespace App\Http\Controllers;

use App\Models\EmergencyIncident;
use App\Models\EquipmentFault;
use App\Models\HospitalDocument;
use App\Models\HospitalProfile;
use App\Models\TrackedExpiryItem;
use App\Models\TransportAsset;
use App\Services\LeaveService;
use App\Services\NotificationService;
use App\Services\OperationsTaskService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    public function __construct(private readonly LeaveService $leaveService, private readonly NotificationService $notifications, private readonly OperationsTaskService $operationTasks) {}

    public function index()
    {
        $hospitals = $this->accessibleHospitals();
        $ids = $hospitals->pluck('id');
        $payload = $this->payload($ids);

        return view('operations.index', [...$payload, 'hospitals' => $hospitals, 'readiness' => $this->readiness($hospitals, $payload)]);
    }

    public function data()
    {
        $hospitals = $this->accessibleHospitals();
        $payload = $this->payload($hospitals->pluck('id'));

        return response()->json([...$payload, 'hospitals' => $hospitals, 'readiness' => $this->readiness($hospitals, $payload)]);
    }

    public function storeIncident(Request $request)
    {
        $data = $request->validate([
            'hospital_profile_id' => ['required', 'uuid', 'exists:hospital_profiles,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:5000'],
            'severity' => ['required', Rule::in(['high', 'critical'])],
            'attachment_url' => ['nullable', 'string', 'max:500'],
        ]);
        $hospital = $this->hospital($data['hospital_profile_id']);
        $island = $hospital->island;

        $incident = EmergencyIncident::create([...$data, 'island_id' => $island?->id, 'status' => 'active', 'created_by' => auth()->id()]);

        $recipients = array_filter([$island?->assigned_staff_id, $island?->atoll?->coordinator_id, $island?->atoll?->supervisor_id]);
        $name = $hospital->hospitalContact?->hospital_name ?? $island?->name ?? 'Hospital';
        $this->notifications->notifyUsers($recipients, 'Emergency escalation: '.$data['severity'], "{$name}: {$data['title']}");

        return response()->json($incident->load($this->incidentRelations()), 201);
    }

    public function updateIncident(Request $request, string $id)
    {
        $incident = EmergencyIncident::findOrFail($id);
        $this->authorizeRecord($incident);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'acknowledged', 'resolved', 'cancelled'])], 'description' => ['nullable', 'string', 'max:5000']]);
        if ($data['status'] === 'acknowledged') $data['acknowledged_at'] = now();
        if ($data['status'] === 'resolved') $data['resolved_at'] = now();
        $incident->update($data);
        return response()->json($incident->fresh()->load($this->incidentRelations()));
    }

    public function storeFault(Request $request)
    {
        $data = $request->validate([
            'hospital_profile_id' => ['required', 'uuid', 'exists:hospital_profiles,id'], 'equipment_name' => ['required', 'string', 'max:180'],
            'asset_tag' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:100'],
            'severity' => ['required', Rule::in(['low','medium','high','critical'])], 'description' => ['required', 'string', 'max:5000'],
            'photo_url' => ['nullable', 'string', 'max:500'], 'assigned_to' => ['nullable', 'uuid', 'exists:profiles,id'],
            'expected_return_date' => ['nullable', 'date'],
        ]);
        $hospital = $this->hospital($data['hospital_profile_id']);
        $fault = EquipmentFault::create([...$data, 'status' => 'reported', 'created_by' => auth()->id()]);
        if (in_array($data['severity'], ['high','critical'], true)) {
            $island = $hospital->island;
            $this->notifications->notifyUsers(array_filter([$island?->assigned_staff_id,$island?->atoll?->coordinator_id,$island?->atoll?->supervisor_id]), 'Critical equipment fault', "{$data['equipment_name']}: {$data['description']}");
        }
        return response()->json($fault->load('hospitalProfile.hospitalContact'), 201);
    }

    public function updateFault(Request $request, string $id)
    {
        $fault = EquipmentFault::findOrFail($id); $this->authorizeRecord($fault);
        $data = $request->validate(['status'=>['nullable',Rule::in(['reported','assessing','repairing','operational','retired'])],'repair_notes'=>['nullable','string','max:5000'],'expected_return_date'=>['nullable','date'],'assigned_to'=>['nullable','uuid','exists:profiles,id']]);
        $history = $fault->maintenance_history ?? [];
        $history[] = ['at'=>now()->toIso8601String(),'by'=>auth()->id(),'status'=>$data['status'] ?? $fault->status,'notes'=>$data['repair_notes'] ?? null];
        $fault->update([...$data,'maintenance_history'=>$history]);
        return response()->json($fault->fresh()->load(['hospitalProfile.hospitalContact','task']));
    }

    public function storeTransport(Request $request)
    {
        $data = $request->validate($this->transportRules(true)); $this->hospital($data['hospital_profile_id']);
        return response()->json(TransportAsset::create([...$data,'updated_by'=>auth()->id()])->load('hospitalProfile.hospitalContact'), 201);
    }

    public function updateTransport(Request $request, string $id)
    {
        $asset=TransportAsset::findOrFail($id); $this->authorizeRecord($asset); $data=$request->validate($this->transportRules(false));
        $asset->update([...$data,'updated_by'=>auth()->id()]); return response()->json($asset->fresh()->load(['hospitalProfile.hospitalContact','task']));
    }

    public function storeExpiry(Request $request)
    {
        $data=$request->validate(['hospital_profile_id'=>['required','uuid','exists:hospital_profiles,id'],'item_type'=>['required',Rule::in(['medicine','reagent','licence','equipment_service','certification','other'])],'name'=>['required','string','max:180'],'reference_number'=>['nullable','string','max:120'],'expiry_date'=>['required','date'],'warning_days'=>['required','integer','min:1','max:365'],'quantity'=>['nullable','numeric','min:0'],'notes'=>['nullable','string','max:5000'],'document_url'=>['nullable','string','max:500']]);
        $this->hospital($data['hospital_profile_id']); $item=TrackedExpiryItem::create([...$data,'status'=>'active','created_by'=>auth()->id()]); return response()->json($item->fresh()->load(['hospitalProfile.hospitalContact','task']),201);
    }

    public function updateExpiry(Request $request,string $id)
    {
        $item=TrackedExpiryItem::findOrFail($id); $this->authorizeRecord($item); $data=$request->validate(['status'=>['required',Rule::in(['active','renewed','used','disposed','cancelled'])],'expiry_date'=>['nullable','date'],'notes'=>['nullable','string','max:5000']]); $item->update($data); return response()->json($item->fresh()->load(['hospitalProfile.hospitalContact','task']));
    }

    public function createActionTask(Request $request)
    {
        $data=$request->validate(['type'=>['required',Rule::in(['incident','fault','transport','expiry'])],'id'=>['required','uuid']]);
        $models=['incident'=>EmergencyIncident::class,'fault'=>EquipmentFault::class,'transport'=>TransportAsset::class,'expiry'=>TrackedExpiryItem::class];
        $record=$models[$data['type']]::findOrFail($data['id']); $this->authorizeRecord($record);
        $existing=(bool)$record->task_id; $task=$this->operationTasks->createFor($record);
        return response()->json(['task'=>$task,'already_existed'=>$existing],$existing?200:201);
    }

    public function storeDocument(Request $request)
    {
        $data=$request->validate(['hospital_profile_id'=>['required','uuid','exists:hospital_profiles,id'],'category'=>['required',Rule::in(['sop','inspection','licence','emergency_plan','policy','certificate','other'])],'title'=>['required','string','max:180'],'version'=>['nullable','string','max:50'],'issue_date'=>['nullable','date'],'expiry_date'=>['nullable','date','after_or_equal:issue_date'],'file_url'=>['required','string','max:500'],'notes'=>['nullable','string','max:5000']]);
        $this->hospital($data['hospital_profile_id']); return response()->json(HospitalDocument::create([...$data,'uploaded_by'=>auth()->id()])->load('hospitalProfile.hospitalContact'),201);
    }

    public function destroyDocument(string $id)
    {
        $document=HospitalDocument::findOrFail($id); $this->authorizeRecord($document); $document->delete(); return response()->json(['success'=>true]);
    }

    public function executivePdf()
    {
        $hospitals=$this->accessibleHospitals(); $payload=$this->payload($hospitals->pluck('id')); $readiness=$this->readiness($hospitals,$payload);
        return Pdf::loadView('operations.executive-pdf',[...$payload,'hospitals'=>$hospitals,'readiness'=>$readiness,'generatedAt'=>now()])->setPaper('a4','landscape')->download('RAHS-operations-report-'.now()->format('Y-m-d').'.pdf');
    }

    private function transportRules(bool $create): array
    {
        $required=$create?'required':'sometimes';
        return ['hospital_profile_id'=>[$required,'uuid','exists:hospital_profiles,id'],'type'=>[$required,Rule::in(['ambulance','launch'])],'name'=>[$required,'string','max:180'],'registration_number'=>['nullable','string','max:100'],'status'=>[$required,Rule::in(['operational','unavailable','maintenance'])],'unavailable_reason'=>['nullable','string','max:2000'],'expected_return_date'=>['nullable','date'],'last_service_date'=>['nullable','date'],'next_service_date'=>['nullable','date'],'notes'=>['nullable','string','max:5000']];
    }

    private function payload($ids): array
    {
        return [
            'incidents'=>EmergencyIncident::with($this->incidentRelations())->whereIn('hospital_profile_id',$ids)->latest()->get(),
            'faults'=>EquipmentFault::with(['hospitalProfile.hospitalContact','task'])->whereIn('hospital_profile_id',$ids)->latest()->get(),
            'transport'=>TransportAsset::with(['hospitalProfile.hospitalContact','task'])->whereIn('hospital_profile_id',$ids)->latest()->get(),
            'expiryItems'=>TrackedExpiryItem::with(['hospitalProfile.hospitalContact','task'])->whereIn('hospital_profile_id',$ids)->orderBy('expiry_date')->get(),
            'documents'=>HospitalDocument::with('hospitalProfile.hospitalContact')->whereIn('hospital_profile_id',$ids)->latest()->get(),
        ];
    }

    private function readiness($hospitals,array $payload)
    {
        return $hospitals->map(function($hospital) use($payload){
            $id=$hospital->id; $critical=collect($payload['incidents'])->where('hospital_profile_id',$id)->whereIn('status',['active','acknowledged'])->where('severity','critical')->count()+collect($payload['faults'])->where('hospital_profile_id',$id)->whereNotIn('status',['operational','retired'])->where('severity','critical')->count();
            $warnings=collect($payload['incidents'])->where('hospital_profile_id',$id)->whereIn('status',['active','acknowledged'])->count()+collect($payload['faults'])->where('hospital_profile_id',$id)->whereNotIn('status',['operational','retired'])->count()+collect($payload['transport'])->where('hospital_profile_id',$id)->where('status','!=','operational')->count()+collect($payload['expiryItems'])->where('hospital_profile_id',$id)->where('status','active')->filter(fn($item)=>$item->expiry_date->lte(now()->addDays($item->warning_days)))->count();
            return ['hospital_id'=>$id,'name'=>$hospital->hospitalContact?->hospital_name??$hospital->island?->name??'Hospital','island'=>$hospital->island?->name,'atoll'=>$hospital->island?->atoll?->name,'status'=>$critical?'red':($warnings?'amber':'green'),'critical'=>$critical,'warnings'=>$warnings];
        })->values();
    }

    private function accessibleHospitals()
    {
        $user=auth()->user(); return HospitalProfile::with(['hospitalContact:id,hospital_name','island.atoll'])->get()->filter(fn($h)=>$this->leaveService->userCanAccessHospital($user->id,$user->role,$h))->values();
    }
    private function hospital(string $id): HospitalProfile { $h=HospitalProfile::with(['hospitalContact','island.atoll'])->findOrFail($id); abort_unless($this->leaveService->userCanAccessHospital(auth()->id(),auth()->user()->role,$h),403); return $h; }
    private function authorizeRecord(Model $record): void { $this->hospital($record->hospital_profile_id); }
    private function incidentRelations(): array { return ['hospitalProfile.hospitalContact','task','creator:id,first_name,last_name']; }
}
