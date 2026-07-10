<?php
require_once __DIR__ . '/Mailer.php';

/** Shared order state-transition logic used by both Stripe and PayPal flows. */
class OrderService
{
    public static function findByToken(string $token): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM orders WHERE payment_token = ? LIMIT 1');
        $stmt->execute([$token]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    /** Idempotent: safe to call more than once for the same order. */
    public static function markPaid(int $orderId, string $method, string $reference): bool
    {
        $db = Database::get();
        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            return false;
        }
        if ($order['status'] === 'paid') {
            return true; // already processed
        }

        $db->prepare('UPDATE orders SET status = "paid", payment_method = ?, payment_reference = ? WHERE id = ?')
            ->execute([$method, $reference, $orderId]);

        if ($order['discount_code']) {
            $db->prepare('UPDATE discount_codes SET used_count = used_count + 1 WHERE code = ?')
                ->execute([$order['discount_code']]);
        }

        $order['status'] = 'paid';
        $order['payment_method'] = $method;
        Mailer::orderPaid($order);

        return true;
    }

    public static function markPaymentPending(int $orderId): void
    {
        $db = Database::get();
        $stmt = $db->prepare('SELECT status FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        $current = $stmt->fetchColumn();
        if ($current === 'approved') {
            $db->prepare('UPDATE orders SET status = "payment_pending" WHERE id = ?')->execute([$orderId]);
        }
    }
}
