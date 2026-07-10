<?php
/**
 * Shared site header. Expects optional $pageTitle and $pageDescription to be
 * set before including. Works from both site root and /pages/ /admin/.
 */
$pageTitle = $pageTitle ?? get_setting('business_name', SITE_NAME);
$pageDescription = $pageDescription ?? 'Authentic homemade Indian catering for weddings, parties, birthdays and corporate events.';
$cartCount = Cart::count();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?> | <?= e(get_setting('business_name', SITE_NAME)) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<meta name="theme-color" content="#0e2f22">
<link rel="icon" href="<?= e(base_url('assets/images/logo.jpg')) ?>">
<link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header" id="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?= e(base_url('index.php')) ?>">
      <img src="<?= e(base_url('assets/images/logo.jpg')) ?>" alt="<?= e(get_setting('business_name', SITE_NAME)) ?>" class="brand-logo">
      <span class="brand-text">
        <strong><?= e(get_setting('business_name', SITE_NAME)) ?></strong>
        <small><?= e(get_setting('business_tagline', '')) ?></small>
      </span>
    </a>

    <div class="header-search">
      <input type="search" id="live-search" placeholder="Search the menu (try 'paneer')..." autocomplete="off" aria-label="Search menu">
      <div id="live-search-results" class="search-results" hidden></div>
    </div>

    <nav class="main-nav" id="main-nav">
      <a href="<?= e(base_url('index.php')) ?>">Home</a>
      <a href="<?= e(base_url('pages/menu.php')) ?>">Menu</a>
      <a href="<?= e(base_url('pages/contact.php')) ?>">Contact</a>
      <a href="<?= e(base_url('pages/checkout.php')) ?>" class="nav-cart">
        Cart <span class="cart-badge" id="cart-badge"><?= (int)$cartCount ?></span>
      </a>
    </nav>

    <button class="nav-toggle" id="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<main id="main">
