<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$input = json_input();
if (!csrf_verify($input['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'message' => 'Your session expired, please refresh the page.'], 419);
}

$menuItemId = (int)($input['menu_item_id'] ?? 0);
$tierName = trim((string)($input['tier_name'] ?? ''));
$qty = max(1, min(99, (int)($input['qty'] ?? 1)));

$stmt = Database::get()->prepare('SELECT * FROM menu_items WHERE id = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$menuItemId]);
$menuItem = $stmt->fetch();
if (!$menuItem) {
    json_response(['ok' => false, 'message' => 'This dish is no longer available.'], 404);
}

$unitPrice = null;
if ($tierName) {
    $tierStmt = Database::get()->prepare('SELECT price FROM menu_item_tiers WHERE menu_item_id = ? AND tier_name = ? LIMIT 1');
    $tierStmt->execute([$menuItemId, $tierName]);
    $tier = $tierStmt->fetch();
    if (!$tier) {
        json_response(['ok' => false, 'message' => 'Please choose a valid size.'], 422);
    }
    $unitPrice = (float)$tier['price'];
} elseif ($menuItem['unit_price'] !== null) {
    $unitPrice = (float)$menuItem['unit_price'];
} else {
    json_response(['ok' => false, 'message' => 'Please choose a size.'], 422);
}

$unitPrice = round($unitPrice * (1 - (float)$menuItem['discount_percent'] / 100), 2);

Cart::add($menuItemId, $menuItem['name'], $tierName, $unitPrice, $qty, $menuItem['image_path']);

json_response(['ok' => true, 'cart_count' => Cart::count(), 'subtotal' => number_format(Cart::subtotal(), 2)]);
