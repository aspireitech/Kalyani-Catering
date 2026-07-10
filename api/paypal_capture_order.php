<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/PayPalClient.php';
require_once __DIR__ . '/../includes/OrderService.php';

$input = json_input();
if (!csrf_verify($input['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'message' => 'Your session expired, please refresh the page.'], 419);
}

$token = (string)($input['token'] ?? '');
$paypalOrderId = (string)($input['paypal_order_id'] ?? '');
$order = OrderService::findByToken($token);
if (!$order || !$paypalOrderId) {
    json_response(['ok' => false, 'message' => 'Invalid payment session.'], 404);
}

$result = PayPalClient::captureOrder($paypalOrderId);
$status = $result['status'] ?? '';

if ($status === 'COMPLETED') {
    OrderService::markPaid((int)$order['id'], 'paypal', $paypalOrderId);
    json_response(['ok' => true, 'order_number' => $order['order_number']]);
}

error_log('PayPal capture failed: ' . json_encode($result));
json_response(['ok' => false, 'message' => 'PayPal payment could not be completed. Please try again.'], 500);
