<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogItem;
use Illuminate\Http\Request;

/**
 * Sepet — session bazlı (basit) implementasyon.
 * Faz 5'te DB'ye taşınacak.
 */
class CartController extends Controller
{
    private const SESSION_KEY = 'b2c_cart';

    public function index()
    {
        $cart  = session(self::SESSION_KEY, []);
        $items = $this->hydrateCart($cart);

        return view('b2c.cart.index', compact('items', 'cart'));
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'catalog_item_id' => 'required|integer|exists:catalog_items,id',
            'pax_count'       => 'required|integer|min:1|max:500',
            'service_date'    => 'nullable|date|after:today',
        ]);

        $item = CatalogItem::published()->findOrFail($validated['catalog_item_id']);

        $cart = session(self::SESSION_KEY, []);

        // Aynı ürün zaten sepette mi? (tarih bazlı row)
        $rowId = md5($item->id . ($validated['service_date'] ?? ''));

        $cart[$rowId] = [
            'catalog_item_id' => $item->id,
            'slug'            => $item->slug,
            'title'           => $item->title,
            'pax_count'       => (int) $validated['pax_count'],
            'service_date'    => $validated['service_date'] ?? null,
            'pricing_type'    => $item->pricing_type,
            'base_price'      => $item->base_price,
            'currency'        => $item->currency,
        ];

        session([self::SESSION_KEY => $cart]);

        if ($request->expectsJson()) {
            return response()->json(['count' => count($cart), 'message' => 'Sepete eklendi.']);
        }

        // Ürün sayfasından "Rezervasyon Yap" ile gelindiyse direkt checkout'a gönder
        // (EnsureB2CAuth giriş yapmamış kullanıcıyı B2C login'e yönlendirir)
        if ($request->input('checkout')) {
            return redirect()->route('b2c.checkout.show');
        }

        return redirect()->route('b2c.cart.index')->with('success', '"' . $item->title . '" sepete eklendi.');
    }

    public function update(Request $request, string $rowId)
    {
        $cart = session(self::SESSION_KEY, []);

        if (isset($cart[$rowId])) {
            $cart[$rowId]['pax_count'] = max(1, (int) $request->input('pax_count', 1));
            session([self::SESSION_KEY => $cart]);
        }

        return back();
    }

    public function remove(string $rowId)
    {
        $cart = session(self::SESSION_KEY, []);
        unset($cart[$rowId]);
        session([self::SESSION_KEY => $cart]);

        return back()->with('success', 'Ürün sepetten kaldırıldı.');
    }

    private function hydrateCart(array $cart): array
    {
        if (empty($cart)) return [];

        $ids   = array_column($cart, 'catalog_item_id');
        $dbMap = CatalogItem::published()->whereIn('id', $ids)->get()->keyBy('id');

        $result = [];
        foreach ($cart as $rowId => $row) {
            $dbItem = $dbMap->get($row['catalog_item_id']);
            if ($dbItem) {
                $result[$rowId] = array_merge($row, ['model' => $dbItem]);
            }
        }

        return $result;
    }
}
