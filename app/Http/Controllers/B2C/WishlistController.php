<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cWishlistItem;
use App\Models\B2C\CatalogItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function toggle(Request $request): JsonResponse
    {
        $request->validate(['item_id' => 'required|integer|exists:catalog_items,id']);
        $sid  = session()->getId();
        $iid  = (int) $request->input('item_id');

        $existing = B2cWishlistItem::where('session_id', $sid)->where('catalog_item_id', $iid)->first();

        if ($existing) {
            $existing->delete();
            $saved = false;
        } else {
            B2cWishlistItem::create(['session_id' => $sid, 'catalog_item_id' => $iid, 'created_at' => now()]);
            $saved = true;
        }

        $count = B2cWishlistItem::where('session_id', $sid)->count();

        return response()->json(['saved' => $saved, 'count' => $count]);
    }

    public function index(): \Illuminate\View\View
    {
        $sid   = session()->getId();
        $items = B2cWishlistItem::where('session_id', $sid)
            ->with(['item.category'])
            ->latest('created_at')
            ->get()
            ->pluck('item')
            ->filter();

        $savedIds = $items->pluck('id')->all();

        return view('b2c.wishlist.index', compact('items', 'savedIds'));
    }
}
