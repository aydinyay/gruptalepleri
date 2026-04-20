<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Services\DailyQuizService;

class DailyQuizController extends Controller
{
    public function show()
    {
        $quiz = (new DailyQuizService())->getToday();

        if (! $quiz) {
            return response()->json(['error' => true], 503);
        }

        return response()->json($quiz);
    }
}
