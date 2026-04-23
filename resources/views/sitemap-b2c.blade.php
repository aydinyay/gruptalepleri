<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://gruprezervasyonlari.com/</loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://gruprezervasyonlari.com/hizmetler</loc>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc>https://gruprezervasyonlari.com/grup-ucak-talebi</loc>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc>https://gruprezervasyonlari.com/transfer</loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc>https://gruprezervasyonlari.com/blog</loc>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc>https://gruprezervasyonlari.com/hakkimizda</loc>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
  <url>
    <loc>https://gruprezervasyonlari.com/iletisim</loc>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
  <url>
    <loc>https://gruprezervasyonlari.com/tedarikci-ol</loc>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
@foreach($categories as $cat)
  <url>
    <loc>https://gruprezervasyonlari.com/hizmetler/{{ $cat->slug }}</loc>
    <lastmod>{{ $cat->updated_at->toDateString() }}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
@endforeach
@foreach($items as $item)
  <url>
    <loc>https://gruprezervasyonlari.com/urun/{{ $item->slug }}</loc>
    <lastmod>{{ $item->updated_at->toDateString() }}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
  </url>
@endforeach
@foreach($blogYazilari as $yazi)
  <url>
    <loc>https://gruprezervasyonlari.com/blog/{{ $yazi->slug }}</loc>
    <lastmod>{{ ($yazi->yayinlanma_tarihi ?? $yazi->updated_at)->toDateString() }}</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
@endforeach
</urlset>
