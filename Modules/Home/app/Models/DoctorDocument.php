<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Home\Database\factories\DoctorDocumentFactory;

class DoctorDocument extends Model
{
    protected $table = 'doctor_documents';
    protected $fillable = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
