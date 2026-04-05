<?php

namespace App\Http\Controllers;

use App\Models\BlogKategorisi;
use App\Models\BlogYazisi;

class BlogPublicController extends Controller
{
    public function index()
    {
        $yaziler    = BlogYazisi::yayinda()->with('kategori')->latest('yayinlanma_tarihi')->paginate(9);
        $kategoriler = BlogKategorisi::whereHas('yaziler', fn($q) => $q->yayinda())->get();
        return view('blog.index', compact('yaziler', 'kategoriler'));
    }

    public function kategori(BlogKategorisi $kategori)
    {
        $yaziler    = $kategori->yaziler()->yayinda()->latest('yayinlanma_tarihi')->paginate(9);
        $kategoriler = BlogKategorisi::whereHas('yaziler', fn($q) => $q->yayinda())->get();
        return view('blog.index', compact('yaziler', 'kategoriler', 'kategori'));
    }

    public function show(string $slug)
    {
        $yazi = BlogYazisi::yayinda()->where('slug', $slug)->firstOrFail();
        $yazi->increment('goruntuleme');

        $ilgili = BlogYazisi::yayinda()
            ->where('id', '!=', $yazi->id)
            ->where('kategori_id', $yazi->kategori_id)
            ->latest('yayinlanma_tarihi')
            ->limit(3)
            ->get();

        return view('blog.show', compact('yazi', 'ilgili'));
    }
}
