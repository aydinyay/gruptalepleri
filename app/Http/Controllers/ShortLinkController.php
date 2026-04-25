<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ShortLinkController extends Controller
{
    public function redirect(string $code)
    {
        $link = DB::table('short_links')->where('code', $code)->first();

        if (! $link) {
            abort(404);
        }

        if ($link->expires_at && now()->gt($link->expires_at)) {
            abort(410, 'Bu link süresi dolmuş.');
        }

        return redirect()->away($link->url);
    }
}
