<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/StripeClient.php';
require_once __DIR__ . '/../includes/OrderService.php';

$token = $_GET['token'] ?? '';
$sessionId = $_GET['session_id'] ?? '';
$order = OrderService::findByToken($token);

if (!$order || !$sessionId) {
    redirect(base_url('pages/pay.php') . '?token=' . urlencode($token));
}

$session = StripeClient::retrieveCheckoutSession($sessionId);

if (($session['payment_status'] ?? '') === 'paid') {
    OrderService::markPaid((int)$order['id'], 'stripe', $sessionId);
    redirect(base_url('pages/order-confirmation.php') . '?order=' . urlencode($order['order_number']) . '&paid=1');
}

flash_set('error', 'Payment was not completed. Please try again.');
redirect(base_url('pages/pay.php') . '?token=' . urlencode($token));
