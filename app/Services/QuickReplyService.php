<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\QuickReplyLog;
use App\Models\QuickReplySession;
use App\Models\Request as RequestModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class QuickReplyService
{
    public function createSession(
        User $actor,
        string $rawText,
        ?int $manualAgencyId = null,
        string $membershipMode = QuickReplySession::MEMBERSHIP_AUTO
    ): QuickReplySession {
        $session = QuickReplySession::create([
            'user_id' => $actor->id,
            'raw_text' => trim($rawText),
            'manual_agency_id' => $manualAgencyId,
            'membership_mode' => $membershipMode,
            'status' => QuickReplySession::STATUS_DRAFT,
        ]);

        return $this->analyzeSession($session, $actor);
    }

    public function analyzeSession(QuickReplySession $session, ?User $actor = null): QuickReplySession
    {
        $parsed = $this->parseRawText($session->raw_text);
        $agencyCandidates = $this->matchAgencies($parsed, $session->manual_agency_id);
        $requestCandidates = $this->matchRequests($parsed, $agencyCandidates);
        $resolvedMembership = $this->resolveMembership($session->membership_mode, $agencyCandidates);
        $confidence = $this->resolveConfidence($agencyCandidates, $requestCandidates);
        $selectedRequestId = $this->pickSuggestedRequestId($parsed, $requestCandidates);
        $selectedAgencyId = (int) ($agencyCandidates[0]['agency_id'] ?? 0) ?: null;
        $selectedUserId = (int) ($agencyCandidates[0]['user_id'] ?? 0) ?: null;

        $session->update([
            'parsed_payload' => $parsed,
            'agency_candidates' => $agencyCandidates,
            'request_candidates' => $requestCandidates,
            'resolved_membership' => $resolvedMembership,
            'match_confidence' => $confidence,
            'selected_request_id' => $selectedRequestId,
            'selected_agency_id' => $selectedAgencyId,
            'selected_user_id' => $selectedUserId,
            'requires_manual_review' => $this->requiresManualReview($parsed, $requestCandidates, $confidence),
            'requires_new_account' => $resolvedMembership === QuickReplySession::MEMBERSHIP_NON_MEMBER,
            'status' => QuickReplySession::STATUS_NEEDS_REVIEW,
        ]);

        $this->addLog($session, $actor?->id, 'session.analyzed', [
            'confidence' => $confidence,
            'resolved_membership' => $resolvedMembership,
            'agency_candidate_count' => count($agencyCandidates),
            'request_candidate_count' => count($requestCandidates),
            'selected_request_id' => $selectedRequestId,
        ]);

        return $session->fresh();
    }

    public function addLog(QuickReplySession $session, ?int $userId, string $action, array $context = [], string $level = 'info'): QuickReplyLog
    {
        return QuickReplyLog::create([
            'session_id' => $session->id,
            'user_id' => $userId,
            'level' => $level,
            'action' => $action,
            'context' => $context,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function parseRawText(string $rawText): array
    {
        $text = trim($rawText);
        $lines = preg_split('/\R/u', $text) ?: [];
        $nonEmptyLines = array_values(array_filter(array_map('trim', $lines), static fn ($line) => $line !== ''));

        $detected = [
            'agency_name' => $this->detectAgencyNameFromLines($nonEmptyLines),
            'pax' => $this->extractPax($text),
            'airline' => $this->extractAirline($text),
            'price_per_pax' => $this->extractPricePerPax($text),
            'currency' => $this->extractCurrency($text),
            'gtpnr' => $this->extractGtpnr($text),
            'option_date' => null,
            'option_time' => null,
            'from_iata' => null,
            'to_iata' => null,
            'departure_date' => null,
            'return_date' => null,
            'flight_lines' => [],
            'raw_summary' => mb_substr($text, 0, 2000),
            'missing_fields' => [],
            'parser_sources' => [
                'deterministic' => true,
                'gemini' => false,
            ],
        ];

        $flightLines = $this->extractFlightLines($text);
        if (! empty($flightLines)) {
            $detected['flight_lines'] = $flightLines;
            $detected['from_iata'] = $flightLines[0]['from_iata'] ?? null;
            $detected['to_iata'] = $flightLines[0]['to_iata'] ?? null;
            $detected['departure_date'] = $flightLines[0]['departure_date'] ?? null;
            if (isset($flightLines[1]['departure_date'])) {
                $detected['return_date'] = $flightLines[1]['departure_date'];
            }
        } else {
            $route = $this->extractRoute($text);
            $detected['from_iata'] = $route['from_iata'];
            $detected['to_iata'] = $route['to_iata'];
        }

        $option = $this->extractOptionDateTime($text);
        $detected['option_date'] = $option['date'];
        $detected['option_time'] = $option['time'];

        $ai = $this->parseWithGemini($text);
        if (is_array($ai)) {
            $detected = $this->mergeMissingFromAi($detected, $ai);
            $detected['parser_sources']['gemini'] = true;
        }

        $detected['missing_fields'] = $this->detectMissingFields($detected);

        return $detected;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<int, array<string, mixed>>
     */
    public function matchAgencies(array $parsed, ?int $manualAgencyId = null): array
    {
        if ($manualAgencyId) {
            $agency = Agency::with('user')->find($manualAgencyId);
            if ($agency) {
                return [[
                    'agency_id' => $agency->id,
                    'user_id' => $agency->user_id,
                    'agency_name' => $agency->company_title ?: $agency->tourism_title ?: ('Acente #' . $agency->id),
                    'user_name' => $agency->user?->name,
                    'phone' => $agency->phone ?: $agency->user?->phone,
                    'email' => $agency->email ?: $agency->user?->email,
                    'score' => 100.0,
                    'match_type' => 'manual',
                ]];
            }
        }

        $needle = $this->normalizeText((string) ($parsed['agency_name'] ?? ''));
        if ($needle === '') {
            return [];
        }

        $agencies = Agency::with('user')
            ->select(['id', 'user_id', 'company_title', 'tourism_title', 'contact_name', 'phone', 'email', 'is_active'])
            ->limit(500)
            ->get();

        $candidates = [];
        foreach ($agencies as $agency) {
            $score = $this->agencySimilarityScore($needle, $agency);
            if ($score < 35) {
                continue;
            }

            $candidates[] = [
                'agency_id' => $agency->id,
                'user_id' => $agency->user_id,
                'agency_name' => $agency->company_title ?: $agency->tourism_title ?: ('Acente #' . $agency->id),
                'user_name' => $agency->user?->name,
                'phone' => $agency->phone ?: $agency->user?->phone,
                'email' => $agency->email ?: $agency->user?->email,
                'score' => round($score, 2),
                'match_type' => $score >= 90 ? 'exact' : 'similar',
            ];
        }

        usort($candidates, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($candidates, 0, 8);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array<int, array<string, mixed>>  $agencyCandidates
     * @return array<int, array<string, mixed>>
     */
    public function matchRequests(array $parsed, array $agencyCandidates): array
    {
        $gtpnr = strtoupper(trim((string) ($parsed['gtpnr'] ?? '')));
        if ($gtpnr !== '') {
            $direct = RequestModel::with(['segments', 'user'])->whereRaw('UPPER(gtpnr) = ?', [$gtpnr])->first();
            if ($direct) {
                return [[
                    'request_id' => $direct->id,
                    'gtpnr' => $direct->gtpnr,
                    'agency_name' => $direct->agency_name,
                    'status' => $direct->status,
                    'pax_total' => $direct->pax_total,
                    'from_iata' => $direct->segments->first()?->from_iata,
                    'to_iata' => $direct->segments->first()?->to_iata,
                    'departure_date' => $direct->segments->first()?->departure_date,
                    'score' => 100.0,
                    'match_type' => 'gtpnr',
                ]];
            }
        }

        $query = RequestModel::query()
            ->with(['segments', 'user'])
            ->whereIn('status', [
                RequestModel::STATUS_BEKLEMEDE,
                RequestModel::STATUS_ISLEMDE,
                RequestModel::STATUS_FIYATLANDIRILDI,
                RequestModel::STATUS_DEPOZITODA,
            ])
            ->orderByDesc('id')
            ->limit(350);

        $bestAgencyUserId = (int) ($agencyCandidates[0]['user_id'] ?? 0);
        if ($bestAgencyUserId > 0) {
            $query->where(function ($builder) use ($bestAgencyUserId, $parsed): void {
                $builder->where('user_id', $bestAgencyUserId);
                if (! empty($parsed['agency_name'])) {
                    $builder->orWhere('agency_name', 'like', '%' . trim((string) $parsed['agency_name']) . '%');
                }
            });
        }

        $requests = $query->get();
        $candidates = [];
        foreach ($requests as $request) {
            $score = $this->requestSimilarityScore($request, $parsed, $agencyCandidates);
            if ($score < 20) {
                continue;
            }

            $firstSeg = $request->segments->first();
            $candidates[] = [
                'request_id' => $request->id,
                'gtpnr' => $request->gtpnr,
                'agency_name' => $request->agency_name,
                'status' => $request->status,
                'pax_total' => $request->pax_total,
                'from_iata' => $firstSeg?->from_iata,
                'to_iata' => $firstSeg?->to_iata,
                'departure_date' => $firstSeg?->departure_date,
                'score' => round($score, 2),
                'match_type' => $score >= 80 ? 'strong' : 'candidate',
            ];
        }

        usort($candidates, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($candidates, 0, 10);
    }

    private function pickSuggestedRequestId(array $parsed, array $requestCandidates): ?int
    {
        if (! empty($parsed['gtpnr']) && ! empty($requestCandidates[0]['request_id'])) {
            return (int) $requestCandidates[0]['request_id'];
        }

        $best = $requestCandidates[0] ?? null;
        $second = $requestCandidates[1] ?? null;
        if (! $best) {
            return null;
        }

        $bestScore = (float) ($best['score'] ?? 0);
        $secondScore = (float) ($second['score'] ?? 0);

        if ($bestScore >= 85 && ($bestScore - $secondScore) >= 10) {
            return (int) $best['request_id'];
        }

        return null;
    }

    private function resolveMembership(string $mode, array $agencyCandidates): string
    {
        if ($mode === QuickReplySession::MEMBERSHIP_MEMBER) {
            return QuickReplySession::MEMBERSHIP_MEMBER;
        }
        if ($mode === QuickReplySession::MEMBERSHIP_NON_MEMBER) {
            return QuickReplySession::MEMBERSHIP_NON_MEMBER;
        }

        $best = (float) ($agencyCandidates[0]['score'] ?? 0);
        if ($best >= 70) {
            return QuickReplySession::MEMBERSHIP_MEMBER;
        }
        if (empty($agencyCandidates)) {
            return QuickReplySession::MEMBERSHIP_NON_MEMBER;
        }

        return QuickReplySession::MEMBERSHIP_UNKNOWN;
    }

    private function resolveConfidence(array $agencyCandidates, array $requestCandidates): float
    {
        $agency = (float) ($agencyCandidates[0]['score'] ?? 0);
        $request = (float) ($requestCandidates[0]['score'] ?? 0);

        if ($agency === 0.0 && $request === 0.0) {
            return 0.0;
        }

        return round(($agency * 0.35) + ($request * 0.65), 2);
    }

    private function requiresManualReview(array $parsed, array $requestCandidates, float $confidence): bool
    {
        if ($confidence < 70) {
            return true;
        }

        if (empty($requestCandidates)) {
            return true;
        }

        if (! empty($parsed['missing_fields'])) {
            $required = ['agency_name', 'pax', 'from_iata', 'to_iata'];
            foreach ($required as $field) {
                if (in_array($field, $parsed['missing_fields'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function detectMissingFields(array $detected): array
    {
        $required = ['agency_name', 'pax', 'from_iata', 'to_iata', 'price_per_pax'];
        $missing = [];
        foreach ($required as $field) {
            if (Arr::get($detected, $field) === null || Arr::get($detected, $field) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function detectAgencyNameFromLines(array $lines): ?string
    {
        foreach ($lines as $line) {
            $clean = trim((string) $line);
            if ($clean === '' || mb_strlen($clean) > 80) {
                continue;
            }
            if (preg_match('/\d/', $clean) === 1) {
                continue;
            }
            if (preg_match('/^(vf|tk|pc|xh|ajet)\b/i', $clean) === 1) {
                continue;
            }

            return $clean;
        }

        return null;
    }

    private function extractPax(string $text): ?int
    {
        if (preg_match('/(\d{1,3})\s*(?:pax|kişi|kisi|yolcu)\b/iu', $text, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractAirline(string $text): ?string
    {
        if (preg_match('/\b(AJET|THY|TURKISH AIRLINES|PEGASUS|SUNEXPRESS|ANADOLUJET|CORONDON|CORANDON|CORONDON|PC|TK|VF)\b/iu', $text, $matches) === 1) {
            return strtoupper(trim((string) $matches[1]));
        }

        return null;
    }

    private function extractPricePerPax(string $text): ?float
    {
        if (preg_match('/(?:kişi\s*başı|kisi\s*basi|pax\s*price|fiyat)\s*[:\-]?\s*([\d\.,]+)/iu', $text, $matches) === 1) {
            return $this->toFloat($matches[1]);
        }

        return null;
    }

    private function extractCurrency(string $text): ?string
    {
        if (preg_match('/\b(TRY|TL|USD|EUR)\b/i', $text, $matches) === 1) {
            $v = strtoupper((string) $matches[1]);
            return $v === 'TL' ? 'TRY' : $v;
        }

        return null;
    }

    private function extractGtpnr(string $text): ?string
    {
        preg_match_all('/\b([A-Z]{2,3}-[A-Z0-9]{6}|[A-Z0-9]{6})\b/u', strtoupper($text), $matches);
        $tokens = array_values(array_unique($matches[1] ?? []));
        if (empty($tokens)) {
            return null;
        }

        foreach ($tokens as $token) {
            $exists = RequestModel::query()
                ->whereRaw('UPPER(gtpnr) = ?', [strtoupper($token)])
                ->exists();
            if ($exists) {
                return strtoupper($token);
            }
        }

        // Veritabaninda yoksa sadece tireli formati aday kabul et, aksi halde null don.
        foreach ($tokens as $token) {
            if (str_contains($token, '-')) {
                return strtoupper($token);
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractFlightLines(string $text): array
    {
        $pattern = '/\b([A-Z]{2}\d{2,4})\s+(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4})\s+([A-Z]{3})\s*[-–]\s*([A-Z]{3})\s+(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})/u';
        preg_match_all($pattern, strtoupper($text), $matches, PREG_SET_ORDER);

        $rows = [];
        foreach ($matches as $row) {
            $date = $this->parseFlexibleDate($row[2]);
            $rows[] = [
                'flight_number' => trim($row[1]),
                'departure_date' => $date?->format('Y-m-d'),
                'from_iata' => trim($row[3]),
                'to_iata' => trim($row[4]),
                'departure_time' => trim($row[5]),
                'arrival_time' => trim($row[6]),
            ];
        }

        return $rows;
    }

    /**
     * @return array{from_iata:?string,to_iata:?string}
     */
    private function extractRoute(string $text): array
    {
        if (preg_match('/\b([A-Z]{3})\s*[-–]\s*([A-Z]{3})\b/u', strtoupper($text), $matches) === 1) {
            return ['from_iata' => $matches[1], 'to_iata' => $matches[2]];
        }

        return ['from_iata' => null, 'to_iata' => null];
    }

    /**
     * @return array{date:?string,time:?string}
     */
    private function extractOptionDateTime(string $text): array
    {
        $date = null;
        $time = null;

        if (preg_match('/opsiyon[^\n\r]{0,80}?(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4}|\d{1,2}\s+[a-zçğıöşü]+\s+\d{4})/iu', $text, $dateMatch) === 1) {
            $parsedDate = $this->parseFlexibleDate($dateMatch[1]);
            $date = $parsedDate?->format('Y-m-d');
        }

        if (preg_match('/opsiyon[^\n\r]{0,80}?(\d{1,2}:\d{2})/iu', $text, $timeMatch) === 1) {
            $time = $timeMatch[1];
        }

        return ['date' => $date, 'time' => $time];
    }

    private function parseFlexibleDate(string $value): ?Carbon
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        $value = str_replace(['.', '/'], '-', $value);

        $monthMap = [
            'ocak' => '01', 'şubat' => '02', 'subat' => '02', 'mart' => '03', 'nisan' => '04',
            'mayıs' => '05', 'mayis' => '05', 'haziran' => '06', 'temmuz' => '07', 'ağustos' => '08',
            'agustos' => '08', 'eylül' => '09', 'eylul' => '09', 'ekim' => '10', 'kasım' => '11',
            'kasim' => '11', 'aralık' => '12', 'aralik' => '12',
        ];

        foreach ($monthMap as $name => $month) {
            if (str_contains($value, $name)) {
                $value = preg_replace('/\b' . preg_quote($name, '/') . '\b/u', $month, $value);
                break;
            }
        }

        $formats = ['d-m-Y', 'd-m-y', 'Y-m-d'];
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value, config('app.timezone'));
                if ($date !== false) {
                    return $date;
                }
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    private function toFloat(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $normalized = str_replace([' ', "\u{00A0}"], '', $value);
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function parseWithGemini(string $text): ?array
    {
        $apiKey = (string) config('services.gemini.key');
        if ($apiKey === '') {
            return null;
        }

        $model = (string) config('services.gemini.text_model', 'gemini-2.5-flash');
        $prompt = implode("\n", [
            'Asagidaki havayolu teklif metnini JSON olarak ayrıştır.',
            'Sadece JSON don. Aciklama yazma.',
            'Eksik alanlar icin null kullan, veri uydurma.',
            'JSON schema:',
            '{"agency_name":null,"gtpnr":null,"pax":null,"airline":null,"from_iata":null,"to_iata":null,"departure_date":null,"return_date":null,"price_per_pax":null,"currency":null,"option_date":null,"option_time":null,"flight_lines":[]}',
            'Metin:',
            $text,
        ]);

        try {
            $response = Http::timeout(40)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['thinkingConfig' => ['thinkingBudget' => 0]],
                ]
            );
        } catch (\Throwable) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $raw = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        return $this->decodeJsonPayload($raw);
    }

    private function decodeJsonPayload(string $raw): ?array
    {
        $clean = trim(str_replace(['```json', '```'], '', $raw));
        if ($clean === '') {
            return null;
        }

        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $first = strpos($clean, '{');
        $last = strrpos($clean, '}');
        if ($first === false || $last === false || $last <= $first) {
            return null;
        }

        $slice = substr($clean, $first, ($last - $first + 1));
        $decoded = json_decode($slice, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function mergeMissingFromAi(array $detected, array $ai): array
    {
        $fields = [
            'agency_name', 'gtpnr', 'pax', 'airline', 'from_iata', 'to_iata',
            'departure_date', 'return_date', 'price_per_pax', 'currency', 'option_date', 'option_time',
        ];

        foreach ($fields as $field) {
            if (($detected[$field] ?? null) !== null && ($detected[$field] ?? '') !== '') {
                continue;
            }
            if (! array_key_exists($field, $ai)) {
                continue;
            }
            $detected[$field] = $ai[$field];
        }

        if (empty($detected['flight_lines']) && is_array($ai['flight_lines'] ?? null)) {
            $detected['flight_lines'] = $ai['flight_lines'];
        }

        return $detected;
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
            'Ç' => 'c', 'Ğ' => 'g', 'İ' => 'i', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u',
        ]);
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function agencySimilarityScore(string $needle, Agency $agency): float
    {
        $candidates = array_filter([
            $agency->company_title,
            $agency->tourism_title,
            $agency->contact_name,
            $agency->user?->name,
        ]);

        $best = 0.0;
        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeText((string) $candidate);
            if ($normalized === '') {
                continue;
            }

            if ($normalized === $needle) {
                return 100.0;
            }

            similar_text($needle, $normalized, $percent);
            $best = max($best, (float) $percent);
        }

        return $best;
    }

    private function requestSimilarityScore(RequestModel $request, array $parsed, array $agencyCandidates): float
    {
        $score = 0.0;

        $bestAgencyUserId = (int) ($agencyCandidates[0]['user_id'] ?? 0);
        if ($bestAgencyUserId > 0 && (int) $request->user_id === $bestAgencyUserId) {
            $score += 35;
        }

        $pax = (int) ($parsed['pax'] ?? 0);
        if ($pax > 0) {
            $diff = abs((int) $request->pax_total - $pax);
            if ($diff === 0) {
                $score += 20;
            } elseif ($diff <= 2) {
                $score += 10;
            }
        }

        $firstSeg = $request->segments->first();
        $parsedFrom = strtoupper((string) ($parsed['from_iata'] ?? ''));
        $parsedTo = strtoupper((string) ($parsed['to_iata'] ?? ''));
        if ($firstSeg && $parsedFrom !== '' && strtoupper((string) $firstSeg->from_iata) === $parsedFrom) {
            $score += 10;
        }
        if ($firstSeg && $parsedTo !== '' && strtoupper((string) $firstSeg->to_iata) === $parsedTo) {
            $score += 10;
        }

        $parsedDeparture = (string) ($parsed['departure_date'] ?? '');
        if ($firstSeg && $parsedDeparture !== '' && $firstSeg->departure_date) {
            $firstDate = Carbon::parse($firstSeg->departure_date);
            try {
                $parsedDate = Carbon::parse($parsedDeparture);
                $days = $firstDate->diffInDays($parsedDate, false);
                if ($days === 0) {
                    $score += 15;
                } elseif (abs($days) <= 1) {
                    $score += 8;
                }
            } catch (\Throwable) {
            }
        }

        $airline = strtoupper((string) ($parsed['airline'] ?? ''));
        if ($airline !== '') {
            $preferred = strtoupper((string) ($request->preferred_airline ?? ''));
            if ($preferred !== '' && str_contains($preferred, $airline)) {
                $score += 5;
            }
        }

        return min($score, 100.0);
    }
}
