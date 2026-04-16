<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogCategory;
use App\Models\B2C\CatalogItem;
use App\Models\B2C\SupplierApplication;
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
            'total_items'       => CatalogItem::count(),
            'published_items'   => CatalogItem::where('is_published', true)->count(),
            'pending_publish'   => CatalogItem::where('is_published', false)->count(),
            'total_categories'  => CatalogCategory::count(),
            'pending_supplier_apps' => SupplierApplication::where('status', 'pending')->count(),
        ];

        $latestItems = CatalogItem::with('category')->latest()->limit(10)->get();
        $pendingApps = SupplierApplication::where('status', 'pending')->latest()->limit(5)->get();

        return view('superadmin.b2c.dashboard', compact('stats', 'latestItems', 'pendingApps'));
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

        CatalogItem::create($validated);

        return redirect()->route('superadmin.b2c.catalog')
            ->with('success', 'Ürün oluşturuldu.');
    }

    public function catalogEdit(CatalogItem $item)
    {
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
