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

        $xml = $isB2C ? $this->buildB2C() : $this->buildB2B();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function url(string $loc, string $changefreq, float $priority, ?string $lastmod = null): string
    {
        $lastmodTag = $lastmod ? "\n    <lastmod>{$lastmod}</lastmod>" : '';
        $p = number_format($priority, 1, '.', '');
        return "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>{$lastmodTag}\n    <changefreq>{$changefreq}</changefreq>\n    <priority>{$p}</priority>\n  </url>\n";
    }

    private function buildB2B(): string
    {
        $lines = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $lines .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $lines .= $this->url('https://gruptalepleri.com/', 'daily', 1.0);
        $lines .= $this->url('https://gruptalepleri.com/register', 'monthly', 0.9);
        $lines .= $this->url('https://gruptalepleri.com/acente-tanitim.html', 'monthly', 0.8, '2026-03-27');
        $lines .= $this->url('https://gruptalepleri.com/blog', 'weekly', 0.8);

        BlogYazisi::yayinda()->latest('yayinlanma_tarihi')->get(['slug', 'updated_at', 'yayinlanma_tarihi'])
            ->each(function ($y) use (&$lines) {
                $d = ($y->yayinlanma_tarihi ?? $y->updated_at)?->toDateString();
                $lines .= $this->url('https://gruptalepleri.com/blog/' . $y->slug, 'monthly', 0.7, $d);
            });

        $lines .= '</urlset>';
        return $lines;
    }

    private function buildB2C(): string
    {
        $lines = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $lines .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $base = 'https://gruprezervasyonlari.com';
        $lines .= $this->url("{$base}/", 'daily', 1.0);
        $lines .= $this->url("{$base}/hizmetler", 'weekly', 0.9);
        $lines .= $this->url("{$base}/grup-ucak-talebi", 'monthly', 0.8);
        $lines .= $this->url("{$base}/transfer", 'weekly', 0.8);
        $lines .= $this->url("{$base}/blog", 'weekly', 0.7);
        $lines .= $this->url("{$base}/hakkimizda", 'monthly', 0.5);
        $lines .= $this->url("{$base}/iletisim", 'monthly', 0.5);
        $lines .= $this->url("{$base}/tedarikci-ol", 'monthly', 0.6);

        CatalogCategory::whereHas('items', fn($q) => $q->published())
            ->get(['slug', 'updated_at'])
            ->each(function ($cat) use (&$lines, $base) {
                $lines .= $this->url("{$base}/hizmetler/{$cat->slug}", 'weekly', 0.8, $cat->updated_at?->toDateString());
            });

        CatalogItem::published()->latest('updated_at')->get(['slug', 'updated_at'])
            ->each(function ($item) use (&$lines, $base) {
                $lines .= $this->url("{$base}/urun/{$item->slug}", 'weekly', 0.9, $item->updated_at?->toDateString());
            });

        BlogYazisi::yayinda()->latest('yayinlanma_tarihi')->get(['slug', 'updated_at', 'yayinlanma_tarihi'])
            ->each(function ($y) use (&$lines, $base) {
                $d = ($y->yayinlanma_tarihi ?? $y->updated_at)?->toDateString();
                $lines .= $this->url("{$base}/blog/{$y->slug}", 'monthly', 0.6, $d);
            });

        $lines .= '</urlset>';
        return $lines;
    }
}
