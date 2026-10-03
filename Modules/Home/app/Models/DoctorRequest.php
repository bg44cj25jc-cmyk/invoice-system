<?php

namespace Modules\Home\app\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorRequest extends Model
{

    protected $fillable = [
        'first_name',
        'last_name',
        'postal_code',
        'speciality',
        'email',
        'subjects',
        'proof_document'
    ];

}
