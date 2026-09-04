<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TrackedExpiryItem extends Model { use HasUuids; protected $fillable=['hospital_profile_id','task_id','item_type','name','reference_number','expiry_date','warning_days','quantity','status','notes','document_url','created_by']; protected $casts=['expiry_date'=>'date','warning_days'=>'integer','quantity'=>'decimal:2']; public function hospitalProfile(): BelongsTo{return $this->belongsTo(HospitalProfile::class);} public function task(): BelongsTo{return $this->belongsTo(Task::class);} }
