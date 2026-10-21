<?php

namespace App\Services;

class DeliveryService
{
    /**
     * Devuelve todas las modalidades de entrega configuradas con sus metadatos.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $methods = config('shop.delivery_methods');

        if (! is_array($methods) || empty($methods)) {
            $defaultCost = (float) config('shop.shipping', 6.00);
            return [
                'standard' => [
                    'id'          => 'standard',
                    'name'        => 'Envío estándar',
                    'cost'        => $defaultCost,
                    'estimate'    => 'Servientrega · 2–5 días hábiles',
                    'description' => 'Entrega en tu dirección.',
                    'badge'       => 'Servientrega · 2–5 días hábiles',
                    'icon'        => 'truck',
                ],
                'express' => [
                    'id'          => 'express',
                    'name'        => 'Envío exprés',
                    'cost'        => $defaultCost,
                    'estimate'    => 'Servientrega · 1–2 días hábiles',
                    'description' => 'Modalidad de entrega prioritaria.',
                    'badge'       => 'Servientrega · 1–2 días hábiles',
                    'icon'        => 'zap',
                ],
                'pickup' => [
                    'id'          => 'pickup',
                    'name'        => 'Retiro en tienda',
                    'cost'        => 0.00,
                    'estimate'    => 'Pendiente de confirmación',
                    'description' => 'Recoge personalmente tu pedido en la sucursal Centro.',
                    'notice'      => 'Debes esperar la confirmación de que tu pedido está listo para retirar.',
                    'location'    => 'Sucursal Centro',
                    'badge'       => 'Pendiente de confirmación',
                    'icon'        => 'store',
                ],
            ];
        }

        return $methods;
    }

    /**
     * Obtiene la configuración de una modalidad específica.
     */
    public static function get(?string $method): ?array
    {
        if ($method === null) {
            return null;
        }

        return self::all()[$method] ?? null;
    }

    /**
     * Determina el costo oficial de envío para una modalidad dada.
     * Nunca confía en el valor enviado por el navegador.
     */
    public static function costFor(?string $method): float
    {
        if ($method === 'pickup') {
            return 0.00;
        }

        $info = self::get($method);
        if ($info && isset($info['cost'])) {
            return (float) $info['cost'];
        }

        return (float) config('shop.shipping', 6.00);
    }

    /**
     * Devuelve el tiempo estimado comunicado al cliente.
     */
    public static function estimateFor(?string $method): string
    {
        if ($method === 'pickup') {
            return 'Pendiente de confirmación';
        }

        if ($method === 'express') {
            return self::get('express')['estimate'] ?? 'Servientrega · 1–2 días hábiles';
        }

        return self::get($method)['estimate'] ?? 'Servientrega · 2–5 días hábiles';
    }

    /**
     * Nombre legible de la modalidad.
     */
    public static function labelFor(?string $method): string
    {
        $info = self::get($method);
        if ($info && ! empty($info['name'])) {
            return (string) $info['name'];
        }

        return match ($method) {
            'pickup'   => 'Retiro en tienda',
            'express'  => 'Envío exprés',
            'standard' => 'Envío estándar',
            default    => ucfirst((string) $method),
        };
    }

    /**
     * Valida si el método es uno de los permitidos.
     */
    public static function isValid(string $method): bool
    {
        return array_key_exists($method, self::all());
    }
}
