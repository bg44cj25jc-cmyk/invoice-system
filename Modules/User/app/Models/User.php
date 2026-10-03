<?php

namespace Modules\User\app\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

use Modules\User\app\Models\PaymentSetting;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;
	
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
	
	public function paymentSetting()
    {
        return $this->hasOne(PaymentSetting::class, 'company_id');
    }
	
	
	
	// A company can have many employees
	public function employees()
	{
		return $this->belongsToMany(User::class, 'company_employees', 'company_id', 'employee_id')
					->withPivot(['status', 'invite_token', 'access_permissions', 'customer_permissions'])
					->withTimestamps();
	}

	public function companies()
	{
		return $this->belongsToMany(User::class, 'company_employees', 'employee_id', 'company_id')
					->withPivot(['status', 'invite_token', 'access_permissions', 'customer_permissions'])
					->withTimestamps();
	}





}
