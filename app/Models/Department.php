<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasUuids;

    protected $table = 'departments';

    protected $fillable = ['name', 'color', 'status'];

    protected $keyType = 'string';

    public $incrementing = false;

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'department_id');
    }
}
