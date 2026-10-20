<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalyticsTracker
{
    /**
     * Registra un evento de analítica de comportamiento de forma segura.
     */
    public static function track(
        string $eventType,
        ?int $productId = null,
        ?array $metadata = null,
        ?Request $request = null
    ): ?AnalyticsEvent {
        try {
            $req = $request ?? request();
            $userAgent = (string) $req->userAgent();

            $deviceType = 'desktop';
            if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $userAgent)) {
                $deviceType = 'tablet';
            } elseif (preg_match('/mobile|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop/i', $userAgent)) {
                $deviceType = 'mobile';
            }

            return AnalyticsEvent::create([
                'event_type'  => $eventType,
                'product_id'  => $productId,
                'user_id'     => auth()->id(),
                'session_id'  => session()->isStarted() ? session()->getId() : null,
                'device_type' => $deviceType,
                'metadata'    => $metadata,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AnalyticsTracker::track failed: ' . $e->getMessage());
            return null;
        }
    }
}
