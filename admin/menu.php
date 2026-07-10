<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$db = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired, please try again.');
        redirect('menu.php');
    }
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete' && $id) {
        $item = $db->prepare('SELECT image_path FROM menu_items WHERE id = ?');
        $item->execute([$id]);
        if ($path = $item->fetchColumn()) {
            $full = __DIR__ . '/../uploads/menu/' . $path;
            if (is_file($full)) { @unlink($full); }
        }
        $db->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
        flash_set('success', 'Menu item deleted.');
    } elseif ($action === 'toggle_active' && $id) {
        $db->prepare('UPDATE menu_items SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
        flash_set('success', 'Item availability updated.');
    } elseif ($action === 'toggle_new' && $id) {
        $db->prepare('UPDATE menu_items SET is_new = NOT is_new WHERE id = ?')->execute([$id]);
        flash_set('success', 'Item "new" flag updated.');
    }
    redirect('menu.php');
}

$items = $db->query("SELECT * FROM menu_items ORDER BY course_type, sort_order")->fetchAll();
$tierStmt = $db->query("SELECT menu_item_id, tier_name, price FROM menu_item_tiers ORDER BY sort_order");
$tiersByItem = [];
foreach ($tierStmt->fetchAll() as $t) {
    $tiersByItem[$t['menu_item_id']][] = $t;
}

$pageTitle = 'Menu Items';
$activeNav = 'menu';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <p class="muted" style="margin:0;"><?= count($items) ?> dishes total. Toggle availability, mark as New (triggers newsletter eligibility), or edit pricing.</p>
    <a href="menu_edit.php" class="btn btn-primary">+ Add New Dish</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th></th><th>Name</th><th>Cuisine</th><th>Diet</th><th>Course</th><th>Price</th><th>Discount</th><th>Active</th><th>New</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($items as $item): $tiers = $tiersByItem[$item['id']] ?? []; ?>
        <tr>
          <td><img class="thumb-preview" src="<?= $item['image_path'] ? e(base_url('uploads/menu/' . $item['image_path'])) : e(base_url('assets/images/logo.jpg')) ?>" alt=""></td>
          <td><?= e($item['name']) ?></td>
          <td><?= e($item['cuisine']) ?></td>
          <td><?= $item['diet_type'] === 'veg' ? '🌱 Veg' : '🍗 Non-Veg' ?></td>
          <td><?= e(course_label($item['course_type'])) ?></td>
          <td>
            <?php if ($tiers): ?>
              <?php foreach ($tiers as $t): ?><div><?= e($t['tier_name']) ?>: <?= money((float)$t['price']) ?></div><?php endforeach; ?>
            <?php else: ?>
              <?= $item['unit_price'] !== null ? money((float)$item['unit_price']) : '—' ?>
            <?php endif; ?>
          </td>
          <td><?= (float)$item['discount_percent'] > 0 ? (float)$item['discount_percent'] . '%' : '—' ?></td>
          <td>
            <form method="post" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <input type="hidden" name="action" value="toggle_active">
              <button type="submit" class="btn btn-sm <?= $item['is_active'] ? 'btn-primary' : 'btn-outline' ?>"><?= $item['is_active'] ? 'Active' : 'Hidden' ?></button>
            </form>
          </td>
          <td>
            <form method="post" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <input type="hidden" name="action" value="toggle_new">
              <button type="submit" class="btn btn-sm <?= $item['is_new'] ? 'btn-gold' : 'btn-outline' ?>"><?= $item['is_new'] ? 'New' : 'Mark New' ?></button>
            </form>
          </td>
          <td style="white-space:nowrap;">
            <a href="menu_edit.php?id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this dish permanently?');">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="10" class="muted">No menu items yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
