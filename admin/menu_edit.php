<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$db = Database::get();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$item = null;
$tiers = ['Small' => '', 'Medium' => '', 'Full' => ''];

if ($id) {
    $stmt = $db->prepare('SELECT * FROM menu_items WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        flash_set('error', 'Menu item not found.');
        redirect('menu.php');
    }
    $tierStmt = $db->prepare('SELECT tier_name, price FROM menu_item_tiers WHERE menu_item_id = ?');
    $tierStmt->execute([$id]);
    foreach ($tierStmt->fetchAll() as $t) {
        $tiers[$t['tier_name']] = $t['price'];
    }
}
$usesTiers = $item ? (bool)array_filter($tiers) : true;

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expired, please try again.';
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $cuisine = trim((string)($_POST['cuisine'] ?? 'Indian'));
    $dietType = ($_POST['diet_type'] ?? 'veg') === 'non-veg' ? 'non-veg' : 'veg';
    $courseType = trim((string)($_POST['course_type'] ?? 'starter'));
    $servingInfo = trim((string)($_POST['serving_info'] ?? ''));
    $pricingMode = ($_POST['pricing_mode'] ?? 'tiers') === 'unit' ? 'unit' : 'tiers';
    $unitPrice = ($_POST['unit_price'] ?? '') !== '' ? (float)$_POST['unit_price'] : null;
    $quantityAvailable = ($_POST['quantity_available'] ?? '') !== '' ? (int)$_POST['quantity_available'] : null;
    $discountPercent = (float)($_POST['discount_percent'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isNew = isset($_POST['is_new']) ? 1 : 0;
    $submittedTiers = [
        'Small' => $_POST['tier_small'] ?? '',
        'Medium' => $_POST['tier_medium'] ?? '',
        'Full' => $_POST['tier_full'] ?? '',
    ];

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($pricingMode === 'unit' && $unitPrice === null) {
        $errors[] = 'Please enter a price.';
    }
    if ($pricingMode === 'tiers' && !array_filter($submittedTiers)) {
        $errors[] = 'Please enter at least one tray size price.';
    }

    $imagePath = $item['image_path'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed, please try again.';
        } elseif ($file['size'] > 4 * 1024 * 1024) {
            $errors[] = 'Image must be smaller than 4MB.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($allowed[$mime])) {
                $errors[] = 'Image must be a JPG, PNG, or WEBP file.';
            } else {
                $newName = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
                $destDir = __DIR__ . '/../uploads/menu/';
                if (move_uploaded_file($file['tmp_name'], $destDir . $newName)) {
                    if ($imagePath && is_file($destDir . $imagePath)) {
                        @unlink($destDir . $imagePath);
                    }
                    $imagePath = $newName;
                } else {
                    $errors[] = 'Could not save uploaded image.';
                }
            }
        }
    }

    if (!$errors) {
        $baseSlug = slugify($name);
        $slug = $baseSlug;
        $suffix = 1;
        while (true) {
            $check = $db->prepare('SELECT id FROM menu_items WHERE slug = ? AND id != ?');
            $check->execute([$slug, $id]);
            if (!$check->fetch()) break;
            $slug = $baseSlug . '-' . (++$suffix);
        }

        if ($id) {
            $stmt = $db->prepare(
                'UPDATE menu_items SET name=?, slug=?, description=?, cuisine=?, diet_type=?, course_type=?, serving_info=?, unit_price=?, quantity_available=?, discount_percent=?, image_path=?, is_active=?, is_new=? WHERE id=?'
            );
            $stmt->execute([$name, $slug, $description, $cuisine, $dietType, $courseType, $servingInfo, $pricingMode === 'unit' ? $unitPrice : null, $quantityAvailable, $discountPercent, $imagePath, $isActive, $isNew, $id]);
            $itemId = $id;
        } else {
            $stmt = $db->prepare(
                'INSERT INTO menu_items (name, slug, description, cuisine, diet_type, course_type, serving_info, unit_price, quantity_available, discount_percent, image_path, is_active, is_new, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $maxOrder = (int)$db->query('SELECT COALESCE(MAX(sort_order),0) FROM menu_items')->fetchColumn();
            $stmt->execute([$name, $slug, $description, $cuisine, $dietType, $courseType, $servingInfo, $pricingMode === 'unit' ? $unitPrice : null, $quantityAvailable, $discountPercent, $imagePath, $isActive, $isNew, $maxOrder + 1]);
            $itemId = (int)$db->lastInsertId();
        }

        $db->prepare('DELETE FROM menu_item_tiers WHERE menu_item_id = ?')->execute([$itemId]);
        if ($pricingMode === 'tiers') {
            $servesText = ['Small' => 'Serves 8-10', 'Medium' => 'Serves 18-20', 'Full' => 'Serves 35-40'];
            $order = 1;
            $tierStmt = $db->prepare('INSERT INTO menu_item_tiers (menu_item_id, tier_name, serves_text, price, sort_order) VALUES (?,?,?,?,?)');
            foreach ($submittedTiers as $tierName => $price) {
                if ($price === '' || $price === null) continue;
                $tierStmt->execute([$itemId, $tierName, $servesText[$tierName], (float)$price, $order++]);
            }
        }

        flash_set('success', $id ? 'Menu item updated.' : 'Menu item created.');
        redirect('menu.php');
    }
}

$pageTitle = $item ? 'Edit Dish' : 'Add New Dish';
$activeNav = 'menu';
require_once __DIR__ . '/includes/admin_header.php';
?>

<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="admin-card">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

  <fieldset>
    <legend>Basic Info</legend>
    <div class="field"><label for="name">Dish Name</label><input type="text" id="name" name="name" value="<?= e($item['name'] ?? '') ?>" required></div>
    <div class="field"><label for="description">Description</label><textarea id="description" name="description" rows="3"><?= e($item['description'] ?? '') ?></textarea></div>
    <div class="field-row">
      <div class="field"><label for="cuisine">Cuisine</label><input type="text" id="cuisine" name="cuisine" list="cuisine-list" value="<?= e($item['cuisine'] ?? 'Indian') ?>"></div>
      <datalist id="cuisine-list"><option value="Indian"><option value="Indo-Chinese"></datalist>
      <div class="field">
        <label for="course_type">Course Type</label>
        <select id="course_type" name="course_type">
          <?php foreach (COURSE_LABELS as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= ($item['course_type'] ?? 'starter') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label>Diet Type</label>
      <div class="radio-group">
        <label class="radio-card <?= ($item['diet_type'] ?? 'veg') === 'veg' ? 'active' : '' ?>"><input type="radio" name="diet_type" value="veg" <?= ($item['diet_type'] ?? 'veg') === 'veg' ? 'checked' : '' ?>> 🌱 Veg</label>
        <label class="radio-card <?= ($item['diet_type'] ?? '') === 'non-veg' ? 'active' : '' ?>"><input type="radio" name="diet_type" value="non-veg" <?= ($item['diet_type'] ?? '') === 'non-veg' ? 'checked' : '' ?>> 🍗 Non-Veg</label>
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Pricing &amp; Quantity</legend>
    <div class="field">
      <label>Pricing Style</label>
      <div class="radio-group">
        <label class="radio-card <?= $usesTiers ? 'active' : '' ?>"><input type="radio" name="pricing_mode" value="tiers" <?= $usesTiers ? 'checked' : '' ?>> Tray Sizes (Small/Medium/Full)</label>
        <label class="radio-card <?= !$usesTiers ? 'active' : '' ?>"><input type="radio" name="pricing_mode" value="unit" <?= !$usesTiers ? 'checked' : '' ?>> Single Price (e.g. drinks, sides)</label>
      </div>
    </div>
    <div class="field-row">
      <div class="field"><label for="tier_small">Small (8-10) Price</label><input type="number" step="0.01" min="0" id="tier_small" name="tier_small" value="<?= e($tiers['Small']) ?>"></div>
      <div class="field"><label for="tier_medium">Medium (18-20) Price</label><input type="number" step="0.01" min="0" id="tier_medium" name="tier_medium" value="<?= e($tiers['Medium']) ?>"></div>
      <div class="field"><label for="tier_full">Full (35-40) Price</label><input type="number" step="0.01" min="0" id="tier_full" name="tier_full" value="<?= e($tiers['Full']) ?>"></div>
    </div>
    <div class="field-row">
      <div class="field"><label for="unit_price">Single Item Price</label><input type="number" step="0.01" min="0" id="unit_price" name="unit_price" value="<?= e($item['unit_price'] ?? '') ?>"></div>
      <div class="field"><label for="serving_info">Serving Info Label</label><input type="text" id="serving_info" name="serving_info" placeholder="e.g. Glass, Bowl, Tray" value="<?= e($item['serving_info'] ?? '') ?>"></div>
    </div>
    <div class="field-row">
      <div class="field"><label for="quantity_available">Quantity Available (optional)</label><input type="number" min="0" id="quantity_available" name="quantity_available" placeholder="Leave blank for unlimited" value="<?= e($item['quantity_available'] ?? '') ?>"></div>
      <div class="field"><label for="discount_percent">Discount % (optional)</label><input type="number" step="0.01" min="0" max="100" id="discount_percent" name="discount_percent" value="<?= e($item['discount_percent'] ?? '0') ?>"></div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Photo</legend>
    <img id="image-preview" class="thumb-preview" style="width:120px;height:120px;margin-bottom:10px;" src="<?= $item['image_path'] ?? null ? e(base_url('uploads/menu/' . $item['image_path'])) : e(base_url('assets/images/logo.jpg')) ?>" alt="">
    <input type="file" id="image-input" name="image" accept="image/png,image/jpeg,image/webp">
  </fieldset>

  <fieldset>
    <legend>Visibility</legend>
    <label style="display:flex;align-items:center;gap:8px;font-weight:400;"><input type="checkbox" style="width:auto;" name="is_active" <?= ($item['is_active'] ?? 1) ? 'checked' : '' ?>> Active (visible on menu)</label>
    <label style="display:flex;align-items:center;gap:8px;font-weight:400;margin-top:8px;"><input type="checkbox" style="width:auto;" name="is_new" <?= ($item['is_new'] ?? 0) ? 'checked' : '' ?>> Mark as "New" (shows New badge)</label>
  </fieldset>

  <button type="submit" class="btn btn-primary">Save Dish</button>
  <a href="menu.php" class="btn btn-outline">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
