<?php

namespace Modules\DashBoard\App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'subscription_id',
        'order_id',
        'plan_name',
        'amount',
        'status',
        'customer_id',
        'invoice_id',
        'payment_date',
    ];

    public function user()
    {
        return $this->belongsTo(FrontEndUser::class, 'user_id', 'id');
    }

    // REAL RELATIONSHIP to companies through user
    public function companies()
    {
        return $this->belongsToMany(
            FrontEndUser::class,
            'company_employees',
            'employee_id',
            'company_id'
        )->where('role', 'company');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
	
	public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'payment_id', 'id');
    }
}
