<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (Auth::check()) {
    redirect('dashboard.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired, please try again.';
    } elseif (Auth::attempt(trim((string)($_POST['email'] ?? '')), (string)($_POST['password'] ?? ''))) {
        redirect('dashboard.php');
    } else {
        $error = 'Invalid email or password.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login | <?= e(get_setting('business_name', SITE_NAME)) ?></title>
<link rel="icon" href="<?= e(base_url('assets/images/logo.jpg')) ?>">
<link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body>
<div class="admin-login-wrap">
  <div class="admin-login-card">
    <div style="text-align:center;margin-bottom:20px;">
      <img src="<?= e(base_url('assets/images/logo.jpg')) ?>" alt="" style="width:64px;height:64px;border-radius:50%;margin:0 auto 10px;">
      <h2 style="margin:0;">Admin Login</h2>
    </div>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" required autofocus></div>
      <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
  </div>
</div>
</body>
</html>
