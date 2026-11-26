<?php
// Admin accounts, sessions and CSRF protection.

function admin_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('nm_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/admin', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
    ini_set('session.use_strict_mode', '1');
    session_start();
    // Sign out after 2 hours without activity.
    if (!empty($_SESSION['uid']) && time() - ($_SESSION['seen'] ?? 0) > 7200) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['seen'] = time();
}

function users(): array { return json_load('users', []); }
function save_users(array $u): void { json_save('users', array_values($u)); }

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    foreach (users() as $u) if ($u['email'] === $_SESSION['uid']) return $u;
    return null;
}

function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: ?p=login'); exit; }
    return $u;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Your session expired. Go back, reload the page and try again.');
    }
}

function password_problem(string $p): ?string {
    if (strlen($p) < 12) return 'Use at least 12 characters.';
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) return 'Use letters and at least one number.';
    return null;
}

/** Returns null on success, or an error message. */
function attempt_login(string $email, string $password): ?string {
    $email = strtolower(trim($email));
    $key = client_key();
    if (too_many('login', $key, 5, 900) || too_many('login-user', $email, 8, 900)) {
        return 'Too many attempts. Wait 15 minutes and try again.';
    }
    foreach (users() as $u) {
        if ($u['email'] === $email && password_verify($password, $u['hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $email;
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            return null;
        }
    }
    note_attempt('login', $key);
    note_attempt('login-user', $email);
    return 'Wrong email or password.';
}

function flash(?string $msg = null, string $kind = 'ok'): ?array {
    if ($msg !== null) { $_SESSION['flash'] = [$kind, $msg]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
