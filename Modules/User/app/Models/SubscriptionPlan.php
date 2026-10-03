<?php

namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\User\app\Models\PlanFeature;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'price', 'currency', 'billing_cycle', 'invoice_limit',
        'customer_management', 'employee_account', 'product_management', 'is_active'
    ];

    public function features()
	{
		return $this->hasMany(PlanFeature::class, 'plan_id');
	}

}


