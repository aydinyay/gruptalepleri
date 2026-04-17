<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cOrder;
use App\Models\B2C\B2cPayment;
use App\Models\B2C\CatalogCategory;
use App\Models\B2C\CatalogItem;
use App\Models\B2C\CatalogItemLocation;
use App\Models\B2C\SupplierApplication;
use App\Models\CharterPresetPackage;
use App\Models\LeisurePackageTemplate;
use App\Models\TransferVehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Superadmin — B2C Katalog Yönetimi
 *
 * Route prefix: /superadmin/b2c
 * Middleware: auth + role:superadmin
 */
class B2cCatalogController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────────

    public function dashboard()
    {
        $stats = [
            'total_items'           => CatalogItem::count(),
            'published_items'       => CatalogItem::where('is_published', true)->count(),
            'pending_publish'       => CatalogItem::where('is_published', false)->count(),
            'total_categories'      => CatalogCategory::count(),
            'pending_supplier_apps' => SupplierApplication::where('status', 'pending')->count(),
        ];

        $latestItems = CatalogItem::with('category')->latest()->limit(10)->get();
        $pendingApps = SupplierApplication::where('status', 'pending')->latest()->limit(5)->get();

        // Leisure şablonları ve Transfer araçları — mevcut CatalogItem bağlantısıyla
        $linkedItems = CatalogItem::whereIn('reference_type', ['leisure_package', 'transfer_vehicle_type', 'charter_package'])
            ->get()
            ->keyBy(fn ($ci) => $ci->reference_type . '_' . $ci->reference_id);

        $leisureTemplates = LeisurePackageTemplate::orderBy('product_type')->orderBy('sort_order')->get()
            ->each(fn ($t) => $t->catalogItem = $linkedItems->get('leisure_package_' . $t->id));

        $transferVehicleTypes = TransferVehicleType::orderBy('sort_order')->get()
            ->each(fn ($vt) => $vt->catalogItem = $linkedItems->get('transfer_vehicle_type_' . $vt->id));

        $charterPackages = CharterPresetPackage::where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get()
            ->each(fn ($p) => $p->catalogItem = $linkedItems->get('charter_package_' . $p->id));

        $orderStats = [
            'total'           => B2cOrder::count(),
            'pending_payment' => B2cOrder::where('payment_status', 'unpaid')->where('status', 'pending')->count(),
            'paid'            => B2cOrder::where('payment_status', 'paid')->count(),
            'inquiry'         => B2cOrder::whereIn('status', ['pending_quote', 'quote_sent'])->count(),
            'revenue_try'     => (float) B2cPayment::where('status', 'paid')->sum('charged_try_amount'),
        ];

        $recentOrders = B2cOrder::with('item')->latest()->limit(10)->get();

        return view('superadmin.b2c.dashboard', compact(
            'stats', 'latestItems', 'pendingApps',
            'leisureTemplates', 'transferVehicleTypes', 'charterPackages',
            'orderStats', 'recentOrders'
        ));
    }

    // ── B2C Rezervasyon Listesi ────────────────────────────────────────────

    public function ordersIndex(Request $request)
    {
        $orders = B2cOrder::with('item')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->payment, fn ($q) => $q->where('payment_status', $request->payment))
            ->latest()
            ->paginate(30);

        return view('superadmin.b2c.orders', compact('orders'));
    }

    // ── Charter Paketi B2C Toggle ──────────────────────────────────────────

    public function charterTogglePublish(CharterPresetPackage $package): RedirectResponse
    {
        $existing = CatalogItem::where('reference_type', 'charter_package')
            ->where('reference_id', $package->id)
            ->first();

        if ($existing) {
            $nowPublished = ! $existing->is_published;
            $existing->update([
                'is_published'  => $nowPublished,
                'published_at'  => $nowPublished ? now() : null,
                'title'         => $package->title,
                'base_price'    => $package->price,
                'currency'      => $package->currency,
                'cover_image'   => $this->absoluteImageUrl($package->hero_image_url),
            ]);
            $this->syncCharterLocations($existing, $package);
            $msg = $nowPublished ? 'Charter paketi B2C\'de yayına alındı.' : 'Charter paketi B2C\'den kaldırıldı.';
        } else {
            $category = CatalogCategory::where('slug', 'charter')
                ->orWhere('slug', 'ozel-jet-charter')
                ->orWhere('slug', 'air-charter')
                ->first();
            $baseSlug = Str::slug($package->title . '-' . $package->code);
            $slug = $baseSlug;
            $i = 1;
            while (CatalogItem::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }

            $newItem = CatalogItem::create([
                'reference_type'  => 'charter_package',
                'reference_id'    => $package->id,
                'title'           => $package->title,
                'slug'            => $slug,
                'product_type'    => 'charter',
                'pricing_type'    => 'fixed',
                'base_price'      => $package->price,
                'currency'        => $package->currency,
                'cover_image'     => $this->absoluteImageUrl($package->hero_image_url),
                'is_published'    => true,
                'published_at'    => now(),
                'is_active'       => true,
                'category_id'     => $category?->id,
                'short_desc'      => $package->summary,
                'min_pax'         => $package->suggested_pax,
                'destination_city'=> $package->from_label ?? $package->from_iata,
            ]);
            $this->syncCharterLocations($newItem, $package);
            $msg = 'Charter paketi B2C\'ye eklendi ve yayına alındı.';
        }

        return back()->with('success', $msg);
    }

    // ── Leisure şablonu B2C toggle ─────────────────────────────────────────

    public function leisureTogglePublish(LeisurePackageTemplate $template): RedirectResponse
    {
        $existing = CatalogItem::where('reference_type', 'leisure_package')
            ->where('reference_id', $template->id)
            ->first();

        if ($existing) {
            $nowPublished = ! $existing->is_published;
            $existing->update([
                'is_published' => $nowPublished,
                'published_at' => $nowPublished ? now() : $existing->published_at,
                // Fiyat/görsel değişmiş olabilir — tazele
                'title'       => $template->name_tr,
                'short_desc'  => $template->summary_tr,
                'cover_image' => $this->absoluteImageUrl($template->hero_image_url),
                'base_price'  => $template->base_price_per_person,
                'currency'    => $template->currency ?? 'EUR',
                'duration_hours' => $template->duration_hours,
                'max_pax'     => $template->max_pax,
                'rating_avg'  => $template->rating ?? 0,
                'review_count' => $template->review_count ?? 0,
            ]);
            $msg = $nowPublished ? '"' . $template->name_tr . '" B2C\'de yayına alındı.' : '"' . $template->name_tr . '" yayından kaldırıldı.';
        } else {
            $productType = match ($template->product_type) {
                'dinner_cruise'  => 'leisure',
                'yacht_charter'  => 'charter',
                default          => 'tour',
            };

            $slug = Str::slug($template->name_tr);
            if (CatalogItem::where('slug', $slug)->exists()) {
                $slug .= '-' . $template->id;
            }

            CatalogItem::create([
                'reference_type'      => 'leisure_package',
                'reference_id'        => $template->id,
                'title'               => $template->name_tr,
                'slug'                => $slug,
                'short_desc'          => $template->summary_tr,
                'cover_image'         => $this->absoluteImageUrl($template->hero_image_url),
                'product_type'        => $productType,
                'owner_type'          => 'platform',
                'pricing_type'        => 'fixed',
                'base_price'          => $template->base_price_per_person,
                'currency'            => $template->currency ?? 'EUR',
                'duration_hours'      => $template->duration_hours,
                'min_pax'             => 1,
                'max_pax'             => $template->max_pax,
                'rating_avg'          => $template->rating ?? 0,
                'review_count'        => $template->review_count ?? 0,
                'destination_city'    => 'İstanbul',
                'destination_country' => 'Türkiye',
                'is_active'           => true,
                'is_published'        => true,
                'published_at'        => now(),
                'sort_order'          => $template->sort_order ?? 0,
            ]);
            $msg = '"' . $template->name_tr . '" B2C kataloğuna eklendi ve yayına alındı.';
        }

        return back()->with('success', $msg);
    }

    // ── Transfer araç tipi B2C toggle ─────────────────────────────────────

    public function transferVehicleTogglePublish(TransferVehicleType $vehicleType): RedirectResponse
    {
        $existing = CatalogItem::where('reference_type', 'transfer_vehicle_type')
            ->where('reference_id', $vehicleType->id)
            ->first();

        if ($existing) {
            $nowPublished = ! $existing->is_published;
            $existing->update([
                'is_published' => $nowPublished,
                'published_at' => $nowPublished ? now() : $existing->published_at,
                'title'        => $vehicleType->name . ' Transfer',
                'cover_image'  => $vehicleType->firstPhotoUrl(),
                'max_pax'      => $vehicleType->max_passengers,
            ]);
            $msg = $nowPublished ? '"' . $vehicleType->name . '" B2C\'de yayına alındı.' : '"' . $vehicleType->name . '" yayından kaldırıldı.';
        } else {
            $slug = Str::slug($vehicleType->name . ' transfer');
            if (CatalogItem::where('slug', $slug)->exists()) {
                $slug .= '-' . $vehicleType->id;
            }

            CatalogItem::create([
                'reference_type'      => 'transfer_vehicle_type',
                'reference_id'        => $vehicleType->id,
                'title'               => $vehicleType->name . ' Transfer',
                'slug'                => $slug,
                'short_desc'          => $vehicleType->description,
                'cover_image'         => $vehicleType->firstPhotoUrl(),
                'product_type'        => 'transfer',
                'owner_type'          => 'platform',
                'pricing_type'        => 'request',
                'currency'            => 'EUR',
                'min_pax'             => 1,
                'max_pax'             => $vehicleType->max_passengers,
                'destination_city'    => 'İstanbul',
                'destination_country' => 'Türkiye',
                'is_active'           => true,
                'is_published'        => true,
                'published_at'        => now(),
                'sort_order'          => $vehicleType->sort_order ?? 0,
            ]);
            $msg = '"' . $vehicleType->name . '" transfer aracı B2C kataloğuna eklendi ve yayına alındı.';
        }

        return back()->with('success', $msg);
    }

    // ── Kategori CRUD ─────────────────────────────────────────────────────

    public function categories()
    {
        $categories = CatalogCategory::withCount(['items', 'publishedItems'])
            ->rootCategories()
            ->ordered()
            ->with('children')
            ->get();

        return view('superadmin.b2c.categories', compact('categories'));
    }

    public function categoryCreate()
    {
        $parentCategories = CatalogCategory::active()->rootCategories()->ordered()->get();
        return view('superadmin.b2c.category-form', compact('parentCategories'));
    }

    public function categoryStore(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:120',
            'slug'             => 'nullable|string|max:120|unique:catalog_categories,slug',
            'parent_id'        => 'nullable|integer|exists:catalog_categories,id',
            'description'      => 'nullable|string|max:500',
            'icon'             => 'nullable|string|max:50',
            'cover_image'      => 'nullable|string|max:255',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer|min:0',
            'meta_title'       => 'nullable|string|max:120',
            'meta_description' => 'nullable|string|max:250',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        CatalogCategory::create($validated);

        return redirect()->route('superadmin.b2c.categories')
            ->with('success', 'Kategori oluşturuldu.');
    }

    public function categoryEdit(CatalogCategory $category)
    {
        $parentCategories = CatalogCategory::active()->rootCategories()
            ->where('id', '!=', $category->id)->ordered()->get();

        return view('superadmin.b2c.category-form', compact('category', 'parentCategories'));
    }

    public function categoryUpdate(Request $request, CatalogCategory $category)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:120',
            'slug'             => 'nullable|string|max:120|unique:catalog_categories,slug,' . $category->id,
            'parent_id'        => 'nullable|integer|exists:catalog_categories,id',
            'description'      => 'nullable|string|max:500',
            'icon'             => 'nullable|string|max:50',
            'cover_image'      => 'nullable|string|max:255',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer|min:0',
            'meta_title'       => 'nullable|string|max:120',
            'meta_description' => 'nullable|string|max:250',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $category->update($validated);

        return redirect()->route('superadmin.b2c.categories')
            ->with('success', 'Kategori güncellendi.');
    }

    // ── Ürün Kataloğu CRUD ────────────────────────────────────────────────

    public function catalog(Request $request)
    {
        $query = CatalogItem::with('category')
            ->when($request->get('kategori'), fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->get('tip'),      fn ($q, $v) => $q->where('product_type', $v))
            ->when($request->get('durum'),    fn ($q, $v) => match ($v) {
                'published'   => $q->where('is_published', true),
                'unpublished' => $q->where('is_published', false),
                default       => $q,
            })
            ->latest();

        $items      = $query->paginate(20)->withQueryString();
        $categories = CatalogCategory::active()->ordered()->get();

        return view('superadmin.b2c.catalog', compact('items', 'categories'));
    }

    public function catalogCreate()
    {
        $categories = CatalogCategory::active()->ordered()->get();
        return view('superadmin.b2c.catalog-form', compact('categories'));
    }

    public function catalogStore(Request $request)
    {
        $validated = $this->validateCatalogItem($request);
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);

        $validated['is_active']    = $request->boolean('is_active');
        $validated['is_featured']  = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');

        if ($validated['is_published']) {
            $validated['published_at'] = now();
        }

        if ($request->hasFile('cover_image_file')) {
            $validated['cover_image'] = $this->saveCoverImage($request);
        }

        $item = CatalogItem::create($validated);
        $this->syncLocations($item, $request->input('locations_json', '[]'));

        return redirect()->route('superadmin.b2c.catalog')
            ->with('success', 'Ürün oluşturuldu.');
    }

    public function catalogEdit(CatalogItem $item)
    {
        $item->load('locations');
        $categories = CatalogCategory::active()->ordered()->get();
        return view('superadmin.b2c.catalog-form', compact('item', 'categories'));
    }

    public function catalogUpdate(Request $request, CatalogItem $item)
    {
        $validated = $this->validateCatalogItem($request, $item->id);
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);

        // Checkbox alanları: işaretlenmeyince HTTP'de gelmez — boolean() false döner
        $validated['is_active']    = $request->boolean('is_active');
        $validated['is_featured']  = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');

        if ($validated['is_published'] && ! $item->is_published) {
            $validated['published_at'] = now();
        }

        if ($request->hasFile('cover_image_file')) {
            if ($item->cover_image && !str_starts_with($item->cover_image, 'http')) {
                $oldPath = public_path('uploads/' . $item->cover_image);
                if (file_exists($oldPath)) @unlink($oldPath);
            }
            $validated['cover_image'] = $this->saveCoverImage($request);
        }

        $item->update($validated);
        $this->syncLocations($item, $request->input('locations_json', '[]'));

        return redirect()->route('superadmin.b2c.catalog')
            ->with('success', 'Ürün güncellendi.');
    }

    public function catalogTogglePublish(CatalogItem $item)
    {
        $item->update([
            'is_published' => ! $item->is_published,
            'published_at' => ! $item->is_published ? now() : $item->published_at,
        ]);

        $msg = $item->is_published ? 'Ürün yayına alındı.' : 'Ürün yayından kaldırıldı.';
        return back()->with('success', $msg);
    }

    public function catalogToggleFeatured(CatalogItem $item): RedirectResponse
    {
        $item->update(['is_featured' => ! $item->is_featured]);
        $msg = $item->is_featured ? '"' . $item->title . '" öne çıkan olarak işaretlendi.' : '"' . $item->title . '" öne çıkandan kaldırıldı.';
        return back()->with('success', $msg);
    }

    // ── Tedarikçi Başvuruları ─────────────────────────────────────────────

    public function supplierApplications()
    {
        $apps = SupplierApplication::latest()->paginate(20);
        return view('superadmin.b2c.supplier-applications', compact('apps'));
    }

    public function supplierApplicationUpdate(Request $request, SupplierApplication $app)
    {
        $validated = $request->validate([
            'status' => 'required|in:reviewing,approved,rejected',
        ]);

        $app->update([
            'status'              => $validated['status'],
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at'         => now(),
        ]);

        return back()->with('success', 'Başvuru durumu güncellendi.');
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /** Lokasyon listesini JSON'dan alıp tüm mevcut kayıtlarla senkronize eder */
    private function syncLocations(CatalogItem $item, string $json): void
    {
        $rows = collect(json_decode($json, true) ?: [])
            ->filter(fn ($r) => !empty($r['name']) && !empty($r['type']))
            ->map(fn ($r) => [
                'type' => $r['type'],
                'name' => trim($r['name']),
                'slug' => Str::slug(trim($r['name'])),
            ])
            ->unique(fn ($r) => $r['type'] . '|' . $r['slug'])
            ->values();

        $item->locations()->delete();
        foreach ($rows as $row) {
            $item->locations()->create($row);
        }
    }

    /** Charter paket için from/to şehirlerini lokasyon olarak kaydeder */
    private function syncCharterLocations(CatalogItem $item, CharterPresetPackage $package): void
    {
        $locs = [];
        foreach (['from_label' => $package->from_label, 'to_label' => $package->to_label] as $label) {
            if ($label && trim($label) !== '') {
                $locs[] = ['type' => 'il', 'name' => trim($label), 'slug' => Str::slug(trim($label))];
            }
        }
        if (empty($locs)) return;
        $item->locations()->delete();
        foreach (collect($locs)->unique('slug') as $loc) {
            $item->locations()->create($loc);
        }
    }

    /** Göreli yolları gruptalepleri.com tabanlı tam URL'ye çevirir */
    private function absoluteImageUrl(?string $url): ?string
    {
        if (!$url) return null;
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return $url;
        return rtrim(config('app.url', 'https://gruptalepleri.com'), '/') . '/' . ltrim($url, '/');
    }

    private function saveCoverImage(Request $request): string
    {
        $file = $request->file('cover_image_file');
        $ext  = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $name = 'catalog/' . Str::random(32) . '.' . $ext;
        $dest = public_path('uploads/' . dirname($name));

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $file->move($dest, basename($name));

        return $name; // örn: "catalog/AbCdEfGh12345678.jpg"
    }

    private function validateCatalogItem(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'category_id'         => 'nullable|integer|exists:catalog_categories,id',
            'owner_type'          => 'required|in:platform,supplier',
            'supplier_id'         => 'nullable|integer|exists:users,id',
            'product_type'        => 'required|in:transfer,charter,leisure,tour,hotel,visa,other',
            'reference_type'      => 'nullable|string|max:80',
            'reference_id'        => 'nullable|integer',
            'title'               => 'required|string|max:200',
            'slug'                => 'nullable|string|max:200|unique:catalog_items,slug,' . ($ignoreId ?? 'NULL'),
            'short_desc'          => 'nullable|string|max:300',
            'full_desc'           => 'nullable|string',
            'cover_image'         => 'nullable|string|max:255',
            'cover_image_file'    => 'nullable|image|max:4096',
            'pricing_type'        => 'required|in:fixed,quote,request',
            'base_price'          => 'nullable|numeric|min:0',
            'currency'            => 'required|string|size:3',
            'is_active'           => 'boolean',
            'is_featured'         => 'boolean',
            'badge_label'         => 'nullable|string|max:40',
            'is_published'        => 'boolean',
            'destination_city'    => 'nullable|string|max:100',
            'destination_country' => 'nullable|string|max:100',
            'duration_days'       => 'nullable|integer|min:0',
            'duration_hours'      => 'nullable|integer|min:0',
            'min_pax'             => 'nullable|integer|min:1',
            'max_pax'             => 'nullable|integer|min:1',
            'sort_order'          => 'integer|min:0',
            'rating_avg'          => 'nullable|numeric|min:0|max:5',
            'review_count'        => 'nullable|integer|min:0',
            'meta_title'          => 'nullable|string|max:120',
            'meta_description'    => 'nullable|string|max:250',
        ]);
    }
}
