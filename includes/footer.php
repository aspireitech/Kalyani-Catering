<?php $flash = flash_get('success'); $flashError = flash_get('error'); ?>
</main>

<div id="toast" class="toast" hidden></div>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col">
      <img src="<?= e(base_url('assets/images/logo.jpg')) ?>" alt="<?= e(get_setting('business_name', SITE_NAME)) ?>" class="footer-logo">
      <p><?= e(get_setting('business_tagline', '')) ?></p>
      <div class="social-links">
        <?php if ($fb = get_setting('facebook_url')): ?><a href="<?= e($fb) ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
        <?php if ($ig = get_setting('instagram_url')): ?><a href="<?= e($ig) ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
      </div>
    </div>

    <div class="footer-col">
      <h4>Get In Touch</h4>
      <p><?= e(get_setting('business_address')) ?></p>
      <p>Call/WhatsApp: <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', get_setting('business_phone'))) ?>"><?= e(get_setting('business_phone')) ?></a></p>
      <p><a href="<?= e(base_url('pages/contact.php')) ?>">Contact form &rarr;</a></p>
    </div>

    <div class="footer-col">
      <h4>Order Delivery</h4>
      <p>Prefer delivery apps? Find us here too:</p>
      <div class="delivery-links">
        <?php if ($ue = get_setting('ubereats_url')): ?><a class="delivery-btn ubereats" href="<?= e($ue) ?>" target="_blank" rel="noopener">Uber Eats</a><?php endif; ?>
        <?php if ($dd = get_setting('doordash_url')): ?><a class="delivery-btn doordash" href="<?= e($dd) ?>" target="_blank" rel="noopener">DoorDash</a><?php endif; ?>
        <?php if (!get_setting('ubereats_url') && !get_setting('doordash_url')): ?><p class="muted">Order directly on our site for the best prices.</p><?php endif; ?>
      </div>
    </div>

    <div class="footer-col">
      <h4>New Dish Alerts</h4>
      <p>Be the first to know when we add a new dish or run a special offer.</p>
      <form id="newsletter-form" class="newsletter-form">
        <input type="email" name="email" placeholder="you@example.com" required aria-label="Email address">
        <button type="submit">Subscribe</button>
      </form>
      <p class="form-msg" id="newsletter-msg" aria-live="polite"></p>
    </div>
  </div>

  <div class="footer-bottom container">
    <p>&copy; <?= date('Y') ?> <?= e(get_setting('business_name', SITE_NAME)) ?>. All rights reserved.</p>
  </div>
</footer>

<script>
  window.KC = {
    baseUrl: <?= json_encode(rtrim(SITE_URL, '/') . '/') ?>,
    csrfToken: <?= json_encode(csrf_token()) ?>,
    flashSuccess: <?= json_encode($flash) ?>,
    flashError: <?= json_encode($flashError) ?>
  };
</script>
<script src="<?= e(base_url('assets/js/main.js')) ?>"></script>
</body>
</html>
