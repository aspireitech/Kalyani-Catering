<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$orderNumber = $_GET['order'] ?? '';
$paidFlag = isset($_GET['paid']);
$order = null;
if ($orderNumber) {
    $stmt = Database::get()->prepare('SELECT order_number, customer_name, event_date, status, total FROM orders WHERE order_number = ? LIMIT 1');
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();
}

$pageTitle = 'Order Confirmation';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:640px;text-align:center;">
    <?php if ($order): ?>
      <div class="hero-eyebrow" style="color:var(--gold-500);">Order #<?= e($order['order_number']) ?></div>
      <?php if ($paidFlag || $order['status'] === 'paid'): ?>
        <h1>Payment Confirmed!</h1>
        <p class="lead" style="max-width:none;">Thank you, <?= e($order['customer_name']) ?>! Your order is confirmed for <?= e(date('F j, Y', strtotime($order['event_date']))) ?>. A receipt has been emailed to you.</p>
      <?php else: ?>
        <h1>Thank You for Your Order Request!</h1>
        <p class="lead" style="max-width:none;">Hi <?= e($order['customer_name']) ?>, we've received your request for <?= e(date('F j, Y', strtotime($order['event_date']))) ?> and will review it shortly. You'll receive an email with a secure payment link once it's approved.</p>
      <?php endif; ?>
      <p><strong>Total: <?= money((float)$order['total']) ?></strong></p>
      <a href="<?= e(base_url('pages/menu.php')) ?>" class="btn btn-primary">Back to Menu</a>
    <?php else: ?>
      <h1>Order Not Found</h1>
      <p>We couldn't find that order. If you just placed one, check your email for confirmation.</p>
      <a href="<?= e(base_url('pages/menu.php')) ?>" class="btn btn-primary">Back to Menu</a>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
