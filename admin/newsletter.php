<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/Mailer.php';
Auth::requireLogin();

$db = Database::get();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired, please try again.');
        redirect('newsletter.php');
    }

    $menuItemId = (int)($_POST['menu_item_id'] ?? 0);
    $subject = trim((string)($_POST['subject'] ?? ''));
    $bodyText = trim((string)($_POST['body'] ?? ''));

    if ($subject === '' || $bodyText === '') {
        flash_set('error', 'Please fill in a subject and message.');
        redirect('newsletter.php');
    }

    $subscribers = $db->query('SELECT email, unsubscribe_token FROM subscribers WHERE is_active = 1')->fetchAll();
    $bodyHtml = '<p>' . nl2br(e($bodyText)) . '</p>';

    foreach ($subscribers as $sub) {
        Mailer::newsletterCampaign($sub['email'], $sub['unsubscribe_token'], $subject, $bodyHtml);
    }

    $db->prepare('INSERT INTO newsletter_campaigns (subject, body, menu_item_id, recipient_count) VALUES (?,?,?,?)')
        ->execute([$subject, $bodyText, $menuItemId ?: null, count($subscribers)]);

    flash_set('success', 'Announcement sent to ' . count($subscribers) . ' subscriber(s)!');
    redirect('newsletter.php');
}

$subscribers = $db->query('SELECT * FROM subscribers ORDER BY subscribed_at DESC')->fetchAll();
$newDishes = $db->query("SELECT id, name, description FROM menu_items WHERE is_new = 1 AND is_active = 1")->fetchAll();
$campaigns = $db->query('SELECT * FROM newsletter_campaigns ORDER BY sent_at DESC LIMIT 10')->fetchAll();

$pageTitle = 'Newsletter';
$activeNav = 'newsletter';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= count(array_filter($subscribers, fn($s) => $s['is_active'])) ?></div><div class="label">Active Subscribers</div></div>
  <div class="stat-card"><div class="num"><?= count($newDishes) ?></div><div class="label">Dishes Marked "New"</div></div>
</div>

<div class="admin-card">
  <h3 style="margin-top:0;">Send a New Dish Announcement</h3>
  <p class="muted">Emails every active subscriber. Pick a dish marked "New" (set on the Menu Items page) to pre-fill the message, or write your own.</p>
  <form method="post" id="newsletter-send-form">
    <div class="field">
      <label for="menu_item_id">Announce a Dish (optional)</label>
      <select id="menu_item_id" name="menu_item_id" onchange="var opts=JSON.parse(this.selectedOptions[0].dataset.info||'null'); if(opts){document.getElementById('subject').value='New Dish: '+opts.name+'!'; document.getElementById('body').value='We just added '+opts.name+' to our menu! '+opts.description;}">
        <option value="">— Write a custom message —</option>
        <?php foreach ($newDishes as $d): ?>
          <option value="<?= (int)$d['id'] ?>" data-info='<?= json_encode(['name' => $d['name'], 'description' => $d['description']]) ?>'><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label for="subject">Subject</label><input type="text" id="subject" name="subject" required></div>
    <div class="field"><label for="body">Message</label><textarea id="body" name="body" rows="4" required></textarea></div>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <button type="submit" class="btn btn-primary" onclick="return confirm('Send this announcement to all active subscribers now?');">Send to All Subscribers</button>
  </form>
</div>

<div class="admin-card">
  <h3 style="margin-top:0;">Sent Campaigns</h3>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Subject</th><th>Recipients</th><th>Sent</th></tr></thead>
      <tbody>
        <?php foreach ($campaigns as $c): ?>
          <tr><td><?= e($c['subject']) ?></td><td><?= (int)$c['recipient_count'] ?></td><td><?= e(date('M j, Y g:ia', strtotime($c['sent_at']))) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$campaigns): ?><tr><td colspan="3" class="muted">No campaigns sent yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="admin-card">
  <h3 style="margin-top:0;">Subscribers (<?= count($subscribers) ?>)</h3>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Email</th><th>Status</th><th>Subscribed</th></tr></thead>
      <tbody>
        <?php foreach ($subscribers as $s): ?>
          <tr><td><?= e($s['email']) ?></td><td><?= $s['is_active'] ? '<span class="pill pill-paid">Active</span>' : '<span class="pill pill-cancelled">Unsubscribed</span>' ?></td><td><?= e(date('M j, Y', strtotime($s['subscribed_at']))) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$subscribers): ?><tr><td colspan="3" class="muted">No subscribers yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
