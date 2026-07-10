<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/Mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('pages/contact.php'));
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Your session expired, please try again.');
    redirect(base_url('pages/contact.php'));
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$department = in_array($_POST['department'] ?? '', ['general', 'billing', 'customer_service'], true) ? $_POST['department'] : 'general';
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('error', 'Please fill in your name, a valid email, and a message.');
    redirect(base_url('pages/contact.php'));
}

$stmt = Database::get()->prepare(
    'INSERT INTO contact_messages (name, email, phone, department, message) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$name, $email, $phone, $department, $message]);

Mailer::contactFormReceived(compact('name', 'email', 'phone', 'department', 'message'));

flash_set('success', "Thanks $name! We've received your message and will get back to you soon.");
redirect(base_url('pages/contact.php'));
