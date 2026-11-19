<?php

declare(strict_types=1);

namespace App\Services\Shop;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use SePay\Builders\CheckoutBuilder;
use SePay\Config\UrlConfig;
use SePay\Exceptions\SePayException;
use SePay\SePayClient;

class SepayPaymentService
{
    private ?SePayClient $client = null;

    /**
     * Determine if SePay Merchant credentials are configured.
     */
    public function isConfigured(): bool
    {
        $merchantId = (string) (config('sepay.merchant_id') ?? config('services.sepay.merchant_id'));
        $secretKey = (string) (config('sepay.secret_key') ?? config('services.sepay.secret_key'));

        return trim($merchantId) !== '' && trim($secretKey) !== '';
    }

    /**
     * Determine if SePay IPN Secret Key is configured.
     */
    public function isIpnSecretKeyConfigured(): bool
    {
        $ipnSecret = (string) (config('sepay.ipn_secret_key') ?? config('services.sepay.ipn_secret_key'));

        return trim($ipnSecret) !== '';
    }

    /**
     * Get or initialize the SePay SDK client instance.
     *
     * @throws ValidationException
     */
    public function getClient(): SePayClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'payment' => ['Cổng thanh toán SePay chưa được cấu hình Merchant ID hoặc Secret Key.'],
            ]);
        }

        $merchantId = (string) (config('sepay.merchant_id') ?? config('services.sepay.merchant_id'));
        $secretKey = (string) (config('sepay.secret_key') ?? config('services.sepay.secret_key'));
        $environment = (string) (config('sepay.environment') ?? config('services.sepay.environment') ?? UrlConfig::ENVIRONMENT_SANDBOX);

        if (! UrlConfig::isValidEnvironment($environment)) {
            $environment = UrlConfig::ENVIRONMENT_SANDBOX;
        }

        $this->client = new SePayClient($merchantId, $secretKey, $environment);

        return $this->client;
    }

    /**
     * Generate a unique, deterministic invoice number for an order payment attempt.
     * Format: OPM-{order_number}-A{attempt_number}
     */
    public function generateInvoiceNumber(Order $order, int $attemptNumber): string
    {
        return "{$order->order_number}-A{$attemptNumber}";
    }

    /**
     * Generate checkout form payload with signed fields using the official SePay SDK.
     *
     * @return array{
     *     checkout_url: string,
     *     fields: array<string, mixed>,
     *     invoice_number: string
     * }
     *
     * @throws ValidationException
     */
    public function generateCheckoutForm(Order $order, OrderPayment $payment): array
    {
        $client = $this->getClient();
        $environment = $client->getEnvironment();

        $frontendUrl = rtrim((string) (config('sepay.frontend_url') ?? config('services.sepay.frontend_url') ?? 'http://localhost:3000'), '/');
        $successUrl = (string) (config('sepay.success_url') ?? "{$frontendUrl}/don-hang/{$order->public_id}/ket-qua?invoice={$payment->transaction_ref}");
        $cancelUrl = (string) (config('sepay.cancel_url') ?? "{$frontendUrl}/don-hang/{$order->public_id}/ket-qua?invoice={$payment->transaction_ref}&status=cancelled");
        $errorUrl = (string) (config('sepay.error_url') ?? "{$frontendUrl}/don-hang/{$order->public_id}/ket-qua?invoice={$payment->transaction_ref}&status=error");

        try {
            $checkoutData = CheckoutBuilder::make()
                ->merchant($client->getMerchantId())
                ->currency('VND')
                ->orderAmount((int) $order->total_vnd)
                ->operation('PURCHASE')
                ->orderInvoiceNumber((string) $payment->transaction_ref)
                ->orderDescription("Thanh toan don hang {$order->order_number}")
                ->successUrl($successUrl)
                ->cancelUrl($cancelUrl)
                ->errorUrl($errorUrl)
                ->build();

            $formFields = $client->checkout()->generateFormFields($checkoutData);
            $checkoutUrl = $client->checkout()->getCheckoutUrl($environment);

            return [
                'checkout_url' => $checkoutUrl,
                'fields' => $formFields,
                'invoice_number' => (string) $payment->transaction_ref,
            ];
        } catch (SePayException $e) {
            Log::error('SePay generateCheckoutForm error: '.$e->getMessage(), [
                'order_id' => $order->id,
                'invoice' => $payment->transaction_ref,
            ]);

            throw ValidationException::withMessages([
                'payment' => ['Không thể khởi tạo phiên thanh toán SePay: '.$e->getMessage()],
            ]);
        }
    }

    /**
     * Validate the incoming X-Secret-Key header for IPN webhook requests.
     * Fail-closed: returns false if IPN secret is not configured or mismatch.
     */
    public function verifyIpnSecretKey(?string $incomingKey): bool
    {
        if (empty($incomingKey)) {
            return false;
        }

        $configuredKey = (string) (config('sepay.ipn_secret_key') ?? config('services.sepay.ipn_secret_key'));

        if (trim($configuredKey) === '') {
            Log::warning('SePay IPN received but SEPAY_IPN_SECRET_KEY is not configured on server (fail-closed).');

            return false;
        }

        return hash_equals(trim($configuredKey), trim($incomingKey));
    }

    /**
     * Query order status directly from SePay Gateway API.
     *
     * @return array<string, mixed>|null
     */
    public function retrieveOrderFromGateway(string $orderIdOrInvoice): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $client = $this->getClient();

            // Try direct retrieve by SePay order ID or query list by invoice
            if (str_starts_with($orderIdOrInvoice, 'OPM-')) {
                $response = $client->orders()->list(['q' => $orderIdOrInvoice, 'per_page' => 1]);
                $data = $response['data'] ?? [];

                return ! empty($data) ? $data[0] : null;
            }

            return $client->orders()->retrieve($orderIdOrInvoice);
        } catch (\Throwable $e) {
            Log::warning('SePay retrieveOrderFromGateway failed: '.$e->getMessage(), [
                'query' => $orderIdOrInvoice,
            ]);

            return null;
        }
    }
}
