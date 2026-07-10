# Kalyani Catering — Website

A lightweight, mobile-first catering website built in plain PHP + MySQL
(no Node/build step, no Composer dependencies required) so it deploys
directly onto shared hosting control panels like **cPanel** or **hPanel**
(Hostinger, GoDaddy, Bluehost, etc.) via File Manager or FTP.

## What's included

- **Hero homepage**, full **menu** with category filters (cuisine, veg/non-veg,
  starters/mains/rice/dal/desserts/drinks/sides) and live search-as-you-type
  (fires after 2 characters, in the header on every page).
- **Cart & checkout** with tray-size selection (Small/Medium/Full), a date
  picker for future event dates (configurable minimum lead time), delivery
  or pickup, and discount codes.
- **Manual admin approval workflow**: customers submit an order request —
  no payment is taken yet. An admin reviews it in the dashboard and either
  approves (customer gets an emailed secure payment link) or rejects it.
- **Payments**: Stripe Checkout (card + Apple Pay) and PayPal Orders API
  (PayPal balance/card + Venmo for eligible buyers) — both implemented with
  plain cURL REST calls, no SDKs to install.
- **Admin panel**: menu CRUD (name, description, cuisine, diet type, course,
  tray/unit pricing, quantity, discount %, photo upload), order review/
  approval/rejection, discount code creation with shareable promo links,
  newsletter (announce new dishes to subscribers), contact message inbox,
  and site settings.
- **Contact form** routed to General / Billing / Customer Service.
- **Newsletter**: footer signup, optional Mailchimp Audience sync, and a
  built-in "announce a new dish" broadcast tool with unsubscribe links.
- Optional Uber Eats / DoorDash links in the footer.
- Single lightweight stylesheet and a single vanilla-JS file — no
  frameworks, no webfonts, fast on mobile connections.

## Requirements

- PHP 8.0+ with `pdo_mysql`, `curl`, and `openssl` extensions (all standard
  on cPanel/hPanel PHP hosting — no Composer or SSH access needed).
- MySQL 5.7+ / MariaDB 10.3+.
- Apache with `mod_rewrite` and `.htaccess` support (default on shared hosting).

## Deploying to cPanel / hPanel

1. **Create the database.** In cPanel/hPanel, open *MySQL Databases*,
   create a database and a database user, and add the user to the database
   with all privileges. Note the DB name, username, password and host
   (usually `localhost`).
2. **Import the schema.** Open *phpMyAdmin*, select your new database, go
   to *Import*, and upload `db.sql` from this project.
3. **Upload the files.** Upload the entire project to `public_html` (or a
   subfolder if this isn't the primary domain) via File Manager or FTP.
4. **Configure the app.** Copy `config.sample.php` to `config.php` (same
   folder) and fill in:
   - `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` from step 1
   - `SITE_URL` — your live domain, no trailing slash
   - Stripe, PayPal, and SMTP credentials (see below)
5. **Set folder permissions.** Ensure `uploads/menu/` is writable by PHP
   (usually `755`, sometimes `775` depending on host).
6. **Log in to the admin panel** at `/admin/login.php` using:
   - Email: `admin@kalyanicatering.com`
   - Password: `Kalyani@123`
   **Change this password immediately** under Admin → Settings.

## Stripe setup

1. Get your API keys from the [Stripe Dashboard](https://dashboard.stripe.com/apikeys)
   and put them in `config.php` (`STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`).
2. Add a webhook endpoint pointing at `https://yourdomain.com/api/stripe_webhook.php`,
   subscribed to the `checkout.session.completed` event, and copy the
   signing secret into `STRIPE_WEBHOOK_SECRET`. (The site also confirms
   payment on the customer's browser redirect as a fast path, but the
   webhook is the durable source of truth if they close the tab early.)
3. **Apple Pay** appears automatically inside Stripe Checkout once you
   verify your domain in Stripe Dashboard → Settings → Payment methods →
   Apple Pay. No code changes needed.

## PayPal setup

1. Create a REST app at the [PayPal Developer Dashboard](https://developer.paypal.com/dashboard/applications)
   and copy the Client ID and Secret into `config.php`.
2. Set `PAYPAL_MODE` to `sandbox` while testing and `live` when you go live.
3. **Venmo** is offered automatically by the PayPal buttons for eligible US
   buyers — no extra configuration required.

## Email (SMTP) setup

Order confirmations, approval/payment emails, contact form notifications,
and newsletter announcements are sent through a built-in SMTP client (no
Composer/PHPMailer needed). Fill in `SMTP_HOST`, `SMTP_PORT`,
`SMTP_ENCRYPTION`, `SMTP_USERNAME`, `SMTP_PASSWORD` in `config.php` with
any mailbox — your cPanel/hPanel email account, Gmail (with an App
Password), Zoho, SendGrid, Mailgun, etc. If `SMTP_HOST` is left blank the
site falls back to PHP's built-in `mail()`, which is easier to set up but
more likely to be filtered as spam.

**Optional:** set `MAILCHIMP_API_KEY` and `MAILCHIMP_AUDIENCE_ID` to also
sync newsletter signups into a Mailchimp audience for more advanced email
marketing.

## Order lifecycle

```
pending_approval → approved → payment_pending → paid → completed
                 → rejected
                            ↳ (or) cancelled
```

Customers never pay at checkout — they submit a request, and payment is
only requested after an admin approves the order from `/admin/orders.php`.

## Local development

```bash
cp config.sample.php config.php   # edit DB credentials for your local MySQL
mysql -u root -p your_db < db.sql
php -S localhost:8000
```

Then visit `http://localhost:8000`. Set `SITE_URL` in `config.php` to match.

## Project structure

```
/                     Hero homepage (index.php)
/pages/                Menu, checkout, payment, contact, confirmation pages
/api/                   AJAX/JSON endpoints (cart, search, discounts, payments)
/admin/                 Admin panel (login-protected)
/includes/              Shared PHP classes (Database, Cart, Auth, Mailer, Stripe/PayPal clients)
/assets/css, /assets/js Single stylesheet + single script (no build step)
/uploads/menu/          Uploaded dish photos
db.sql                  Full MySQL schema + starter menu data
config.sample.php       Copy to config.php and fill in your credentials
```
