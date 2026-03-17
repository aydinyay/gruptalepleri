<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuickReplySession;
use App\Models\Request as RequestModel;
use App\Services\QuickReplyService;
use Illuminate\Http\Request;

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
}
