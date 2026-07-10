<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$token = $_GET['token'] ?? '';
$done = false;
if ($token) {
    $stmt = Database::get()->prepare('UPDATE subscribers SET is_active = 0 WHERE unsubscribe_token = ?');
    $stmt->execute([$token]);
    $done = $stmt->rowCount() > 0;
}

$pageTitle = 'Unsubscribe';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
  <div class="container" style="max-width:560px;text-align:center;">
    <?php if ($done): ?>
      <h1>You're Unsubscribed</h1>
      <p>You won't receive new dish announcements from us anymore. You're always welcome back!</p>
    <?php else: ?>
      <h1>Link Not Valid</h1>
      <p>We couldn't find that subscription. If you keep receiving emails you don't want, please contact us.</p>
    <?php endif; ?>
    <a href="<?= e(base_url('index.php')) ?>" class="btn btn-primary">Back to Home</a>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
