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

        $data = $this->generate();
        if (! $data) return null;

        $record = DailyQuizQuestion::create([
            'quiz_date'      => Carbon::today()->toDateString(),
            'question'       => $data['question'],
            'option_a'       => $data['option_a'],
            'option_b'       => $data['option_b'],
            'option_c'       => $data['option_c'],
            'correct_option' => strtolower($data['correct_option']),
            'explanation'    => $data['explanation'],
        ]);

        return $this->format($record);
    }

    private function generate(): ?array
    {
        $apiKey = config('services.gemini.key');
        if (! $apiKey) return null;

        $date  = Carbon::today()->locale('tr')->isoFormat('D MMMM YYYY');
        $model = config('services.gemini.text_model', 'gemini-2.5-flash');

        $prompt = "Tarih: {$date}. GrupRezervasyonlari.com (grup seyahat, yat, dinner cruise, transfer, özel jet) için Türkçe turizm/havacılık/seyahat sorusu üret. Orta zorluk, 3 şık, 1 doğru. Sadece JSON: {\"question\":\"...\",\"option_a\":\"...\",\"option_b\":\"...\",\"option_c\":\"...\",\"correct_option\":\"a\",\"explanation\":\"...\"}";

        try {
            $response = Http::timeout(15)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.9, 'maxOutputTokens' => 700],
                ]
            );

            Log::info('DailyQuiz HTTP: ' . $response->status() . ' body: ' . mb_substr($response->body(), 0, 300));

            if (! $response->successful()) {
                Log::warning('DailyQuizService HTTP ' . $response->status() . ': ' . $response->body());
                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text', '');
            Log::info('DailyQuiz raw[' . mb_strlen($text) . ']: ' . str_replace("\n",'↵',mb_substr($text, 0, 800)));
            $text = trim(preg_replace('/```json|```/i', '', $text));
            if (preg_match('/\{.*\}/s', $text, $m)) $text = $m[0];

            $data = json_decode($text, true);
            if (! is_array($data) || ! isset($data['question'], $data['correct_option'])) {
                Log::warning('DailyQuiz parse fail: ' . $text);
                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('DailyQuizService exception: ' . $e->getMessage());
            return null;
        }
    }

    private function format(DailyQuizQuestion $q): array
    {
        return [
            'question'       => $q->question,
            'options'        => [
                'a' => $q->option_a,
                'b' => $q->option_b,
                'c' => $q->option_c,
            ],
            'correct'        => $q->correct_option,
            'explanation'    => $q->explanation,
        ];
    }
}
