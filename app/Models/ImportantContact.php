<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportantContact extends Model
{
    use HasUuids;

    protected $table = 'important_contacts';

    protected $fillable = [
        'name', 'title', 'organization', 'phone_primary', 'phone_secondary',
        'email', 'notes', 'priority', 'status',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'priority' => 'integer',
    ];
}
