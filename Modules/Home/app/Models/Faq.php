<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;


class Faq extends Model
{
    protected $fillable = [];

    public function doctorFaqs()
    {
        return $this->hasMany(DoctorFaq::class, 'faq_id');
    }

}
