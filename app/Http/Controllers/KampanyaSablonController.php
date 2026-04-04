<?php

namespace App\Http\Controllers;

use App\Models\KampanyaSablon;
use Illuminate\Http\Request;

class KampanyaSablonController extends Controller
{
    public function index()
    {
        $this->assertSuperadmin();
        $sablonlar = KampanyaSablon::latest()->get();
        return view('superadmin.pazarlama.sablonlar.index', compact('sablonlar'));
    }

    public function create()
    {
        $this->assertSuperadmin();
        $sablon = null;
        return view('superadmin.pazarlama.sablonlar.form', compact('sablon'));
    }

    public function store(Request $request)
    {
        $this->assertSuperadmin();
        $data = $this->validasyon($request);
        KampanyaSablon::create($data + ['olusturan_id' => auth()->id()]);
        return redirect()->route('superadmin.sablonlar.index')->with('success', 'Şablon oluşturuldu.');
    }

    public function edit(KampanyaSablon $sablon)
    {
        $this->assertSuperadmin();
        return view('superadmin.pazarlama.sablonlar.form', compact('sablon'));
    }

    public function update(Request $request, KampanyaSablon $sablon)
    {
        $this->assertSuperadmin();
        $sablon->update($this->validasyon($request));
        return redirect()->route('superadmin.sablonlar.index')->with('success', 'Şablon güncellendi.');
    }

    public function destroy(KampanyaSablon $sablon)
    {
        $this->assertSuperadmin();
        $sablon->delete();
        return back()->with('success', 'Şablon silindi.');
    }

    public function preview(KampanyaSablon $sablon)
    {
        $this->assertSuperadmin();
        $html = str_replace(
            ['{{acente_adi}}', '{{belge_no}}', '{{kayit_url}}'],
            ['ÖRNEK ACENTESİ A.Ş.', '12345', url('/register')],
            $sablon->html_icerik ?? ''
        );
        return response($html)->header('Content-Type', 'text/html');
    }

    private function validasyon(Request $request): array
    {
        return $request->validate([
            'ad'          => 'required|string|max:150',
            'tip'         => 'required|in:email,sms',
            'konu'        => 'nullable|string|max:255',
            'html_icerik' => 'nullable|string',
            'sms_icerik'  => 'nullable|string|max:160',
            'aktif'       => 'boolean',
        ]) + ['aktif' => $request->boolean('aktif')];
    }

    private function assertSuperadmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
    }
}
// deploy
