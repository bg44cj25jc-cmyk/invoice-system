<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TimeOverlap implements Rule
{
    protected $day;
    protected $startTime;
    protected $endTime;
    protected $userId;

    public function __construct($day, $startTime, $endTime, $userId)
    {
        $this->day = $day;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->userId = $userId;
    }

    public function passes($attribute, $value)
    {
        $existingEntries = DB::table('doctor_available_days')
            ->where('day', $this->day)
            ->where('user_id', $this->userId)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<', $this->endTime)
                        ->where('end_time', '>', $this->startTime);
                })
                    ->orWhere(function ($query) {
                        $query->where('start_time', '<=', $this->startTime)
                            ->where('end_time', '>=', $this->endTime);
                    });
            })
            ->exists();

        return !$existingEntries;
    }

    public function message()
    {
        return 'The time range overlaps with another entry in the request.';
    }
}
