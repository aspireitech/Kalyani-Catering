<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Contact Us';
$pageDescription = 'Get in touch with Kalyani Catering for general inquiries, billing questions, or customer service.';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">We'd Love to Hear From You</div>
      <h1>Contact Us</h1>
    </div>

    <div class="contact-grid">
      <div>
        <div class="info-card">
          <h4>📍 Address</h4>
          <p><?= e(get_setting('business_address')) ?></p>
        </div>
        <div class="info-card">
          <h4>📞 Call / WhatsApp</h4>
          <p><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', get_setting('business_phone'))) ?>"><?= e(get_setting('business_phone')) ?></a></p>
        </div>
        <div class="info-card">
          <h4>💬 General Inquiries</h4>
          <p>Menu questions, catering availability, event planning help.<br><a href="mailto:<?= e(get_setting('email_general')) ?>"><?= e(get_setting('email_general')) ?></a></p>
        </div>
        <div class="info-card">
          <h4>🧾 Billing</h4>
          <p>Payment, invoices, discount codes and receipts.<br><a href="mailto:<?= e(get_setting('email_billing')) ?>"><?= e(get_setting('email_billing')) ?></a></p>
        </div>
        <div class="info-card">
          <h4>🤝 Customer Service</h4>
          <p>Order changes, complaints, and general support.<br><a href="mailto:<?= e(get_setting('email_customer_service')) ?>"><?= e(get_setting('email_customer_service')) ?></a></p>
        </div>
      </div>

      <div>
        <form id="contact-form" class="admin-card" method="post" action="<?= e(base_url('api/contact_submit.php')) ?>">
          <div class="field"><label for="name">Name</label><input type="text" id="name" name="name" required></div>
          <div class="field-row">
            <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" required></div>
            <div class="field"><label for="phone">Phone (optional)</label><input type="tel" id="phone" name="phone"></div>
          </div>
          <div class="field">
            <label for="department">Department</label>
            <select id="department" name="department">
              <option value="general">General Inquiry</option>
              <option value="billing">Billing</option>
              <option value="customer_service">Customer Service</option>
            </select>
          </div>
          <div class="field"><label for="message">Message</label><textarea id="message" name="message" rows="5" required></textarea></div>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <button type="submit" class="btn btn-primary btn-block">Send Message</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
