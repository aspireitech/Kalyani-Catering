<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Authentic Indian Catering in Herriman, UT';
$pageDescription = 'Kalyani Catering brings homemade Indian flavors to weddings, parties, birthdays and corporate events. Order online for pickup or delivery.';

$db = Database::get();
$featured = $db->query(
    "SELECT * FROM menu_items WHERE is_active = 1 AND is_new = 1 ORDER BY sort_order LIMIT 6"
)->fetchAll();
if (count($featured) < 3) {
    $featured = $db->query("SELECT * FROM menu_items WHERE is_active = 1 ORDER BY sort_order LIMIT 6")->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <div>
      <div class="hero-eyebrow">Homemade Taste &middot; Made With Love</div>
      <h1>Good Food, Great Memories</h1>
      <p class="lead">Authentic Indian catering for weddings, parties, birthdays, festivals and corporate events — freshly cooked with pure ingredients, delivered right to your celebration.</p>
      <div class="hero-actions">
        <a href="<?= e(base_url('pages/menu.php')) ?>" class="btn btn-gold">Browse the Menu</a>
        <a href="<?= e(base_url('pages/contact.php')) ?>" class="btn btn-outline" style="border-color:#fff;color:#fff;">Get a Quote</a>
      </div>
      <div class="hero-badges">
        <span class="hero-badge"><span class="dot"></span>Freshly Cooked</span>
        <span class="hero-badge"><span class="dot"></span>Hygienic Preparation</span>
        <span class="hero-badge"><span class="dot"></span>Affordable Prices</span>
        <span class="hero-badge"><span class="dot"></span>Made Like Home</span>
      </div>
    </div>
    <div class="hero-photo">
      <img src="<?= e(base_url('assets/images/logo.jpg')) ?>" alt="Kalyani Catering">
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">Why Kalyani Catering</div>
      <h2>Every Order, Made With Care</h2>
    </div>
    <div class="feature-grid">
      <div class="feature-card"><div class="icon">🌿</div><h3>Fresh Ingredients</h3><p>Quality you can trust in every dish.</p></div>
      <div class="feature-card"><div class="icon">❤️</div><h3>Made With Love</h3><p>Hygienic, homemade preparation.</p></div>
      <div class="feature-card"><div class="icon">👨‍👩‍👧‍👦</div><h3>Every Occasion</h3><p>Weddings, birthdays &amp; family gatherings.</p></div>
      <div class="feature-card"><div class="icon">🚚</div><h3>Pickup or Delivery</h3><p>Choose what works best for your event.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">Catering For</div>
      <h2>All Occasions</h2>
    </div>
    <div class="occasion-grid">
      <div class="occasion-chip">Weddings</div>
      <div class="occasion-chip">Birthday Parties</div>
      <div class="occasion-chip">Festivals</div>
      <div class="occasion-chip">Corporate Events</div>
      <div class="occasion-chip">Family Gatherings</div>
      <div class="occasion-chip">Anniversaries</div>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">Fan Favorites</div>
      <h2>Popular &amp; New Dishes</h2>
    </div>
    <div class="menu-grid">
      <?php foreach ($featured as $item): ?>
        <div class="menu-card">
          <div class="menu-card-media">
            <?php if ($item['image_path']): ?>
              <img src="<?= e(base_url('uploads/menu/' . $item['image_path'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
            <?php else: ?>
              <span class="emoji-fallback"><?= $item['diet_type'] === 'veg' ? '🥗' : '🍛' ?></span>
            <?php endif; ?>
            <?php if ($item['is_new']): ?><span class="badge badge-new">New</span><?php endif; ?>
          </div>
          <div class="menu-card-body">
            <span class="cuisine"><?= e($item['cuisine']) ?> &middot; <?= e(course_label($item['course_type'])) ?></span>
            <h3><?= e($item['name']) ?></h3>
            <p class="desc"><?= e($item['description']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:30px;">
      <a href="<?= e(base_url('pages/menu.php')) ?>" class="btn btn-primary">See Full Menu</a>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section-alt">
  <div class="container" style="text-align:center;">
    <h2>Ready to Book Your Event?</h2>
    <p class="lead" style="margin:0 auto 20px;">Pick your dishes, choose a date, and we'll take care of the rest. Every order is personally reviewed by our team before payment.</p>
    <a href="<?= e(base_url('pages/menu.php')) ?>" class="btn btn-gold">Start Your Order</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
