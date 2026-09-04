<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    use HasUuids;

    protected $table = 'user_roles';

    protected $fillable = ['user_id', 'role'];

    protected $keyType = 'string';

    public $incrementing = false;

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'user_id', 'id');
    }
}
