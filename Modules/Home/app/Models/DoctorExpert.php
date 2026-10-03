<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorExpert extends Model
{
    protected $table = 'doctor_expert';
    protected $fillable = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
