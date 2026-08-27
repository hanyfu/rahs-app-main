<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HospitalContact extends Model
{
    use HasUuids;

    protected $table = 'hospital_contacts';

    protected $fillable = [
        'hospital_name', 'island_id', 'manager_name', 'contact_number',
        'contact_designation', 'notes', 'status',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'island_id' => 'string',
    ];

    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class, 'island_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(HospitalProfile::class, 'hospital_contact_id');
    }
}
