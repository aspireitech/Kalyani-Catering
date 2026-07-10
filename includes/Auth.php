<?php
/** Admin authentication (separate session key from the public cart session). */
class Auth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function attempt(string $email, string $password): bool
    {
        self::start();
        $stmt = Database::get()->prepare('SELECT * FROM admin_users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_id'] = (int)$user['id'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_role'] = $user['role'];
            return true;
        }
        return false;
    }

    public static function check(): bool
    {
        self::start();
        return !empty($_SESSION['admin_id']);
    }

    public static function requireLogin(): void
    {
        self::start();
        if (!self::check()) {
            redirect(base_url('admin/login.php'));
        }
    }

    public static function id(): ?int
    {
        self::start();
        return $_SESSION['admin_id'] ?? null;
    }

    public static function name(): string
    {
        self::start();
        return $_SESSION['admin_name'] ?? 'Admin';
    }

    public static function logout(): void
    {
        self::start();
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
    }
}
