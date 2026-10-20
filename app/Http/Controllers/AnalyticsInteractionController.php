<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsInteractionController extends Controller
{
    /**
     * Registra interacciones de interfaz (clics en botones, filtros, tarjetas).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event'      => ['required', 'string', 'max:50'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'metadata'   => ['nullable', 'array'],
        ]);

        AnalyticsTracker::track(
            $data['event'],
            $data['product_id'] ?? null,
            $data['metadata'] ?? null,
            $request
        );

        return response()->json(['ok' => true]);
    }
}
