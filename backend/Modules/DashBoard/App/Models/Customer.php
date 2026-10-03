<?php

namespace Modules\DashBoard\App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\DashBoard\App\Models\Country;
use Modules\DashBoard\App\Models\IcelandPostalCodes;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
		'company_id',
        'user_customer_id',
        'ssn',
        'full_name',
        'address',
        'zip_code',
        'country',
        'currency',
        'discount',
        'discound',
        'vat',
        'telephone',
        'mobile',
        'email',
        'number_per_unit',
        'invoice_in_email',
        'invoice_in_online_bank',
        'send_electronic_invoice',
        'send_elecctronic_invoice',
        'payment_method',
    ];

    protected $appends = ['postal_location_name_text', 'customer_name'];

    public function countryName()
    {
        return $this->belongsTo(Country::class, 'country', 'id');
    }

    public function postalLocationName()
    {
        // zip_code in customers table → postal_code in iceland_postal_codes table
        return $this->belongsTo(IcelandPostalCodes::class, 'zip_code', 'postal_code');
    }

    public function getPostalLocationNameTextAttribute()
    {
        return $this->postalLocationName->name ?? null;
    }

    public function getCustomerNameAttribute()
    {
        return $this->full_name;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
