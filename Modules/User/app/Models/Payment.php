<?php

namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'subscription_id', 'order_id', 'plan_name', 'amount', 'status'
    ];
    
}