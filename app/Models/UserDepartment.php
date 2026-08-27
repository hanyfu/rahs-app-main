<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserDepartment extends Model
{
    use HasUuids;

    protected $table = 'user_departments';

    protected $fillable = ['name', 'description', 'color', 'status'];

    protected $keyType = 'string';

    public $incrementing = false;

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'user_department_id');
    }
}
