<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;


class Language extends Model
{

    protected $fillable = [];

    public function doctorLanguages()
    {
        return $this->hasMany(DoctorLanguage::class, 'language_id');
    }

}
