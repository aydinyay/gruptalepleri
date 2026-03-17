<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\QuickReplySession;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Services\SmsService;
use App\Services\QuickReplyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class QuickReplyController extends Controller
{
    private function assertAuthorized(): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin'], true), 403);
    }

    public function index(Request $request)
    {
        $this->assertAuthorized();

        $activeSession = null;
        if ($request->filled('session')) {
            $activeSession = QuickReplySession::with(['logs.user', 'selectedRequest.segments', 'selectedAgency.user'])
                ->find((int) $request->integer('session'));
        }

        $recentSessions = QuickReplySession::query()
            ->with(['selectedRequest', 'selectedAgency'])
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $membershipModes = [
            QuickReplySession::MEMBERSHIP_AUTO => 'Otomatik belirle',
            QuickReplySession::MEMBERSHIP_MEMBER => 'Üye Acenteye Yanıt',
            QuickReplySession::MEMBERSHIP_NON_MEMBER => 'Üye Olmayan Acenteye Yanıt',
        ];

        $statuses = [
            RequestModel::STATUS_BEKLEMEDE,
            RequestModel::STATUS_ISLEMDE,
            RequestModel::STATUS_FIYATLANDIRILDI,
            RequestModel::STATUS_DEPOZITODA,
        ];

        return view('admin.quick-reply.index', [
            'activeSession' => $activeSession,
            'recentSessions' => $recentSessions,
            'membershipModes' => $membershipModes,
            'activeRequestStatuses' => $statuses,
            'routeNamePrefix' => auth()->user()->role === 'superadmin' ? 'superadmin.quick-reply' : 'admin.quick-reply',
        ]);
    }

    public function parse(Request $request, QuickReplyService $quickReplyService)
    {
        $this->assertAuthorized();

        $validated = $request->validate([
            'raw_text' => 'required|string|min:10|max:20000',
            'manual_agency_id' => 'nullable|integer|exists:agencies,id',
            'membership_mode' => 'nullable|in:auto,member,non_member',
        ]);

        $session = $quickReplyService->createSession(
            auth()->user(),
            $validated['raw_text'],
            isset($validated['manual_agency_id']) ? (int) $validated['manual_agency_id'] : null,
            (string) ($validated['membership_mode'] ?? QuickReplySession::MEMBERSHIP_AUTO)
        );

        $quickReplyService->addLog($session, auth()->id(), 'session.created', [
            'manual_agency_id' => $session->manual_agency_id,
            'membership_mode' => $session->membership_mode,
        ]);

        return redirect()
            ->route($this->indexRouteName(), ['session' => $session->id])
            ->with('success', 'Hızlı Yanıtla metni ayrıştırıldı. Önizleme ekranından kontrol edip onaylayabilirsiniz.');
    }

    public function saveReview(Request $request, QuickReplySession $session, QuickReplyService $quickReplyService)
    {
        $this->assertAuthorized();
        $this->assertSessionAccess($session);

        $validated = $request->validate([
            'membership_mode' => 'required|in:auto,member,non_member',
            'selected_request_id' => 'nullable|integer|exists:requests,id',
            'selected_agency_id' => 'nullable|integer|exists:agencies,id',
            'selected_user_id' => 'nullable|integer|exists:users,id',
            'edited_payload' => 'nullable|array',
            'edited_payload.agency_name' => 'nullable|string|max:255',
            'edited_payload.gtpnr' => 'nullable|string|max:20',
            'edited_payload.pax' => 'nullable|integer|min:1|max:999',
            'edited_payload.airline' => 'nullable|string|max:100',
            'edited_payload.from_iata' => 'nullable|string|max:10',
            'edited_payload.to_iata' => 'nullable|string|max:10',
            'edited_payload.departure_date' => 'nullable|date',
            'edited_payload.return_date' => 'nullable|date',
            'edited_payload.price_per_pax' => 'nullable|numeric|min:0',
            'edited_payload.currency' => 'nullable|string|max:10',
            'edited_payload.option_date' => 'nullable|date',
            'edited_payload.option_time' => 'nullable|string|max:10',
            'edited_payload.offer_text' => 'nullable|string|max:5000',
            'edited_payload.supplier_reference' => 'nullable|string|max:255',
        ]);

        $payload = $validated['edited_payload'] ?? [];
        $session->update([
            'membership_mode' => $validated['membership_mode'],
            'selected_request_id' => $validated['selected_request_id'] ?? null,
            'selected_agency_id' => $validated['selected_agency_id'] ?? null,
            'selected_user_id' => $validated['selected_user_id'] ?? null,
            'edited_payload' => $payload ?: null,
        ]);

        $quickReplyService->addLog($session, auth()->id(), 'session.review_saved', [
            'selected_request_id' => $session->selected_request_id,
            'selected_agency_id' => $session->selected_agency_id,
            'selected_user_id' => $session->selected_user_id,
            'edited_fields' => array_keys($payload),
        ]);

        return redirect()
            ->route($this->indexRouteName(), ['session' => $session->id])
            ->with('success', 'Önizleme düzenlemeleri kaydedildi.');
    }

    public function agencySearch(Request $request)
    {
        $this->assertAuthorized();

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        $items = Agency::query()
            ->with('user')
            ->where(function ($builder) use ($q): void {
                $builder->where('company_title', 'like', "%{$q}%")
                    ->orWhere('tourism_title', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get()
            ->map(static function (Agency $agency): array {
                $name = $agency->company_title ?: $agency->tourism_title ?: ('Acente #' . $agency->id);
                return [
                    'id' => $agency->id,
                    'name' => $name,
                    'user_id' => $agency->user_id,
                    'user_name' => $agency->user?->name,
                    'phone' => $agency->phone ?: $agency->user?->phone,
                    'email' => $agency->email ?: $agency->user?->email,
                ];
            })
            ->values();

        return response()->json(['items' => $items]);
    }

    public function confirm(Request $request, QuickReplySession $session, QuickReplyService $quickReplyService): RedirectResponse
    {
        $this->assertAuthorized();
        $this->assertSessionAccess($session);

        $validated = $request->validate([
            'membership_mode' => 'required|in:auto,member,non_member',
            'selected_request_id' => 'required|integer|exists:requests,id',
            'selected_agency_id' => 'nullable|integer|exists:agencies,id',
            'selected_user_id' => 'nullable|integer|exists:users,id',
            'offer.airline' => 'nullable|string|max:100',
            'offer.airline_pnr' => 'nullable|string|max:100',
            'offer.flight_number' => 'nullable|string|max:50',
            'offer.flight_departure_time' => 'nullable|date_format:H:i',
            'offer.flight_arrival_time' => 'nullable|date_format:H:i',
            'offer.baggage_kg' => 'nullable|integer|min:0|max:99',
            'offer.pax_confirmed' => 'nullable|integer|min:1|max:999',
            'offer.currency' => 'nullable|string|max:10',
            'offer.price_per_pax' => 'nullable|numeric|min:0',
            'offer.cost_price' => 'nullable|numeric|min:0',
            'offer.deposit_rate' => 'nullable|numeric|min:0|max:100',
            'offer.deposit_amount' => 'nullable|numeric|min:0',
            'offer.option_date' => 'nullable|date',
            'offer.option_time' => 'nullable|date_format:H:i',
            'offer.offer_text' => 'nullable|string|max:5000',
            'offer.supplier_reference' => 'nullable|string|max:255',
            'new_account.agency_name' => 'nullable|string|max:255',
            'new_account.contact_name' => 'nullable|string|max:255',
            'new_account.phone' => 'nullable|string|max:30',
            'new_account.email' => 'nullable|email|max:255',
        ]);

        $requestModel = RequestModel::query()
            ->with(['user.agency'])
            ->findOrFail((int) $validated['selected_request_id']);

        $offer = (array) ($validated['offer'] ?? []);
        $parsed = (array) ($session->edited_payload ?: $session->parsed_payload ?: []);
        $offer = $this->hydrateOfferPayload($offer, $parsed);

        $membershipMode = $validated['membership_mode'];
        $resolvedUser = null;
        $resolvedAgency = null;
        $isNewAccountCreated = false;

        if ($membershipMode === 'member' || $membershipMode === 'auto') {
            [$resolvedUser, $resolvedAgency] = $this->resolveExistingMember(
                $requestModel,
                isset($validated['selected_user_id']) ? (int) $validated['selected_user_id'] : null,
                isset($validated['selected_agency_id']) ? (int) $validated['selected_agency_id'] : null
            );
        }

        if ($membershipMode === 'non_member') {
            [$resolvedUser, $resolvedAgency, $isNewAccountCreated] = $this->resolveNonMember(
                $requestModel,
                $validated['new_account'] ?? [],
                isset($validated['selected_user_id']) ? (int) $validated['selected_user_id'] : null,
                isset($validated['selected_agency_id']) ? (int) $validated['selected_agency_id'] : null
            );
        }

        if (! $resolvedUser) {
            return redirect()
                ->route($this->indexRouteName(), ['session' => $session->id])
                ->with('error', 'Kayıt için kullanıcı eşleşmesi bulunamadı. Üye tipini ve acente seçimini kontrol edin.');
        }

        $this->attachRequestToUser($requestModel, $resolvedUser, $resolvedAgency);

        $createdOfferId = $this->storeOfferThroughExistingFlow($requestModel, $session, $offer);
        if (! $createdOfferId) {
            $quickReplyService->addLog($session, auth()->id(), 'session.confirm_failed', [
                'request_id' => $requestModel->id,
                'reason' => 'offer_not_created',
            ], 'error');

            return redirect()
                ->route($this->indexRouteName(), ['session' => $session->id])
                ->with('error', 'Teklif kaydı oluşturulamadı. Lütfen verileri kontrol edip tekrar deneyin.');
        }

        if ($isNewAccountCreated) {
            $this->sendOnboardingAccess($resolvedUser, $resolvedAgency, $requestModel);
            $quickReplyService->addLog($session, auth()->id(), 'session.onboarding_sent', [
                'user_id' => $resolvedUser->id,
                'agency_id' => $resolvedAgency?->id,
                'email' => $resolvedUser->email,
                'phone' => $resolvedUser->phone,
            ]);
        }

        $session->update([
            'status' => QuickReplySession::STATUS_CONFIRMED,
            'selected_request_id' => $requestModel->id,
            'selected_user_id' => $resolvedUser->id,
            'selected_agency_id' => $resolvedAgency?->id,
            'membership_mode' => $membershipMode,
            'resolved_membership' => $membershipMode === 'non_member'
                ? QuickReplySession::MEMBERSHIP_NON_MEMBER
                : QuickReplySession::MEMBERSHIP_MEMBER,
            'confirmed_by_user_id' => auth()->id(),
            'confirmed_at' => now(),
            'final_offer_id' => $createdOfferId,
            'requires_manual_review' => false,
            'confirmation_summary' => sprintf(
                'GTPNR %s için teklif kaydedildi. Kullanıcı: %s (%s).',
                $requestModel->gtpnr,
                $resolvedUser->name,
                $resolvedUser->email
            ),
        ]);

        $quickReplyService->addLog($session, auth()->id(), 'session.confirmed', [
            'request_id' => $requestModel->id,
            'gtpnr' => $requestModel->gtpnr,
            'offer_id' => $createdOfferId,
            'resolved_user_id' => $resolvedUser->id,
            'resolved_agency_id' => $resolvedAgency?->id,
            'membership_mode' => $membershipMode,
        ]);

        return redirect()
            ->route($this->indexRouteName(), ['session' => $session->id])
            ->with('success', 'Hızlı Yanıtla onayı tamamlandı. Teklif standart akış ile kaydedildi.');
    }

    private function assertSessionAccess(QuickReplySession $session): void
    {
        if (auth()->user()->role === 'superadmin') {
            return;
        }

        abort_unless($session->user_id === auth()->id(), 403);
    }

    private function indexRouteName(): string
    {
        return auth()->user()->role === 'superadmin'
            ? 'superadmin.quick-reply.index'
            : 'admin.quick-reply.index';
    }

    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function hydrateOfferPayload(array $offer, array $parsed): array
    {
        return [
            'airline' => $offer['airline'] ?? ($parsed['airline'] ?? null),
            'airline_pnr' => $offer['airline_pnr'] ?? null,
            'flight_number' => $offer['flight_number'] ?? ($parsed['flight_lines'][0]['flight_number'] ?? null),
            'flight_departure_time' => $offer['flight_departure_time'] ?? ($parsed['flight_lines'][0]['departure_time'] ?? null),
            'flight_arrival_time' => $offer['flight_arrival_time'] ?? ($parsed['flight_lines'][0]['arrival_time'] ?? null),
            'baggage_kg' => $offer['baggage_kg'] ?? null,
            'pax_confirmed' => $offer['pax_confirmed'] ?? ($parsed['pax'] ?? null),
            'currency' => $offer['currency'] ?? ($parsed['currency'] ?? 'TRY'),
            'price_per_pax' => $offer['price_per_pax'] ?? ($parsed['price_per_pax'] ?? null),
            'cost_price' => $offer['cost_price'] ?? null,
            'deposit_rate' => $offer['deposit_rate'] ?? null,
            'deposit_amount' => $offer['deposit_amount'] ?? null,
            'option_date' => $offer['option_date'] ?? ($parsed['option_date'] ?? null),
            'option_time' => $offer['option_time'] ?? ($parsed['option_time'] ?? null),
            'offer_text' => $offer['offer_text'] ?? null,
            'supplier_reference' => $offer['supplier_reference'] ?? null,
        ];
    }

    /**
     * @return array{0:?User,1:?Agency}
     */
    private function resolveExistingMember(RequestModel $requestModel, ?int $selectedUserId, ?int $selectedAgencyId): array
    {
        if ($selectedUserId) {
            $user = User::with('agency')->find($selectedUserId);
            return [$user, $user?->agency];
        }

        if ($selectedAgencyId) {
            $agency = Agency::with('user')->find($selectedAgencyId);
            return [$agency?->user, $agency];
        }

        $user = $requestModel->user;
        return [$user, $user?->agency];
    }

    /**
     * @param  array<string, mixed>  $newAccount
     * @return array{0:?User,1:?Agency,2:bool}
     */
    private function resolveNonMember(RequestModel $requestModel, array $newAccount, ?int $selectedUserId, ?int $selectedAgencyId): array
    {
        if ($selectedUserId) {
            $user = User::with('agency')->find($selectedUserId);
            return [$user, $user?->agency, false];
        }

        if ($selectedAgencyId) {
            $agency = Agency::with('user')->find($selectedAgencyId);
            return [$agency?->user, $agency, false];
        }

        $agencyName = trim((string) ($newAccount['agency_name'] ?? ''));
        $contactName = trim((string) ($newAccount['contact_name'] ?? ''));
        $phone = trim((string) ($newAccount['phone'] ?? ''));
        $email = trim((string) ($newAccount['email'] ?? ''));

        if ($agencyName === '' || $contactName === '' || $phone === '' || $email === '') {
            return [null, null, false];
        }

        $existing = User::query()
            ->where(function ($builder) use ($email, $phone): void {
                $builder->where('email', $email)->orWhere('phone', $phone);
            })
            ->first();
        if ($existing) {
            return [$existing, $existing->agency, false];
        }

        $password = Str::password(10);
        $user = User::create([
            'name' => $contactName,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
            'role' => 'acente',
        ]);

        $agency = Agency::create([
            'user_id' => $user->id,
            'company_title' => $agencyName,
            'tourism_title' => $agencyName,
            'contact_name' => $contactName,
            'phone' => $phone,
            'email' => $email,
            'is_active' => true,
        ]);

        // Yeni kullanıcı/şifre bilgilendirmesini session meta içinde tutuyoruz.
        $requestModel->refresh();

        return [$user, $agency, true];
    }

    private function attachRequestToUser(RequestModel $requestModel, User $user, ?Agency $agency): void
    {
        $requestModel->update([
            'user_id' => $user->id,
            'agency_name' => $agency?->company_title ?: $requestModel->agency_name,
            'phone' => $agency?->phone ?: $user->phone ?: $requestModel->phone,
            'email' => $agency?->email ?: $user->email ?: $requestModel->email,
        ]);
    }

    /**
     * @param  array<string, mixed>  $offer
     */
    private function storeOfferThroughExistingFlow(RequestModel $requestModel, QuickReplySession $session, array $offer): ?int
    {
        $beforeLastOfferId = (int) ($requestModel->offers()->max('id') ?? 0);

        $payload = [
            'airline' => $offer['airline'],
            'airline_pnr' => $offer['airline_pnr'],
            'flight_number' => $offer['flight_number'],
            'flight_departure_time' => $offer['flight_departure_time'],
            'flight_arrival_time' => $offer['flight_arrival_time'],
            'baggage_kg' => $offer['baggage_kg'],
            'pax_confirmed' => $offer['pax_confirmed'],
            'supplier_reference' => $offer['supplier_reference'],
            'currency' => $offer['currency'] ?: 'TRY',
            'price_per_pax' => $offer['price_per_pax'],
            'cost_price' => $offer['cost_price'],
            'deposit_rate' => $offer['deposit_rate'],
            'deposit_amount' => $offer['deposit_amount'],
            'option_date' => $offer['option_date'],
            'option_time' => $offer['option_time'],
            'offer_text' => $offer['offer_text'],
            'admin_raw_note' => $session->raw_text,
            'ai_raw_output' => json_encode($session->parsed_payload, JSON_UNESCAPED_UNICODE),
        ];

        $offerRequest = Request::create(
            $payload,
            [],
            [],
            [],
            [],
            array_merge($_SERVER, ['REQUEST_METHOD' => 'POST'])
        );
        $offerRequest->setUserResolver(static fn () => auth()->user());
        app(\App\Http\Controllers\Admin\RequestController::class)->storeOffer($offerRequest, $requestModel->gtpnr);

        $createdOffer = $requestModel->offers()
            ->where('id', '>', $beforeLastOfferId)
            ->orderByDesc('id')
            ->first();

        return $createdOffer?->id;
    }

    private function sendOnboardingAccess(User $user, ?Agency $agency, RequestModel $requestModel): void
    {
        try {
            $token = Password::broker()->createToken($user);
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));

            $subject = 'Grup Talepleri Hesap Erişimi';
            $agencyName = $agency?->company_title ?: $agency?->tourism_title ?: $requestModel->agency_name;
            $message = "Merhaba {$user->name},\n\n"
                . "{$agencyName} için hesabınız oluşturuldu.\n"
                . "Talebinizi görmek için aşağıdaki bağlantıdan şifrenizi belirleyin:\n{$resetUrl}\n\n"
                . "Talep No: {$requestModel->gtpnr}\n"
                . "Giriş: " . url('/login') . "\n\n"
                . "Grup Talepleri";

            Mail::raw($message, static function ($mail) use ($user, $subject): void {
                $mail->to($user->email, $user->name)->subject($subject);
            });

            if (! empty($user->phone)) {
                $sms = "Grup Talepleri hesabiniz olusturuldu. Sifre belirleme linki: {$resetUrl}";
                (new SmsService())->send($requestModel->id, 'acente', $user->name, (string) $user->phone, $sms);
            }
        } catch (\Throwable) {
            // Erişim bilgilendirmesi hata verse de ana teklif akışı başarısız sayılmaz.
        }
    }
}
