/* Kalyani Catering — single vanilla-JS file, no framework/build step. */
(function () {
  'use strict';

  var KC = window.KC || {};

  function toast(message, isError) {
    var el = document.getElementById('toast');
    if (!el) return;
    el.textContent = message;
    el.classList.toggle('err', !!isError);
    el.hidden = false;
    requestAnimationFrame(function () { el.classList.add('show'); });
    clearTimeout(el._t);
    el._t = setTimeout(function () {
      el.classList.remove('show');
      setTimeout(function () { el.hidden = true; }, 250);
    }, 3200);
  }
  KC.toast = toast;

  document.addEventListener('DOMContentLoaded', function () {
    if (KC.flashSuccess) toast(KC.flashSuccess, false);
    if (KC.flashError) toast(KC.flashError, true);

    initNav();
    initLiveSearch();
    initNewsletterForm();
    initMenuGrid();
    initCartPage();
    initCheckoutPage();
    initPayPage();
    initAdminSidebar();
    initDiscountShare();
    initImagePreview();
  });

  // ---------------- Mobile nav ----------------
  function initNav() {
    var toggle = document.getElementById('nav-toggle');
    var nav = document.getElementById('main-nav');
    if (!toggle || !nav) return;
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  function initAdminSidebar() {
    var toggle = document.getElementById('admin-nav-toggle');
    var sidebar = document.getElementById('admin-sidebar');
    if (!toggle || !sidebar) return;
    toggle.addEventListener('click', function () { sidebar.classList.toggle('open'); });
  }

  // ---------------- Live search (fires after 2 chars) ----------------
  function initLiveSearch() {
    var input = document.getElementById('live-search');
    var results = document.getElementById('live-search-results');
    if (!input || !results) return;
    var timer = null;

    input.addEventListener('input', function () {
      var q = input.value.trim();
      clearTimeout(timer);
      if (q.length < 2) {
        results.hidden = true;
        results.innerHTML = '';
        return;
      }
      timer = setTimeout(function () { runSearch(q); }, 180);
    });

    document.addEventListener('click', function (e) {
      if (!results.contains(e.target) && e.target !== input) {
        results.hidden = true;
      }
    });

    function runSearch(q) {
      fetch(KC.baseUrl + 'api/menu_search.php?q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          renderResults(data.items || []);
        })
        .catch(function () { results.hidden = true; });
    }

    function renderResults(items) {
      if (!items.length) {
        results.innerHTML = '<div class="search-empty">No dishes found. Try another search.</div>';
        results.hidden = false;
        return;
      }
      results.innerHTML = items.map(function (item) {
        var img = item.image
          ? '<img src="' + KC.baseUrl + 'uploads/menu/' + item.image + '" alt="">'
          : '';
        return '<a class="search-result-item" href="' + KC.baseUrl + 'pages/menu.php#' + item.slug + '">' +
          '<span class="search-result-thumb">' + img + '</span>' +
          '<span class="search-result-info"><strong>' + escapeHtml(item.name) + '</strong>' +
          '<span>' + escapeHtml(item.course_label) + (item.price ? ' &middot; $' + item.price : '') + '</span></span>' +
          '</a>';
      }).join('');
      results.hidden = false;
    }
  }

  function escapeHtml(str) {
    var d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  // ---------------- Newsletter signup (footer) ----------------
  function initNewsletterForm() {
    var form = document.getElementById('newsletter-form');
    if (!form) return;
    var msg = document.getElementById('newsletter-msg');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var email = form.email.value.trim();
      msg.textContent = 'Subscribing...';
      msg.className = 'form-msg';
      fetch(KC.baseUrl + 'api/newsletter_subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, csrf_token: KC.csrfToken })
      }).then(function (r) { return r.json(); }).then(function (data) {
        msg.textContent = data.message || (data.ok ? 'Subscribed!' : 'Something went wrong.');
        msg.className = 'form-msg ' + (data.ok ? 'ok' : 'err');
        if (data.ok) form.reset();
      }).catch(function () {
        msg.textContent = 'Network error, please try again.';
        msg.className = 'form-msg err';
      });
    });
  }

  // ---------------- Menu grid: category filters, tier select, add to cart ----------------
  function initMenuGrid() {
    var grid = document.getElementById('menu-grid');
    if (!grid) return;

    var chips = document.querySelectorAll('.filter-chip');
    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        var group = chip.dataset.group;
        document.querySelectorAll('.filter-chip[data-group="' + group + '"]').forEach(function (c) {
          c.classList.remove('active');
        });
        chip.classList.add('active');
        applyFilters();
      });
    });

    function applyFilters() {
      var groups = {};
      document.querySelectorAll('.filter-chip[data-group]').forEach(function (c) {
        groups[c.dataset.group] = true;
      });
      var activeValues = {};
      Object.keys(groups).forEach(function (g) {
        var active = document.querySelector('.filter-chip[data-group="' + g + '"].active');
        activeValues[g] = active ? active.dataset.value : 'all';
      });
      var visibleCount = 0;
      grid.querySelectorAll('.menu-card').forEach(function (card) {
        var show = Object.keys(activeValues).every(function (g) {
          return activeValues[g] === 'all' || card.dataset[g] === activeValues[g];
        });
        card.style.display = show ? '' : 'none';
        if (show) visibleCount++;
      });
      var empty = document.getElementById('menu-empty');
      if (empty) empty.hidden = visibleCount !== 0;
    }

    grid.querySelectorAll('.menu-card').forEach(function (card) {
      var tierBtns = card.querySelectorAll('.tier-btn');
      tierBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          tierBtns.forEach(function (b) { b.classList.remove('active'); });
          btn.classList.add('active');
        });
      });

      var qtyInput = card.querySelector('.qty-input');
      var minus = card.querySelector('.qty-minus');
      var plus = card.querySelector('.qty-plus');
      if (minus && plus && qtyInput) {
        minus.addEventListener('click', function () {
          qtyInput.value = Math.max(1, parseInt(qtyInput.value || '1', 10) - 1);
        });
        plus.addEventListener('click', function () {
          qtyInput.value = Math.min(99, parseInt(qtyInput.value || '1', 10) + 1);
        });
      }

      var addBtn = card.querySelector('.add-to-cart-btn');
      if (addBtn) {
        addBtn.addEventListener('click', function () {
          var activeTier = card.querySelector('.tier-btn.active');
          if (tierBtns.length && !activeTier) {
            toast('Please choose a size first.', true);
            return;
          }
          var payload = {
            menu_item_id: card.dataset.id,
            tier_name: activeTier ? activeTier.dataset.tier : '',
            qty: qtyInput ? parseInt(qtyInput.value || '1', 10) : 1,
            csrf_token: KC.csrfToken
          };
          addBtn.disabled = true;
          fetch(KC.baseUrl + 'api/cart_add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          }).then(function (r) { return r.json(); }).then(function (data) {
            addBtn.disabled = false;
            if (data.ok) {
              updateCartBadge(data.cart_count);
              toast(card.dataset.name + ' added to cart');
            } else {
              toast(data.message || 'Could not add to cart', true);
            }
          }).catch(function () {
            addBtn.disabled = false;
            toast('Network error, please try again.', true);
          });
        });
      }
    });

    if (location.hash) {
      var target = document.querySelector('[data-slug="' + location.hash.slice(1) + '"]');
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function updateCartBadge(count) {
    var badge = document.getElementById('cart-badge');
    if (badge) badge.textContent = count;
  }

  // ---------------- Cart page ----------------
  function initCartPage() {
    var list = document.getElementById('cart-list');
    if (!list) return;

    list.addEventListener('click', function (e) {
      var key = e.target.dataset.key;
      if (!key) return;
      if (e.target.classList.contains('cart-item-remove')) {
        cartUpdate(key, 0);
      } else if (e.target.classList.contains('cart-qty-minus') || e.target.classList.contains('cart-qty-plus')) {
        var input = list.querySelector('input[data-key="' + key + '"]');
        var val = parseInt(input.value || '1', 10);
        val = e.target.classList.contains('cart-qty-plus') ? val + 1 : Math.max(0, val - 1);
        input.value = val;
        cartUpdate(key, val);
      }
    });

    list.addEventListener('change', function (e) {
      if (e.target.classList.contains('cart-qty-input')) {
        cartUpdate(e.target.dataset.key, Math.max(0, parseInt(e.target.value || '0', 10)));
      }
    });

    function cartUpdate(key, qty) {
      fetch(KC.baseUrl + 'api/cart_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key: key, qty: qty, csrf_token: KC.csrfToken })
      }).then(function (r) { return r.json(); }).then(function () {
        location.reload();
      });
    }
  }

  // ---------------- Checkout page ----------------
  function initCheckoutPage() {
    var form = document.getElementById('checkout-form');
    if (!form) return;

    var dateInput = form.querySelector('#event_date');
    if (dateInput) {
      var min = new Date();
      min.setDate(min.getDate() + parseInt(dateInput.dataset.leadDays || '3', 10));
      dateInput.min = min.toISOString().slice(0, 10);
    }

    form.querySelectorAll('input[name="fulfillment_type"]').forEach(function (radio) {
      radio.addEventListener('change', toggleAddress);
      radio.closest('.radio-card').addEventListener('click', function () {
        form.querySelectorAll('.radio-card').forEach(function (c) { c.classList.remove('active'); });
        radio.closest('.radio-card').classList.add('active');
      });
    });
    function toggleAddress() {
      var addressField = document.getElementById('address-field');
      var isDelivery = form.querySelector('input[name="fulfillment_type"]:checked').value === 'delivery';
      if (addressField) addressField.hidden = !isDelivery;
    }
    toggleAddress();

    var discountBtn = document.getElementById('apply-discount-btn');
    var discountInput = document.getElementById('discount_code');
    if (discountBtn) {
      discountBtn.addEventListener('click', function () {
        var code = document.getElementById('discount_code').value.trim();
        var msg = document.getElementById('discount-msg');
        if (!code) return;
        fetch(KC.baseUrl + 'api/apply_discount.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ code: code, csrf_token: KC.csrfToken })
        }).then(function (r) { return r.json(); }).then(function (data) {
          msg.textContent = data.message;
          msg.className = 'form-msg ' + (data.ok ? 'ok' : 'err');
          if (data.ok) {
            document.getElementById('summary-discount-row').hidden = false;
            document.getElementById('summary-discount-amount').textContent = '-$' + data.discount_amount;
            document.getElementById('summary-total').textContent = '$' + data.new_total;
            document.getElementById('applied_discount_code').value = code;
          }
        });
      });
      if (discountInput && discountInput.value.trim()) {
        discountBtn.click();
      }
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var submitBtn = form.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Submitting...';
      var formData = new FormData(form);
      var payload = {};
      formData.forEach(function (v, k) { payload[k] = v; });
      payload.csrf_token = KC.csrfToken;

      fetch(KC.baseUrl + 'api/submit_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).then(function (r) { return r.json(); }).then(function (data) {
        if (data.ok) {
          window.location.href = KC.baseUrl + 'pages/order-confirmation.php?order=' + data.order_number;
        } else {
          toast(data.message || 'Please check the form and try again.', true);
          submitBtn.disabled = false;
          submitBtn.textContent = 'Submit Order Request';
        }
      }).catch(function () {
        toast('Network error, please try again.', true);
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Order Request';
      });
    });
  }

  // ---------------- Payment page (Stripe + PayPal) ----------------
  function initPayPage() {
    var payRoot = document.getElementById('pay-root');
    if (!payRoot) return;
    var token = payRoot.dataset.token;

    var stripeBtn = document.getElementById('pay-stripe-btn');
    var stripeLabel = document.getElementById('pay-stripe-label');
    if (stripeBtn) {
      stripeBtn.addEventListener('click', function () {
        stripeBtn.disabled = true;
        if (stripeLabel) stripeLabel.textContent = 'Redirecting to secure checkout...';
        fetch(KC.baseUrl + 'api/stripe_create_session.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ token: token, csrf_token: KC.csrfToken })
        }).then(function (r) { return r.json(); }).then(function (data) {
          if (data.ok && data.url) {
            window.location.href = data.url;
          } else {
            toast(data.message || 'Could not start Stripe checkout.', true);
            stripeBtn.disabled = false;
            if (stripeLabel) stripeLabel.textContent = '💳 Pay with Credit / Debit Card';
          }
        });
      });
    }

    // Shared PayPal Orders API flow, reused for both the PayPal-branded and
    // Venmo-branded buttons (Venmo payments are processed through PayPal).
    function paypalOrderHandlers() {
      return {
        createOrder: function () {
          return fetch(KC.baseUrl + 'api/paypal_create_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: token, csrf_token: KC.csrfToken })
          }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data.ok) throw new Error(data.message || 'Could not start checkout.');
            return data.paypal_order_id;
          });
        },
        onApprove: function (data) {
          return fetch(KC.baseUrl + 'api/paypal_capture_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: token, paypal_order_id: data.orderID, csrf_token: KC.csrfToken })
          }).then(function (r) { return r.json(); }).then(function (result) {
            if (result.ok) {
              window.location.href = KC.baseUrl + 'pages/order-confirmation.php?order=' + result.order_number + '&paid=1';
            } else {
              toast(result.message || 'Payment could not be captured.', true);
            }
          });
        },
        onError: function () {
          toast('Checkout failed. Please try again.', true);
        }
      };
    }

    if (window.paypal) {
      var renderedAny = false;

      var paypalButtons = window.paypal.Buttons(Object.assign({
        fundingSource: window.paypal.FUNDING.PAYPAL,
        style: { layout: 'horizontal', color: 'gold', label: 'paypal', height: 45, tagline: false }
      }, paypalOrderHandlers()));
      var paypalContainer = document.getElementById('paypal-button-container');
      if (paypalContainer) {
        if (paypalButtons.isEligible()) {
          paypalButtons.render('#paypal-button-container');
          renderedAny = true;
        } else {
          paypalContainer.remove();
        }
      }

      var venmoButtons = window.paypal.Buttons(Object.assign({
        fundingSource: window.paypal.FUNDING.VENMO,
        style: { height: 45 }
      }, paypalOrderHandlers()));
      var venmoContainer = document.getElementById('venmo-button-container');
      if (venmoContainer) {
        if (venmoButtons.isEligible()) {
          venmoButtons.render('#venmo-button-container');
          renderedAny = true;
        } else {
          venmoContainer.remove();
        }
      }

      var paypalSection = document.getElementById('paypal-section');
      if (paypalSection) paypalSection.hidden = !renderedAny;
    }
  }

  // ---------------- Admin: discount code share box ----------------
  function initDiscountShare() {
    document.querySelectorAll('.copy-share-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var text = btn.previousElementSibling.textContent;
        navigator.clipboard.writeText(text).then(function () {
          toast('Copied to clipboard!');
        });
      });
    });
  }

  // ---------------- Admin: image upload preview ----------------
  function initImagePreview() {
    var input = document.getElementById('image-input');
    var preview = document.getElementById('image-preview');
    if (!input || !preview) return;
    input.addEventListener('change', function () {
      if (input.files && input.files[0]) {
        preview.src = URL.createObjectURL(input.files[0]);
      }
    });
  }
})();
