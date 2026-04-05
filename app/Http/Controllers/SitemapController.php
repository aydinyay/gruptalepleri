<?php

namespace App\Http\Controllers;

use App\Models\BlogYazisi;

class SitemapController extends Controller
{
    public function index()
    {
        $blogYazilari = BlogYazisi::yayinda()
            ->latest('yayinlanma_tarihi')
            ->get(['slug', 'updated_at', 'yayinlanma_tarihi']);

        $content = view('sitemap', compact('blogYazilari'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
