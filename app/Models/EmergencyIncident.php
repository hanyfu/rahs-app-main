<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyIncident extends Model { use HasUuids; protected $fillable=['hospital_profile_id','island_id','task_id','title','description','severity','status','attachment_url','acknowledged_at','resolved_at','created_by']; protected $casts=['acknowledged_at'=>'datetime','resolved_at'=>'datetime']; public function hospitalProfile(): BelongsTo{return $this->belongsTo(HospitalProfile::class);} public function task(): BelongsTo{return $this->belongsTo(Task::class);} public function creator(): BelongsTo{return $this->belongsTo(Profile::class,'created_by');} }
