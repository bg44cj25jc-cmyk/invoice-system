<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;


class DoctorInformation extends Model
{
    protected $table = 'doctor_information';
    protected $fillable = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
