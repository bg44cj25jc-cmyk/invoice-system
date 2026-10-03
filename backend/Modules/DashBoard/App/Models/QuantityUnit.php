<?php

namespace Modules\DashBoard\App\Models;

use Illuminate\Database\Eloquent\Model;

class QuantityUnit extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'quantity_unit_id');
    }
}
