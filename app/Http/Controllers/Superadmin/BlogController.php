<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\BlogKategorisi;
use App\Models\BlogYazisi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $yaziler = BlogYazisi::with('kategori')->latest()->paginate(20);
        return view('superadmin.blog.index', compact('yaziler'));
    }

    public function create()
    {
        $kategoriler = BlogKategorisi::where('aktif', true)->orderBy('ad')->get();
        return view('superadmin.blog.form', compact('kategoriler'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'baslik'             => 'required|string|max:255',
            'ozet'               => 'required|string|max:500',
            'icerik'             => 'required|string',
            'kategori_id'        => 'nullable|exists:blog_kategorileri,id',
            'kapak_gorseli_url'  => 'nullable|url|max:500',
            'kapak_gorseli_file' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120',
            'meta_baslik'        => 'nullable|string|max:255',
            'meta_aciklama'      => 'nullable|string|max:320',
            'yazar'              => 'nullable|string|max:100',
            'durum'              => 'required|in:taslak,yayinda',
            'yayinlanma_tarihi'  => 'nullable|date',
        ]);

        $data['kapak_gorseli'] = $this->gorselKaydet($request);
        unset($data['kapak_gorseli_url'], $data['kapak_gorseli_file']);

        $data['slug'] = $this->uniqueSlug($request->baslik);
        $data['yazar'] = $data['yazar'] ?: 'GrupTalepleri Editör';
        if ($data['durum'] === 'yayinda' && empty($data['yayinlanma_tarihi'])) {
            $data['yayinlanma_tarihi'] = now();
        }

        BlogYazisi::create($data);

        return redirect()->route('superadmin.blog.index')->with('success', 'Yazı oluşturuldu.');
    }

    public function edit(BlogYazisi $blog)
    {
        $kategoriler = BlogKategorisi::where('aktif', true)->orderBy('ad')->get();
        return view('superadmin.blog.form', compact('blog', 'kategoriler'));
    }

    public function update(Request $request, BlogYazisi $blog)
    {
        $data = $request->validate([
            'baslik'             => 'required|string|max:255',
            'ozet'               => 'required|string|max:500',
            'icerik'             => 'required|string',
            'kategori_id'        => 'nullable|exists:blog_kategorileri,id',
            'kapak_gorseli_url'  => 'nullable|url|max:500',
            'kapak_gorseli_file' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120',
            'gorsel_sil'         => 'nullable|boolean',
            'meta_baslik'        => 'nullable|string|max:255',
            'meta_aciklama'      => 'nullable|string|max:320',
            'yazar'              => 'nullable|string|max:100',
            'durum'              => 'required|in:taslak,yayinda',
            'yayinlanma_tarihi'  => 'nullable|date',
        ]);

        $yeniGorsel = $this->gorselKaydet($request, $blog->kapak_gorseli);
        $data['kapak_gorseli'] = $yeniGorsel;
        unset($data['kapak_gorseli_url'], $data['kapak_gorseli_file'], $data['gorsel_sil']);

        if ($data['durum'] === 'yayinda' && empty($data['yayinlanma_tarihi']) && !$blog->yayinlanma_tarihi) {
            $data['yayinlanma_tarihi'] = now();
        }

        $blog->update($data);

        return redirect()->route('superadmin.blog.index')->with('success', 'Yazı güncellendi.');
    }

    public function destroy(BlogYazisi $blog)
    {
        $blog->delete();
        return back()->with('success', 'Yazı silindi.');
    }

    // Kategoriler
    public function kategoriler()
    {
        $kategoriler = BlogKategorisi::withCount('yaziler')->orderBy('ad')->get();
        return view('superadmin.blog.kategoriler', compact('kategoriler'));
    }

    public function kategoriStore(Request $request)
    {
        $data = $request->validate(['ad' => 'required|string|max:100']);
        $slug = Str::slug($data['ad'], '-');
        $i = 1;
        $base = $slug;
        while (BlogKategorisi::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        BlogKategorisi::create(['ad' => $data['ad'], 'slug' => $slug]);
        return back()->with('success', 'Kategori eklendi.');
    }

    public function kategoriDestroy(BlogKategorisi $kategori)
    {
        $kategori->delete();
        return back()->with('success', 'Kategori silindi.');
    }

    private function gorselKaydet(Request $request, ?string $mevcutGorsel = null): ?string
    {
        // Görsel sil işaretlendiyse ve mevcut görsel yerel dosyaysa sil
        if ($request->boolean('gorsel_sil')) {
            $this->yerelGorselSil($mevcutGorsel);
            return null;
        }

        // Dosya yüklendiyse
        if ($request->hasFile('kapak_gorseli_file') && $request->file('kapak_gorseli_file')->isValid()) {
            $this->yerelGorselSil($mevcutGorsel);
            $file = $request->file('kapak_gorseli_file');
            $ad = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/blog'), $ad);
            return '/uploads/blog/' . $ad;
        }

        // URL girilmişse
        $url = trim($request->input('kapak_gorseli_url', ''));
        if ($url !== '') {
            // Eski yerel dosyayı silme (URL'e geçiş)
            if ($mevcutGorsel && str_starts_with($mevcutGorsel, '/uploads/')) {
                $this->yerelGorselSil($mevcutGorsel);
            }
            return $url;
        }

        // Değişiklik yok
        return $mevcutGorsel;
    }

    private function yerelGorselSil(?string $gorsel): void
    {
        if ($gorsel && str_starts_with($gorsel, '/uploads/')) {
            $path = public_path(ltrim($gorsel, '/'));
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    private function uniqueSlug(string $baslik): string
    {
        $slug = Str::slug($baslik, '-');
        $i = 1;
        $base = $slug;
        while (BlogYazisi::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
