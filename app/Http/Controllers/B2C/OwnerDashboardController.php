<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogItem;
use App\Models\SistemAyar;
use Illuminate\Http\Request;

class OwnerDashboardController extends Controller
{
    private function authenticate(Request $request): bool
    {
        // Zaten oturumu doğrulanmış mı?
        if (session('owner_auth') === true) {
            return true;
        }

        // URL'den token geliyorsa doğrula ve session'a al
        $expected = config('b2c.owner_token', env('GRT_OWNER_TOKEN', ''));
        if (! $expected) return false;

        if ($request->get('t') === $expected) {
            session(['owner_auth' => true]);
            return true;
        }

        return false;
    }

    public function pricing(Request $request)
    {
        if (! $this->authenticate($request)) {
            abort(404);
        }

        // Token URL'de varsa temiz URL'ye yönlendir
        if ($request->has('t')) {
            return redirect()->route('b2c.owner.pricing');
        }

        $items = CatalogItem::with('category')
            ->orderByRaw("FIELD(publish_status,'b2c','b2b','draft')")
            ->orderBy('product_type')
            ->orderBy('title')
            ->get();

        $usdKuru = (float) SistemAyar::get('usd_kuru', '34');
        $eurKuru = (float) SistemAyar::get('eur_kuru', '37');

        return view('b2c.owner.pricing', compact('items', 'usdKuru', 'eurKuru'));
    }

    public function pricingUpdate(Request $request, CatalogItem $item)
    {
        if (! $this->authenticate($request)) {
            abort(404);
        }

        $data = $request->validate([
            'cost_price'     => 'nullable|numeric|min:0',
            'gt_price'       => 'nullable|numeric|min:0',
            'base_price'     => 'nullable|numeric|min:0',
            'pricing_notes'  => 'nullable|string|max:500',
            'publish_status' => 'nullable|in:draft,b2b,b2c',
        ]);

        if (isset($data['publish_status'])) {
            $data['is_published'] = ($data['publish_status'] === 'b2c');
        }

        $item->update($data);

        return redirect()->route('b2c.owner.pricing')
            ->with('updated', $item->title . ' güncellendi.');
    }

    public function logout()
    {
        session()->forget('owner_auth');
        return redirect('/');
    }
}
