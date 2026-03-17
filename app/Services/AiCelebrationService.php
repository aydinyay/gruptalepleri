<?php

namespace App\Services;

use App\Models\AiCelebrationCampaign;
use App\Models\AiCelebrationUserState;
use App\Models\SistemAyar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

            $needsGeneration = $campaign->wasRecentlyCreated
                || blank($campaign->title)
                || blank($campaign->message)
                || blank($campaign->image_path);

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
            'event_name' => (string) ($data['event_name'] ?? 'Ozel Gun'),
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
        $imagePath = $this->generateImage($campaign, $visualPrompt);

        $campaign->fill([
            'title' => (string) ($textData['title'] ?? $campaign->title ?? ''),
            'message' => (string) ($textData['message'] ?? $campaign->message ?? ''),
            'cta_text' => (string) ($textData['cta_text'] ?? $campaign->cta_text ?? 'Detaylari Gor'),
            'cta_url' => $this->normalizeCtaUrl((string) ($textData['cta_url'] ?? $campaign->cta_url ?? '/dashboard')),
            'visual_prompt' => $visualPrompt,
            'ai_payload' => [
                'text' => $textData,
                'generated_at' => now()->toISOString(),
            ],
            'image_path' => $imagePath ?? $campaign->image_path,
            'is_ai_generated' => true,
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
        if ($apiKey === '') {
            return $fallback;
        }

        $prompt = implode("\n", [
            'Sen GrupTalepleri.com B2B platformu icin kutlama icerigi ureten bir asistansin.',
            'Sadece JSON dondur. Markdown veya aciklama yazma.',
            'JSON semasi:',
            '{"title":"", "message":"", "cta_text":"", "cta_url":"", "visual_prompt":""}',
            'Kurallar:',
            '- title en fazla 60 karakter',
            '- message en fazla 240 karakter',
            '- Metin sade, kurumsal ve pozitif olsun',
            '- Hicbir sekilde fiyat, indirim, spam, siyasi ifade olmasin',
            '- cta_url mutlak URL olmak zorunda degil, /dashboard kullanilabilir',
            'Etkinlik adi: ' . $campaign->event_name,
            'Kategori: ' . $campaign->category,
            'Etkinlik tarihi: ' . ($campaign->event_date?->format('Y-m-d') ?? '-'),
            'Ek konu talebi: ' . trim((string) ($topicPrompt ?: $campaign->topic_prompt ?: '-')),
        ]);

        try {
            $response = Http::timeout(45)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
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
            Log::warning('AI kutlama metin uretimi basarisiz HTTP: ' . $response->status());
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

    private function generateImage(AiCelebrationCampaign $campaign, string $visualPrompt): ?string
    {
        $prompt = trim($visualPrompt) !== '' ? trim($visualPrompt) : ('GrupTalepleri icin ' . $campaign->event_name . ' kutlama gorseli');
        $apiKey = (string) config('services.gemini.key');

        if ($apiKey !== '') {
            try {
                $response = Http::timeout(60)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-preview-image-generation:generateContent?key={$apiKey}",
                    [
                        'contents' => [[
                            'parts' => [[
                                'text' => $prompt . '. Metin yazma, sadece gorsel uret.',
                            ]],
                        ]],
                        'generationConfig' => [
                            'responseModalities' => ['TEXT', 'IMAGE'],
                        ],
                    ]
                );

                if ($response->ok()) {
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

                        return $this->storeImageBinary($campaign, $binary, $extension);
                    }
                }
            } catch (\Throwable $exception) {
                Log::warning('AI kutlama gorsel uretimi fallback: ' . $exception->getMessage());
            }
        }

        return $this->storeFallbackSvg($campaign);
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
            'message' => 'Grup Talepleri ailesi olarak ' . $eventName . ' gununuzu ictenlikle kutlar, bereketli bir gun dileriz.',
            'cta_text' => 'Talepleri Gor',
            'cta_url' => '/dashboard',
            'visual_prompt' => trim($eventName . ' icin mavi-kirmizi tonlarda modern, temiz ve kurumsal kutlama afisi. GrupTalepleri stili.'),
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

        $safeTitle = htmlspecialchars(Str::limit($campaign->event_name, 40, ''), ENT_QUOTES, 'UTF-8');
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
  <text x="80" y="300" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="64" font-weight="700">{$safeTitle}</text>
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
}

