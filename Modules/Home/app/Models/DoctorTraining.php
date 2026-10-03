<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;


class DoctorTraining extends Model
{
    protected $table = 'doctor_trainings';
    protected $fillable = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
