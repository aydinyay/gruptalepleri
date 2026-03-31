<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AiCelebrationCampaign;
use App\Models\Agency;
use App\Models\BroadcastNotification;
use App\Models\KullaniciBildirimi;
use App\Models\OpsiyonUyariAyar;
use App\Models\RequestNotification;
use App\Models\SistemAyar;
use App\Models\SmsNotificationSetting;
use App\Models\TransferSupplier;
use App\Models\User;
use App\Services\AiCelebrationService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SuperadminController extends Controller
{
    private function assertSuperadmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
    }

    private function bildirimEslestirmeSorgusu(KullaniciBildirimi $bildirim)
    {
        if ($bildirim->type === 'broadcast' && ! empty($bildirim->broadcast_id)) {
            return KullaniciBildirimi::query()
                ->where('type', 'broadcast')
                ->where('broadcast_id', $bildirim->broadcast_id);
        }

        return KullaniciBildirimi::query()
            ->where('type', $bildirim->type)
            ->where('title', $bildirim->title)
            ->where('message', $bildirim->message)
            ->when(
                $bildirim->url === null,
                fn ($q) => $q->whereNull('url'),
                fn ($q) => $q->where('url', $bildirim->url)
            );
    }

    private function broadcastPushTitle(BroadcastNotification $broadcast): string
    {
        return trim(($broadcast->emoji ? $broadcast->emoji . ' ' : '') . $broadcast->title);
    }

    private function broadcastBildirimleriniSil(BroadcastNotification $broadcast): int
    {
        $silinen = KullaniciBildirimi::query()
            ->where('type', 'broadcast')
            ->where('broadcast_id', $broadcast->id)
            ->delete();

        // Geriye dönük uyumluluk: eski kayıtlarda broadcast_id yoktu.
        if ($silinen === 0) {
            $silinen = KullaniciBildirimi::query()
                ->where('type', 'broadcast')
                ->where('title', $this->broadcastPushTitle($broadcast))
                ->where('message', $broadcast->message)
                ->delete();
        }

        return $silinen;
    }

    // ── ACENTELER ────────────────────────────────────────────────────────────

    public function acenteler()
    {
        $acenteler = Agency::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $supplierByUserId = collect();
        if (Schema::hasTable('transfer_suppliers')) {
            $supplierByUserId = TransferSupplier::query()
                ->whereIn('user_id', $acenteler->pluck('user_id')->filter()->values()->all())
                ->get()
                ->keyBy('user_id');
        }

        $acenteler->each(function (Agency $agency) use ($supplierByUserId): void {
            $agency->setRelation('transferSupplier', $supplierByUserId->get($agency->user_id));
        });

        return view('superadmin.acenteler', compact('acenteler'));
    }

    public function acenteToggle(Agency $agency)
    {
        $agency->update(['is_active' => !$agency->is_active]);
        $durum = $agency->is_active ? 'aktif' : 'pasif';
        return back()->with('success', "{$agency->company_title} {$durum} yapıldı.");
    }

    public function acenteRolDegistir(Agency $agency, Request $request)
    {
        $request->validate(['role' => 'required|in:acente,admin,superadmin']);
        $agency->user->update(['role' => $request->role]);
        return back()->with('success', 'Rol güncellendi.');
    }

    public function acenteGuncelle(Agency $agency, Request $request)
    {
        $request->validate([
            'company_title' => 'required|string|max:200',
            'tourism_title' => 'nullable|string|max:200',
            'contact_name'  => 'nullable|string|max:150',
            'phone'         => 'nullable|string|max:50',
            'email'         => 'nullable|email|max:150',
            'tursab_no'     => 'nullable|string|max:50',
            'tax_number'    => 'nullable|string|max:50',
            'tax_office'    => 'nullable|string|max:100',
            'address'       => 'nullable|string|max:500',
        ]);

        $agency->update($request->only([
            'company_title', 'tourism_title', 'contact_name',
            'phone', 'email', 'tursab_no', 'tax_number', 'tax_office', 'address',
        ]));

        return back()->with('success', "{$agency->company_title} güncellendi.");
    }

    public function acenteSil(Agency $agency)
    {
        $user = $agency->user;
        $agency->delete();
        $user?->delete();
        return back()->with('success', 'Acente silindi.');
    }

    public function acenteIadeBadgeToggle(Agency $agency)
    {
        $user = $agency->user;
        if (!$user) return back();
        $user->update(['show_iade_badge' => !$user->show_iade_badge]);
        $durum = $user->show_iade_badge ? 'açıldı' : 'kapatıldı';
        return back()->with('success', "İade badge görünümü {$durum}.");
    }

    public function acenteBroadcastYetkiToggle(Agency $agency)
    {
        $user = $agency->user;
        if (!$user) return back();
        $user->update(['can_send_broadcast' => !$user->can_send_broadcast]);
        $durum = $user->can_send_broadcast ? 'verildi' : 'alındı';
        return back()->with('success', "Duyuru gönderme yetkisi {$durum}: {$agency->company_title}");
    }


    public function acenteTransferSupplierToggle(Agency $agency)
    {
        if (! Schema::hasTable('transfer_suppliers')) {
            return back()->withErrors(['transfer_supplier' => 'Transfer tablolari bulunamadi. Lutfen once migration calistirin.']);
        }

        $user = $agency->user;
        if (! $user) {
            return back()->withErrors(['transfer_supplier' => 'Bu acente icin bagli kullanici bulunamadi.']);
        }

        if ($user->role !== 'acente') {
            return back()->withErrors(['transfer_supplier' => 'Transfer tedarikci yetkisi sadece acente rolune verilebilir.']);
        }

        $supplier = TransferSupplier::query()->firstOrNew(['user_id' => $user->id]);

        if (! $supplier->exists) {
            $supplier->fill([
                'company_name' => $agency->company_title ?: ($user->name . ' Transfer'),
                'contact_name' => $agency->contact_name ?: $user->name,
                'phone' => $agency->phone ?: $user->phone,
                'email' => $agency->email ?: $user->email,
                'city' => 'Istanbul',
                'commission_rate' => 12,
                'is_active' => true,
            ]);
        }

        $grantAccess = ! (bool) $supplier->is_approved;
        $supplier->is_approved = $grantAccess;
        $supplier->approved_at = $grantAccess ? now() : null;
        $supplier->save();

        $statusText = $grantAccess ? 'verildi' : 'kaldirildi';

        return back()->with('success', "Transfer tedarikci yetkisi {$statusText}: {$agency->company_title}");
    }

    public function broadcastYetkiToggleById(User $user)
    {
        $user->update(['can_send_broadcast' => !$user->can_send_broadcast]);
        $durum = $user->can_send_broadcast ? 'verildi' : 'alındı';
        return back()->with('success', "Duyuru gönderme yetkisi {$durum}: {$user->name}");
    }

    // ── BROADCAST GEÇMİŞİ ────────────────────────────────────────────────────

    public function broadcastGecmisi()
    {
        $duyurular = BroadcastNotification::with('sender')
            ->orderByDesc('created_at')
            ->paginate(30);

        $adminler = User::whereIn('role', ['admin', 'superadmin'])->get();

        return view('superadmin.broadcast-gecmisi', compact('duyurular', 'adminler'));
    }

    // ── SMS AYARLARI ──────────────────────────────────────────────────────────

    public function smsAyarlari()
    {
        $ayarlar           = SmsNotificationSetting::orderBy('event')->orderBy('label')->get();
        $events            = ['new_agency', 'new_request', 'offer_added', 'offer_accepted', 'all'];
        $opsiyonAyarlar    = OpsiyonUyariAyar::orderBy('saat_oncesi', 'desc')->get();
        $schedulerAralik   = (int) SistemAyar::get('opsiyon_check_aralik', 1440);
        $smsBaslangic      = SistemAyar::get('sms_baslangic_saat', '08:00');
        $smsBitis          = SistemAyar::get('sms_bitis_saat', '21:00');
        $notificationSystems = [
            'sms' => SistemAyar::smsEnabled(),
            'email' => SistemAyar::emailEnabled(),
            'push' => SistemAyar::pushEnabled(),
            'broadcast' => SistemAyar::broadcastEnabled(),
        ];

        return view('superadmin.sms-ayarlari', compact('ayarlar', 'events', 'opsiyonAyarlar', 'schedulerAralik', 'smsBaslangic', 'smsBitis', 'notificationSystems'));
    }

    public function siteAyarlari(Request $request)
    {
        $allowedTabs = ['bildirim', 'sms', 'duyuru', 'rapor', 'ai', 'sirket'];
        $activeTab = $request->query('sekme', 'bildirim');
        if (! in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'bildirim';
        }

        $notificationSystems = [
            'sms' => SistemAyar::smsEnabled(),
            'email' => SistemAyar::emailEnabled(),
            'push' => SistemAyar::pushEnabled(),
            'broadcast' => SistemAyar::broadcastEnabled(),
            'admin_sms_copy' => SistemAyar::adminSmsCopyEnabled(),
            'admin_email_copy' => SistemAyar::adminEmailCopyEnabled(),
        ];

        $stats = [
            'sms_kural' => SmsNotificationSetting::count(),
            'opsiyon_kural' => OpsiyonUyariAyar::count(),
            'duyuru' => BroadcastNotification::count(),
            'iletisim_log' => RequestNotification::count(),
        ];

        $schedulerAralik = (int) SistemAyar::get('opsiyon_check_aralik', 1440);
        $smsBaslangic = SistemAyar::get('sms_baslangic_saat', '08:00');
        $smsBitis = SistemAyar::get('sms_bitis_saat', '21:00');
        $opsiyonAyarlar = OpsiyonUyariAyar::orderBy('saat_oncesi', 'desc')->limit(5)->get();
        $recentSmsRules = SmsNotificationSetting::orderByDesc('updated_at')->limit(5)->get();
        $recentBroadcasts = BroadcastNotification::with('sender')->orderByDesc('created_at')->limit(5)->get();
        $recentLogs = RequestNotification::orderByDesc('created_at')->limit(5)->get();
        $channelCounts = [
            'sms' => RequestNotification::where('channel', 'sms')->count(),
            'email' => RequestNotification::where('channel', 'email')->count(),
        ];

        $aiModuleEnabled = SistemAyar::aiCelebrationEnabled();
        $aiCampaigns = collect();
        $aiDismissedCampaigns = collect();
        $aiStats = [
            'toplam' => 0,
            'yayinda' => 0,
            'taslak' => 0,
            'istenmeyen' => 0,
        ];

        if ($activeTab === 'ai') {
            $today = now()->startOfDay();
            $campaignBaseQuery = AiCelebrationCampaign::query()
                ->with(['creator', 'approver', 'publisher', 'dismisser'])
                ->withCount([
                    'userStates as seen_users_count' => fn ($query) => $query->where('seen_count', '>', 0),
                    'userStates as closed_users_count' => fn ($query) => $query->whereNotNull('closed_at'),
                ])
                ->orderByDesc('publish_starts_at')
                ->orderByDesc('event_date');

            $aiCampaigns = (clone $campaignBaseQuery)
                ->where('status', '!=', AiCelebrationCampaign::STATUS_DISMISSED)
                ->where(function ($query) use ($today) {
                    $query
                        ->whereNull('event_date')
                        ->orWhereDate('event_date', '>=', $today);
                })
                ->limit(40)
                ->get();

            $aiDismissedCampaigns = (clone $campaignBaseQuery)
                ->where('status', AiCelebrationCampaign::STATUS_DISMISSED)
                ->limit(25)
                ->get();

            $aiStats = [
                'toplam' => AiCelebrationCampaign::count(),
                'yayinda' => AiCelebrationCampaign::publishedActive()->count(),
                'taslak' => AiCelebrationCampaign::whereIn('status', [
                    AiCelebrationCampaign::STATUS_DRAFT,
                    AiCelebrationCampaign::STATUS_APPROVED,
                ])->count(),
                'istenmeyen' => AiCelebrationCampaign::where('status', AiCelebrationCampaign::STATUS_DISMISSED)->count(),
            ];
        }

        $sirketBilgileri = [];
        if ($activeTab === 'sirket') {
            $sirketKeys = [
                'sirket_unvan', 'sirket_vkn', 'sirket_vergi_dairesi',
                'sirket_mersis_no', 'sirket_adres', 'sirket_telefon', 'sirket_cep',
                'sirket_whatsapp', 'sirket_eposta', 'sirket_tursab_no',
                'sirket_tursab_grup', 'sirket_instagram', 'sirket_facebook', 'sirket_twitter', 'sirket_linkedin',
                // Eski tek-hesap keys (geriye uyumluluk)
                'banka_adi', 'banka_hesap_sahibi', 'banka_iban', 'banka_sube', 'banka_aciklama',
                // Çok hesap (1-4, TRY/USD/EUR)
                'banka_adi_1','banka_sube_1','banka_hesap_sahibi_1','banka_iban_1','banka_doviz_1','banka_aciklama_1',
                'banka_adi_2','banka_sube_2','banka_hesap_sahibi_2','banka_iban_2','banka_doviz_2','banka_aciklama_2',
                'banka_adi_3','banka_sube_3','banka_hesap_sahibi_3','banka_iban_3','banka_doviz_3','banka_aciklama_3',
                'banka_adi_4','banka_sube_4','banka_hesap_sahibi_4','banka_iban_4','banka_doviz_4','banka_aciklama_4',
            ];
            foreach ($sirketKeys as $key) {
                $sirketBilgileri[$key] = SistemAyar::get($key, '');
            }
        }

        return view(
            'superadmin.site-ayarlari',
            compact(
                'activeTab',
                'notificationSystems',
                'stats',
                'schedulerAralik',
                'smsBaslangic',
                'smsBitis',
                'opsiyonAyarlar',
                'recentSmsRules',
                'recentBroadcasts',
                'recentLogs',
                'channelCounts',
                'aiModuleEnabled',
                'aiCampaigns',
                'aiDismissedCampaigns',
                'aiStats',
                'sirketBilgileri'
            )
        );
    }

    public function sirketBilgileriGuncelle(Request $request)
    {
        $this->assertSuperadmin();

        $keys = [
            'sirket_unvan', 'sirket_vkn', 'sirket_vergi_dairesi',
            'sirket_mersis_no', 'sirket_adres', 'sirket_telefon', 'sirket_cep',
            'sirket_whatsapp', 'sirket_eposta', 'sirket_tursab_no',
            'sirket_tursab_grup', 'sirket_instagram', 'sirket_facebook', 'sirket_twitter', 'sirket_linkedin',
            'banka_adi', 'banka_hesap_sahibi', 'banka_iban', 'banka_sube', 'banka_aciklama',
            'banka_adi_1','banka_sube_1','banka_hesap_sahibi_1','banka_iban_1','banka_doviz_1','banka_aciklama_1',
            'banka_adi_2','banka_sube_2','banka_hesap_sahibi_2','banka_iban_2','banka_doviz_2','banka_aciklama_2',
            'banka_adi_3','banka_sube_3','banka_hesap_sahibi_3','banka_iban_3','banka_doviz_3','banka_aciklama_3',
            'banka_adi_4','banka_sube_4','banka_hesap_sahibi_4','banka_iban_4','banka_doviz_4','banka_aciklama_4',
        ];

        foreach ($keys as $key) {
            SistemAyar::set($key, trim($request->input($key, '')));
        }

        return redirect()
            ->route('superadmin.site.ayarlar', ['sekme' => 'sirket'])
            ->with('success', 'Şirket ve banka bilgileri güncellendi.');
    }

    public function aiKutlamaAyarGuncelle(Request $request)
    {
        $this->assertSuperadmin();
        SistemAyar::set(SistemAyar::KEY_AI_CELEBRATION_ENABLED, $request->boolean('ai_celebration_enabled'));

        return redirect()
            ->route('superadmin.site.ayarlar', ['sekme' => 'ai'])
            ->with('success', 'AI kutlama modul ayari guncellendi.');
    }

    public function aiKutlamaTara(Request $request, AiCelebrationService $aiCelebrationService)
    {
        $this->assertSuperadmin();

        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:30',
            'force_refresh' => 'nullable|boolean',
        ]);

        $days = (int) ($validated['days'] ?? 7);
        $forceRefresh = (bool) ($validated['force_refresh'] ?? false);
        $sonuc = $aiCelebrationService->scanUpcomingSuggestions($days, $forceRefresh, auth()->id());

        $mesaj = sprintf(
            'Tarama tamamlandi. Yeni:%d Uretilen:%d Mevcut:%d Istenmeyen atlandi:%d',
            $sonuc['created'],
            $sonuc['generated'],
            $sonuc['existing'],
            $sonuc['skipped_dismissed']
        );

        return redirect()
            ->route('superadmin.site.ayarlar', ['sekme' => 'ai'])
            ->with('success', $mesaj);
    }

    public function aiKutlamaManuelOlustur(Request $request, AiCelebrationService $aiCelebrationService)
    {
        $this->assertSuperadmin();

        $validated = $request->validate([
            'event_name' => 'required|string|max:160',
            'event_date' => 'nullable|date',
            'category' => 'nullable|string|max:50',
            'topic_prompt' => 'required|string|max:5000',
            'display_mode' => 'nullable|in:banner,popup,card',
            'frequency_cap' => 'nullable|integer|min:1|max:20',
            'priority' => 'nullable|integer|min:1|max:999',
            'show_on_public' => 'nullable|boolean',
            'publish_starts_at' => 'nullable|date',
            'publish_ends_at' => 'nullable|date|after_or_equal:publish_starts_at',
        ]);

        $aiCelebrationService->createManualSuggestion([
            'event_name' => $validated['event_name'],
            'event_date' => $validated['event_date'] ?? null,
            'category' => $validated['category'] ?? 'genel',
            'topic_prompt' => $validated['topic_prompt'],
            'display_mode' => $validated['display_mode'] ?? AiCelebrationCampaign::DISPLAY_BANNER,
            'frequency_cap' => (int) ($validated['frequency_cap'] ?? 1),
            'priority' => (int) ($validated['priority'] ?? 100),
            'show_on_public' => (bool) ($validated['show_on_public'] ?? false),
            'publish_starts_at' => $this->parseDateTime($request->input('publish_starts_at')),
            'publish_ends_at' => $this->parseDateTime($request->input('publish_ends_at')),
        ], auth()->id());

        return redirect()
            ->route('superadmin.site.ayarlar', ['sekme' => 'ai'])
            ->with('success', 'Manuel AI kutlama onerisi olusturuldu.');
    }

    public function aiKutlamaYenidenUret(
        Request $request,
        AiCelebrationCampaign $campaign,
        AiCelebrationService $aiCelebrationService
    ) {
        $this->assertSuperadmin();

        $aiCelebrationService->generateContent($campaign, $request->input('topic_prompt'), auth()->id());

        return redirect()
            ->route('superadmin.site.ayarlar', ['sekme' => 'ai'])
            ->with('success', 'AI icerigi yeniden uretildi.');
    }

    public function aiKutlamaGuncelle(
        Request $request,
        AiCelebrationCampaign $campaign,
        AiCelebrationService $aiCelebrationService
    ) {
        $this->assertSuperadmin();

        $this->validateAiCampaignUpdate($request);

        $aiCelebrationService->updateCampaign(
            $campaign,
            $this->aiCampaignPayloadFromRequest($request),
            auth()->id()
        );

        return redirect()
            ->route('superadmin.site.ayarlar', ['sekme' => 'ai'])
            ->with('success', 'AI kutlama kaydi guncellendi.');
    }

    public function aiKutlamaYayinla(
        Request $request,
        AiCelebrationCampaign $campaign,
        AiCelebrationService $aiCelebrationService
    ) {
        $this->assertSuperadmin();

        $editableFields = [
            'event_name',
            'event_date',
            'category',
            'title',
            'message',
            'cta_text',
            'cta_url',
            'topic_prompt',
            'display_mode',
            'frequency_cap',
            'priority',
            'publish_starts_at',
            'publish_ends_at',
            'show_on_public',
            'show_on_authenticated',
        ];

        $updatedCampaign = $campaign->fresh();
        if ($request->hasAny($editableFields)) {
            $this->validateAiCampaignUpdate($request);

            $updatedCampaign = $aiCelebrationService->updateCampaign(
                $campaign,
                $this->aiCampaignPayloadFromRequest($request),
                auth()->id()
            );
        }

        if (blank($updatedCampaign->title) || blank($updatedCampaign->message) || blank($updatedCampaign->image_path)) {
            $updatedCampaign = $aiCelebrationService->generateContent($updatedCampaign, $updatedCampaign->topic_prompt, auth()->id());
        }

        $aiCelebrationService->publishCampaign($updatedCampaign, auth()->id());

        return redirect()
            ->route('superadmin.site.ayarlar', ['sekme' => 'ai'])
            ->with('success', 'AI kutlama yayina alindi.');
    }

    public function aiKutlamaDurdur(AiCelebrationCampaign $campaign, AiCelebrationService $aiCelebrationService)
    {
        $this->assertSuperadmin();

        $aiCelebrationService->stopCampaign($campaign, auth()->id());

        return redirect()
            ->route('superadmin.site.ayarlar', ['sekme' => 'ai'])
            ->with('success', 'AI kutlama yayini durduruldu.');
    }

    public function aiKutlamaIstenmeyen(AiCelebrationCampaign $campaign, AiCelebrationService $aiCelebrationService)
    {
        $this->assertSuperadmin();

        $aiCelebrationService->dismissCampaign($campaign, auth()->id(), 'Superadmin tarafindan istenmeyen olarak isaretlendi.');

        return redirect()
            ->route('superadmin.site.ayarlar', ['sekme' => 'ai'])
            ->with('success', 'Kayit istenmeyen listesine tasindi.');
    }

    public function aiKutlamaGeriAl(AiCelebrationCampaign $campaign, AiCelebrationService $aiCelebrationService)
    {
        $this->assertSuperadmin();

        $aiCelebrationService->restoreCampaign($campaign, auth()->id());

        return redirect()
            ->route('superadmin.site.ayarlar', ['sekme' => 'ai'])
            ->with('success', 'Kayit istenmeyen listesinden geri alindi.');
    }

    public function aiKutlamaOnizleme(AiCelebrationCampaign $campaign)
    {
        $this->assertSuperadmin();

        return view('superadmin.ai-kutlama-preview', compact('campaign'));
    }

    private function validateAiCampaignUpdate(Request $request): void
    {
        $request->validate([
            'event_name' => 'required|string|max:160',
            'event_date' => 'nullable|date',
            'category' => 'nullable|string|max:50',
            'title' => 'required|string|max:160',
            'message' => 'required|string|max:3000',
            'cta_text' => 'nullable|string|max:120',
            'cta_url' => 'nullable|string|max:500',
            'topic_prompt' => 'nullable|string|max:5000',
            'display_mode' => 'required|in:banner,popup,card',
            'frequency_cap' => 'required|integer|min:1|max:20',
            'priority' => 'required|integer|min:1|max:999',
            'publish_starts_at' => 'nullable|date',
            'publish_ends_at' => 'nullable|date|after_or_equal:publish_starts_at',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function aiCampaignPayloadFromRequest(Request $request): array
    {
        return [
            'event_name' => $request->input('event_name'),
            'event_date' => $request->input('event_date') ?: null,
            'category' => $request->input('category', 'genel'),
            'title' => $request->input('title'),
            'message' => $request->input('message'),
            'cta_text' => $request->input('cta_text'),
            'cta_url' => $request->input('cta_url'),
            'topic_prompt' => $request->input('topic_prompt'),
            'display_mode' => $request->input('display_mode', AiCelebrationCampaign::DISPLAY_BANNER),
            'show_on_public' => $request->boolean('show_on_public'),
            'show_on_authenticated' => $request->boolean('show_on_authenticated', true),
            'frequency_cap' => (int) $request->input('frequency_cap', 1),
            'priority' => (int) $request->input('priority', 100),
            'publish_starts_at' => $this->parseDateTime($request->input('publish_starts_at')),
            'publish_ends_at' => $this->parseDateTime($request->input('publish_ends_at')),
        ];
    }

    private function parseDateTime(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function aktifAdimYenile()
    {
        try {
            // Sadece UG-KSK3AG'yi düzelt
            $talep = \App\Models\Request::with(['offers', 'payments'])->where('gtpnr', 'UG-KSK3AG')->first();
            if (!$talep) {
                return back()->with('error', 'Talep bulunamadı.');
            }

            $kabulTeklif = $talep->offers->firstWhere('durum', \App\Models\Offer::DURUM_KABUL);
            if (!$kabulTeklif) {
                $bekleyenTeklif = $talep->offers->firstWhere('durum', \App\Models\Offer::DURUM_BEKLEMEDE);
                if ($bekleyenTeklif) {
                    $bekleyenTeklif->update(['durum' => \App\Models\Offer::DURUM_KABUL]);
                    $talep->load('offers');
                    $kabulTeklif = $talep->offers->firstWhere('durum', \App\Models\Offer::DURUM_KABUL);
                }
            }

            $depozitoVar = $kabulTeklif && $talep->payments->where('offer_id', $kabulTeklif->id)->where('payment_type', 'depozito')->isNotEmpty();

            if ($kabulTeklif && $kabulTeklif->deposit_amount > 0 && !$depozitoVar) {
                \App\Models\RequestPayment::create([
                    'request_id'   => $talep->id,
                    'offer_id'     => $kabulTeklif->id,
                    'sequence'     => 1,
                    'payment_type' => 'depozito',
                    'amount'       => $kabulTeklif->deposit_amount,
                    'currency'     => $kabulTeklif->currency,
                    'status'       => \App\Models\RequestPayment::STATUS_TASLAK,
                    'is_active'    => false,
                    'created_by'   => auth()->id(),
                ]);
            }

            $talep->refreshAktifAdim();
            return back()->with('success', 'UG-KSK3AG düzeltildi. Teklif: ' . ($kabulTeklif?->id ?? 'yok') . ' Depozito: ' . ($kabulTeklif?->deposit_amount ?? 0));
        } catch (\Throwable $e) {
            return back()->with('error', 'Hata: ' . $e->getMessage() . ' | ' . basename($e->getFile()) . ':' . $e->getLine());
        }
    }

    public function bildirimSistemleriGuncelle(Request $request)
    {
        SistemAyar::set(SistemAyar::KEY_SMS_ENABLED, $request->boolean('sms_enabled'));
        SistemAyar::set(SistemAyar::KEY_EMAIL_ENABLED, $request->boolean('email_enabled'));
        SistemAyar::set(SistemAyar::KEY_PUSH_ENABLED, $request->boolean('push_enabled'));
        SistemAyar::set(SistemAyar::KEY_BROADCAST_ENABLED, $request->boolean('broadcast_enabled'));
        SistemAyar::set(SistemAyar::KEY_ADMIN_SMS_COPY, $request->boolean('admin_sms_copy_enabled'));
        SistemAyar::set(SistemAyar::KEY_ADMIN_EMAIL_COPY, $request->boolean('admin_email_copy_enabled'));

        return back()->with('success', 'Bildirim sistemleri güncellendi.');
    }

    public function schedulerAralikGuncelle(Request $request)
    {
        $request->validate(['aralik' => 'required|integer|in:1,5,15,30,60,360,720,1440']);
        SistemAyar::set('opsiyon_check_aralik', $request->aralik);
        \Illuminate\Support\Facades\Cache::forget('opsiyon_check_son_calisma');
        return back()->with('success', 'Kontrol aralığı güncellendi.');
    }

    public function smsSaatGuncelle(Request $request)
    {
        $request->validate([
            'sms_baslangic' => 'required|date_format:H:i',
            'sms_bitis'     => 'required|date_format:H:i|after:sms_baslangic',
        ]);
        SistemAyar::set('sms_baslangic_saat', $request->sms_baslangic);
        SistemAyar::set('sms_bitis_saat',     $request->sms_bitis);
        return back()->with('success', 'SMS gönderim saatleri güncellendi.');
    }

    public function opsiyonAyarEkle(Request $request)
    {
        $request->validate(['saat_oncesi' => 'required|integer|min:1|max:168']);
        OpsiyonUyariAyar::firstOrCreate(
            ['saat_oncesi' => $request->saat_oncesi],
            ['sms_aktif' => $request->boolean('sms_aktif', true), 'push_aktif' => $request->boolean('push_aktif', true)]
        );
        return back()->with('success', $request->saat_oncesi . ' saat öncesi uyarı eklendi.');
    }

    public function opsiyonAyarToggle(OpsiyonUyariAyar $opsiyonAyar)
    {
        $opsiyonAyar->update(['is_active' => !$opsiyonAyar->is_active]);
        return back()->with('success', $opsiyonAyar->is_active ? 'Aktif edildi.' : 'Pasif edildi.');
    }

    public function opsiyonAyarSil(OpsiyonUyariAyar $opsiyonAyar)
    {
        $opsiyonAyar->delete();
        return back()->with('success', 'Opsiyon uyarısı silindi.');
    }

    public function smsAyarEkle(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:100',
            'phone' => 'required|string|max:200',
            'event' => 'required|in:new_agency,new_request,offer_added,offer_accepted,all',
        ]);

        SmsNotificationSetting::create($request->only('label', 'phone', 'event'));

        return back()->with('success', 'SMS bildirimi eklendi.');
    }

    public function smsAyarGuncelle(SmsNotificationSetting $ayar, Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:100',
            'phone' => 'required|string|max:200',
            'event' => 'required|in:new_agency,new_request,offer_added,offer_accepted,all',
        ]);
        $ayar->update($request->only('label', 'phone', 'event'));
        return back()->with('success', 'SMS kuralı güncellendi.');
    }

    public function smsAyarToggle(SmsNotificationSetting $ayar)
    {
        $ayar->update(['is_active' => !$ayar->is_active]);
        return back()->with('success', $ayar->is_active ? 'Aktif edildi.' : 'Pasif edildi.');
    }

    public function smsAyarSil(SmsNotificationSetting $ayar)
    {
        $ayar->delete();
        return back()->with('success', 'Silindi.');
    }

    // ── BROADCAST SİLME (superadmin — tüm duyurular silinebilir) ─────────────

    public function broadcastSil(BroadcastNotification $broadcast)
    {
        $this->assertSuperadmin();
        $silinenBildirim = $this->broadcastBildirimleriniSil($broadcast);
        $broadcast->delete();
        return back()->with('success', "Duyuru silindi. {$silinenBildirim} çan bildirimi kaldırıldı.");
    }

    public function broadcastHepsiniSil()
    {
        $this->assertSuperadmin();
        $broadcastIds = BroadcastNotification::query()->pluck('id');
        $silinenBildirim = 0;

        if ($broadcastIds->isNotEmpty()) {
            $silinenBildirim += KullaniciBildirimi::query()
                ->where('type', 'broadcast')
                ->whereIn('broadcast_id', $broadcastIds)
                ->delete();
        }

        // Geriye dönük uyumluluk: broadcast_id olmayan eski broadcast push kayıtları.
        $silinenBildirim += KullaniciBildirimi::query()
            ->where('type', 'broadcast')
            ->whereNull('broadcast_id')
            ->delete();

        BroadcastNotification::truncate();
        return back()->with('success', "Tüm duyurular silindi. {$silinenBildirim} çan bildirimi kaldırıldı.");
    }

    // ── SMS LOG SİLME ────────────────────────────────────────────────────────

    public function smsLogSil(RequestNotification $log)
    {
        $log->delete();
        return back()->with('success', 'Log silindi.');
    }

    public function smsLogHepsiniSil()
    {
        RequestNotification::truncate();
        return back()->with('success', 'Tüm SMS/email logları silindi.');
    }

    // ── BİLDİRİM SİLME ───────────────────────────────────────────────────────

    public function smsTeslimDurumlariGuncelle(Request $request)
    {
        $this->assertSuperadmin();

        $limit = (int) $request->input('limit', 100);
        $limit = max(1, min(300, $limit));

        $sonuc = (new SmsService())->refreshDeliveryStatuses($limit);
        $mesaj = sprintf(
            'SMS durum guncelleme tamamlandi. Kontrol:%d Guncel:%d Iletildi:%d Iletilemedi:%d Bekliyor:%d Hata:%d',
            $sonuc['checked'],
            $sonuc['updated'],
            $sonuc['delivered'],
            $sonuc['undelivered'],
            $sonuc['pending'],
            $sonuc['errors']
        );

        return back()->with('success', $mesaj);
    }

    public function bildirimSil(KullaniciBildirimi $bildirim)
    {
        // Sadece kendi bildirimini silebilir
        abort_unless($bildirim->user_id === auth()->id(), 403);
        $bildirim->delete();
        return response()->json(['ok' => true]);
    }

    public function bildirimHepsiniSil()
    {
        KullaniciBildirimi::where('user_id', auth()->id())->delete();
        return response()->json(['ok' => true]);
    }

    public function bildirimHerkestenSil(KullaniciBildirimi $bildirim)
    {
        $this->assertSuperadmin();
        abort_unless($bildirim->user_id === auth()->id(), 403);

        $silinen = $this->bildirimEslestirmeSorgusu($bildirim)->delete();
        return response()->json(['ok' => true, 'deleted' => $silinen]);
    }

    public function bildirimSecilenleriHerkestenSil(Request $request)
    {
        $this->assertSuperadmin();

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $tohumlar = KullaniciBildirimi::query()
            ->where('user_id', auth()->id())
            ->whereIn('id', $validated['ids'])
            ->get();

        $silinenToplam = 0;
        $islenen = [];

        foreach ($tohumlar as $bildirim) {
            $anahtar = ($bildirim->type === 'broadcast' && ! empty($bildirim->broadcast_id))
                ? 'broadcast:' . $bildirim->broadcast_id
                : 'fingerprint:' . md5($bildirim->type . '|' . $bildirim->title . '|' . $bildirim->message . '|' . ($bildirim->url ?? ''));

            if (isset($islenen[$anahtar])) {
                continue;
            }

            $islenen[$anahtar] = true;
            $silinenToplam += $this->bildirimEslestirmeSorgusu($bildirim)->delete();
        }

        return response()->json(['ok' => true, 'deleted' => $silinenToplam, 'groups' => count($islenen)]);
    }

    public function bildirimHepsiniHerkestenSil()
    {
        $this->assertSuperadmin();

        $tohumlar = KullaniciBildirimi::query()
            ->where('user_id', auth()->id())
            ->get();

        $silinenToplam = 0;
        $islenen = [];

        foreach ($tohumlar as $tohum) {
            $anahtar = ($tohum->type === 'broadcast' && ! empty($tohum->broadcast_id))
                ? 'broadcast:' . $tohum->broadcast_id
                : 'fingerprint:' . md5($tohum->type . '|' . $tohum->title . '|' . $tohum->message . '|' . ($tohum->url ?? ''));

            if (isset($islenen[$anahtar])) {
                continue;
            }

            $islenen[$anahtar] = true;
            $silinenToplam += $this->bildirimEslestirmeSorgusu($tohum)->delete();
        }

        return response()->json(['ok' => true, 'deleted' => $silinenToplam, 'groups' => count($islenen)]);
    }

    // ── SMS RAPORLAR ──────────────────────────────────────────────────────────

    public function smsRaporlar(Request $request)
    {
        $channel = $request->input('channel', 'all');
        if (! in_array($channel, ['all', 'sms', 'email'], true)) {
            $channel = 'all';
        }

        $sharedFilters = function ($queryBuilder) use ($request): void {
            if ($request->filled('recipient')) {
                $queryBuilder->where('recipient', $request->recipient);
            }
            if ($request->filled('status')) {
                $queryBuilder->where('status', $request->status);
            }
            if ($request->filled('tarih')) {
                $queryBuilder->whereDate('created_at', $request->tarih);
            }
        };

        $query = RequestNotification::with('request')
            ->orderBy('created_at', 'desc');

        $sharedFilters($query);

        if ($channel !== 'all') {
            $query->where('channel', $channel);
        }

        $countQuery = RequestNotification::query();
        $sharedFilters($countQuery);
        $groupedCounts = $countQuery
            ->selectRaw('channel, count(*) as total')
            ->groupBy('channel')
            ->pluck('total', 'channel');

        $channelCounts = [
            'sms' => (int) ($groupedCounts['sms'] ?? 0),
            'email' => (int) ($groupedCounts['email'] ?? 0),
        ];
        $channelCounts['all'] = $channelCounts['sms'] + $channelCounts['email'];

        $logs = $query->paginate(50)->withQueryString();
        $smsInfo = (new SmsService())->getAccountInfo();
        $smsBalance = [
            'available' => $smsInfo['available'],
            'balance' => $smsInfo['balance'],
            'raw' => $smsInfo['raw'],
            'message' => $smsInfo['message'],
        ];

        return view('superadmin.sms-raporlar', compact('logs', 'channel', 'channelCounts', 'smsBalance', 'smsInfo'));
    }
}
