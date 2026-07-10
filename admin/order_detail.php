<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/OrderService.php';
Auth::requireLogin();

$db = Database::get();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) {
    flash_set('error', 'Order not found.');
    redirect('orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired, please try again.');
        redirect('order_detail.php?id=' . $id);
    }
    $action = $_POST['action'] ?? '';
    $notes = trim((string)($_POST['admin_notes'] ?? ''));
    $payUrl = base_url('pages/pay.php') . '?token=' . urlencode($order['payment_token']);

    if ($action === 'approve') {
        $db->prepare('UPDATE orders SET status = "approved", admin_notes = ? WHERE id = ?')->execute([$notes, $id]);
        $order['admin_notes'] = $notes;
        Mailer::orderApproved($order, $payUrl);
        flash_set('success', 'Order approved and payment link emailed to customer.');
    } elseif ($action === 'reject') {
        $db->prepare('UPDATE orders SET status = "rejected", admin_notes = ? WHERE id = ?')->execute([$notes, $id]);
        $order['admin_notes'] = $notes;
        Mailer::orderRejected($order);
        flash_set('success', 'Order rejected and customer notified.');
    } elseif ($action === 'resend_link') {
        Mailer::orderApproved($order, $payUrl);
        flash_set('success', 'Payment link re-sent to customer.');
    } elseif ($action === 'mark_paid') {
        OrderService::markPaid($id, 'manual', 'Marked paid by admin (' . Auth::name() . ')');
        flash_set('success', 'Order marked as paid.');
    } elseif ($action === 'mark_completed') {
        $db->prepare('UPDATE orders SET status = "completed" WHERE id = ?')->execute([$id]);
        flash_set('success', 'Order marked as completed.');
    } elseif ($action === 'cancel') {
        $db->prepare('UPDATE orders SET status = "cancelled", admin_notes = ? WHERE id = ?')->execute([$notes, $id]);
        flash_set('success', 'Order cancelled.');
    } elseif ($action === 'save_notes') {
        $db->prepare('UPDATE orders SET admin_notes = ? WHERE id = ?')->execute([$notes, $id]);
        flash_set('success', 'Notes saved.');
    }
    redirect('order_detail.php?id=' . $id);
}

$itemStmt = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll();
$payUrl = base_url('pages/pay.php') . '?token=' . urlencode($order['payment_token']);

$pageTitle = 'Order ' . $order['order_number'];
$activeNav = 'orders';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;align-items:center;">
    <div>
      <h3 style="margin:0;">Order #<?= e($order['order_number']) ?></h3>
      <span class="pill pill-<?= e($order['status']) ?>"><?= e(str_replace('_', ' ', $order['status'])) ?></span>
    </div>
    <div class="muted">Placed <?= e(date('M j, Y g:ia', strtotime($order['created_at']))) ?></div>
  </div>
</div>

<div class="cart-layout">
  <div>
    <div class="admin-card">
      <h3 style="margin-top:0;">Items</h3>
      <table>
        <thead><tr><th>Dish</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><?= e($item['item_name']) ?><?= $item['tier_name'] ? ' (' . e($item['tier_name']) . ')' : '' ?></td>
              <td><?= (int)$item['quantity'] ?></td>
              <td><?= money((float)$item['unit_price']) ?></td>
              <td><?= money((float)$item['line_total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="summary-row" style="margin-top:14px;"><span>Subtotal</span><span><?= money((float)$order['subtotal']) ?></span></div>
      <?php if ((float)$order['discount_amount'] > 0): ?>
        <div class="summary-row"><span>Discount <?= $order['discount_code'] ? '(' . e($order['discount_code']) . ')' : '' ?></span><span>-<?= money((float)$order['discount_amount']) ?></span></div>
      <?php endif; ?>
      <div class="summary-row total"><span>Total</span><span><?= money((float)$order['total']) ?></span></div>
    </div>

    <div class="admin-card">
      <h3 style="margin-top:0;">Admin Notes</h3>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <textarea id="admin-notes-textarea" name="admin_notes" rows="3" placeholder="Internal notes or a reason if rejecting..."><?= e($order['admin_notes']) ?></textarea>
        <div style="margin-top:10px;">
          <button type="submit" name="action" value="save_notes" class="btn btn-outline btn-sm">Save Notes</button>
        </div>
      </form>
    </div>
  </div>

  <div>
    <div class="admin-card">
      <h4 style="margin-top:0;">Customer</h4>
      <p><strong><?= e($order['customer_name']) ?></strong><br>
      <a href="mailto:<?= e($order['customer_email']) ?>"><?= e($order['customer_email']) ?></a><br>
      <?= e($order['customer_phone']) ?></p>
      <p><strong>Event:</strong> <?= e(date('F j, Y', strtotime($order['event_date']))) ?><?= $order['event_time'] ? ' at ' . e(date('g:ia', strtotime($order['event_time']))) : '' ?></p>
      <p><strong><?= $order['fulfillment_type'] === 'delivery' ? 'Delivery' : 'Pickup' ?></strong><?= $order['address'] ? '<br>' . nl2br(e($order['address'])) : '' ?></p>
      <?php if ($order['notes']): ?><p><strong>Customer notes:</strong><br><?= nl2br(e($order['notes'])) ?></p><?php endif; ?>
    </div>

    <div class="admin-card">
      <h4 style="margin-top:0;">Actions</h4>
      <form method="post" onsubmit="return confirm('Approve this order and email the payment link?');">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="admin_notes" class="sync-admin-notes" value="<?= e($order['admin_notes']) ?>">
        <?php if ($order['status'] === 'pending_approval'): ?>
          <button type="submit" name="action" value="approve" class="btn btn-primary btn-block" style="margin-bottom:8px;">✅ Approve &amp; Send Payment Link</button>
        <?php endif; ?>
      </form>
      <?php if ($order['status'] === 'pending_approval'): ?>
        <form method="post" onsubmit="return confirm('Reject this order? The customer will be notified.');">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <input type="hidden" name="admin_notes" class="sync-admin-notes" value="<?= e($order['admin_notes']) ?>">
          <button type="submit" name="action" value="reject" class="btn btn-danger btn-block" style="margin-bottom:8px;">❌ Reject Order</button>
        </form>
      <?php endif; ?>

      <?php if (in_array($order['status'], ['approved', 'payment_pending'], true)): ?>
        <div class="discount-share-box" style="margin-bottom:10px;">
          <span style="overflow-x:auto;white-space:nowrap;flex:1;"><?= e($payUrl) ?></span>
          <button type="button" class="copy-share-btn btn btn-sm btn-outline">Copy</button>
        </div>
        <form method="post" style="margin-bottom:8px;">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <button type="submit" name="action" value="resend_link" class="btn btn-outline btn-block">Resend Payment Link Email</button>
        </form>
        <form method="post" onsubmit="return confirm('Mark this order as paid manually (e.g. cash/check received)?');">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <button type="submit" name="action" value="mark_paid" class="btn btn-gold btn-block" style="margin-bottom:8px;">Mark Paid Manually</button>
        </form>
        <form method="post" onsubmit="return confirm('Cancel this order?');">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <input type="hidden" name="admin_notes" class="sync-admin-notes" value="<?= e($order['admin_notes']) ?>">
          <button type="submit" name="action" value="cancel" class="btn btn-danger btn-block">Cancel Order</button>
        </form>
      <?php endif; ?>

      <?php if ($order['status'] === 'paid'): ?>
        <p><strong>Paid via:</strong> <?= e(ucfirst($order['payment_method'] ?? '')) ?><br><span class="muted"><?= e($order['payment_reference'] ?? '') ?></span></p>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <button type="submit" name="action" value="mark_completed" class="btn btn-primary btn-block">Mark Event Completed</button>
        </form>
      <?php endif; ?>

      <?php if (in_array($order['status'], ['rejected', 'cancelled', 'completed'], true)): ?>
        <p class="muted">No further actions available for this order.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      var textarea = document.getElementById('admin-notes-textarea');
      var syncField = form.querySelector('.sync-admin-notes');
      if (textarea && syncField) {
        syncField.value = textarea.value;
      }
    });
  });
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
