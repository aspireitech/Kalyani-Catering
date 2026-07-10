<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$input = json_input();
if (!csrf_verify($input['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'message' => 'Your session expired, please refresh the page.'], 419);
}

$code = strtoupper(trim((string)($input['code'] ?? '')));
$subtotal = Cart::subtotal();

if ($code === '') {
    json_response(['ok' => false, 'message' => 'Please enter a code.']);
}

$stmt = Database::get()->prepare('SELECT * FROM discount_codes WHERE code = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$code]);
$discount = $stmt->fetch();

if (!$discount) {
    json_response(['ok' => false, 'message' => 'That discount code is not valid.']);
}
if ($discount['expires_at'] && strtotime($discount['expires_at']) < strtotime('today')) {
    json_response(['ok' => false, 'message' => 'That discount code has expired.']);
}
if ($discount['max_uses'] !== null && (int)$discount['used_count'] >= (int)$discount['max_uses']) {
    json_response(['ok' => false, 'message' => 'That discount code has reached its usage limit.']);
}
if ($subtotal < (float)$discount['min_order_amount']) {
    json_response(['ok' => false, 'message' => 'This code requires a minimum order of ' . money((float)$discount['min_order_amount']) . '.']);
}

$amount = $discount['type'] === 'percent'
    ? round($subtotal * (float)$discount['value'] / 100, 2)
    : min((float)$discount['value'], $subtotal);

json_response([
    'ok' => true,
    'message' => 'Code applied! You saved ' . money($amount) . '.',
    'discount_amount' => number_format($amount, 2),
    'new_total' => number_format($subtotal - $amount, 2),
]);
