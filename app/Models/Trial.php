<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trial extends Model
{
    protected $fillable = [
        'username',
        'password',
        'url',
        'm3u_link',
        'assigned_user'
    ];
}
