<?php

namespace App\Http\Controllers;

use App\Models\BroadcastEmailTrack;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailTrackController extends Controller
{
    /**
     * Open pixel — 1×1 şeffaf GIF döndürür, açılışı loglar.
     */
    public function openPixel(Request $request, string $token): Response
    {
        $track = BroadcastEmailTrack::where('token', $token)->where('type', 'open')->first();

        if ($track && ! $track->triggered_at) {
            $track->update([
                'triggered_at' => now(),
                'hit_count'    => 1,
                'ip'           => $request->ip(),
                'user_agent'   => substr((string) $request->userAgent(), 0, 500),
            ]);
        } elseif ($track && $track->triggered_at) {
            $track->increment('hit_count');
        }

        // 1×1 şeffaf GIF (base64 decode)
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => 'Thu, 01 Jan 1970 00:00:00 GMT',
        ]);
    }

    /**
     * Click redirect — tıklamayı loglar, hedef URL'e yönlendirir.
     */
    public function clickRedirect(Request $request, string $token)
    {
        $track = BroadcastEmailTrack::where('token', $token)->where('type', 'click')->first();

        $destination = $track?->destination_url ?? url('/');

        if ($track && ! $track->triggered_at) {
            $track->update([
                'triggered_at' => now(),
                'hit_count'    => 1,
                'ip'           => $request->ip(),
                'user_agent'   => substr((string) $request->userAgent(), 0, 500),
            ]);
        } elseif ($track && $track->triggered_at) {
            $track->increment('hit_count');
        }

        return redirect()->away($destination);
    }
}
