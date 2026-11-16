<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Product;

interface WhatsAppLinkGeneratorInterface
{
    /**
     * Genera enlace wa.me directo para consultar por un producto y talla específicos.
     */
    public function generateProductLink(Product $product, ?string $size = null): string;

    /**
     * Genera enlace wa.me para que un cliente consulte sobre su pedido.
     */
    public function generateOrderInquiryLink(Order $order): string;

    /**
     * Genera enlace wa.me para que el asesor contacte directamente al cliente.
     */
    public function generateAgentContactLink(Order $order): string;

    /**
     * Genera enlace wa.me genérico con el texto provisto.
     */
    public function generateLink(string $text): string;
}
