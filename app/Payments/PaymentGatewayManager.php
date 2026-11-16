<?php

namespace App\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Payments\Gateways\BankTransferGateway;
use App\Payments\Gateways\CashPaymentGateway;
use App\Payments\Gateways\DeunaGatewayAdapter;
use App\Payments\Gateways\MockPaymentGateway;
use App\Payments\Gateways\PayPhoneGatewayAdapter;
use InvalidArgumentException;
use RuntimeException;

/**
 * PaymentGatewayManager — Factory para resolver el adaptador correcto.
 *
 * Incluye guardas de entorno:
 * - Modo LIVE solo en APP_ENV=production.
 * - Modo MOCK prohibido en APP_ENV=production.
 */
class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGatewayInterface>> */
    private array $gateways = [
        'mock'          => MockPaymentGateway::class,
        'cash'          => CashPaymentGateway::class,
        'bank_transfer' => BankTransferGateway::class,
        'payphone'      => PayPhoneGatewayAdapter::class,
        'deuna'         => DeunaGatewayAdapter::class,
    ];

    public function gateway(?string $driver = null): PaymentGatewayInterface
    {
        $driver ??= config('payment.default_gateway', 'mock');
        $mode     = config('payment.mode', 'mock');

        $this->enforceEnvironmentGuards($driver, $mode);

        if (!array_key_exists($driver, $this->gateways)) {
            throw new InvalidArgumentException(
                "Pasarela de pago desconocida: [{$driver}]. " .
                'Opciones válidas: ' . implode(', ', array_keys($this->gateways))
            );
        }

        return app($this->gateways[$driver]);
    }

    public function availableGateways(): array
    {
        return array_keys($this->gateways);
    }

    private function enforceEnvironmentGuards(string $driver, string $mode): void
    {
        $isProduction = app()->environment('production');

        // Protección: MOCK prohibido en producción.
        if ($isProduction && $mode === 'mock') {
            throw new RuntimeException(
                'PAYMENT_MODE=mock no está permitido en APP_ENV=production. ' .
                'Configure una pasarela real (payphone, deuna, bank_transfer, cash).'
            );
        }

        // Protección: LIVE solo en producción.
        if (!$isProduction && $mode === 'live') {
            throw new RuntimeException(
                'PAYMENT_MODE=live solo está permitido en APP_ENV=production. ' .
                'Use PAYMENT_MODE=mock o PAYMENT_MODE=sandbox en entornos de desarrollo.'
            );
        }

        // MockPaymentGateway requiere que el driver coincida con el modo.
        if ($driver === 'mock' && $isProduction) {
            throw new RuntimeException(
                'MockPaymentGateway no puede usarse en entorno de producción.'
            );
        }
    }
}
