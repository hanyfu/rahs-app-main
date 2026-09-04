<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TransportAsset extends Model { use HasUuids; protected $fillable=['hospital_profile_id','task_id','type','name','registration_number','status','unavailable_reason','expected_return_date','last_service_date','next_service_date','notes','updated_by']; protected $casts=['expected_return_date'=>'date','last_service_date'=>'date','next_service_date'=>'date']; public function hospitalProfile(): BelongsTo{return $this->belongsTo(HospitalProfile::class);} public function task(): BelongsTo{return $this->belongsTo(Task::class);} }
