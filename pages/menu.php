<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Our Menu';
$pageDescription = 'Browse Kalyani Catering\'s full menu of Indian and Indo-Chinese starters, mains, rice, desserts and drinks. Choose your tray size and add to cart.';

if (!empty($_GET['promo'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $_SESSION['pending_promo'] = strtoupper(trim((string)$_GET['promo']));
}

$db = Database::get();
$items = $db->query("SELECT * FROM menu_items WHERE is_active = 1 ORDER BY course_type, sort_order")->fetchAll();

$tierStmt = $db->query("SELECT * FROM menu_item_tiers ORDER BY sort_order");
$tiersByItem = [];
foreach ($tierStmt->fetchAll() as $tier) {
    $tiersByItem[$tier['menu_item_id']][] = $tier;
}

$courses = [];
$cuisines = [];
foreach ($items as $item) {
    $courses[$item['course_type']] = course_label($item['course_type']);
    $cuisines[$item['cuisine']] = $item['cuisine'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-bottom:0;">
  <div class="container">
    <div class="section-head" style="margin-bottom:10px;">
      <div class="eyebrow">Full Menu</div>
      <h1>Choose Your Dishes</h1>
      <p>Pick a size, set the quantity, and add to your cart. Every order is reviewed by our team before payment is requested.</p>
    </div>
  </div>
</section>

<div class="menu-toolbar">
  <div class="container">
    <div class="filter-row">
      <button class="filter-chip active" data-group="course" data-value="all">All Dishes</button>
      <?php foreach ($courses as $key => $label): ?>
        <button class="filter-chip" data-group="course" data-value="<?= e($key) ?>"><?= e($label) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="filter-row-secondary">
      <button class="filter-chip active" data-group="diet" data-value="all">All</button>
      <button class="filter-chip" data-group="diet" data-value="veg">🌱 Veg</button>
      <button class="filter-chip" data-group="diet" data-value="non-veg">🍗 Non-Veg</button>
      <?php if (count($cuisines) > 1): ?>
        <button class="filter-chip active" data-group="cuisine" data-value="all">All Cuisines</button>
        <?php foreach ($cuisines as $c): ?>
          <button class="filter-chip" data-group="cuisine" data-value="<?= e($c) ?>"><?= e($c) ?></button>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="menu-grid" id="menu-grid">
      <?php foreach ($items as $item): $tiers = $tiersByItem[$item['id']] ?? []; ?>
        <div class="menu-card" data-id="<?= (int)$item['id'] ?>" data-name="<?= e($item['name']) ?>" data-diet="<?= e($item['diet_type']) ?>" data-course="<?= e($item['course_type']) ?>" data-cuisine="<?= e($item['cuisine']) ?>" data-slug="<?= e($item['slug']) ?>">
          <div class="menu-card-media">
            <img src="<?= e(dish_image_url($item['image_path'], $item['course_type'], $item['diet_type'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
            <span class="badge <?= $item['diet_type'] === 'veg' ? 'badge-veg' : 'badge-nonveg' ?>"><?= $item['diet_type'] === 'veg' ? 'Veg' : 'Non-Veg' ?></span>
            <?php if ($item['is_new']): ?><span class="badge badge-new">New</span><?php endif; ?>
          </div>
          <div class="menu-card-body">
            <span class="cuisine"><?= e($item['cuisine']) ?> &middot; <?= e(course_label($item['course_type'])) ?></span>
            <h3><?= e($item['name']) ?></h3>
            <p class="desc"><?= e($item['description']) ?></p>
          </div>
          <div class="menu-card-footer">
            <?php if ($tiers): ?>
              <div class="tier-select">
                <?php foreach ($tiers as $i => $tier):
                  $price = (float)$tier['price'] * (1 - (float)$item['discount_percent'] / 100); ?>
                  <button type="button" class="tier-btn <?= $i === 0 ? 'active' : '' ?>" data-tier="<?= e($tier['tier_name']) ?>">
                    <?= e($tier['tier_name']) ?><strong><?= money($price) ?></strong>
                  </button>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="tier-select" style="display:none;"></div>
              <p style="font-weight:700;color:var(--green-900);margin-bottom:10px;">
                <?= money((float)$item['unit_price'] * (1 - (float)$item['discount_percent'] / 100)) ?>
                <?= $item['serving_info'] ? ' / ' . e($item['serving_info']) : '' ?>
              </p>
            <?php endif; ?>
            <div class="add-row">
              <div class="qty-control">
                <button type="button" class="qty-minus" aria-label="Decrease quantity">&minus;</button>
                <input type="number" class="qty-input" value="1" min="1" max="99" aria-label="Quantity">
                <button type="button" class="qty-plus" aria-label="Increase quantity">&plus;</button>
              </div>
              <button type="button" class="btn btn-primary btn-block add-to-cart-btn">Add to Cart</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="no-results" id="menu-empty" hidden>
      <p>No dishes match those filters. Try a different combination.</p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
