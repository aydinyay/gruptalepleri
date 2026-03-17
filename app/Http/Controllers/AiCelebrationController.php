<?php

namespace App\Http\Controllers;

use App\Models\AiCelebrationCampaign;
use App\Services\AiCelebrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiCelebrationController extends Controller
{
    public function seen(
        Request $request,
        AiCelebrationCampaign $campaign,
        AiCelebrationService $aiCelebrationService
    ): JsonResponse {
        $aiCelebrationService->markSeen($campaign, auth()->user());

        return $this->withGuestCookie(
            response()->json(['ok' => true]),
            $request,
            $campaign,
            $aiCelebrationService
        );
    }

    public function closed(
        Request $request,
        AiCelebrationCampaign $campaign,
        AiCelebrationService $aiCelebrationService
    ): JsonResponse {
        $aiCelebrationService->markClosed($campaign, auth()->user());

        return $this->withGuestCookie(
            response()->json(['ok' => true]),
            $request,
            $campaign,
            $aiCelebrationService
        );
    }

    public function clicked(
        Request $request,
        AiCelebrationCampaign $campaign,
        AiCelebrationService $aiCelebrationService
    ): JsonResponse {
        $aiCelebrationService->markClicked($campaign, auth()->user());

        return $this->withGuestCookie(
            response()->json(['ok' => true]),
            $request,
            $campaign,
            $aiCelebrationService
        );
    }

    private function withGuestCookie(
        JsonResponse $response,
        Request $request,
        AiCelebrationCampaign $campaign,
        AiCelebrationService $aiCelebrationService
    ): JsonResponse {
        if (auth()->check()) {
            return $response;
        }

        $minutes = 1440;
        if ($campaign->publish_ends_at) {
            $diff = now()->diffInMinutes($campaign->publish_ends_at, false);
            $minutes = max(60, (int) $diff);
        }

        return $response->cookie(cookie()->make(
            $aiCelebrationService->guestCookieName($campaign->id),
            '1',
            $minutes,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'Lax'
        ));
    }
}

