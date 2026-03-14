<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\OpsiyonUyariAyar;
use App\Models\SistemAyar;
use App\Models\SmsNotificationSetting;
use App\Models\RequestNotification;
use App\Models\User;
use Illuminate\Http\Request;

class SuperadminController extends Controller
{
    // ── ACENTELER ────────────────────────────────────────────────────────────

    public function acenteler()
    {
        $acenteler = Agency::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

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

    public function acenteSil(Agency $agency)
    {
        $user = $agency->user;
        $agency->delete();
        $user?->delete();
        return back()->with('success', 'Acente silindi.');
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

        return view('superadmin.sms-ayarlari', compact('ayarlar', 'events', 'opsiyonAyarlar', 'schedulerAralik', 'smsBaslangic', 'smsBitis'));
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

    // ── SMS RAPORLAR ──────────────────────────────────────────────────────────

    public function smsRaporlar(Request $request)
    {
        $query = RequestNotification::with('request')
            ->orderBy('created_at', 'desc');

        if ($request->filled('recipient')) {
            $query->where('recipient', $request->recipient);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('tarih')) {
            $query->whereDate('created_at', $request->tarih);
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('superadmin.sms-raporlar', compact('logs'));
    }
}
