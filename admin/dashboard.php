<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$db = Database::get();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_approval'")->fetchColumn();
$approvedCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status IN ('approved','payment_pending')")->fetchColumn();
$paidThisMonth = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'paid' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$upcomingCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'paid' AND event_date >= CURDATE()")->fetchColumn();
$subscriberCount = (int)$db->query("SELECT COUNT(*) FROM subscribers WHERE is_active = 1")->fetchColumn();
$newMessages = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();

$recentOrders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8")->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= $pendingCount ?></div><div class="label">Pending Approval</div></div>
  <div class="stat-card"><div class="num"><?= $approvedCount ?></div><div class="label">Awaiting Payment</div></div>
  <div class="stat-card"><div class="num"><?= money($paidThisMonth) ?></div><div class="label">Revenue This Month</div></div>
  <div class="stat-card"><div class="num"><?= $upcomingCount ?></div><div class="label">Upcoming Confirmed Events</div></div>
  <div class="stat-card"><div class="num"><?= $subscriberCount ?></div><div class="label">Newsletter Subscribers</div></div>
  <div class="stat-card"><div class="num"><?= $newMessages ?></div><div class="label">New Contact Messages</div></div>
</div>

<?php if ($pendingCount > 0): ?>
  <div class="alert alert-success" style="background:#fdf1d6;color:#9a6a10;border-color:#f4dfa6;">
    You have <?= $pendingCount ?> order<?= $pendingCount === 1 ? '' : 's' ?> waiting for review. <a href="orders.php?status=pending_approval" style="font-weight:700;">Review now &rarr;</a>
  </div>
<?php endif; ?>

<div class="admin-card">
  <h3 style="margin-top:0;">Recent Orders</h3>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Order #</th><th>Customer</th><th>Event Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($recentOrders as $order): ?>
          <tr>
            <td><?= e($order['order_number']) ?></td>
            <td><?= e($order['customer_name']) ?></td>
            <td><?= e(date('M j, Y', strtotime($order['event_date']))) ?></td>
            <td><?= money((float)$order['total']) ?></td>
            <td><span class="pill pill-<?= e($order['status']) ?>"><?= e(str_replace('_', ' ', $order['status'])) ?></span></td>
            <td><a href="order_detail.php?id=<?= (int)$order['id'] ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recentOrders): ?><tr><td colspan="6" class="muted">No orders yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
