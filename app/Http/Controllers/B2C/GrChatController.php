<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cPriceAlert;
use App\Models\B2C\B2cWishlistItem;
use App\Models\B2C\CatalogItem;
use App\Models\B2C\GrAiGuest;
use App\Models\B2C\GrAiMemory;
use App\Services\GrAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GrChatController extends Controller
{
    private const COOKIE_NAME = 'gr_guest_id';
    private const COOKIE_DAYS = 365;

    // ── Sohbet ──────────────────────────────────────────────────────────────

    public function chat(Request $request)
    {
        $request->validate(['message' => 'required|string|min:1|max:500']);

        $message   = trim($request->input('message'));
        $userId    = auth('b2c')->id();
        $guestUuid = $this->resolveGuestUuid($request);

        // Misafir kaydını güncelle
        if (! $userId) {
            $guest = GrAiGuest::findOrCreateByUuid($guestUuid);
            $guest->touchSeen();
        }

        $result = (new GrAiService())->chat($message, $userId, $guestUuid, app()->getLocale());

        $sid = session()->getId();
        $wishlistAdded = false;
        $alertSet      = false;

        // Wishlist ekleme
        if ($result['wishlist_add'] ?? null) {
            $item = CatalogItem::published()->where('slug', $result['wishlist_add'])->first();
            if ($item) {
                $exists = B2cWishlistItem::where('session_id', $sid)->where('catalog_item_id', $item->id)->exists();
                if (! $exists) {
                    B2cWishlistItem::create(['session_id' => $sid, 'catalog_item_id' => $item->id, 'created_at' => now()]);
                }
                $wishlistAdded = true;
            }
        }

        // Fiyat alarmı
        if ($result['price_alert'] ?? null) {
            $item = CatalogItem::published()->where('slug', $result['price_alert'])->first();
            if ($item) {
                $b2cUser = $userId ? \App\Models\B2C\B2cUser::find($userId) : null;
                B2cPriceAlert::register($sid, $userId, $item->id, $item->slug, (float) $item->base_price, $b2cUser?->email);
                $alertSet = true;
            }
        }

        $response = response()->json([
            'reply'          => $result['reply'],
            'products'       => $result['products'],
            'redirect'       => $result['redirect'] ?? null,
            'wishlist_added' => $wishlistAdded,
            'alert_set'      => $alertSet,
            'error'          => $result['error'],
        ]);

        // Cookie'yi tazele
        if (! $userId) {
            $response->cookie(self::COOKIE_NAME, $guestUuid, 60 * 24 * self::COOKIE_DAYS, '/', null, false, true);
        }

        return $response;
    }

    // ── Login sonrası hafıza birleştirme ─────────────────────────────────────

    public function mergeGuestMemory(Request $request)
    {
        $userId    = auth('b2c')->id();
        $guestUuid = $request->cookie(self::COOKIE_NAME);

        if ($userId && $guestUuid) {
            GrAiMemory::migrateGuestToUser($guestUuid, $userId);
        }

        return response()->json(['ok' => true])
            ->withoutCookie(self::COOKIE_NAME);
    }

    // ── Sohbet geçmişini temizle ─────────────────────────────────────────────

    public function clearHistory(Request $request)
    {
        $userId    = auth('b2c')->id();
        $guestUuid = $this->resolveGuestUuid($request);

        \App\Models\B2C\GrAiSession::when($userId, fn ($q) => $q->where('b2c_user_id', $userId))
            ->when(! $userId, fn ($q) => $q->where('guest_uuid', $guestUuid))
            ->delete();

        return response()->json(['ok' => true]);
    }

    // ── Yardımcı ────────────────────────────────────────────────────────────

    private function resolveGuestUuid(Request $request): string
    {
        $uuid = $request->cookie(self::COOKIE_NAME);
        return ($uuid && Str::isUuid($uuid)) ? $uuid : (string) Str::uuid();
    }
}
