<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ProbePaymentSandboxCommand extends Command
{
    protected $signature = 'payment:sandbox-probe';

    protected $description = 'Diagnóstico DNS/HTTPS/autenticación de PayPhone y DEUNA sin exponer secretos';

    public function handle(): int
    {
        $this->info('PAYMENT_MODE='.(string) config('payment.mode'));
        $this->info('APP_ENV='.(string) config('app.env'));
        $this->info('config:cached='.(app()->configurationIsCached() ? 'yes' : 'no'));

        $this->newLine();
        $this->info('Credenciales (presencia, sin valores):');
        foreach ([
            'PAYPHONE_TOKEN' => (string) config('payment.payphone.token'),
            'PAYPHONE_STORE_ID' => (string) config('payment.payphone.store_id'),
            'PAYPHONE_RESPONSE_URL' => (string) config('payment.payphone.response_url'),
            'DEUNA_API_KEY' => (string) config('payment.deuna.api_key'),
            'DEUNA_STORE_CODE' => (string) config('payment.deuna.store_code'),
            'DEUNA_MERCHANT_ID' => (string) config('payment.deuna.merchant_id'),
            'DEUNA_WEBHOOK_SECRET' => (string) config('payment.deuna.webhook_secret'),
        ] as $name => $value) {
            $this->line($this->presence($name, $value));
        }

        $payphoneHost = parse_url((string) config('payment.payphone.sandbox_url'), PHP_URL_HOST) ?: 'pay.payphonetodoesposible.com';
        $deunaHost = parse_url((string) config('payment.deuna.sandbox_url'), PHP_URL_HOST) ?: 'api.sandbox.deuna.io';

        $this->newLine();
        $this->info('DNS / HTTPS');
        $this->probeHost($payphoneHost);
        $this->probeHost($deunaHost);
        $this->probeHost('api-staging.deuna.com');
        $this->probeHost('sandbox.deuna.com');

        $this->newLine();
        $this->info('PayPhone POST /button/Prepare');
        $this->probePayPhonePrepare();

        $this->newLine();
        $this->info('DEUNA POST /merchants/orders');
        $this->probeDeunaCreateOrder();

        $this->newLine();
        $this->warn('HTTP 401 no se considera integración satisfactoria.');
        $this->warn('LIVE permanece bloqueado. PAYMENT_MODE local esperado: mock.');

        return self::SUCCESS;
    }

    private function presence(string $name, string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return "{$name}=EMPTY";
        }

        return "{$name}=SET len=".strlen($value);
    }

    private function probeHost(string $host): void
    {
        $dns = @gethostbyname($host);
        $resolved = $dns !== $host;
        $this->line("DNS {$host}: ".($resolved ? "OK ip={$dns}" : 'FAIL (no resuelve)'));

        if (! $resolved) {
            $this->line("HTTPS {$host}: SKIP (DNS)");

            return;
        }

        try {
            $response = Http::timeout(15)->withOptions(['http_errors' => false])->get('https://'.$host);
            $this->line("HTTPS {$host}: OK status={$response->status()} class=".$this->classify($response->status(), null));
        } catch (Throwable $e) {
            $this->line('HTTPS '.$host.': FAIL class='.$this->classifyException($e).' msg='.$this->sanitize($e->getMessage()));
        }
    }

    private function probePayPhonePrepare(): void
    {
        $base = rtrim((string) config('payment.payphone.sandbox_url'), '/');
        $token = trim((string) config('payment.payphone.token'));
        if (str_starts_with(strtolower($token), 'bearer ')) {
            $token = trim(substr($token, 7));
        }
        $storeId = (string) config('payment.payphone.store_id');
        $responseUrl = (string) config('payment.payphone.response_url') ?: (string) config('app.url').'/webhooks/payment/payphone';

        if ($token === '') {
            $this->line('PayPhone prepare: SKIP_AUTH (PAYPHONE_TOKEN vacío). Un POST sin token produce HTTP 401 y no certifica la integración.');
        }

        $payload = [
            'amount' => 100,
            'amountWithoutTax' => 100,
            'amountWithTax' => 0,
            'tax' => 0,
            'service' => 0,
            'tip' => 0,
            'clientTransactionId' => 'zyro-probe-'.Str::uuid()->toString(),
            'storeId' => $storeId,
            'currency' => 'USD',
            'reference' => 'ZYRO sandbox probe',
            'responseUrl' => $responseUrl,
        ];

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($base.'/button/Prepare', $payload);

            $errorCode = $response->json('errorCode') ?? $response->json('ErrorCode');
            $requestId = $response->header('X-Request-Id') ?: $response->header('x-request-id');
            $class = $this->classify($response->status(), is_numeric($errorCode) ? (int) $errorCode : null);

            $this->line("PayPhone prepare: HTTP {$response->status()} class={$class} ErrorCode=".($errorCode ?? 'n/a').' request_id='.($requestId ?: 'n/a'));
            $this->line('PayPhone message: '.$this->sanitize((string) ($response->json('message') ?? '')));
        } catch (Throwable $e) {
            $this->line('PayPhone prepare: FAIL class='.$this->classifyException($e).' msg='.$this->sanitize($e->getMessage()));
        }
    }

    private function probeDeunaCreateOrder(): void
    {
        $base = rtrim((string) config('payment.deuna.sandbox_url'), '/');
        $apiKey = (string) config('payment.deuna.api_key');
        $storeCode = (string) config('payment.deuna.store_code') ?: 'all';

        if ($apiKey === '') {
            $this->line('DEUNA createOrder: SKIP_AUTH (DEUNA_API_KEY vacío). Un POST sin clave produce HTTP 401 y no certifica la integración.');
        }

        $headers = [
            'X-API-KEY' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Idempotency-Key' => (string) Str::uuid(),
        ];
        if ($storeCode !== '') {
            $headers['X-Store-Code'] = $storeCode;
        }

        $payload = [
            'order' => [
                'order_id' => 'zyro-probe-'.Str::uuid()->toString(),
                'store_code' => $storeCode,
                'currency' => 'USD',
                'items_total_amount' => 100,
                'tax_amount' => 0,
                'sub_total' => 100,
                'total_amount' => 100,
                'items' => [[
                    'id' => 'probe-item',
                    'name' => 'ZYRO probe',
                    'quantity' => 1,
                    'unit_price' => ['amount' => 100, 'currency' => 'USD'],
                    'total_amount' => ['amount' => 100, 'currency' => 'USD'],
                ]],
            ],
        ];

        try {
            $response = Http::timeout(20)
                ->withHeaders($headers)
                ->post($base.'/merchants/orders', $payload);

            $requestId = $response->header('X-Request-Id') ?: $response->header('x-request-id');
            $this->line('DEUNA createOrder: HTTP '.$response->status().' class='.$this->classify($response->status(), null).' request_id='.($requestId ?: 'n/a'));
            $this->line('DEUNA message: '.$this->sanitize((string) ($response->json('message') ?? $response->json('error') ?? substr($response->body(), 0, 180))));
        } catch (Throwable $e) {
            $this->line('DEUNA createOrder: FAIL class='.$this->classifyException($e).' msg='.$this->sanitize($e->getMessage()));
        }
    }

    private function classify(int $status, ?int $errorCode): string
    {
        if ($status === 0) {
            return 'NETWORK';
        }
        if ($status === 401 || $status === 403 || $errorCode === 1 || $errorCode === 4 || $errorCode === 6 || $errorCode === 802 || $errorCode === 1004) {
            return 'AUTH';
        }
        if ($status >= 200 && $status < 300) {
            return 'OK';
        }
        if ($status >= 400 && $status < 500) {
            return 'VALIDATION';
        }
        if ($status >= 500) {
            return 'PROVIDER';
        }

        return 'OTHER';
    }

    private function classifyException(Throwable $e): string
    {
        $message = strtolower($e->getMessage());
        if (str_contains($message, 'could not resolve') || str_contains($message, 'name or service not known') || str_contains($message, 'getaddrinfo')) {
            return 'DNS';
        }
        if (str_contains($message, 'ssl') || str_contains($message, 'certificate') || str_contains($message, 'tls')) {
            return 'TLS';
        }

        return 'NETWORK';
    }

    private function sanitize(string $message): string
    {
        $message = preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $message) ?? $message;
        $message = preg_replace('/eyJ[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+/', '[redacted-jwt]', $message) ?? $message;

        return substr($message, 0, 240);
    }
}
