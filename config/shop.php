<?php

/**
 * Configuración comercial de ZYRO.
 *
 * Centraliza parámetros como la tasa de IVA para que no estén
 * duplicados en modelos, servicios ni vistas.
 *
 * Para cambiar la tasa en el futuro basta con modificar la variable
 * de entorno IVA_RATE (o el valor por defecto aquí).
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Tasa de IVA
    |--------------------------------------------------------------------------
    | Porcentaje del Impuesto al Valor Agregado expresado como decimal.
    | Ejemplo: 0.15 = 15 %.
    |
    | Variable de entorno: IVA_RATE
    */
    'iva_rate' => (float) env('IVA_RATE', 0.15),

    /*
    |--------------------------------------------------------------------------
    | Costo de envío (demo)
    |--------------------------------------------------------------------------
    | Tarifa fija de envío de demostración en dólares.
    |
    | Variable de entorno: SHIPPING_COST
    */
    'shipping' => (float) env('SHIPPING_COST', 6.00),

    /*
    |--------------------------------------------------------------------------
    | Modalidades de entrega y tiempos estimados
    |--------------------------------------------------------------------------
    | Configuración centralizada de las opciones logísticas de ZYRO.
    */
    'delivery_methods' => [
        'standard' => [
            'id'          => 'standard',
            'name'        => 'Envío estándar',
            'cost'        => (float) env('SHIPPING_COST', 6.00),
            'estimate'    => 'Servientrega · 2–5 días hábiles',
            'description' => 'Entrega en tu dirección.',
            'badge'       => 'Servientrega · 2–5 días hábiles',
            'icon'        => 'truck',
        ],
        'express' => [
            'id'          => 'express',
            'name'        => 'Envío exprés',
            'cost'        => (float) env('SHIPPING_COST', 6.00),
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
    ],

];
