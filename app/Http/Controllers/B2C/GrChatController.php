<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
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

        $result = (new GrAiService())->chat($message, $userId, $guestUuid);

        $response = response()->json([
            'reply'    => $result['reply'],
            'products' => $result['products'],
            'error'    => $result['error'],
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
