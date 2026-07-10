<?php
/**
 * Minimal Stripe REST API client using cURL only (no Composer/SDK required —
 * keeps this deployable on shared hosting without SSH access).
 * Docs: https://docs.stripe.com/api
 */
class StripeClient
{
    private const API_BASE = 'https://api.stripe.com/v1';

    private static function request(string $method, string $path, array $params = []): array
    {
        $ch = curl_init(self::API_BASE . $path);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 20,
        ];
        if ($method === 'POST' && !empty($params)) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($params);
        } elseif ($method === 'GET' && !empty($params)) {
            $ch = curl_init(self::API_BASE . $path . '?' . http_build_query($params));
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['error' => ['message' => 'Stripe request failed: ' . $error]];
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded + ['_status' => $status] : ['error' => ['message' => 'Invalid Stripe response'], '_status' => $status];
    }

    /**
     * @param array $lineItems each: ['name'=>, 'quantity'=>, 'unit_amount'=> (cents)]
     */
    public static function createCheckoutSession(array $lineItems, string $successUrl, string $cancelUrl, string $customerEmail, array $metadata = []): array
    {
        $params = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $customerEmail,
            'payment_method_types' => ['card'], // Apple Pay/Google Pay auto-enabled once domain is verified in Stripe Dashboard
        ];
        foreach ($lineItems as $i => $item) {
            $params['line_items'][$i]['quantity'] = $item['quantity'];
            $params['line_items'][$i]['price_data']['currency'] = 'usd';
            $params['line_items'][$i]['price_data']['unit_amount'] = $item['unit_amount'];
            $params['line_items'][$i]['price_data']['product_data']['name'] = $item['name'];
        }
        foreach ($metadata as $key => $value) {
            $params['metadata'][$key] = (string)$value;
        }
        return self::request('POST', '/checkout/sessions', $params);
    }

    public static function retrieveCheckoutSession(string $sessionId): array
    {
        return self::request('GET', '/checkout/sessions/' . urlencode($sessionId));
    }

    /**
     * Verifies the Stripe-Signature header against the raw payload.
     * Returns the decoded event array on success, or null if invalid.
     */
    public static function verifyWebhookSignature(string $payload, string $signatureHeader): ?array
    {
        $parts = [];
        foreach (explode(',', $signatureHeader) as $piece) {
            [$k, $v] = array_pad(explode('=', $piece, 2), 2, null);
            $parts[$k][] = $v;
        }
        if (empty($parts['t']) || empty($parts['v1'])) {
            return null;
        }
        $timestamp = $parts['t'][0];
        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, STRIPE_WEBHOOK_SECRET);

        $valid = false;
        foreach ($parts['v1'] as $sig) {
            if (hash_equals($expected, $sig)) {
                $valid = true;
                break;
            }
        }
        if (!$valid || abs(time() - (int)$timestamp) > 300) {
            return null;
        }
        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : null;
    }
}
