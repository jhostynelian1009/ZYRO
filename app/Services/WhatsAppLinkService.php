<?php

namespace App\Services;

use App\Contracts\WhatsAppLinkGeneratorInterface;
use App\Models\Order;
use App\Models\Product;

class WhatsAppLinkService implements WhatsAppLinkGeneratorInterface
{
    /**
     * Obtiene el número de WhatsApp oficial configurado en config/shop.php o variable de entorno.
     */
    public function getStorePhoneNumber(): string
    {
        $raw = (string) config('shop.whatsapp_number', env('WHATSAPP_NUMBER', '593999999999'));
        // Limpiar espacios, guiones y signos de más
        return preg_replace('/\D+/', '', $raw) ?: '593999999999';
    }

    public function generateProductLink(Product $product, ?string $size = null): string
    {
        $phone = $this->getStorePhoneNumber();
        $sizeText = filled($size) ? " talla {$size}" : '';
        $sku = $product->sku ?? ('SKU-'.$product->id);
        $text = "Hola ZYRO, deseo consultar por la chompa {$product->name}{$sizeText}. SKU: {$sku} (Ref: {$product->slug})";

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }

    public function generateOrderInquiryLink(Order $order): string
    {
        $phone = $this->getStorePhoneNumber();
        $text = "Hola ZYRO, quisiera consultar sobre mi pedido {$order->order_number}";

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }

    public function generateAgentContactLink(Order $order): string
    {
        $customerPhone = preg_replace('/\D+/', '', (string) $order->phone);
        $phone = filled($customerPhone) ? $customerPhone : $this->getStorePhoneNumber();
        $text = "Hola {$order->customer_name}, te saludamos de ZYRO respecto a tu pedido {$order->order_number}.";

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }

    public function generateLink(string $text): string
    {
        $phone = $this->getStorePhoneNumber();

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }
}
