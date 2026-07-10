<?php
/** Session-backed shopping cart. No DB persistence needed until an order is submitted. */
class Cart
{
    private static function &items(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        return $_SESSION['cart'];
    }

    private static function lineKey(int $menuItemId, string $tierName): string
    {
        return $menuItemId . '|' . $tierName;
    }

    public static function add(int $menuItemId, string $name, string $tierName, float $unitPrice, int $qty, ?string $image): void
    {
        $items = &self::items();
        $key = self::lineKey($menuItemId, $tierName);
        if (isset($items[$key])) {
            $items[$key]['qty'] += $qty;
        } else {
            $items[$key] = [
                'menu_item_id' => $menuItemId,
                'name' => $name,
                'tier_name' => $tierName,
                'unit_price' => $unitPrice,
                'qty' => $qty,
                'image' => $image,
            ];
        }
        if ($items[$key]['qty'] < 1) {
            unset($items[$key]);
        }
    }

    public static function updateQty(string $key, int $qty): void
    {
        $items = &self::items();
        if (!isset($items[$key])) {
            return;
        }
        if ($qty <= 0) {
            unset($items[$key]);
            return;
        }
        $items[$key]['qty'] = $qty;
    }

    public static function remove(string $key): void
    {
        $items = &self::items();
        unset($items[$key]);
    }

    public static function clear(): void
    {
        $items = &self::items();
        $items = [];
    }

    public static function all(): array
    {
        return self::items();
    }

    public static function count(): int
    {
        $total = 0;
        foreach (self::items() as $item) {
            $total += (int)$item['qty'];
        }
        return $total;
    }

    public static function subtotal(): float
    {
        $total = 0.0;
        foreach (self::items() as $item) {
            $total += (float)$item['unit_price'] * (int)$item['qty'];
        }
        return $total;
    }

    public static function isEmpty(): bool
    {
        return count(self::items()) === 0;
    }
}
