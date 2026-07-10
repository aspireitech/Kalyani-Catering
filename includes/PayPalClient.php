<?php
/**
 * Minimal PayPal Orders API v2 client using cURL only (no SDK/Composer).
 * Covers PayPal balance/card and Venmo — Venmo is offered automatically by
 * the PayPal JS SDK buttons for eligible US buyers, no extra server config.
 * Docs: https://developer.paypal.com/docs/api/orders/v2/
 */
class PayPalClient
{
    private static function baseUrl(): string
    {
        return PAYPAL_MODE === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    private static function getAccessToken(): ?string
    {
        $ch = curl_init(self::baseUrl() . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode((string)$body, true);
        return $decoded['access_token'] ?? null;
    }

    private static function request(string $method, string $path, array $payload = []): array
    {
        $token = self::getAccessToken();
        if (!$token) {
            return ['error' => 'Could not authenticate with PayPal'];
        }
        $ch = curl_init(self::baseUrl() . $path);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 20,
        ];
        if (!empty($payload)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode((string)$body, true);
        return is_array($decoded) ? $decoded + ['_status' => $status] : ['error' => 'Invalid PayPal response', '_status' => $status];
    }

    public static function createOrder(float $amount, string $orderNumber, string $referenceId): array
    {
        return self::request('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $referenceId,
                'description' => 'Kalyani Catering order ' . $orderNumber,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]],
        ]);
    }

    public static function captureOrder(string $paypalOrderId): array
    {
        return self::request('POST', '/v2/checkout/orders/' . urlencode($paypalOrderId) . '/capture');
    }
}
