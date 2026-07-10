<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$db = Database::get();
$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['pending_approval', 'approved', 'payment_pending', 'paid', 'rejected', 'completed', 'cancelled'];

if ($statusFilter && in_array($statusFilter, $validStatuses, true)) {
    $stmt = $db->prepare('SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC');
    $stmt->execute([$statusFilter]);
} else {
    $stmt = $db->query('SELECT * FROM orders ORDER BY created_at DESC');
}
$orders = $stmt->fetchAll();

$pageTitle = 'Orders';
$activeNav = 'orders';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="filter-row" style="margin-bottom:18px;">
  <a class="filter-chip <?= $statusFilter === '' ? 'active' : '' ?>" href="orders.php">All</a>
  <?php foreach ($validStatuses as $s): ?>
    <a class="filter-chip <?= $statusFilter === $s ? 'active' : '' ?>" href="orders.php?status=<?= e($s) ?>"><?= e(ucwords(str_replace('_', ' ', $s))) ?></a>
  <?php endforeach; ?>
</div>

<div class="admin-card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Order #</th><th>Customer</th><th>Event Date</th><th>Total</th><th>Status</th><th>Placed</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td><?= e($order['order_number']) ?></td>
            <td><?= e($order['customer_name']) ?><br><span class="muted"><?= e($order['customer_email']) ?></span></td>
            <td><?= e(date('M j, Y', strtotime($order['event_date']))) ?></td>
            <td><?= money((float)$order['total']) ?></td>
            <td><span class="pill pill-<?= e($order['status']) ?>"><?= e(str_replace('_', ' ', $order['status'])) ?></span></td>
            <td><?= e(date('M j', strtotime($order['created_at']))) ?></td>
            <td><a href="order_detail.php?id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline">Review</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="7" class="muted">No orders found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
