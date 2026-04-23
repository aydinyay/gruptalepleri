<?php

namespace App\Http\Controllers;

use App\Models\BlogYazisi;
use App\Models\B2C\CatalogItem;
use App\Models\B2C\CatalogCategory;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function index(Request $request)
    {
        $isB2C = $request->attributes->get('is_b2c', false);

        if ($isB2C) {
            return $this->b2cSitemap();
        }

        return $this->b2bSitemap();
    }

    private function b2bSitemap()
    {
        $blogYazilari = BlogYazisi::yayinda()
            ->latest('yayinlanma_tarihi')
            ->get(['slug', 'updated_at', 'yayinlanma_tarihi']);

        $content = view('sitemap', compact('blogYazilari'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    private function b2cSitemap()
    {
        $items = CatalogItem::published()
            ->latest('updated_at')
            ->get(['slug', 'updated_at']);

        $categories = CatalogCategory::whereHas('items', fn($q) => $q->published())
            ->get(['slug', 'updated_at']);

        $blogYazilari = BlogYazisi::yayinda()
            ->latest('yayinlanma_tarihi')
            ->get(['slug', 'updated_at', 'yayinlanma_tarihi']);

        $content = view('sitemap-b2c', compact('items', 'categories', 'blogYazilari'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
