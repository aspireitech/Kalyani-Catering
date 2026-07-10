<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$token = $_GET['token'] ?? '';
$order = null;
if ($token) {
    $stmt = Database::get()->prepare('SELECT * FROM orders WHERE payment_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $order = $stmt->fetch();
}

$items = [];
if ($order) {
    $stmt = Database::get()->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $stmt->execute([$order['id']]);
    $items = $stmt->fetchAll();
}

$payable = $order && in_array($order['status'], ['approved', 'payment_pending'], true);

$pageTitle = 'Complete Your Payment';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:640px;">
    <?php if (!$order): ?>
      <div style="text-align:center;">
        <h1>Payment Link Not Found</h1>
        <p>This payment link is invalid or has expired. Please contact us if you need help.</p>
        <a href="<?= e(base_url('pages/contact.php')) ?>" class="btn btn-primary">Contact Us</a>
      </div>
    <?php elseif ($order['status'] === 'paid'): ?>
      <div style="text-align:center;">
        <h1>Already Paid</h1>
        <p>Order #<?= e($order['order_number']) ?> has already been paid in full. Thank you!</p>
        <a href="<?= e(base_url('pages/order-confirmation.php?order=' . urlencode($order['order_number']) . '&paid=1')) ?>" class="btn btn-primary">View Confirmation</a>
      </div>
    <?php elseif (!$payable): ?>
      <div style="text-align:center;">
        <h1>Not Ready for Payment</h1>
        <p>Order #<?= e($order['order_number']) ?> is currently <strong><?= e(str_replace('_', ' ', $order['status'])) ?></strong> and isn't awaiting payment.</p>
      </div>
    <?php else: ?>
      <div class="section-head" style="margin-bottom:20px;">
        <div class="eyebrow">Order #<?= e($order['order_number']) ?></div>
        <h1>Complete Your Payment</h1>
        <p>Your order was approved! Choose a payment method below to confirm your booking for <?= e(date('F j, Y', strtotime($order['event_date']))) ?>.</p>
      </div>

      <div class="admin-card">
        <?php foreach ($items as $item): ?>
          <div class="summary-row">
            <span><?= e($item['item_name']) ?><?= $item['tier_name'] ? ' (' . e($item['tier_name']) . ')' : '' ?> &times; <?= (int)$item['quantity'] ?></span>
            <span><?= money((float)$item['line_total']) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if ((float)$order['discount_amount'] > 0): ?>
          <div class="summary-row"><span>Discount<?= $order['discount_code'] ? ' (' . e($order['discount_code']) . ')' : '' ?></span><span>-<?= money((float)$order['discount_amount']) ?></span></div>
        <?php endif; ?>
        <div class="summary-row total"><span>Total Due</span><span><?= money((float)$order['total']) ?></span></div>
      </div>

      <div id="pay-root" data-token="<?= e($token) ?>">
        <div class="pay-methods" style="margin-bottom:18px;">
          <button type="button" id="pay-stripe-btn" class="pay-method-btn active">💳 Pay with Card / Apple Pay<br><span class="muted">Powered by Stripe</span></button>
        </div>
        <div id="paypal-button-container"></div>
        <p class="muted" style="text-align:center;margin-top:10px;">PayPal also lets you pay with Venmo when available on your account.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($payable): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?= urlencode(PAYPAL_CLIENT_ID) ?>&currency=USD&enable-funding=venmo"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
