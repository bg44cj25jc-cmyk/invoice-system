<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;


class DoctorFaq extends Model
{
    protected $table = 'doctor_faqs';
    protected $fillable = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function faq()
    {
        return $this->belongsTo(Faq::class, 'faq_id');
    }

}
