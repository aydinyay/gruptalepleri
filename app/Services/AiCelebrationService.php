<?php

namespace App\Services;

use App\Models\AiCelebrationCampaign;
use App\Models\AiCelebrationUserState;
use App\Models\SistemAyar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiCelebrationService
{
    public function __construct(
        private readonly AiCelebrationCalendarService $calendarService
    ) {
    }

    /**
     * @return array{created:int, generated:int, existing:int, skipped_dismissed:int}
     */
    public function scanUpcomingSuggestions(int $days = 7, bool $forceRefresh = false, ?int $actorId = null): array
    {
        $items = $this->calendarService->upcoming($days, $forceRefresh);
        $stats = [
            'created' => 0,
            'generated' => 0,
            'existing' => 0,
            'skipped_dismissed' => 0,
        ];

        foreach ($items as $item) {
            $isDismissed = AiCelebrationCampaign::query()
                ->where('source_key', $item['source_key'])
                ->whereDate('event_date', $item['event_date'])
                ->where('status', AiCelebrationCampaign::STATUS_DISMISSED)
                ->exists();

            if ($isDismissed) {
                $stats['skipped_dismissed']++;
                continue;
            }

            $window = $this->defaultWindowForDate($item['event_date'] ?? null);

            $campaign = AiCelebrationCampaign::firstOrCreate(
                [
                    'source_key' => $item['source_key'],
                    'event_date' => $item['event_date'],
                ],
                [
                    'event_name' => $item['event_name'],
                    'category' => $item['category'] ?? 'genel',
                    'status' => AiCelebrationCampaign::STATUS_DRAFT,
                    'title' => null,
                    'message' => null,
                    'topic_prompt' => $item['default_prompt'] ?? null,
                    'display_mode' => AiCelebrationCampaign::DISPLAY_BANNER,
                    'show_on_public' => false,
                    'show_on_authenticated' => true,
                    'frequency_cap' => 1,
                    'priority' => (int) ($item['priority'] ?? 100),
                    'publish_starts_at' => $window['start'],
                    'publish_ends_at' => $window['end'],
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]
            );

            if ($campaign->wasRecentlyCreated) {
                $stats['created']++;
            } else {
                $stats['existing']++;
            }

            $sourceFieldsChanged = false;
            if (
                ! $campaign->wasRecentlyCreated
                && $campaign->source_key !== null
                && $campaign->status === AiCelebrationCampaign::STATUS_DRAFT
            ) {
                $newEventName = (string) ($item['event_name'] ?? $campaign->event_name);
                $newCategory = (string) ($item['category'] ?? $campaign->category);
                $newTopicPrompt = (string) ($item['default_prompt'] ?? $campaign->topic_prompt);
                $newPriority = (int) ($item['priority'] ?? $campaign->priority);

                if ($campaign->event_name !== $newEventName) {
                    $campaign->event_name = $newEventName;
                    $sourceFieldsChanged = true;
                }
                if ($campaign->category !== $newCategory) {
                    $campaign->category = $newCategory;
                    $sourceFieldsChanged = true;
                }
                if ((string) $campaign->topic_prompt !== $newTopicPrompt) {
                    $campaign->topic_prompt = $newTopicPrompt;
                    $sourceFieldsChanged = true;
                }
                if ((int) $campaign->priority !== $newPriority) {
                    $campaign->priority = $newPriority;
                    $sourceFieldsChanged = true;
                }

                if ($sourceFieldsChanged) {
                    $campaign->updated_by = $actorId ?? $campaign->updated_by;
                    $campaign->save();
                }
            }

            $needsGeneration = $campaign->wasRecentlyCreated
                || blank($campaign->title)
                || blank($campaign->message)
                || blank($campaign->image_path)
                || $sourceFieldsChanged;

            if ($needsGeneration) {
                $this->generateContent($campaign, $campaign->topic_prompt, $actorId);
                $stats['generated']++;
            }
        }

        return $stats;
    }

    public function createManualSuggestion(array $data, int $actorId): AiCelebrationCampaign
    {
        $window = $this->defaultWindowForDate($data['event_date'] ?? null);

        $campaign = AiCelebrationCampaign::create([
            'source_key' => null,
            'event_name' => (string) ($data['event_name'] ?? 'Özel Gün'),
            'event_date' => $data['event_date'] ?? null,
            'category' => (string) ($data['category'] ?? 'genel'),
            'status' => AiCelebrationCampaign::STATUS_DRAFT,
            'topic_prompt' => (string) ($data['topic_prompt'] ?? ''),
            'display_mode' => (string) ($data['display_mode'] ?? AiCelebrationCampaign::DISPLAY_BANNER),
            'show_on_public' => (bool) ($data['show_on_public'] ?? false),
            'show_on_authenticated' => true,
            'frequency_cap' => max(1, (int) ($data['frequency_cap'] ?? 1)),
            'priority' => max(1, (int) ($data['priority'] ?? 100)),
            'publish_starts_at' => $data['publish_starts_at'] ?? $window['start'],
            'publish_ends_at' => $data['publish_ends_at'] ?? $window['end'],
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        return $this->generateContent($campaign, $campaign->topic_prompt, $actorId);
    }

    public function generateContent(
        AiCelebrationCampaign $campaign,
        ?string $topicPrompt = null,
        ?int $actorId = null
    ): AiCelebrationCampaign {
        $textData = $this->generateTextSuggestion($campaign, $topicPrompt);
        $visualPrompt = (string) ($textData['visual_prompt'] ?? $campaign->visual_prompt ?? '');
        $imageResult = $this->generateImage($campaign, $visualPrompt);
        $imagePath = $imageResult['path'] ?? null;
        $existingPayload = is_array($campaign->ai_payload) ? $campaign->ai_payload : [];

        $campaign->fill([
            'title' => (string) ($textData['title'] ?? $campaign->title ?? ''),
            'message' => (string) ($textData['message'] ?? $campaign->message ?? ''),
            'cta_text' => (string) ($textData['cta_text'] ?? $campaign->cta_text ?? 'Detayları Gör'),
            'cta_url' => $this->normalizeCtaUrl((string) ($textData['cta_url'] ?? $campaign->cta_url ?? '/dashboard')),
            'visual_prompt' => $visualPrompt,
            'ai_payload' => array_merge($existingPayload, [
                'text' => $textData,
                'generated_at' => now()->toISOString(),
                'image_generation' => $imageResult,
            ]),
            'image_path' => $imagePath ?? $campaign->image_path,
            'is_ai_generated' => ($imageResult['source'] ?? null) === 'gemini',
            'updated_by' => $actorId ?? $campaign->updated_by,
        ]);

        $campaign->save();

        return $campaign->fresh();
    }

    public function updateCampaign(AiCelebrationCampaign $campaign, array $data, int $actorId): AiCelebrationCampaign
    {
        $campaign->fill([
            'event_name' => (string) ($data['event_name'] ?? $campaign->event_name),
            'event_date' => $data['event_date'] ?? $campaign->event_date,
            'category' => (string) ($data['category'] ?? $campaign->category),
            'title' => (string) ($data['title'] ?? $campaign->title),
            'message' => (string) ($data['message'] ?? $campaign->message),
            'cta_text' => (string) ($data['cta_text'] ?? $campaign->cta_text),
            'cta_url' => $this->normalizeCtaUrl((string) ($data['cta_url'] ?? $campaign->cta_url)),
            'topic_prompt' => (string) ($data['topic_prompt'] ?? $campaign->topic_prompt),
            'display_mode' => (string) ($data['display_mode'] ?? $campaign->display_mode),
            'show_on_public' => (bool) ($data['show_on_public'] ?? false),
            'show_on_authenticated' => (bool) ($data['show_on_authenticated'] ?? true),
            'frequency_cap' => max(1, (int) ($data['frequency_cap'] ?? $campaign->frequency_cap)),
            'priority' => max(1, (int) ($data['priority'] ?? $campaign->priority)),
            'publish_starts_at' => $data['publish_starts_at'] ?? $campaign->publish_starts_at,
            'publish_ends_at' => $data['publish_ends_at'] ?? $campaign->publish_ends_at,
            'updated_by' => $actorId,
        ]);
        $campaign->save();

        return $campaign->fresh();
    }

    public function publishCampaign(AiCelebrationCampaign $campaign, int $actorId): AiCelebrationCampaign
    {
        $campaign->update([
            'status' => AiCelebrationCampaign::STATUS_PUBLISHED,
            'approved_by' => $actorId,
            'approved_at' => now(),
            'published_by' => $actorId,
            'published_at' => now(),
            'dismissed_by' => null,
            'dismissed_at' => null,
            'dismiss_reason' => null,
            'updated_by' => $actorId,
        ]);

        return $campaign->fresh();
    }

    public function stopCampaign(AiCelebrationCampaign $campaign, int $actorId): AiCelebrationCampaign
    {
        $campaign->update([
            'status' => AiCelebrationCampaign::STATUS_APPROVED,
            'updated_by' => $actorId,
        ]);

        return $campaign->fresh();
    }

    public function dismissCampaign(AiCelebrationCampaign $campaign, int $actorId, ?string $reason = null): AiCelebrationCampaign
    {
        $campaign->update([
            'status' => AiCelebrationCampaign::STATUS_DISMISSED,
            'dismissed_by' => $actorId,
            'dismissed_at' => now(),
            'dismiss_reason' => $reason,
            'updated_by' => $actorId,
        ]);

        return $campaign->fresh();
    }

    public function restoreCampaign(AiCelebrationCampaign $campaign, int $actorId): AiCelebrationCampaign
    {
        $campaign->update([
            'status' => AiCelebrationCampaign::STATUS_DRAFT,
            'dismissed_by' => null,
            'dismissed_at' => null,
            'dismiss_reason' => null,
            'updated_by' => $actorId,
        ]);

        return $campaign->fresh();
    }

    public function activeCampaignForRequest(Request $request, ?User $user = null): ?AiCelebrationCampaign
    {
        if (! SistemAyar::aiCelebrationEnabled()) {
            return null;
        }

        $candidates = AiCelebrationCampaign::query()
            ->publishedActive()
            ->when(
                $user !== null,
                fn ($query) => $query->where('show_on_authenticated', true),
                fn ($query) => $query->where('show_on_public', true)
            )
            ->orderBy('priority')
            ->orderBy('publish_starts_at')
            ->limit(20)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($user !== null) {
            $states = AiCelebrationUserState::query()
                ->where('user_id', $user->id)
                ->whereIn('campaign_id', $candidates->pluck('id'))
                ->get()
                ->keyBy('campaign_id');

            foreach ($candidates as $campaign) {
                $state = $states->get($campaign->id);
                if (! $state) {
                    return $campaign;
                }

                $cap = max(1, (int) $campaign->frequency_cap);
                if ($state->closed_at !== null) {
                    continue;
                }

                if ((int) $state->seen_count >= $cap) {
                    continue;
                }

                return $campaign;
            }

            return null;
        }

        foreach ($candidates as $campaign) {
            if ($request->cookie($this->guestCookieName($campaign->id))) {
                continue;
            }

            return $campaign;
        }

        return null;
    }

    public function markSeen(AiCelebrationCampaign $campaign, ?User $user): void
    {
        if (! $user) {
            return;
        }

        $state = AiCelebrationUserState::firstOrCreate(
            ['campaign_id' => $campaign->id, 'user_id' => $user->id],
            ['seen_count' => 0]
        );

        $state->update([
            'seen_count' => min(((int) $state->seen_count) + 1, 9999),
            'first_seen_at' => $state->first_seen_at ?? now(),
            'last_seen_at' => now(),
            'last_action' => 'seen',
        ]);
    }

    public function markClosed(AiCelebrationCampaign $campaign, ?User $user): void
    {
        if (! $user) {
            return;
        }

        $state = AiCelebrationUserState::firstOrCreate(
            ['campaign_id' => $campaign->id, 'user_id' => $user->id],
            ['seen_count' => 0]
        );

        $state->update([
            'first_seen_at' => $state->first_seen_at ?? now(),
            'last_seen_at' => now(),
            'closed_at' => now(),
            'last_action' => 'closed',
        ]);
    }

    public function markClicked(AiCelebrationCampaign $campaign, ?User $user): void
    {
        if (! $user) {
            return;
        }

        $state = AiCelebrationUserState::firstOrCreate(
            ['campaign_id' => $campaign->id, 'user_id' => $user->id],
            ['seen_count' => 0]
        );

        $state->update([
            'first_seen_at' => $state->first_seen_at ?? now(),
            'last_seen_at' => now(),
            'clicked_at' => now(),
            'last_action' => 'clicked',
        ]);
    }

    public function guestCookieName(int $campaignId): string
    {
        return 'gtp_ai_seen_' . $campaignId;
    }

    /**
     * @return array{start:Carbon,end:Carbon}
     */
    private function defaultWindowForDate(?string $eventDate): array
    {
        $tz = config('app.timezone', 'UTC');
        $start = $eventDate
            ? Carbon::parse($eventDate, $tz)->startOfDay()
            : now()->startOfHour();
        $end = $start->copy()->endOfDay();

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @return array{title:string,message:string,cta_text:string,cta_url:string,visual_prompt:string}
     */
    private function generateTextSuggestion(AiCelebrationCampaign $campaign, ?string $topicPrompt = null): array
    {
        $fallback = $this->fallbackTextSuggestion($campaign, $topicPrompt);
        $apiKey = (string) config('services.gemini.key');
        $model = (string) config('services.gemini.text_model', 'gemini-2.5-flash');
        $timeout = max(10, (int) config('services.gemini.timeout', 45));
        if ($apiKey === '') {
            return $fallback;
        }

        $prompt = implode("\n", [
            'Sen GrupTalepleri.com B2B platformu için kutlama içeriği üreten bir asistansın.',
            'Sadece JSON döndür. Markdown veya açıklama yazma.',
            'JSON semasi:',
            '{"title":"", "message":"", "cta_text":"", "cta_url":"", "visual_prompt":""}',
            'Kurallar:',
            '- title en fazla 60 karakter',
            '- message en fazla 240 karakter',
            '- Metinler Türkçe ve doğal olsun, Türkçe karakterleri doğru kullan (ç, ğ, ı, İ, ö, ş, ü)',
            '- Metin sade, kurumsal ve pozitif olsun',
            '- Hiçbir şekilde fiyat, indirim, spam, siyasi ifade olmasın',
            '- cta_url mutlak URL olmak zorunda değil, /dashboard kullanılabilir',
            'Etkinlik adi: ' . $campaign->event_name,
            'Kategori: ' . $campaign->category,
            'Etkinlik tarihi: ' . ($campaign->event_date?->format('Y-m-d') ?? '-'),
            'Ek konu talebi: ' . trim((string) ($topicPrompt ?: $campaign->topic_prompt ?: '-')),
        ]);

        try {
            $response = Http::timeout($timeout)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'thinkingConfig' => ['thinkingBudget' => 0],
                    ],
                ]
            );
        } catch (\Throwable $exception) {
            Log::warning('AI kutlama metin uretimi baglanti hatasi: ' . $exception->getMessage());
            return $fallback;
        }

        if ($response->failed()) {
            Log::warning('AI kutlama metin uretimi basarisiz: ' . $this->extractGeminiError($response));
            return $fallback;
        }

        $rawText = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $decoded = $this->decodeJsonFromModelText($rawText);
        if (! is_array($decoded)) {
            return $fallback;
        }

        return [
            'title' => Str::limit(trim((string) Arr::get($decoded, 'title', $fallback['title'])), 60, ''),
            'message' => Str::limit(trim((string) Arr::get($decoded, 'message', $fallback['message'])), 240, ''),
            'cta_text' => Str::limit(trim((string) Arr::get($decoded, 'cta_text', $fallback['cta_text'])), 80, ''),
            'cta_url' => trim((string) Arr::get($decoded, 'cta_url', $fallback['cta_url'])),
            'visual_prompt' => trim((string) Arr::get($decoded, 'visual_prompt', $fallback['visual_prompt'])),
        ];
    }

    /**
     * @return array{path:?string,source:string,error:?string}
     */
    private function generateImage(AiCelebrationCampaign $campaign, string $visualPrompt): array
    {
        $prompt = trim($visualPrompt) !== ''
            ? trim($visualPrompt)
            : ('GrupTalepleri için ' . $campaign->event_name . ' kutlama görseli');
        $apiKey = (string) config('services.gemini.key');
        $timeout = max(10, (int) config('services.gemini.timeout', 60));
        $models = $this->geminiImageModels();

        if ($apiKey !== '') {
            $errors = [];
            foreach ($models as $model) {
                try {
                    $response = Http::timeout($timeout)->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                        [
                            'contents' => [[
                                'parts' => [[
                                    'text' => $prompt . '. Üzerinde yazı olmayan, temiz ve kurumsal bir görsel üret.',
                                ]],
                            ]],
                            'generationConfig' => [
                                'responseModalities' => ['TEXT', 'IMAGE'],
                            ],
                        ]
                    );
                } catch (\Throwable $exception) {
                    $errors[] = "{$model}: " . $exception->getMessage();
                    Log::warning('AI kutlama gorsel uretimi baglanti hatasi', [
                        'model' => $model,
                        'error' => $exception->getMessage(),
                    ]);
                    continue;
                }

                if (! $response->ok()) {
                    $errors[] = "{$model}: " . $this->extractGeminiError($response);
                    Log::warning('AI kutlama gorsel uretimi basarisiz HTTP', [
                        'model' => $model,
                        'status' => $response->status(),
                        'body' => Str::limit((string) $response->body(), 600),
                    ]);
                    continue;
                }

                $parts = (array) data_get($response->json(), 'candidates.0.content.parts', []);
                foreach ($parts as $part) {
                    $inlineData = $part['inlineData'] ?? $part['inline_data'] ?? null;
                    if (! is_array($inlineData)) {
                        continue;
                    }

                    $base64 = (string) ($inlineData['data'] ?? '');
                    if ($base64 === '') {
                        continue;
                    }

                    $binary = base64_decode($base64, true);
                    if ($binary === false) {
                        continue;
                    }

                    $mimeType = strtolower((string) ($inlineData['mimeType'] ?? $inlineData['mime_type'] ?? 'image/png'));
                    $extension = str_contains($mimeType, 'jpeg') || str_contains($mimeType, 'jpg') ? 'jpg' : 'png';

                    $storedPath = $this->storeImageBinary($campaign, $binary, $extension);
                    if ($storedPath !== null) {
                        return [
                            'path' => $storedPath,
                            'source' => 'gemini',
                            'error' => null,
                        ];
                    }

                    return [
                        'path' => null,
                        'source' => 'none',
                        'error' => 'AI görseli üretildi ancak dosyaya kaydedilemedi.',
                    ];
                }

                $errors[] = "{$model}: yanıtta image verisi yok";
            }

            $errorMessage = 'Gemini görsel üretimi başarısız.';
            if (! empty($errors)) {
                $errorMessage .= ' ' . implode(' | ', $errors);
            }

            return $this->fallbackImageResult($campaign, $errorMessage);
        }

        return $this->fallbackImageResult($campaign, 'Gemini API anahtarı tanımlı değil.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonFromModelText(string $rawText): ?array
    {
        $trimmed = trim(str_replace(['```json', '```'], '', $rawText));
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $first = strpos($trimmed, '{');
        $last = strrpos($trimmed, '}');
        if ($first === false || $last === false || $last <= $first) {
            return null;
        }

        $jsonSlice = substr($trimmed, $first, $last - $first + 1);
        $decoded = json_decode($jsonSlice, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array{title:string,message:string,cta_text:string,cta_url:string,visual_prompt:string}
     */
    private function fallbackTextSuggestion(AiCelebrationCampaign $campaign, ?string $topicPrompt = null): array
    {
        $basePrompt = trim((string) ($topicPrompt ?: $campaign->topic_prompt));
        $eventName = trim((string) $campaign->event_name);

        $title = Str::limit($eventName . ' Kutlu Olsun', 60, '');
        if ($basePrompt !== '') {
            $title = Str::limit($eventName, 60, '');
        }

        return [
            'title' => $title,
            'message' => 'Grup Talepleri ailesi olarak ' . $eventName . ' gününüzü içtenlikle kutlar, bereketli bir gün dileriz.',
            'cta_text' => 'Talepleri Gör',
            'cta_url' => '/dashboard',
            'visual_prompt' => trim($eventName . ' için mavi-kırmızı tonlarda modern, temiz ve kurumsal kutlama afişi. GrupTalepleri stili.'),
        ];
    }

    private function normalizeCtaUrl(?string $url): ?string
    {
        $value = trim((string) $url);
        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '/'])) {
            return $value;
        }

        return '/' . ltrim($value, '/');
    }

    private function storeImageBinary(AiCelebrationCampaign $campaign, string $binary, string $extension): ?string
    {
        $directory = public_path('uploads/ai-kutlama');
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return null;
        }

        $fileName = sprintf(
            'ai-kutlama-%d-%s-%s.%s',
            $campaign->id,
            now()->format('YmdHis'),
            Str::lower(Str::random(6)),
            $extension
        );
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $fileName;

        if (@file_put_contents($absolutePath, $binary) === false) {
            return null;
        }

        return '/uploads/ai-kutlama/' . $fileName;
    }

    private function storeFallbackSvg(AiCelebrationCampaign $campaign): ?string
    {
        $directory = public_path('uploads/ai-kutlama');
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return null;
        }

        $rawTitle = trim((string) ($campaign->title ?: $campaign->event_name));
        $titleLines = $this->buildSvgTitleLines($rawTitle);
        $lineCount = count($titleLines);
        $fontSize = $lineCount === 1 ? 64 : ($lineCount === 2 ? 56 : 48);
        $lineHeight = $lineCount === 1 ? 74 : ($lineCount === 2 ? 66 : 58);
        $startY = $lineCount === 1 ? 300 : ($lineCount === 2 ? 270 : 245);

        $tspans = [];
        foreach ($titleLines as $index => $line) {
            $safeLine = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            if ($index === 0) {
                $tspans[] = '<tspan x="80" dy="0">' . $safeLine . '</tspan>';
                continue;
            }

            $tspans[] = '<tspan x="80" dy="' . $lineHeight . '">' . $safeLine . '</tspan>';
        }

        $titleMarkup = implode('', $tspans);
        $safeSub = htmlspecialchars('GrupTalepleri', ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="628" viewBox="0 0 1200 628">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1a1a2e"/>
      <stop offset="100%" stop-color="#0f3460"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="628" fill="url(#bg)"/>
  <circle cx="1020" cy="120" r="220" fill="#e94560" opacity="0.15"/>
  <circle cx="120" cy="560" r="180" fill="#4ea8ff" opacity="0.18"/>
  <text x="80" y="{$startY}" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="{$fontSize}" font-weight="700">{$titleMarkup}</text>
  <text x="80" y="360" fill="#ff6f8a" font-family="Segoe UI, Arial, sans-serif" font-size="44" font-weight="600">{$safeSub}</text>
</svg>
SVG;

        $fileName = sprintf(
            'ai-kutlama-%d-%s-%s.svg',
            $campaign->id,
            now()->format('YmdHis'),
            Str::lower(Str::random(6))
        );
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $fileName;

        if (@file_put_contents($absolutePath, $svg) === false) {
            return null;
        }

        return '/uploads/ai-kutlama/' . $fileName;
    }

    /**
     * @return array<int, string>
     */
    private function buildSvgTitleLines(string $title): array
    {
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $title));
        if ($normalized === '') {
            return ['Özel Gün'];
        }

        $wrapped = preg_split('/\n/u', wordwrap($normalized, 24, "\n", true)) ?: [$normalized];
        $wrapped = array_values(array_filter(array_map(static fn ($line) => trim((string) $line), $wrapped)));
        if (empty($wrapped)) {
            return ['Özel Gün'];
        }

        $maxLines = 3;
        if (count($wrapped) <= $maxLines) {
            return $wrapped;
        }

        $lines = array_slice($wrapped, 0, $maxLines);
        $last = (string) end($lines);
        $last = rtrim($last, '. ');
        if (mb_strlen($last, 'UTF-8') > 1) {
            $last = mb_substr($last, 0, mb_strlen($last, 'UTF-8') - 1, 'UTF-8');
        }
        $lines[$maxLines - 1] = rtrim($last) . '…';

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function geminiImageModels(): array
    {
        $configuredModel = (string) config('services.gemini.image_model', 'gemini-2.0-flash-preview-image-generation');

        $models = array_filter([
            trim($configuredModel),
            'gemini-2.0-flash-preview-image-generation',
            'gemini-2.0-flash-exp-image-generation',
        ]);

        return array_values(array_unique($models));
    }

    private function extractGeminiError(Response $response): string
    {
        $status = (int) $response->status();
        $payload = $response->json();

        $message = trim((string) data_get($payload, 'error.message', ''));
        $apiStatus = trim((string) data_get($payload, 'error.status', ''));
        $reason = trim((string) data_get($payload, 'error.details.0.reason', ''));

        $parts = ['HTTP ' . $status];
        if ($apiStatus !== '') {
            $parts[] = $apiStatus;
        }
        if ($reason !== '') {
            $parts[] = $reason;
        }
        if ($message !== '') {
            $parts[] = $message;
        } else {
            $parts[] = Str::limit(trim((string) $response->body()), 240);
        }

        return implode(' | ', array_filter($parts));
    }

    /**
     * @return array{path:?string,source:string,error:?string}
     */
    private function fallbackImageResult(AiCelebrationCampaign $campaign, string $errorMessage): array
    {
        $fallbackPath = $this->storeFallbackSvg($campaign);
        if ($fallbackPath !== null) {
            return [
                'path' => $fallbackPath,
                'source' => 'fallback',
                'error' => $errorMessage,
            ];
        }

        return [
            'path' => null,
            'source' => 'none',
            'error' => $errorMessage . ' Fallback da kaydedilemedi.',
        ];
    }
}
