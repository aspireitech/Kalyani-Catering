<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$db = Database::get();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired, please try again.');
        redirect('settings.php');
    }
    $form = $_POST['form'] ?? '';

    if ($form === 'general') {
        $fields = [
            'business_name', 'business_tagline', 'business_phone', 'business_whatsapp', 'business_address',
            'email_general', 'email_billing', 'email_customer_service', 'ubereats_url', 'doordash_url',
            'order_lead_days', 'facebook_url', 'instagram_url',
        ];
        foreach ($fields as $field) {
            update_setting($field, trim((string)($_POST[$field] ?? '')));
        }
        flash_set('success', 'Settings updated.');
        redirect('settings.php');
    }

    if ($form === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $stmt = $db->prepare('SELECT * FROM admin_users WHERE id = ?');
        $stmt->execute([Auth::id()]);
        $me = $stmt->fetch();

        if (!$me || !password_verify($current, $me['password_hash'])) {
            flash_set('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash_set('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $confirm) {
            flash_set('error', 'New passwords do not match.');
        } else {
            $db->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), Auth::id()]);
            flash_set('success', 'Password updated.');
        }
        redirect('settings.php');
    }
}

$pageTitle = 'Settings';
$activeNav = 'settings';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card">
  <h3 style="margin-top:0;">Business Info</h3>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="form" value="general">
    <div class="field-row">
      <div class="field"><label>Business Name</label><input type="text" name="business_name" value="<?= e(get_setting('business_name')) ?>"></div>
      <div class="field"><label>Tagline</label><input type="text" name="business_tagline" value="<?= e(get_setting('business_tagline')) ?>"></div>
    </div>
    <div class="field"><label>Address</label><input type="text" name="business_address" value="<?= e(get_setting('business_address')) ?>"></div>
    <div class="field-row">
      <div class="field"><label>Phone</label><input type="text" name="business_phone" value="<?= e(get_setting('business_phone')) ?>"></div>
      <div class="field"><label>WhatsApp</label><input type="text" name="business_whatsapp" value="<?= e(get_setting('business_whatsapp')) ?>"></div>
    </div>
    <div class="field-row">
      <div class="field"><label>Facebook URL</label><input type="url" name="facebook_url" value="<?= e(get_setting('facebook_url')) ?>"></div>
      <div class="field"><label>Instagram URL</label><input type="url" name="instagram_url" value="<?= e(get_setting('instagram_url')) ?>"></div>
    </div>

    <fieldset>
      <legend>Department Emails</legend>
      <div class="field-row">
        <div class="field"><label>General</label><input type="email" name="email_general" value="<?= e(get_setting('email_general')) ?>"></div>
        <div class="field"><label>Billing</label><input type="email" name="email_billing" value="<?= e(get_setting('email_billing')) ?>"></div>
        <div class="field"><label>Customer Service</label><input type="email" name="email_customer_service" value="<?= e(get_setting('email_customer_service')) ?>"></div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Delivery Apps (optional)</legend>
      <div class="field-row">
        <div class="field"><label>Uber Eats Store URL</label><input type="url" name="ubereats_url" value="<?= e(get_setting('ubereats_url')) ?>" placeholder="https://ubereats.com/..."></div>
        <div class="field"><label>DoorDash Store URL</label><input type="url" name="doordash_url" value="<?= e(get_setting('doordash_url')) ?>" placeholder="https://doordash.com/..."></div>
      </div>
    </fieldset>

    <div class="field" style="max-width:260px;">
      <label>Order Lead Time (days)</label>
      <input type="number" min="0" name="order_lead_days" value="<?= e(get_setting('order_lead_days', '3')) ?>">
    </div>

    <button type="submit" class="btn btn-primary">Save Settings</button>
  </form>
</div>

<div class="admin-card">
  <h3 style="margin-top:0;">Change Password</h3>
  <form method="post" style="max-width:400px;">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="form" value="password">
    <div class="field"><label>Current Password</label><input type="password" name="current_password" required></div>
    <div class="field"><label>New Password</label><input type="password" name="new_password" required minlength="8"></div>
    <div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" required minlength="8"></div>
    <button type="submit" class="btn btn-primary">Update Password</button>
  </form>
</div>

<div class="admin-card">
  <h3 style="margin-top:0;">Payment &amp; Email Configuration</h3>
  <p class="muted">Stripe, PayPal, and SMTP credentials are set in <code>config.php</code> on the server for security, not here. See the README for setup instructions.</p>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
