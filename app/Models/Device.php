<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'user_id',
        'device_name',
        'device_id',
        'device_token',
    ];
}
