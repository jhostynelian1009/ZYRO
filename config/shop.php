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

];
