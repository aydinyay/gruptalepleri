<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SistemOlaySablon;
use Illuminate\Http\Request;

class SistemOlayController extends Controller
{
    public function index()
    {
        $olaylar = SistemOlaySablon::orderBy('alici')->orderBy('olay_adi')->get();
        return view('superadmin.sistem-olaylar', compact('olaylar'));
    }

    public function edit(SistemOlaySablon $sablon)
    {
        return view('superadmin.sistem-olaylar', [
            'olaylar'       => SistemOlaySablon::orderBy('alici')->orderBy('olay_adi')->get(),
            'duzenlenecek'  => $sablon,
        ]);
    }

    public function update(Request $request, SistemOlaySablon $sablon)
    {
        $data = $request->validate([
            'olay_adi'     => 'required|string|max:150',
            'email_konu'   => 'nullable|string|max:255',
            'email_govde'  => 'nullable|string',
            'sms_govde'    => 'nullable|string|max:500',
            'email_aktif'  => 'nullable|boolean',
            'sms_aktif'    => 'nullable|boolean',
            'degiskenler'  => 'nullable|string', // virgülle ayrılmış liste
        ]);

        // degiskenler alanını array'e çevir
        if (isset($data['degiskenler']) && $data['degiskenler'] !== '') {
            $data['degiskenler'] = array_map('trim', explode(',', $data['degiskenler']));
        } else {
            $data['degiskenler'] = $sablon->degiskenler;
        }

        $data['email_aktif'] = $request->boolean('email_aktif');
        $data['sms_aktif']   = $request->boolean('sms_aktif');

        // Boş string → null yap (Blade'e dönüş)
        if (trim($data['email_govde'] ?? '') === '') $data['email_govde'] = null;
        if (trim($data['sms_govde'] ?? '') === '')   $data['sms_govde']   = null;
        if (trim($data['email_konu'] ?? '') === '')  $data['email_konu']  = null;

        $sablon->update($data);

        return redirect()->route('superadmin.sistem.olaylar')
            ->with('success', '"' . $sablon->olay_adi . '" şablonu güncellendi.');
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

        if (isset($data['degiskenler']) && $data['degiskenler'] !== '') {
            $data['degiskenler'] = array_map('trim', explode(',', $data['degiskenler']));
        } else {
            $data['degiskenler'] = [];
        }

        if (trim($data['email_govde'] ?? '') === '') $data['email_govde'] = null;
        if (trim($data['sms_govde'] ?? '') === '')   $data['sms_govde']   = null;
        if (trim($data['email_konu'] ?? '') === '')  $data['email_konu']  = null;

        SistemOlaySablon::create($data);

        return redirect()->route('superadmin.sistem.olaylar')
            ->with('success', 'Yeni olay eklendi.');
    }

    public function sifirla(SistemOlaySablon $sablon)
    {
        $sablon->update([
            'email_konu'  => null,
            'email_govde' => null,
            'sms_govde'   => null,
        ]);

        return redirect()->route('superadmin.sistem.olaylar')
            ->with('success', '"' . $sablon->olay_adi . '" sıfırlandı — orijinal şablon kullanılacak.');
    }
}
