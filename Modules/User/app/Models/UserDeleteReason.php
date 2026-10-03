<?php

namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;

class UserDeleteReason extends Model
{
    protected $fillable = ['user_id', 'reason'];
}
