<?php

namespace Modules\Login\App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    protected $table = 'admins';
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'token'
    ];

    protected $hidden = [
        'password',
        'token',
    ];

    /**
     * Automatically hash password when setting
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
}