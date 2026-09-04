<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasUuids;

    protected $table = 'departments';

    protected $fillable = ['name', 'color', 'status'];

    protected $keyType = 'string';

    public $incrementing = false;
}
