<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;


class DoctorExperience extends Model
{
    protected $table = 'doctor_experiances';
    protected $fillable = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


}
