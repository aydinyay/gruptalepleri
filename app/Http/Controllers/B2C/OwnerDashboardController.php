<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogItem;
use App\Models\SistemAyar;
use Illuminate\Http\Request;

class OwnerDashboardController extends Controller
{
    private function checkToken(Request $request): bool
    {
        $expected = config('b2c.owner_token', env('GRT_OWNER_TOKEN', ''));
        if (! $expected) return false;
        return $request->get('t') === $expected;
    }

    public function pricing(Request $request)
    {
        if (! $this->checkToken($request)) {
            abort(404);
        }

        $items = CatalogItem::with('category')
            ->orderByRaw("FIELD(publish_status,'b2c','b2b','draft')")
            ->orderBy('product_type')
            ->orderBy('title')
            ->get();

        $token   = $request->get('t');
        $usdKuru = (float) SistemAyar::get('usd_kuru', '34');
        $eurKuru = (float) SistemAyar::get('eur_kuru', '37');

        return view('b2c.owner.pricing', compact('items', 'token', 'usdKuru', 'eurKuru'));
    }

    public function pricingUpdate(Request $request, CatalogItem $item)
    {
        if (! $this->checkToken($request)) {
            abort(404);
        }

        $data = $request->validate([
            'cost_price'     => 'nullable|numeric|min:0',
            'gt_price'       => 'nullable|numeric|min:0',
            'base_price'     => 'nullable|numeric|min:0',
            'pricing_notes'  => 'nullable|string|max:500',
        ]);

        $item->update($data);

        $token = $request->get('t');

        return redirect()->route('b2c.owner.pricing', ['t' => $token])
            ->with('updated', $item->title . ' güncellendi.');
    }
}
