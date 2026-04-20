<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Services\DailyQuizService;

class DailyQuizController extends Controller
{
    public function show()
    {
        try {
            $quiz = (new DailyQuizService())->getToday();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('DailyQuiz fatal: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['error' => true, 'msg' => $e->getMessage()], 503);
        }

        if (! $quiz) {
            return response()->json(['error' => true, 'msg' => 'null result'], 503);
        }

        return response()->json($quiz);
    }
}
