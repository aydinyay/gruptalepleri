<?php

namespace App\Http\Controllers;

use App\Models\Kampanya;
use App\Models\KampanyaSablon;
use App\Models\Acenteler;
use App\Models\TursabDavet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KampanyaController extends Controller
{
    public function index()
    {
        $this->assertSuperadmin();
        $kampanyalar = Kampanya::with('sablon')->latest()->get();

        $istatistik = $kampanyalar->map(function ($k) {
            $gonderilenler = TursabDavet::where('kampanya_etiket', $k->etiket);
            return [
                'id'        => $k->id,
                'toplam'    => (clone $gonderilenler)->count(),
                'basarili'  => (clone $gonderilenler)->where('status', 'sent')->count(),
                'tiklanan'  => (clone $gonderilenler)->whereNotNull('tiklanma_at')->count(),
            ];
        })->keyBy('id');

        return view('superadmin.pazarlama.kampanyalar.index', compact('kampanyalar', 'istatistik'));
    }

    public function create()
    {
        $this->assertSuperadmin();
        $sablonlar = KampanyaSablon::where('aktif', true)->get();
        $iller = Acenteler::whereNotNull('il')->where('il', '!=', '')->distinct()->orderBy('il')->pluck('il');
        return view('superadmin.pazarlama.kampanyalar.form', compact('sablonlar', 'iller'));
    }

    public function store(Request $request)
    {
        $this->assertSuperadmin();

        $request->validate([
            'ad'       => 'required|string|max:150',
            'tip'      => 'required|in:email,sms',
            'sablon_id'=> 'required|exists:kampanya_sablonlar,id',
            'baslangic'=> 'nullable|date',
            'bitis'    => 'nullable|date|after_or_equal:baslangic',
            'slotlar'  => 'required|array|min:1',
            'slotlar.*.saat' => 'required',
            'slotlar.*.adet' => 'required|integer|min:1|max:500',
        ]);

        $slotlar = collect($request->slotlar)->map(fn($s) => [
            'saat'  => $s['saat'],
            'adet'  => (int) $s['adet'],
            'aktif' => true,
        ])->values()->toArray();

        Kampanya::create([
            'ad'         => $request->ad,
            'aciklama'   => $request->aciklama,
            'tip'        => $request->tip,
            'sablon_id'  => $request->sablon_id,
            'hedef'      => [
                'il'         => $request->filtre_il ?? '',
                'ilce'       => $request->filtre_ilce ?? '',
                'grup'       => $request->filtre_grup ?? '',
                'sadece_yeni'=> $request->boolean('sadece_yeni', true),
            ],
            'zamanlama'  => [
                'baslangic' => $request->baslangic ?? '',
                'bitis'     => $request->bitis ?? '',
                'slotlar'   => $slotlar,
            ],
            'durum'      => 'taslak',
            'etiket'     => 'kmp-' . Str::slug($request->ad, '-') . '-' . Str::random(6),
            'olusturan_id' => auth()->id(),
        ]);

        return redirect()->route('superadmin.kampanyalar.index')->with('success', 'Kampanya oluşturuldu.');
    }

    public function show(Kampanya $kampanya)
    {
        $this->assertSuperadmin();

        $gonderilenler = TursabDavet::where('kampanya_etiket', $kampanya->etiket)
            ->orderByDesc('created_at')
            ->paginate(50);

        $istatistik = [
            'toplam'   => TursabDavet::where('kampanya_etiket', $kampanya->etiket)->count(),
            'basarili' => TursabDavet::where('kampanya_etiket', $kampanya->etiket)->where('status', 'sent')->count(),
            'basarisiz'=> TursabDavet::where('kampanya_etiket', $kampanya->etiket)->where('status', 'failed')->count(),
            'tiklanan' => TursabDavet::where('kampanya_etiket', $kampanya->etiket)->whereNotNull('tiklanma_at')->count(),
        ];

        return view('superadmin.pazarlama.kampanyalar.show', compact('kampanya', 'gonderilenler', 'istatistik'));
    }

    public function aktifEt(Kampanya $kampanya)
    {
        $this->assertSuperadmin();
        $kampanya->update(['durum' => 'aktif']);
        return back()->with('success', "«{$kampanya->ad}» kampanyası aktif edildi.");
    }

    public function durdur(Kampanya $kampanya)
    {
        $this->assertSuperadmin();
        $kampanya->update(['durum' => 'durduruldu']);
        return back()->with('success', "«{$kampanya->ad}» kampanyası durduruldu.");
    }

    public function destroy(Kampanya $kampanya)
    {
        $this->assertSuperadmin();
        $kampanya->delete();
        return redirect()->route('superadmin.kampanyalar.index')->with('success', 'Kampanya silindi.');
    }

    private function assertSuperadmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
    }
}
// deploy
