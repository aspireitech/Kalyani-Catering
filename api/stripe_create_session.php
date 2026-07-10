<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/StripeClient.php';
require_once __DIR__ . '/../includes/OrderService.php';

$input = json_input();
if (!csrf_verify($input['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'message' => 'Your session expired, please refresh the page.'], 419);
}

$token = (string)($input['token'] ?? '');
$order = OrderService::findByToken($token);
if (!$order || !in_array($order['status'], ['approved', 'payment_pending'], true)) {
    json_response(['ok' => false, 'message' => 'This order is not available for payment.'], 404);
}

$itemStmt = Database::get()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemStmt->execute([$order['id']]);
$orderItems = $itemStmt->fetchAll();

$lineItems = [];
foreach ($orderItems as $item) {
    $lineItems[] = [
        'name' => $item['item_name'] . ($item['tier_name'] ? ' (' . $item['tier_name'] . ')' : ''),
        'quantity' => (int)$item['quantity'],
        'unit_amount' => (int)round(((float)$item['line_total'] / max(1, (int)$item['quantity'])) * 100),
    ];
}
if ((float)$order['discount_amount'] > 0) {
    // Represent the discount as a negative-priced line is not supported by Stripe;
    // instead we charge the already-discounted total as a single summary line item.
    $lineItems = [[
        'name' => 'Kalyani Catering order ' . $order['order_number'] . ' (incl. discount)',
        'quantity' => 1,
        'unit_amount' => (int)round((float)$order['total'] * 100),
    ]];
}

$successUrl = base_url('api/stripe_success.php') . '?token=' . urlencode($token) . '&session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = base_url('pages/pay.php') . '?token=' . urlencode($token) . '&cancelled=1';

$session = StripeClient::createCheckoutSession($lineItems, $successUrl, $cancelUrl, $order['customer_email'], [
    'order_id' => $order['id'],
    'order_number' => $order['order_number'],
    'payment_token' => $token,
]);

if (empty($session['url'])) {
    error_log('Stripe session creation failed: ' . json_encode($session));
    json_response(['ok' => false, 'message' => 'Could not start Stripe checkout. Please try again or use PayPal.'], 500);
}

OrderService::markPaymentPending((int)$order['id']);

json_response(['ok' => true, 'url' => $session['url']]);
