<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/Mailer.php';

$input = json_input();
if (!csrf_verify($input['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'message' => 'Your session expired, please refresh the page and try again.'], 419);
}

$cartItems = Cart::all();
if (empty($cartItems)) {
    json_response(['ok' => false, 'message' => 'Your cart is empty.'], 422);
}

$name = trim((string)($input['customer_name'] ?? ''));
$email = trim((string)($input['customer_email'] ?? ''));
$phone = trim((string)($input['customer_phone'] ?? ''));
$fulfillment = ($input['fulfillment_type'] ?? '') === 'delivery' ? 'delivery' : 'pickup';
$eventDate = trim((string)($input['event_date'] ?? ''));
$eventTime = trim((string)($input['event_time'] ?? '')) ?: null;
$address = trim((string)($input['address'] ?? ''));
$notes = trim((string)($input['notes'] ?? ''));
$discountCode = strtoupper(trim((string)($input['discount_code'] ?? '')));

if ($name === '' || $email === '' || $phone === '' || $eventDate === '') {
    json_response(['ok' => false, 'message' => 'Please fill in all required fields.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'message' => 'Please enter a valid email address.'], 422);
}
$leadDays = (int)get_setting('order_lead_days', '3');
$minDate = (new DateTime())->modify("+{$leadDays} days")->format('Y-m-d');
if ($eventDate < $minDate) {
    json_response(['ok' => false, 'message' => "Please choose a date at least {$leadDays} days from today."], 422);
}
if ($fulfillment === 'delivery' && $address === '') {
    json_response(['ok' => false, 'message' => 'Please provide a delivery address.'], 422);
}

$subtotal = Cart::subtotal();
$discountAmount = 0.0;
$appliedCode = null;

if ($discountCode !== '') {
    $stmt = Database::get()->prepare('SELECT * FROM discount_codes WHERE code = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$discountCode]);
    $discount = $stmt->fetch();
    if ($discount
        && (!$discount['expires_at'] || strtotime($discount['expires_at']) >= strtotime('today'))
        && ($discount['max_uses'] === null || (int)$discount['used_count'] < (int)$discount['max_uses'])
        && $subtotal >= (float)$discount['min_order_amount']
    ) {
        $discountAmount = $discount['type'] === 'percent'
            ? round($subtotal * (float)$discount['value'] / 100, 2)
            : min((float)$discount['value'], $subtotal);
        $appliedCode = $discountCode;
    }
}

$total = round($subtotal - $discountAmount, 2);
$orderNumber = generate_order_number();
$paymentToken = random_token();

$db = Database::get();
$db->beginTransaction();
try {
    $stmt = $db->prepare(
        'INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, fulfillment_type, event_date, event_time, address, notes, subtotal, discount_code, discount_amount, total, status, payment_token)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending_approval", ?)'
    );
    $stmt->execute([$orderNumber, $name, $email, $phone, $fulfillment, $eventDate, $eventTime, $address, $notes, $subtotal, $appliedCode, $discountAmount, $total, $paymentToken]);
    $orderId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare(
        'INSERT INTO order_items (order_id, menu_item_id, item_name, tier_name, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $items = [];
    foreach ($cartItems as $item) {
        $lineTotal = round((float)$item['unit_price'] * (int)$item['qty'], 2);
        $itemStmt->execute([$orderId, $item['menu_item_id'], $item['name'], $item['tier_name'] ?: null, $item['unit_price'], $item['qty'], $lineTotal]);
        $items[] = [
            'item_name' => $item['name'],
            'tier_name' => $item['tier_name'],
            'quantity' => $item['qty'],
            'line_total' => $lineTotal,
        ];
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('Order submission failed: ' . $e->getMessage());
    json_response(['ok' => false, 'message' => 'We could not process your order. Please try again.'], 500);
}

$order = [
    'order_number' => $orderNumber,
    'customer_name' => $name,
    'customer_email' => $email,
    'event_date' => $eventDate,
    'total' => $total,
];
Mailer::orderReceived($order, $items);
Mailer::adminNewOrderAlert($order);

Cart::clear();

json_response(['ok' => true, 'order_number' => $orderNumber]);
