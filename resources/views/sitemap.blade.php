<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://gruptalepleri.com/</loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://gruptalepleri.com/register</loc>
    <changefreq>monthly</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc>https://gruptalepleri.com/acente-tanitim.html</loc>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
    <lastmod>2026-03-27</lastmod>
  </url>
  <url>
    <loc>https://gruptalepleri.com/blog</loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
@foreach($blogYazilari as $yazi)
  <url>
    <loc>https://gruptalepleri.com/blog/{{ $yazi->slug }}</loc>
    <lastmod>{{ ($yazi->yayinlanma_tarihi ?? $yazi->updated_at)->toDateString() }}</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
@endforeach
</urlset>
