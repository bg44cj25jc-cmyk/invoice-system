<?php

namespace Modules\DashBoard\App\Models;

use Illuminate\Database\Eloquent\Model;


class Medicine extends Model
{
    protected $table = 'medicines';
    protected $fillable = [
        'name',
        'brand',
        'dosage',
        'packaging',
    ];
}