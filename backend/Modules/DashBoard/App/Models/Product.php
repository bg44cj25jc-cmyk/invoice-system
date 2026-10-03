<?php

namespace Modules\DashBoard\App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'product_number', 'name', 'category_id',
        'unit_of_measure', 'currency', 'vat', 'price_excl_vat',
        'price_incl_vat', 'cost_price', 'allow_discount', 'max_discount',
        'number_per_unit', 'note', 'location', 'supplier', 'status'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
	
	public function quantityUnit()
	{
		return $this->belongsTo(QuantityUnit::class, 'quantity_unit_id');
	}
}
