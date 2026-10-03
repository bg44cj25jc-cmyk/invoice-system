<?php

namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;

class Vat extends Model
{
    protected $fillable = [
        'name',
        'percentage',
        'status',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'vat_id');
    }
}
