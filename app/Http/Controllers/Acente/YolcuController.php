<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Acente\Concerns\ResolvesPreviewUser;
use App\Models\Request as TalepModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YolcuController extends Controller
{
    use ResolvesPreviewUser;

    public function index(string $gtpnr)
    {
        $talep = $this->talepYetki($gtpnr);
        $yolcular = $talep->yolcular()->get();

        return view('acente.yolcular', compact('talep', 'yolcular'));
    }

    public function store(Request $request, string $gtpnr)
    {
        if ($blocked = $this->blockPreviewWrites()) {
            return $blocked;
        }

        $talep = $this->talepYetki($gtpnr);

        $request->validate([
            'ad'           => 'required|string|max:100',
            'soyad'        => 'required|string|max:100',
            'tur'          => 'required|in:yetiskin,cocuk,infant',
            'kimlik_no'    => 'nullable|string|max:50',
            'kimlik_tipi'  => 'nullable|in:tc,pasaport',
            'dogum_tarihi' => 'nullable|date',
            'uyruk'        => 'nullable|string|max:3',
            'cinsiyet'     => 'nullable|in:erkek,kadin',
        ]);

        $sira = $talep->yolcular()->max('sira') + 1;

        $talep->yolcular()->create([
            'sira'         => $sira,
            'ad'           => strtoupper(trim($request->ad)),
            'soyad'        => strtoupper(trim($request->soyad)),
            'tur'          => $request->tur ?? 'yetiskin',
            'kimlik_no'    => $request->kimlik_no ?: null,
            'kimlik_tipi'  => $request->kimlik_tipi ?? 'tc',
            'dogum_tarihi' => $request->dogum_tarihi ?: null,
            'uyruk'        => $request->uyruk ? strtoupper($request->uyruk) : null,
            'cinsiyet'     => $request->cinsiyet ?: null,
            'olusturan_id' => auth()->id(),
        ]);

        return back()->with('success', 'Yolcu eklendi.');
    }

    public function destroy(string $gtpnr, int $id)
    {
        if ($blocked = $this->blockPreviewWrites()) {
            return $blocked;
        }

        $talep = $this->talepYetki($gtpnr);
        $talep->yolcular()->findOrFail($id)->delete();

        // Sıra numaralarını yenile
        $talep->yolcular()->orderBy('sira')->get()->each(function ($y, $i) {
            $y->update(['sira' => $i + 1]);
        });

        return back()->with('success', 'Yolcu silindi.');
    }

    public function sablonIndir()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="yolcu-listesi-sablon.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Ad', 'Soyad', 'TC/Pasaport No', 'Kimlik Tipi (tc/pasaport)', 'Dogum Tarihi (YYYY-AA-GG)', 'Uyruk (TR/DE...)', 'Cinsiyet (erkek/kadin)', 'Tur (yetiskin/cocuk/infant)'], ';');
            fputcsv($handle, ['AHMET', 'YILMAZ', '12345678901', 'tc', '1985-06-15', 'TR', 'erkek', 'yetiskin'], ';');
            fputcsv($handle, ['AYSE', 'KAYA', 'A1234567', 'pasaport', '1990-03-22', 'DE', 'kadin', 'yetiskin'], ';');
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function csvYukle(Request $request, string $gtpnr)
    {
        if ($blocked = $this->blockPreviewWrites()) {
            return $blocked;
        }

        $talep = $this->talepYetki($gtpnr);

        $request->validate(['csv_dosya' => 'required|file|mimes:csv,txt|max:2048']);

        $path    = $request->file('csv_dosya')->getRealPath();
        $handle  = fopen($path, 'r');
        $hatalar = [];
        $eklendi = 0;
        $satirNo = 0;
        $siradaki = $talep->yolcular()->max('sira') + 1;

        while (($satir = fgetcsv($handle, 1000, ';')) !== false) {
            $satirNo++;

            // Başlık satırını atla
            if ($satirNo === 1) {
                continue;
            }

            // Boş satırı atla
            if (empty(array_filter($satir))) {
                continue;
            }

            // Sütun sayısı kontrolü
            if (count($satir) < 2) {
                $hatalar[] = "Satır {$satirNo}: Yetersiz sütun.";
                continue;
            }

            $ad           = strtoupper(trim($satir[0] ?? ''));
            $soyad        = strtoupper(trim($satir[1] ?? ''));
            $kimlikNo     = trim($satir[2] ?? '') ?: null;
            $kimlikTipi   = in_array(trim($satir[3] ?? ''), ['tc', 'pasaport']) ? trim($satir[3]) : 'tc';
            $dogumTarihi  = $this->parseTarih(trim($satir[4] ?? ''));
            $uyruk        = strtoupper(trim($satir[5] ?? '')) ?: null;
            $cinsiyet     = in_array(trim($satir[6] ?? ''), ['erkek', 'kadin']) ? trim($satir[6]) : null;
            $tur          = in_array(trim($satir[7] ?? ''), ['yetiskin', 'cocuk', 'infant']) ? trim($satir[7]) : 'yetiskin';

            if (empty($ad) || empty($soyad)) {
                $hatalar[] = "Satır {$satirNo}: Ad ve soyad zorunlu.";
                continue;
            }

            $talep->yolcular()->create([
                'sira'         => $siradaki++,
                'ad'           => $ad,
                'soyad'        => $soyad,
                'tur'          => $tur,
                'kimlik_no'    => $kimlikNo,
                'kimlik_tipi'  => $kimlikTipi,
                'dogum_tarihi' => $dogumTarihi,
                'uyruk'        => $uyruk,
                'cinsiyet'     => $cinsiyet,
                'olusturan_id' => auth()->id(),
            ]);
            $eklendi++;
        }

        fclose($handle);

        $mesaj = "{$eklendi} yolcu eklendi.";
        if (!empty($hatalar)) {
            $mesaj .= ' Hatalar: ' . implode(' | ', $hatalar);
        }

        return back()->with('success', $mesaj);
    }

    private function parseTarih(string $deger): ?string
    {
        if (empty($deger)) return null;
        // YYYY-MM-DD veya YYYY-AA-GG (Türkçe)
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $deger)) {
            return $deger;
        }
        // DD.MM.YYYY
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $deger, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return null;
    }

    private function talepYetki(string $gtpnr): TalepModel
    {
        $query = TalepModel::where('gtpnr', $gtpnr);

        if (!in_array(auth()->user()?->role, ['admin', 'superadmin']) && !$this->isAcentePreviewMode()) {
            $rootId = auth()->user()->acenteRootId();
            $agencyIds = User::where('parent_agency_id', $rootId)->pluck('id')->push($rootId);
            $query->whereIn('user_id', $agencyIds);
        }

        return $query->firstOrFail();
    }
}
