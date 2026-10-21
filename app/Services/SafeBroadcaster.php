<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Emite eventos de broadcasting asegurando que ningún error de transporte
 * (por ejemplo Reverb desconectado o caída de red) interrumpa compras válidas
 * o transacciones en la base de datos.
 */
class SafeBroadcaster
{
    /**
     * Emite un evento capturando de manera segura cualquier fallo de broadcasting.
     */
    public static function broadcast(object $event): void
    {
        try {
            broadcast($event);
        } catch (\Throwable $e) {
            Log::warning('Fallo seguro en emisión de broadcasting: '.$e->getMessage(), [
                'event'     => get_class($event),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
