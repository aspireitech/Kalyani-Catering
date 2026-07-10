<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$input = json_input();
if (!csrf_verify($input['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'message' => 'Your session expired, please refresh and try again.'], 419);
}

$email = trim((string)($input['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'message' => 'Please enter a valid email address.']);
}

$db = Database::get();
$stmt = $db->prepare('SELECT id, is_active FROM subscribers WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    if (!$existing['is_active']) {
        $db->prepare('UPDATE subscribers SET is_active = 1 WHERE id = ?')->execute([$existing['id']]);
    }
} else {
    $token = random_token(20);
    $db->prepare('INSERT INTO subscribers (email, unsubscribe_token) VALUES (?, ?)')->execute([$email, $token]);
}

mailchimp_subscribe($email);

json_response(['ok' => true, 'message' => "You're subscribed! We'll email you about new dishes and offers."]);
