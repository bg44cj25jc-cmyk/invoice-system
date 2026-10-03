<?php

namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\User\app\Models\SubscriptionPlan;

class PlanFeature extends Model
{
    protected $fillable = ['plan_id', 'feature_name', 'is_available'];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}