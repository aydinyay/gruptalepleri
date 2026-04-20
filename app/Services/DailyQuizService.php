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

        $prompt = <<<PROMPT
Sen GrupRezervasyonlari.com için günlük quiz sorusu üreten bir asistansın.
Bugünün tarihi: {$date}.

Dönüşümlü olarak; ya genel turizm/havacılık/coğrafya/seyahat YA DA doğrudan platformumuzun
(GrupRezervasyonlari.com — Türkiye'nin lider grup seyahat sitesi; yat turu, dinner cruise,
havalimanı transferi, özel jet, charter, Boğaz turu, Kapadokya turu, grup rezervasyonları)
avantajlarını, özelliklerini ya da benzersiz tekliflerini anlatan zorlayıcı, ilgi çekici bir soru üret.

Kurallar:
- Soru Türkçe olacak, orta zorlukta
- 3 şık olacak, sadece 1 doğru
- Açıklama kısa ve bilgilendirici (max 2 cümle)

SADECE JSON döndür, başka hiçbir şey yazma:
{"question":"...","option_a":"...","option_b":"...","option_c":"...","correct_option":"a veya b veya c","explanation":"..."}
PROMPT;

        try {
            $response = Http::timeout(15)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.9, 'maxOutputTokens' => 400],
                ]
            );

            if (! $response->successful()) {
                Log::warning('DailyQuizService HTTP ' . $response->status());
                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text', '');
            $text = trim(preg_replace('/```json|```/i', '', $text));
            if (preg_match('/\{.*\}/s', $text, $m)) $text = $m[0];

            $data = json_decode($text, true);
            if (! is_array($data) || ! isset($data['question'], $data['correct_option'])) return null;

            return $data;
        } catch (\Throwable $e) {
            Log::warning('DailyQuizService: ' . $e->getMessage());
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
