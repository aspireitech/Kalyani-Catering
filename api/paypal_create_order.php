<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/PayPalClient.php';
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

$result = PayPalClient::createOrder((float)$order['total'], $order['order_number'], $token);

if (empty($result['id'])) {
    error_log('PayPal order creation failed: ' . json_encode($result));
    json_response(['ok' => false, 'message' => 'Could not start PayPal checkout. Please try again or use card payment.'], 500);
}

OrderService::markPaymentPending((int)$order['id']);

json_response(['ok' => true, 'paypal_order_id' => $result['id']]);
