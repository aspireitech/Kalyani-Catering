<?php
/**
 * Stripe webhook endpoint. Configure this URL in the Stripe Dashboard
 * (Developers > Webhooks) pointing at:
 *   https://yourdomain.com/api/stripe_webhook.php
 * Listen for: checkout.session.completed
 *
 * This is the durable source of truth for marking orders paid — the
 * browser redirect in stripe_success.php also marks it paid as a fast
 * path, but the webhook covers cases where the customer closes the tab.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/StripeClient.php';
require_once __DIR__ . '/../includes/OrderService.php';

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$event = StripeClient::verifyWebhookSignature($payload, $signature);
if (!$event) {
    http_response_code(400);
    exit('Invalid signature');
}

if (($event['type'] ?? '') === 'checkout.session.completed') {
    $session = $event['data']['object'] ?? [];
    $orderId = (int)($session['metadata']['order_id'] ?? 0);
    if ($orderId && ($session['payment_status'] ?? '') === 'paid') {
        OrderService::markPaid($orderId, 'stripe', $session['id'] ?? '');
    }
}

http_response_code(200);
echo 'ok';
