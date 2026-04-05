<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SistemOlayController extends Controller
{
    private function tumOlaylar()
    {
        return DB::table('sistem_olay_sablonlari')
            ->orderBy('alici')
            ->orderBy('olay_adi')
            ->get()
            ->map(function ($o) {
                $o->degiskenler = $o->degiskenler ? json_decode($o->degiskenler, true) : [];
                return $o;
            });
    }

    public function index()
    {
        $olaylar = $this->tumOlaylar();
        return view('superadmin.sistem-olaylar', compact('olaylar'));
    }

    public function edit(int $id)
    {
        $duzenlenecek = DB::table('sistem_olay_sablonlari')->where('id', $id)->first();
        if (!$duzenlenecek) abort(404);
        $duzenlenecek->degiskenler = $duzenlenecek->degiskenler ? json_decode($duzenlenecek->degiskenler, true) : [];
        $olaylar = $this->tumOlaylar();
        return view('superadmin.sistem-olaylar', compact('olaylar', 'duzenlenecek'));
    }

    public function update(Request $request, int $id)
    {
        $sablon = DB::table('sistem_olay_sablonlari')->where('id', $id)->first();
        if (!$sablon) abort(404);

        $data = $request->validate([
            'olay_adi'     => 'required|string|max:150',
            'email_konu'   => 'nullable|string|max:255',
            'email_govde'  => 'nullable|string',
            'sms_govde'    => 'nullable|string|max:500',
            'email_aktif'  => 'nullable|boolean',
            'sms_aktif'    => 'nullable|boolean',
            'degiskenler'  => 'nullable|string',
        ]);

        $degiskenler = isset($data['degiskenler']) && $data['degiskenler'] !== ''
            ? array_map('trim', explode(',', $data['degiskenler']))
            : json_decode($sablon->degiskenler ?? '[]', true);

        DB::table('sistem_olay_sablonlari')->where('id', $id)->update([
            'olay_adi'     => $data['olay_adi'],
            'email_konu'   => trim($data['email_konu'] ?? '') ?: null,
            'email_govde'  => trim($data['email_govde'] ?? '') ?: null,
            'sms_govde'    => trim($data['sms_govde'] ?? '') ?: null,
            'email_aktif'  => $request->boolean('email_aktif') ? 1 : 0,
            'sms_aktif'    => $request->boolean('sms_aktif') ? 1 : 0,
            'degiskenler'  => json_encode($degiskenler),
            'updated_at'   => now(),
        ]);

        return redirect()->route('superadmin.sistem.olaylar')
            ->with('success', '"' . $data['olay_adi'] . '" şablonu güncellendi.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'olay_kodu'   => 'required|string|max:100|unique:sistem_olay_sablonlari,olay_kodu',
            'olay_adi'    => 'required|string|max:150',
            'alici'       => 'required|in:acente,admin,her_ikisi',
            'email_konu'  => 'nullable|string|max:255',
            'email_govde' => 'nullable|string',
            'sms_govde'   => 'nullable|string|max:500',
            'degiskenler' => 'nullable|string',
        ]);

        $degiskenler = isset($data['degiskenler']) && $data['degiskenler'] !== ''
            ? array_map('trim', explode(',', $data['degiskenler']))
            : [];

        DB::table('sistem_olay_sablonlari')->insert([
            'olay_kodu'   => $data['olay_kodu'],
            'olay_adi'    => $data['olay_adi'],
            'alici'       => $data['alici'],
            'email_konu'  => trim($data['email_konu'] ?? '') ?: null,
            'email_govde' => trim($data['email_govde'] ?? '') ?: null,
            'sms_govde'   => trim($data['sms_govde'] ?? '') ?: null,
            'email_aktif' => 1,
            'sms_aktif'   => 1,
            'degiskenler' => json_encode($degiskenler),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->route('superadmin.sistem.olaylar')
            ->with('success', 'Yeni olay eklendi.');
    }

    public function sifirla(int $id)
    {
        $sablon = DB::table('sistem_olay_sablonlari')->where('id', $id)->first();
        if (!$sablon) abort(404);

        DB::table('sistem_olay_sablonlari')->where('id', $id)->update([
            'email_konu'  => null,
            'email_govde' => null,
            'sms_govde'   => null,
            'updated_at'  => now(),
        ]);

        return redirect()->route('superadmin.sistem.olaylar')
            ->with('success', '"' . $sablon->olay_adi . '" sıfırlandı — orijinal şablon kullanılacak.');
    }
}
