<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$db = Database::get();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired, please try again.');
        redirect('discounts.php');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        $description = trim((string)($_POST['description'] ?? ''));
        $type = ($_POST['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        $value = (float)($_POST['value'] ?? 0);
        $minOrder = (float)($_POST['min_order_amount'] ?? 0);
        $maxUses = ($_POST['max_uses'] ?? '') !== '' ? (int)$_POST['max_uses'] : null;
        $expiresAt = ($_POST['expires_at'] ?? '') !== '' ? $_POST['expires_at'] : null;

        if ($code === '' || $value <= 0) {
            flash_set('error', 'Please enter a code and a value greater than 0.');
        } else {
            $stmt = $db->prepare(
                'INSERT INTO discount_codes (code, description, type, value, min_order_amount, max_uses, expires_at) VALUES (?,?,?,?,?,?,?)'
            );
            try {
                $stmt->execute([$code, $description, $type, $value, $minOrder, $maxUses, $expiresAt]);
                flash_set('success', "Code $code created! Share the link below with your customers.");
            } catch (PDOException $e) {
                flash_set('error', 'That code already exists.');
            }
        }
    } elseif ($action === 'toggle_active') {
        $db->prepare('UPDATE discount_codes SET is_active = NOT is_active WHERE id = ?')->execute([(int)$_POST['id']]);
        flash_set('success', 'Discount code updated.');
    } elseif ($action === 'delete') {
        $db->prepare('DELETE FROM discount_codes WHERE id = ?')->execute([(int)$_POST['id']]);
        flash_set('success', 'Discount code deleted.');
    }
    redirect('discounts.php');
}

$codes = $db->query('SELECT * FROM discount_codes ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Discount Codes';
$activeNav = 'discounts';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card">
  <h3 style="margin-top:0;">Create a New Discount Code</h3>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create">
    <div class="field-row">
      <div class="field"><label for="code">Code</label><input type="text" id="code" name="code" placeholder="e.g. SUMMER25" required style="text-transform:uppercase;"></div>
      <div class="field"><label for="description">Description</label><input type="text" id="description" name="description" placeholder="e.g. Summer promo"></div>
    </div>
    <div class="field-row">
      <div class="field">
        <label for="type">Type</label>
        <select id="type" name="type">
          <option value="percent">Percent Off (%)</option>
          <option value="fixed">Fixed Amount Off ($)</option>
        </select>
      </div>
      <div class="field"><label for="value">Value</label><input type="number" step="0.01" min="0" id="value" name="value" required></div>
      <div class="field"><label for="min_order_amount">Minimum Order ($)</label><input type="number" step="0.01" min="0" id="min_order_amount" name="min_order_amount" value="0"></div>
    </div>
    <div class="field-row">
      <div class="field"><label for="max_uses">Max Uses (optional)</label><input type="number" min="1" id="max_uses" name="max_uses" placeholder="Unlimited"></div>
      <div class="field"><label for="expires_at">Expires On (optional)</label><input type="date" id="expires_at" name="expires_at"></div>
    </div>
    <button type="submit" class="btn btn-primary">Create Code</button>
  </form>
</div>

<div class="admin-card">
  <h3 style="margin-top:0;">All Discount Codes</h3>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Code</th><th>Discount</th><th>Min. Order</th><th>Uses</th><th>Expires</th><th>Active</th><th>Share Link</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($codes as $c):
          $shareLink = base_url('pages/menu.php') . '?promo=' . urlencode($c['code']);
          $shareText = 'Use code ' . $c['code'] . ' for ' . ($c['type'] === 'percent' ? $c['value'] . '% off' : '$' . $c['value'] . ' off') . ' at ' . get_setting('business_name', SITE_NAME) . '! ' . $shareLink;
        ?>
        <tr>
          <td><strong><?= e($c['code']) ?></strong><br><span class="muted"><?= e($c['description']) ?></span></td>
          <td><?= $c['type'] === 'percent' ? (float)$c['value'] . '%' : money((float)$c['value']) ?></td>
          <td><?= money((float)$c['min_order_amount']) ?></td>
          <td><?= (int)$c['used_count'] ?><?= $c['max_uses'] ? ' / ' . (int)$c['max_uses'] : '' ?></td>
          <td><?= $c['expires_at'] ? e(date('M j, Y', strtotime($c['expires_at']))) : '—' ?></td>
          <td>
            <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="action" value="toggle_active">
              <button type="submit" class="btn btn-sm <?= $c['is_active'] ? 'btn-primary' : 'btn-outline' ?>"><?= $c['is_active'] ? 'Active' : 'Paused' ?></button>
            </form>
          </td>
          <td>
            <div class="discount-share-box">
              <span style="overflow-x:auto;white-space:nowrap;max-width:180px;"><?= e($shareText) ?></span>
              <button type="button" class="copy-share-btn btn btn-sm btn-outline">Copy</button>
            </div>
          </td>
          <td>
            <form method="post" onsubmit="return confirm('Delete this code?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="action" value="delete">
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$codes): ?><tr><td colspan="8" class="muted">No discount codes yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
