<?php
namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = [
        'company_id',
        'bank_no',
        'bank_identifier',
        'due_date_rule',
        'final_due_date_rule',
        'cancellation_date_rule',
        'payment_currency',
        'default_action',
    ];

    public function employeesWithRights()
    {
        return $this->belongsToMany(User::class, 'payment_setting_employees', 'payment_setting_id', 'employee_id');
    }

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }
}

