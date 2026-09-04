<?php

namespace App\Services;

use App\Models\EmergencyIncident;
use App\Models\EquipmentFault;
use App\Models\TrackedExpiryItem;
use App\Models\TransportAsset;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

class OperationsTaskService
{
    public function createFor(Model $record): Task
    {
        if ($record->task_id && ($existing = Task::withTrashed()->find($record->task_id))) return $existing;
        $hospital = $record->hospitalProfile()->with(['hospitalContact', 'island.atoll'])->firstOrFail();
        $island = $hospital->island;
        [$title, $description, $priority, $type, $dueDate] = $this->details($record);
        $task = Task::create(['title'=>$title,'creator_description'=>$description,'status'=>'pending','priority'=>$priority,'assigned_by'=>auth()->id() ?: $record->created_by ?: $record->updated_by,'assigned_to'=>$island?->atoll?->coordinator_id ?: $island?->assigned_staff_id,'island_id'=>$island?->id,'due_date'=>$dueDate,'task_types'=>['Hospital Operations',$type]]);
        $record->update(['task_id'=>$task->id]);
        return $task;
    }

    private function details(Model $record): array
    {
        $hospital=$record->hospitalProfile?->hospitalContact?->hospital_name??'Hospital';
        return match(true){
            $record instanceof EmergencyIncident=>["[EMERGENCY] {$record->title}",$record->description,'urgent','Emergency escalation',today()],
            $record instanceof EquipmentFault=>["Repair: {$record->equipment_name}","{$hospital}: {$record->description}",$record->severity==='critical'?'urgent':'high','Equipment fault',$record->expected_return_date?:today()->addDays(3)],
            $record instanceof TransportAsset=>["Restore {$record->type}: {$record->name}","{$hospital}: ".($record->unavailable_reason?:'Return this asset to operational service.'),'high','Fleet availability',$record->expected_return_date?:today()->addDays(3)],
            $record instanceof TrackedExpiryItem=>["Renew/replace: {$record->name}","{$hospital}: {$record->item_type} reference {$record->reference_number} requires action.",$record->expiry_date->isPast()?'urgent':'high','Compliance deadline',$record->expiry_date],
        };
    }
}
