<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Home\Database\factories\DoctorTransportFactory;

class DoctorTransport extends Model
{
    protected $table = 'doctor_transport';
    protected $fillable = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
