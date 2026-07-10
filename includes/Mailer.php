<?php
require_once __DIR__ . '/SmtpMailer.php';

/** Templated transactional + marketing emails, built on SmtpMailer. */
class Mailer
{
    private static function layout(string $title, string $bodyHtml): string
    {
        $name = e(get_setting('business_name', SITE_NAME));
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;background:#f4f1ea;font-family:Georgia,\'Times New Roman\',serif;color:#26362c;">'
            . '<div style="max-width:600px;margin:0 auto;padding:24px;">'
            . '<div style="background:#0e2f22;padding:24px;text-align:center;border-radius:10px 10px 0 0;">'
            . '<span style="color:#e8c574;font-size:22px;letter-spacing:2px;font-weight:bold;">' . $name . '</span>'
            . '</div>'
            . '<div style="background:#ffffff;padding:28px;border-radius:0 0 10px 10px;border:1px solid #e7e0cf;">'
            . '<h2 style="color:#0e2f22;margin-top:0;">' . e($title) . '</h2>'
            . $bodyHtml
            . '</div>'
            . '<p style="text-align:center;color:#9a9689;font-size:12px;margin-top:16px;">' . $name . ' &middot; ' . e(get_setting('business_address')) . '</p>'
            . '</div></body></html>';
    }

    private static function dispatch(string $to, string $subject, string $bodyHtml, string $title, ?string $replyTo = null): bool
    {
        return SmtpMailer::send([$to], $subject, self::layout($title, $bodyHtml), $replyTo);
    }

    public static function orderReceived(array $order, array $items): void
    {
        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr><td style="padding:6px 0;">' . e($item['item_name']) . ($item['tier_name'] ? ' (' . e($item['tier_name']) . ')' : '') . ' &times; ' . (int)$item['quantity'] . '</td><td style="text-align:right;">' . money((float)$item['line_total']) . '</td></tr>';
        }
        $body = '<p>Hi ' . e($order['customer_name']) . ',</p>'
            . '<p>Thanks for your order request! We have received it and our team will review it shortly. '
            . 'You will get another email once it is <strong>approved</strong>, with a secure link to complete payment.</p>'
            . '<p><strong>Order #' . e($order['order_number']) . '</strong><br>Requested date: ' . e(date('F j, Y', strtotime($order['event_date']))) . '</p>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:12px;">' . $rows . '</table>'
            . '<p style="text-align:right;font-size:18px;margin-top:8px;"><strong>Total: ' . money((float)$order['total']) . '</strong></p>';
        self::dispatch($order['customer_email'], 'We received your order request — ' . $order['order_number'], $body, 'Order Request Received');
    }

    public static function orderApproved(array $order, string $payUrl): void
    {
        $body = '<p>Hi ' . e($order['customer_name']) . ',</p>'
            . '<p>Great news — your order <strong>#' . e($order['order_number']) . '</strong> has been approved!</p>'
            . '<p>Please complete payment to confirm your booking for ' . e(date('F j, Y', strtotime($order['event_date']))) . '.</p>'
            . '<p style="text-align:center;margin:24px 0;"><a href="' . e($payUrl) . '" style="background:#0e2f22;color:#e8c574;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;">Pay Now — ' . money((float)$order['total']) . '</a></p>'
            . '<p>If the button doesn\'t work, copy this link into your browser:<br>' . e($payUrl) . '</p>';
        self::dispatch($order['customer_email'], 'Your order is approved — complete payment for ' . $order['order_number'], $body, 'Order Approved');
    }

    public static function orderRejected(array $order): void
    {
        $reason = $order['admin_notes'] ? '<p><strong>Note from our team:</strong> ' . nl2br(e($order['admin_notes'])) . '</p>' : '';
        $body = '<p>Hi ' . e($order['customer_name']) . ',</p>'
            . '<p>Unfortunately we are unable to fulfill order <strong>#' . e($order['order_number']) . '</strong> for ' . e(date('F j, Y', strtotime($order['event_date']))) . '.</p>'
            . $reason
            . '<p>Please contact us if you would like to choose another date or adjust your order.</p>';
        self::dispatch($order['customer_email'], 'Update on your order — ' . $order['order_number'], $body, 'Order Update');
    }

    public static function orderPaid(array $order): void
    {
        $body = '<p>Hi ' . e($order['customer_name']) . ',</p>'
            . '<p>Payment received! Order <strong>#' . e($order['order_number']) . '</strong> is confirmed for ' . e(date('F j, Y', strtotime($order['event_date']))) . '.</p>'
            . '<p><strong>Amount paid:</strong> ' . money((float)$order['total']) . '</p>'
            . '<p>We can\'t wait to cater your event. Thank you for choosing us!</p>';
        self::dispatch($order['customer_email'], 'Payment confirmed — ' . $order['order_number'], $body, 'Payment Confirmed');

        $adminEmail = get_setting('email_billing');
        if ($adminEmail) {
            self::dispatch($adminEmail, 'Payment received for ' . $order['order_number'], '<p>Order #' . e($order['order_number']) . ' from ' . e($order['customer_name']) . ' was just paid (' . money((float)$order['total']) . ').</p>', 'Payment Received');
        }
    }

    public static function adminNewOrderAlert(array $order): void
    {
        $adminEmail = get_setting('email_general');
        if (!$adminEmail) {
            return;
        }
        $body = '<p>New order request <strong>#' . e($order['order_number']) . '</strong> from ' . e($order['customer_name']) . ' (' . e($order['customer_email']) . ') for ' . e(date('F j, Y', strtotime($order['event_date']))) . '.</p>'
            . '<p>Total: ' . money((float)$order['total']) . '</p>'
            . '<p><a href="' . e(base_url('admin/orders.php')) . '">Review in admin panel</a></p>';
        self::dispatch($adminEmail, 'New order request — ' . $order['order_number'], $body, 'New Order Request');
    }

    public static function contactFormReceived(array $submission): void
    {
        $deptKey = 'email_' . $submission['department'];
        $to = get_setting($deptKey, get_setting('email_general'));
        if (!$to) {
            return;
        }
        $body = '<p><strong>From:</strong> ' . e($submission['name']) . ' (' . e($submission['email']) . ')</p>'
            . '<p><strong>Phone:</strong> ' . e($submission['phone'] ?: '—') . '</p>'
            . '<p><strong>Department:</strong> ' . e(ucfirst(str_replace('_', ' ', $submission['department']))) . '</p>'
            . '<p><strong>Message:</strong><br>' . nl2br(e($submission['message'])) . '</p>';
        self::dispatch($to, 'New contact form message — ' . ucfirst(str_replace('_', ' ', $submission['department'])), $body, 'New Contact Message', $submission['email']);
    }

    public static function newsletterCampaign(string $email, string $unsubToken, string $subject, string $bodyHtml): void
    {
        $unsubUrl = base_url('pages/unsubscribe.php?token=' . urlencode($unsubToken));
        $body = $bodyHtml
            . '<p style="text-align:center;margin-top:24px;"><a href="' . e(base_url('pages/menu.php')) . '" style="background:#0e2f22;color:#e8c574;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">Order Now</a></p>'
            . '<p style="text-align:center;margin-top:20px;"><a href="' . e($unsubUrl) . '" style="color:#9a9689;font-size:12px;">Unsubscribe from these emails</a></p>';
        self::dispatch($email, $subject, $body, $subject);
    }
}
