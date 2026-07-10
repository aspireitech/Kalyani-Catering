<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$db = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired, please try again.');
        redirect('messages.php');
    }
    $id = (int)($_POST['id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['new', 'read', 'replied'], true) ? $_POST['status'] : 'read';
    $db->prepare('UPDATE contact_messages SET status = ? WHERE id = ?')->execute([$status, $id]);
    redirect('messages.php');
}

$messages = $db->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Contact Messages';
$activeNav = 'messages';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>From</th><th>Department</th><th>Message</th><th>Status</th><th>Received</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($messages as $m): ?>
          <tr>
            <td><strong><?= e($m['name']) ?></strong><br><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><?= $m['phone'] ? '<br>' . e($m['phone']) : '' ?></td>
            <td><?= e(ucfirst(str_replace('_', ' ', $m['department']))) ?></td>
            <td style="max-width:320px;white-space:normal;"><?= e(mb_strimwidth($m['message'], 0, 160, '…')) ?></td>
            <td><span class="pill <?= $m['status'] === 'new' ? 'pill-pending_approval' : ($m['status'] === 'replied' ? 'pill-paid' : 'pill-completed') ?>"><?= e(ucfirst($m['status'])) ?></span></td>
            <td><?= e(date('M j, Y', strtotime($m['created_at']))) ?></td>
            <td>
              <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <select name="status" onchange="this.form.submit()">
                  <option value="new" <?= $m['status'] === 'new' ? 'selected' : '' ?>>New</option>
                  <option value="read" <?= $m['status'] === 'read' ? 'selected' : '' ?>>Read</option>
                  <option value="replied" <?= $m['status'] === 'replied' ? 'selected' : '' ?>>Replied</option>
                </select>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$messages): ?><tr><td colspan="6" class="muted">No messages yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
