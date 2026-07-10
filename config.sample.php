<?php
/**
 * Kalyani Catering — site configuration.
 *
 * Copy this file to config.php (same folder) and fill in your real values.
 * config.php is git-ignored and must NEVER be committed with real secrets.
 *
 * On cPanel/hPanel: create the MySQL database + user in "MySQL Databases",
 * import db.sql via phpMyAdmin, then edit the DB_* constants below to match.
 */

// ---------------------------------------------------------------------
// Database (cPanel/hPanel: MySQL Databases panel gives you these values)
// ---------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'kalyani_catering');
define('DB_USER', 'kalyani');
define('DB_PASS', 'kalyani_dev_pw');

// ---------------------------------------------------------------------
// Site
// ---------------------------------------------------------------------
define('SITE_URL', 'https://www.kalyanicatering.com'); // no trailing slash
define('SITE_NAME', 'Kalyani Catering');
define('ADMIN_SESSION_NAME', 'kalyani_admin_session');

// ---------------------------------------------------------------------
// Stripe (https://dashboard.stripe.com/apikeys)
// Apple Pay works automatically inside Stripe Checkout once your domain
// is verified in Stripe Dashboard > Settings > Payment methods > Apple Pay.
// ---------------------------------------------------------------------
define('STRIPE_SECRET_KEY', 'sk_test_REPLACE_WITH_YOUR_STRIPE_SECRET_KEY');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_REPLACE_WITH_YOUR_STRIPE_PUBLISHABLE_KEY');
define('STRIPE_WEBHOOK_SECRET', 'whsec_REPLACE_WITH_YOUR_STRIPE_WEBHOOK_SECRET');

// ---------------------------------------------------------------------
// PayPal (https://developer.paypal.com/dashboard/applications) — the
// same REST app also enables the Venmo funding source automatically for
// eligible US buyers when the JS SDK button is rendered.
// Use 'sandbox' while testing, switch to 'live' when you go live.
// ---------------------------------------------------------------------
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' or 'live'
define('PAYPAL_CLIENT_ID', 'REPLACE_WITH_YOUR_PAYPAL_CLIENT_ID');
define('PAYPAL_CLIENT_SECRET', 'REPLACE_WITH_YOUR_PAYPAL_CLIENT_SECRET');

// ---------------------------------------------------------------------
// SMTP (used for order emails, contact form, newsletter announcements)
// Works with any SMTP provider: your cPanel/hPanel mailbox, Gmail app
// password, Zoho Mail, SendGrid, Mailgun, etc.
// ---------------------------------------------------------------------
define('SMTP_HOST', 'mail.yourdomain.com');
define('SMTP_PORT', 587);           // 587 = STARTTLS, 465 = implicit TLS
define('SMTP_ENCRYPTION', 'tls');   // 'tls' or 'ssl'
define('SMTP_USERNAME', 'orders@yourdomain.com');
define('SMTP_PASSWORD', 'REPLACE_WITH_YOUR_SMTP_PASSWORD');
define('SMTP_FROM_EMAIL', 'orders@yourdomain.com');
define('SMTP_FROM_NAME', 'Kalyani Catering');

// ---------------------------------------------------------------------
// Mailchimp (optional) — if set, new newsletter subscribers are also
// synced to this Audience via the API. Leave blank to skip.
// https://admin.mailchimp.com/account/api/
// ---------------------------------------------------------------------
define('MAILCHIMP_API_KEY', ''); // e.g. abc123-us21 (suffix after - is the datacenter)
define('MAILCHIMP_AUDIENCE_ID', '');

// ---------------------------------------------------------------------
// Misc
// ---------------------------------------------------------------------
define('APP_DEBUG', false); // true only on your local/dev environment
date_default_timezone_set('America/Denver');
