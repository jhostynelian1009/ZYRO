<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Aquí se registran todos los canales de difusión de eventos y sus reglas de
| autorización. El acceso se valida en el servidor; nunca se confía en IDs
| enviados por JavaScript.
|
*/

/**
 * Canal privado para administradores.
 * Solo usuarios autenticados con rol administrativo pueden escuchar.
 */
Broadcast::channel('admin', function (User $user): bool {
    return $user->isAdmin();
});

/**
 * Canal privado por usuario.
 * Cada cliente solo puede suscribirse a su propio ID.
 */
Broadcast::channel('user.{userId}', function (User $user, int|string $userId): bool {
    return (int) $user->id === (int) $userId;
});

/**
 * Canal privado por pedido.
 * Solo el cliente propietario o un administrador tienen acceso.
 */
Broadcast::channel('orders.{orderId}', function (User $user, int|string $orderId): bool {
    $order = Order::query()->find($orderId);

    if (! $order) {
        return false;
    }

    return (int) $order->user_id === (int) $user->id || $user->isAdmin();
});
