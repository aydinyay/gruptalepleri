<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\LeisureClientOffer;
use Illuminate\Http\Request;

class LeisureOfferPrintController extends Controller
{
    public function __invoke(Request $request, LeisureClientOffer $offer)
    {
        $offer->load(['request.user', 'packageTemplate']);
        abort_unless($offer->request?->user_id === auth()->id(), 403);

        $lang = $request->query('lang', $offer->request->language_preference ?: 'tr');

        return view('leisure.offer-print', [
            'offer' => $offer,
            'lang' => in_array($lang, ['tr', 'en'], true) ? $lang : 'tr',
            'isShare' => false,
        ]);
    }
}
