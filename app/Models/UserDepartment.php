<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserDepartment extends Model
{
    use HasUuids;

    protected $table = 'user_departments';

    protected $fillable = ['name', 'description', 'color', 'status'];

    protected $keyType = 'string';

    public $incrementing = false;
}
