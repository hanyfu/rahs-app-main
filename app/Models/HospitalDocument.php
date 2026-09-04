<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class HospitalDocument extends Model { use HasUuids; protected $fillable=['hospital_profile_id','category','title','version','issue_date','expiry_date','file_url','notes','uploaded_by']; protected $casts=['issue_date'=>'date','expiry_date'=>'date']; public function hospitalProfile(): BelongsTo{return $this->belongsTo(HospitalProfile::class);} }
