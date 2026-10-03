<?php

namespace Modules\DashBoard\App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\DashBoard\App\Models\SubscriptionPlan;
use Modules\DashBoard\App\Models\Country;
use Modules\DashBoard\App\Models\IcelandPostalCodes;
class FrontEndUser extends Model
{
    protected $table = 'users';
    protected $dates = ['deleted_at'];

   protected $fillable = [
        'company_name',
	   'contact_person_name',
        'email',
        'password',
        'type',
        'phone',
        'address',
		'phone_code',
		'phone_number',
		'bank_name',
		'account_number',
		'invoice_in_online_bank_misc',
		'invoice_in_online_bank',
		'show_acc_no_invoice',
		'connect_national_register',
		'access_key',
		'gln_username',
		'gln_password', 
	   'plan_expiry_date',
	   'active_plan_name',
    	'status', 
    ]; 

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'access_permissions' => 'array',
        'customer_permissions' => 'array',
    ];
	
	
public function postalCodeDetail()
{
    return $this->belongsTo(IcelandPostalCodes::class, 'postal_code', 'postal_code');
}

	
	public function invoices()
{
    return $this->hasMany(Invoice::class, 'company_id');
}
	
	public function paymentSetting()
    {
        return $this->hasOne(PaymentSetting::class, 'company_id');
    }
	
	public function country_details(){ 
		return $this->belongsTo(Country::class, 'country','id');
	}
	
	public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'active_plan_id','id');
    }
	
		public function employees()
	{
		return $this->belongsToMany(FrontEndUser::class, 'company_employees', 'company_id', 'employee_id')
					->withPivot(['status', 'invite_token', 'access_permissions', 'customer_permissions'])
					->withTimestamps();
	}

	public function companies()
{
    return $this->belongsToMany(
            FrontEndUser::class,
            'company_employees',
            'employee_id',
            'company_id'
        )
        ->where('users.role', 'company')
        ->select('users.id', 'users.company_name', 'users.email', 'users.phone_number') // keep needed fields
        ->withPivot(['status', 'invite_token', 'access_permissions', 'customer_permissions'])
        ->withTimestamps();
}

	
	public function company()
	{
		return $this->belongsToMany(FrontEndUser::class, 'company_employees', 'employee_id', 'company_id')
					->where('role', 'company');
	}

	
	
}
