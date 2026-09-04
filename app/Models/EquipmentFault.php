<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EquipmentFault extends Model { use HasUuids; protected $fillable=['hospital_profile_id','task_id','equipment_name','asset_tag','category','severity','description','photo_url','status','expected_return_date','repair_notes','maintenance_history','assigned_to','created_by']; protected $casts=['expected_return_date'=>'date','maintenance_history'=>'array']; public function hospitalProfile(): BelongsTo{return $this->belongsTo(HospitalProfile::class);} public function assignee(): BelongsTo{return $this->belongsTo(Profile::class,'assigned_to');} public function task(): BelongsTo{return $this->belongsTo(Task::class);} }
