<?php
/** General-purpose helpers shared across public pages, API endpoints and admin. */

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function base_url(string $path = ''): string
{
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function generate_order_number(): string
{
    return 'KC-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

/** @return array<string,string> */
function get_settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $stmt = Database::get()->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache;
}

function get_setting(string $key, string $default = ''): string
{
    $settings = get_settings();
    return $settings[$key] ?? $default;
}

function update_setting(string $key, string $value): void
{
    $stmt = Database::get()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

function flash_set(string $key, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

const COURSE_LABELS = [
    'starter' => 'Starters',
    'main'    => 'Main Course',
    'rice'    => 'Rice & Biryani',
    'dal'     => 'Dal & Soups',
    'dessert' => 'Desserts',
    'drink'   => 'Drinks',
    'side'    => 'Sides',
];

function course_label(string $course): string
{
    return COURSE_LABELS[$course] ?? ucfirst($course);
}

/** Path (relative to /assets/images/dishes/) to a branded placeholder illustration for a dish. */
function dish_placeholder_path(string $courseType, string $dietType): string
{
    $map = [
        'starter' => $dietType === 'non-veg' ? 'starter-nonveg.svg' : 'starter-veg.svg',
        'main'    => $dietType === 'non-veg' ? 'main-nonveg.svg' : 'main-veg.svg',
        'rice'    => 'rice.svg',
        'dal'     => 'dal.svg',
        'dessert' => 'dessert.svg',
        'drink'   => 'drink.svg',
        'side'    => 'side.svg',
    ];
    return $map[$courseType] ?? ($dietType === 'non-veg' ? 'main-nonveg.svg' : 'main-veg.svg');
}

function dish_image_url(?string $imagePath, string $courseType, string $dietType): string
{
    if ($imagePath) {
        return base_url('uploads/menu/' . $imagePath);
    }
    return base_url('assets/images/dishes/' . dish_placeholder_path($courseType, $dietType));
}

/** Best-effort sync of a subscriber to Mailchimp. No-op if not configured. */
function mailchimp_subscribe(string $email, string $name = ''): void
{
    if (empty(MAILCHIMP_API_KEY) || empty(MAILCHIMP_AUDIENCE_ID) || !str_contains(MAILCHIMP_API_KEY, '-')) {
        return;
    }
    $dc = substr(MAILCHIMP_API_KEY, strpos(MAILCHIMP_API_KEY, '-') + 1);
    $url = "https://{$dc}.api.mailchimp.com/3.0/lists/" . MAILCHIMP_AUDIENCE_ID . '/members';
    $payload = [
        'email_address' => $email,
        'status' => 'subscribed',
        'merge_fields' => ['FNAME' => $name],
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_USERPWD => 'anystring:' . MAILCHIMP_API_KEY,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
