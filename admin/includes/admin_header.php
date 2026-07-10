<?php
/** Included after Auth::requireLogin() in every admin page. Expects $pageTitle and $activeNav. */
$activeNav = $activeNav ?? '';
$navItems = [
    'dashboard' => ['label' => 'Dashboard', 'href' => 'dashboard.php'],
    'orders' => ['label' => 'Orders', 'href' => 'orders.php'],
    'menu' => ['label' => 'Menu Items', 'href' => 'menu.php'],
    'discounts' => ['label' => 'Discount Codes', 'href' => 'discounts.php'],
    'newsletter' => ['label' => 'Newsletter', 'href' => 'newsletter.php'],
    'messages' => ['label' => 'Contact Messages', 'href' => 'messages.php'],
    'settings' => ['label' => 'Settings', 'href' => 'settings.php'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Admin') ?> | <?= e(get_setting('business_name', SITE_NAME)) ?> Admin</title>
<?php require __DIR__ . '/../../includes/favicon.php'; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body class="admin-body">
<div id="toast" class="toast" hidden></div>
<div class="admin-shell">
  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="brand">
      <strong style="color:#fff;font-family:Georgia,serif;"><?= e(get_setting('business_name', SITE_NAME)) ?></strong>
      <div style="font-size:11px;color:var(--gold-400);text-transform:uppercase;letter-spacing:.06em;">Admin Panel</div>
    </div>
    <?php foreach ($navItems as $key => $item): ?>
      <a href="<?= e($item['href']) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>"><?= e($item['label']) ?></a>
    <?php endforeach; ?>
    <a href="logout.php">Log Out</a>
    <a href="<?= e(base_url('index.php')) ?>" target="_blank">View Site &rarr;</a>
  </aside>
  <main class="admin-main">
    <div class="admin-topbar">
      <button class="btn btn-outline mobile-nav-toggle" id="admin-nav-toggle">☰ Menu</button>
      <h1 style="margin:0;font-size:22px;"><?= e($pageTitle ?? 'Admin') ?></h1>
      <span class="muted">Signed in as <?= e(Auth::name()) ?></span>
    </div>
