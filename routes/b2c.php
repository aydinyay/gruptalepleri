<?php

use App\Http\Controllers\B2C\HomeController;
use App\Http\Controllers\B2C\CatalogController;
use App\Http\Controllers\B2C\ProductController;
use App\Http\Controllers\B2C\CustomerAuthController;
use App\Http\Controllers\B2C\CustomerProfileController;
use App\Http\Controllers\B2C\CartController;
use App\Http\Controllers\B2C\CheckoutController;
use App\Http\Controllers\B2C\OrderController;
use App\Http\Controllers\B2C\SupplierApplyController;
use App\Http\Controllers\B2C\QuickLeadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| B2C Routes — gruprezervasyonlari.com
|--------------------------------------------------------------------------
|
| Bu dosyadaki route'ların tümü yalnızca DomainRouter middleware'i
| aracılığıyla gruprezervasyonlari.com domain'ine yönlendirilir.
| B2B gruptalepleri.com tarafında bu route'lar aktif DEĞİLDİR.
|
| Route isimleri 'b2c.' prefix'i ile başlar.
|
*/

// ── Ana Sayfa ──────────────────────────────────────────────────────────────
// NOT: / rotası web.php'de tanımlıdır (her iki domain'i de yönetir).
// b2c.php sonradan yüklendiğinden burada tekrar tanımlanmamalı — ezip bozar.

// ── Arama Autocomplete API ─────────────────────────────────────────────────
Route::get('/api/search-suggest', function (\Illuminate\Http\Request $request) {
    $q = trim($request->get('q', ''));
    $results = ['popular' => [], 'items' => []];

    if (strlen($q) < 2) {
        // Boş/kısa sorgu → popüler destinasyonlar + kategoriler
        $results['popular'] = [
            ['type'=>'city',  'icon'=>'bi-geo-alt-fill', 'label'=>'İstanbul',  'sub'=>'Türkiye'],
            ['type'=>'city',  'icon'=>'bi-geo-alt-fill', 'label'=>'Antalya',   'sub'=>'Türkiye'],
            ['type'=>'city',  'icon'=>'bi-geo-alt-fill', 'label'=>'Bodrum',    'sub'=>'Türkiye'],
            ['type'=>'city',  'icon'=>'bi-geo-alt-fill', 'label'=>'Kapadokya', 'sub'=>'Türkiye'],
            ['type'=>'city',  'icon'=>'bi-geo-alt-fill', 'label'=>'Marmaris',  'sub'=>'Türkiye'],
            ['type'=>'city',  'icon'=>'bi-geo-alt-fill', 'label'=>'Dubai',     'sub'=>'BAE'],
        ];
    } else {
        // Ürün ara
        $items = \App\Models\B2C\CatalogItem::published()
            ->where(fn($qb) => $qb->where('title', 'like', "%{$q}%")
                ->orWhere('destination_city', 'like', "%{$q}%"))
            ->with('category')
            ->limit(5)
            ->get(['id','title','slug','product_type','destination_city','base_price','currency','pricing_type']);

        foreach ($items as $item) {
            $typeIcons = ['transfer'=>'bi-car-front-fill','charter'=>'bi-airplane-fill','leisure'=>'bi-water','tour'=>'bi-map-fill','hotel'=>'bi-building','visa'=>'bi-passport','other'=>'bi-grid'];
            $results['items'][] = [
                'type'  => 'product',
                'icon'  => $typeIcons[$item->product_type] ?? 'bi-grid',
                'label' => $item->title,
                'sub'   => $item->destination_city ?? ($item->category->name ?? ''),
                'url'   => route('b2c.product.show', $item->slug),
                'price' => $item->pricing_type === 'fixed' && $item->base_price
                    ? number_format($item->base_price, 0, ',', '.') . ' ' . $item->currency
                    : null,
            ];
        }

        // Şehir eşleşmesi
        $cities = \App\Models\B2C\CatalogItem::published()
            ->where('destination_city', 'like', "%{$q}%")
            ->whereNotNull('destination_city')
            ->selectRaw('destination_city, COUNT(*) as cnt')
            ->groupBy('destination_city')
            ->limit(3)
            ->get();

        foreach ($cities as $c) {
            $results['popular'][] = [
                'type'  => 'city',
                'icon'  => 'bi-geo-alt-fill',
                'label' => $c->destination_city,
                'sub'   => $c->cnt . ' aktivite',
            ];
        }
    }

    return response()->json($results);
})->name('b2c.api.search-suggest')->middleware('throttle:60,1');

// ── Hizmet Kategorileri ────────────────────────────────────────────────────
Route::get('/hizmetler', [CatalogController::class, 'index'])->name('b2c.catalog.index');
Route::get('/hizmetler/{slug}', [CatalogController::class, 'category'])->name('b2c.catalog.category');

// ── Ürün Detay ─────────────────────────────────────────────────────────────
Route::get('/urun/{slug}', [ProductController::class, 'show'])->name('b2c.product.show');

// ── Destinasyon Landing Pages ──────────────────────────────────────────────
Route::get('/destinasyon/{slug}', [CatalogController::class, 'destination'])->name('b2c.destination');

// ── Hızlı Teklif / Lead Toplama ────────────────────────────────────────────
Route::post('/hizli-teklif', [QuickLeadController::class, 'store'])
    ->name('b2c.quick-lead.store')
    ->middleware('throttle:10,1');

// ── Blog (mevcut blog_yazilari tablosu kullanılır) ─────────────────────────
Route::get('/blog', [\App\Http\Controllers\BlogPublicController::class, 'index'])->name('b2c.blog.index');
Route::get('/blog/{slug}', [\App\Http\Controllers\BlogPublicController::class, 'show'])->name('b2c.blog.show');

// ── Tedarikçi Ol ───────────────────────────────────────────────────────────
Route::get('/tedarikci-ol', [SupplierApplyController::class, 'show'])->name('b2c.supplier-apply.show');
Route::post('/tedarikci-ol', [SupplierApplyController::class, 'store'])
    ->name('b2c.supplier-apply.store')
    ->middleware('throttle:5,1');

// ── Hakkımızda / Statik Sayfalar ───────────────────────────────────────────
Route::get('/hakkimizda', fn () => view('b2c.static.hakkimizda'))->name('b2c.hakkimizda');
Route::get('/iletisim', fn () => view('b2c.static.iletisim'))->name('b2c.iletisim');
Route::post('/iletisim', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'name'    => 'required|string|max:100',
        'email'   => 'required|email|max:150',
        'phone'   => 'nullable|string|max:30',
        'subject' => 'required|string|max:100',
        'message' => 'required|string|max:2000',
    ]);
    // TODO: mail gönder veya DB'ye kaydet
    return back()->with('contact_success', true);
})->name('b2c.iletisim.post')->middleware('throttle:5,1');
Route::get('/kvkk', fn () => view('b2c.static.kvkk'))->name('b2c.kvkk');
Route::get('/gizlilik-politikasi', fn () => view('b2c.static.gizlilik'))->name('b2c.gizlilik');
Route::get('/mesafeli-satis-sozlesmesi', fn () => view('b2c.static.mesafeli-satis'))->name('b2c.mesafeli-satis');
Route::get('/iptal-iade', fn () => view('b2c.static.iptal-iade'))->name('b2c.iptal-iade');
Route::get('/on-bilgilendirme', fn () => view('b2c.static.on-bilgilendirme'))->name('b2c.on-bilgilendirme');

// ── B2C Auth (Müşteri Girişi) ──────────────────────────────────────────────
Route::middleware('guest:b2c')->group(function () {
    Route::get('/hesabim/giris', [CustomerAuthController::class, 'showLogin'])->name('b2c.auth.login');
    Route::post('/hesabim/giris', [CustomerAuthController::class, 'login'])->name('b2c.auth.login.post')
        ->middleware('throttle:10,1');

    Route::get('/hesabim/kayit', [CustomerAuthController::class, 'showRegister'])->name('b2c.auth.register');
    Route::post('/hesabim/kayit', [CustomerAuthController::class, 'register'])->name('b2c.auth.register.post')
        ->middleware('throttle:5,1');

    Route::get('/hesabim/sifremi-unuttum', [CustomerAuthController::class, 'showForgotPassword'])->name('b2c.auth.forgot');
    Route::post('/hesabim/sifremi-unuttum', [CustomerAuthController::class, 'sendResetLink'])->name('b2c.auth.forgot.post')
        ->middleware('throttle:5,1');

    Route::get('/hesabim/sifre-sifirla/{token}', [CustomerAuthController::class, 'showResetPassword'])->name('b2c.auth.reset');
    Route::post('/hesabim/sifre-sifirla', [CustomerAuthController::class, 'resetPassword'])->name('b2c.auth.reset.post');
});

Route::post('/hesabim/cikis', [CustomerAuthController::class, 'logout'])->name('b2c.auth.logout');

// ── Müşteri Paneli (giriş zorunlu) ────────────────────────────────────────
Route::middleware('b2c_auth')->prefix('hesabim')->name('b2c.account.')->group(function () {
    Route::get('/', [CustomerProfileController::class, 'index'])->name('index');
    Route::get('/profil', [CustomerProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [CustomerProfileController::class, 'update'])->name('profile.update');
    Route::get('/siparislerim', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/siparislerim/{ref}', [OrderController::class, 'show'])->name('orders.show');
});

// ── Sepet ─────────────────────────────────────────────────────────────────
Route::prefix('sepet')->name('b2c.cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/ekle', [CartController::class, 'add'])->name('add')->middleware('throttle:30,1');
    Route::patch('/guncelle/{rowId}', [CartController::class, 'update'])->name('update');
    Route::delete('/sil/{rowId}', [CartController::class, 'remove'])->name('remove');
});

// ── Ödeme ─────────────────────────────────────────────────────────────────
Route::middleware('b2c_auth')->prefix('odeme')->name('b2c.checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'show'])->name('show');
    Route::post('/olustur', [CheckoutController::class, 'create'])->name('create')->middleware('throttle:5,1');
    Route::get('/basarili', [CheckoutController::class, 'success'])->name('success');
    Route::get('/basarisiz', [CheckoutController::class, 'fail'])->name('fail');
});

// ── Paynkolay Callback (CSRF muaf — ödeme gateway'inden gelir) ────────────
Route::withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->prefix('odeme/callback')->name('b2c.payment.callback.')->group(function () {
        Route::match(['get', 'post'], '/basarili', [CheckoutController::class, 'paynkolaySuccess'])->name('success');
        Route::match(['get', 'post'], '/basarisiz', [CheckoutController::class, 'paynkolayFail'])->name('fail');
    });

// ── Guest (Misafir) Rezervasyon — Tüm Ürün Tipleri ────────────────────────────
Route::post('/urun/{slug}/rezervasyon', [\App\Http\Controllers\B2C\GuestBookingController::class, 'book'])
    ->name('b2c.guest.booking.book')
    ->middleware('throttle:10,1');
Route::get('/rezervasyon/{ref}', [\App\Http\Controllers\B2C\GuestBookingController::class, 'show'])
    ->name('b2c.guest.booking.show');
Route::post('/rezervasyon/{order}/odeme', [\App\Http\Controllers\B2C\GuestPaymentController::class, 'start'])
    ->name('b2c.guest.payment.start')
    ->middleware('throttle:5,1');
Route::withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->prefix('rezervasyon/odeme')->name('b2c.guest.payment.')->group(function () {
        Route::match(['get', 'post'], '/basarili', [\App\Http\Controllers\B2C\GuestPaymentController::class, 'success'])->name('success');
        Route::match(['get', 'post'], '/basarisiz', [\App\Http\Controllers\B2C\GuestPaymentController::class, 'fail'])->name('fail');
    });

// ── Leisure / Yat Rezervasyon ──────────────────────────────────────────────────
Route::post('/urun/leisure-talep', [\App\Http\Controllers\B2C\LeisureInquiryController::class, 'store'])
    ->name('b2c.leisure.inquiry.store')
    ->middleware('throttle:10,1');
Route::get('/urun/leisure-talep/tesekkur', [\App\Http\Controllers\B2C\LeisureInquiryController::class, 'confirm'])
    ->name('b2c.leisure.inquiry.confirm');
Route::get('/urun/leisure-rezervasyon/{gtpnr}', [\App\Http\Controllers\B2C\LeisureInquiryController::class, 'show'])
    ->name('b2c.leisure.booking.show');
Route::post('/urun/leisure-odeme/{booking}', [\App\Http\Controllers\B2C\LeisurePaymentController::class, 'start'])
    ->name('b2c.leisure.payment.start')
    ->middleware('throttle:5,1');

// Paynkolay B2C leisure callbacks (CSRF muaf)
Route::withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->prefix('urun/leisure-odeme')->name('b2c.leisure.payment.')->group(function () {
        Route::match(['get', 'post'], '/basarili', [\App\Http\Controllers\B2C\LeisurePaymentController::class, 'success'])->name('success');
        Route::match(['get', 'post'], '/basarisiz', [\App\Http\Controllers\B2C\LeisurePaymentController::class, 'fail'])->name('fail');
    });

// ── B2C Transfer ───────────────────────────────────────────────────────────
Route::prefix('transfer')->name('b2c.transfer.')->group(function () {
    Route::get('/',                                      [\App\Http\Controllers\B2C\TransferController::class, 'index'])->name('index');
    Route::get('/bolgeler',                              [\App\Http\Controllers\B2C\TransferController::class, 'zones'])->name('zones');
    Route::get('/fiyat-sorgula',                         [\App\Http\Controllers\B2C\TransferController::class, 'priceQuery'])->name('price-query')->middleware('throttle:30,1');
    Route::post('/ara',                                  [\App\Http\Controllers\B2C\TransferController::class, 'search'])->name('search');
    Route::get('/checkout/{quoteToken}',                 [\App\Http\Controllers\B2C\TransferController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/{quoteToken}/rezervasyon',    [\App\Http\Controllers\B2C\TransferController::class, 'book'])->name('book');
    Route::get('/rezervasyon/{bookingRef}',              [\App\Http\Controllers\B2C\TransferController::class, 'bookingShow'])->name('booking');
    Route::get('/rezervasyon/{bookingRef}/voucher',     [\App\Http\Controllers\B2C\TransferController::class, 'bookingVoucher'])->name('voucher');
});

// Transfer ödeme callback'leri (CSRF muaf)
Route::withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->prefix('transfer/odeme')->name('b2c.transfer.payment.')->group(function () {
        Route::match(['get', 'post'], '/basarili', [\App\Http\Controllers\B2C\TransferController::class, 'paymentSuccess'])->name('success');
        Route::match(['get', 'post'], '/basarisiz', [\App\Http\Controllers\B2C\TransferController::class, 'paymentFail'])->name('fail');
    });

