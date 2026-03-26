<?php

namespace App\Http\Controllers;

use App\Models\LeisureClientOffer;
use Illuminate\Http\Request;

class LeisureShareController extends Controller
{
    public function __invoke(Request $request, LeisureClientOffer $offer)
    {
        $offer->load(['request.user', 'packageTemplate']);
        abort_unless($offer->status === 'sent' || $offer->status === 'accepted', 404);

        return view('leisure.offer-print', [
            'offer' => $offer,
            'lang' => in_array($request->query('lang'), ['tr', 'en'], true)
                ? $request->query('lang')
                : ($offer->request->language_preference ?: 'tr'),
            'isShare' => true,
        ]);
    }
}
