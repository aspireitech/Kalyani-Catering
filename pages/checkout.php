<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Your Cart & Checkout';
$cartItems = Cart::all();
$subtotal = Cart::subtotal();
$leadDays = (int)get_setting('order_lead_days', '3');
$pendingPromo = $_SESSION['pending_promo'] ?? '';
unset($_SESSION['pending_promo']);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-head" style="margin-bottom:20px;">
      <div class="eyebrow">Cart</div>
      <h1>Review &amp; Request Your Order</h1>
      <p>Submit your request below — our team will review it and send you a secure payment link once approved.</p>
    </div>

    <?php if (empty($cartItems)): ?>
      <div class="empty-cart">
        <h3>Your cart is empty</h3>
        <p>Add some delicious dishes to get started.</p>
        <a href="<?= e(base_url('pages/menu.php')) ?>" class="btn btn-primary">Browse the Menu</a>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <div>
          <div class="admin-card">
            <h3 style="margin-top:0;">Items (<?= Cart::count() ?>)</h3>
            <div id="cart-list">
              <?php foreach ($cartItems as $key => $item): ?>
                <div class="cart-item">
                  <div class="cart-item-thumb"></div>
                  <div class="cart-item-info">
                    <strong><?= e($item['name']) ?><?= $item['tier_name'] ? ' — ' . e($item['tier_name']) : '' ?></strong>
                    <span><?= money((float)$item['unit_price']) ?> each</span>
                  </div>
                  <div class="qty-control">
                    <button type="button" class="cart-qty-minus" data-key="<?= e($key) ?>">&minus;</button>
                    <input type="number" class="cart-qty-input" data-key="<?= e($key) ?>" value="<?= (int)$item['qty'] ?>" min="0" max="99">
                    <button type="button" class="cart-qty-plus" data-key="<?= e($key) ?>">&plus;</button>
                  </div>
                  <strong style="min-width:70px;text-align:right;"><?= money((float)$item['unit_price'] * (int)$item['qty']) ?></strong>
                  <button type="button" class="cart-item-remove" data-key="<?= e($key) ?>">Remove</button>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <form id="checkout-form" class="admin-card">
            <fieldset>
              <legend>Your Details</legend>
              <div class="field-row">
                <div class="field"><label for="customer_name">Full Name</label><input type="text" id="customer_name" name="customer_name" required></div>
                <div class="field"><label for="customer_phone">Phone</label><input type="tel" id="customer_phone" name="customer_phone" required></div>
              </div>
              <div class="field"><label for="customer_email">Email</label><input type="email" id="customer_email" name="customer_email" required></div>
            </fieldset>

            <fieldset>
              <legend>Delivery or Pickup</legend>
              <div class="radio-group">
                <label class="radio-card active">
                  <input type="radio" name="fulfillment_type" value="pickup" checked> Pickup
                </label>
                <label class="radio-card">
                  <input type="radio" name="fulfillment_type" value="delivery"> Delivery
                </label>
              </div>
              <div class="field" id="address-field" style="margin-top:14px;" hidden>
                <label for="address">Delivery Address</label>
                <textarea id="address" name="address" rows="2"></textarea>
              </div>
            </fieldset>

            <fieldset>
              <legend>Event Date</legend>
              <div class="field-row">
                <div class="field">
                  <label for="event_date">Date</label>
                  <input type="date" id="event_date" name="event_date" data-lead-days="<?= $leadDays ?>" required>
                </div>
                <div class="field">
                  <label for="event_time">Time (optional)</label>
                  <input type="time" id="event_time" name="event_time">
                </div>
              </div>
              <p class="muted">Please allow at least <?= $leadDays ?> days' notice so we can prepare fresh.</p>
            </fieldset>

            <fieldset>
              <legend>Notes for Our Team</legend>
              <textarea name="notes" rows="3" placeholder="Allergies, spice level, setup instructions, etc."></textarea>
            </fieldset>

            <input type="hidden" id="applied_discount_code" name="discount_code" value="">
            <button type="submit" class="btn btn-primary btn-block">Submit Order Request</button>
          </form>
        </div>

        <div>
          <div class="summary-card">
            <h3 style="margin-top:0;">Order Summary</h3>
            <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
            <div class="summary-row" id="summary-discount-row" hidden><span>Discount</span><span id="summary-discount-amount">-$0.00</span></div>
            <div class="discount-row">
              <input type="text" id="discount_code" placeholder="Discount code" value="<?= e($pendingPromo) ?>">
              <button type="button" id="apply-discount-btn" class="btn btn-outline btn-sm">Apply</button>
            </div>
            <p class="form-msg" id="discount-msg"></p>
            <div class="summary-row total"><span>Total</span><span id="summary-total"><?= money($subtotal) ?></span></div>
            <p class="muted" style="margin-top:14px;">No payment is taken yet. We'll email you a secure payment link once your order is approved.</p>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
