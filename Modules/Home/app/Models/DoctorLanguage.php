<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;


class DoctorLanguage extends Model
{
    protected $table = 'doctor_language';
    protected $fillable = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function language()
    {
        return $this->hasOne(Language::class, 'id', 'language_id');
    }
}
