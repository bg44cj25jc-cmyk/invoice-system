<?php

namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;

class ExemptVat extends Model
{
    protected $fillable = [
        'name',
        //'percentage',
        'status',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'vat');
    }
}
