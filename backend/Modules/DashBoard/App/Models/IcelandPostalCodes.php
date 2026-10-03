<?php

namespace Modules\DashBoard\App\Models;

use Illuminate\Database\Eloquent\Model;

class IcelandPostalCodes extends Model
{
    protected $fillable = ['postal_code', 'location_name', 'area_description', 'is_selected'];
}
