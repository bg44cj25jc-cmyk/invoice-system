<?php
namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'name', 'discount', 'status'
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
