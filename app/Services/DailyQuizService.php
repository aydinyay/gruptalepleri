<?php

namespace App\Services;

use App\Models\B2C\DailyQuizQuestion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DailyQuizService
{
    public function getToday(): ?array
    {
        $existing = DailyQuizQuestion::today();
        if ($existing) {
            return $this->format($existing);
        }

        $batch = $this->generateBatch();
        if (! $batch) return null;

        $first = $batch[0];
        $record = DailyQuizQuestion::create([
            'quiz_date'      => Carbon::today()->toDateString(),
            'question'       => $first['question'],
            'option_a'       => $first['option_a'],
            'option_b'       => $first['option_b'],
            'option_c'       => $first['option_c'],
            'correct_option' => strtolower($first['correct_option']),
            'explanation'    => $first['explanation'],
            'batch_json'     => json_encode($batch, JSON_UNESCAPED_UNICODE),
        ]);

        return $this->format($record);
    }

    private function generateBatch(): ?array
    {
        $apiKey = config('services.gemini.key');
        if (! $apiKey) return null;

        $date  = Carbon::today()->locale('tr')->isoFormat('D MMMM YYYY');
        $model = config('services.gemini.text_model', 'gemini-2.5-flash');

        $prompt = "Tarih: {$date}. GrupRezervasyonlari.com için 5 farklı Türkçe turizm/seyahat sorusu üret. Her soru max 12 kelime, her şık max 4 kelime. Sadece JSON dizisi döndür: [{\"question\":\"...\",\"option_a\":\"...\",\"option_b\":\"...\",\"option_c\":\"...\",\"correct_option\":\"a\",\"explanation\":\"...\"}, ...]";

        try {
            $response = Http::timeout(20)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature'      => 0.9,
                        'maxOutputTokens'  => 2000,
                        'responseMimeType' => 'application/json',
                    ],
                ]
            );

            if (! $response->successful()) {
                Log::warning('DailyQuizService HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 300));
                return null;
            }

            $text = trim($response->json('candidates.0.content.parts.0.text', ''));
            Log::info('DailyQuiz batch raw: ' . str_replace("\n",'↵', mb_substr($text, 0, 800)));

            $data = json_decode($text, true);
            if (! is_array($data) || count($data) < 1 || ! isset($data[0]['question'], $data[0]['correct_option'])) {
                Log::warning('DailyQuiz batch parse fail json_err=' . json_last_error() . ': ' . mb_substr($text, 0, 300));
                return null;
            }

            return array_slice($data, 0, 5);
        } catch (\Throwable $e) {
            Log::error('DailyQuizService exception: ' . $e->getMessage());
            return null;
        }
    }

    private function format(DailyQuizQuestion $q): array
    {
        $batch = null;
        if ($q->batch_json) {
            $batch = json_decode($q->batch_json, true);
        }

        if (! $batch || count($batch) < 2) {
            $batch = [[
                'question'       => $q->question,
                'option_a'       => $q->option_a,
                'option_b'       => $q->option_b,
                'option_c'       => $q->option_c,
                'correct_option' => $q->correct_option,
                'explanation'    => $q->explanation,
            ]];
        }

        return [
            'questions' => array_map(fn($item) => [
                'question' => $item['question'],
                'options'  => [
                    'a' => $item['option_a'],
                    'b' => $item['option_b'],
                    'c' => $item['option_c'],
                ],
                'correct'     => strtolower($item['correct_option']),
                'explanation' => $item['explanation'],
            ], $batch),
        ];
    }
}
