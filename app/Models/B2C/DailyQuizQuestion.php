<?php

namespace App\Models\B2C;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DailyQuizQuestion extends Model
{
    protected $table = 'daily_quiz_questions';

    protected $fillable = [
        'quiz_date', 'question', 'option_a', 'option_b', 'option_c',
        'correct_option', 'explanation',
    ];

    public static function today(): ?self
    {
        return static::where('quiz_date', Carbon::today()->toDateString())->first();
    }
}
