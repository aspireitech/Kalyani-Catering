<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$input = json_input();
if (!csrf_verify($input['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'message' => 'Your session expired, please refresh the page.'], 419);
}

$key = (string)($input['key'] ?? '');
$qty = (int)($input['qty'] ?? 0);

if ($key === '') {
    json_response(['ok' => false, 'message' => 'Invalid cart item.'], 422);
}

Cart::updateQty($key, $qty);

json_response(['ok' => true, 'cart_count' => Cart::count(), 'subtotal' => number_format(Cart::subtotal(), 2)]);
