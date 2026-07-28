<?php
/**
 * Admin authentication: sessions, passwords, CSRF, login throttling.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

define('STJB_MAX_LOGIN_FAILS', 8);
define('STJB_LOCKOUT_SECONDS', 900);   // 15 minutes
define('STJB_IDLE_TIMEOUT', 7200);     // 2 hours of inactivity

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    // cPanel sits behind a proxy on some plans.
    return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function auth_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('STJBADMIN');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/admin',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Expire idle sessions.
    if (isset($_SESSION['last_seen']) && time() - (int)$_SESSION['last_seen'] > STJB_IDLE_TIMEOUT) {
        auth_logout();
        session_start();
        $_SESSION['flash'] = ['type' => 'warn', 'msg' => 'You were signed out after 2 hours of inactivity.'];
    }
    $_SESSION['last_seen'] = time();
}

/* ------------------------------------------------------------------ *
 * Users
 * ------------------------------------------------------------------ */

function users_all(): array
{
    $d = data_load('users', ['users' => []]);
    return is_array($d['users'] ?? null) ? $d['users'] : [];
}

function users_save(array $users): bool
{
    return data_save('users', ['users' => array_values($users)]);
}

function user_find(string $username): ?array
{
    foreach (users_all() as $u) {
        if (strcasecmp((string)$u['username'], $username) === 0) {
            return $u;
        }
    }
    return null;
}

function user_update(string $username, array $changes): bool
{
    $users = users_all();
    foreach ($users as $i => $u) {
        if (strcasecmp((string)$u['username'], $username) === 0) {
            $users[$i] = array_merge($u, $changes);
            return users_save($users);
        }
    }
    return false;
}

function auth_user(): ?array
{
    if (empty($_SESSION['username'])) {
        return null;
    }
    return user_find((string)$_SESSION['username']);
}

function auth_check(): bool
{
    return auth_user() !== null;
}

/** Redirect to the login page unless signed in. */
function auth_require(): array
{
    auth_start();
    $user = auth_user();
    if (!$user) {
        $_SESSION['flash'] = ['type' => 'warn', 'msg' => 'Please sign in first.'];
        header('Location: index.php');
        exit;
    }
    // Force a password change on first sign-in.
    $self = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (!empty($user['must_change']) && $self !== 'password.php' && $self !== 'logout.php') {
        header('Location: password.php?first=1');
        exit;
    }
    return $user;
}

/* ------------------------------------------------------------------ *
 * Login throttling — slows down password guessing.
 * ------------------------------------------------------------------ */

function throttle_key(string $username): string
{
    return sha1(strtolower($username) . '|' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
}

function throttle_state(): array
{
    return data_load('_throttle', []);
}

/** Seconds remaining in a lockout, or 0 if not locked out. */
function throttle_locked(string $username): int
{
    $s = throttle_state()[throttle_key($username)] ?? null;
    if (!$s || ($s['fails'] ?? 0) < STJB_MAX_LOGIN_FAILS) {
        return 0;
    }
    $left = STJB_LOCKOUT_SECONDS - (time() - (int)($s['last'] ?? 0));
    return $left > 0 ? $left : 0;
}

function throttle_fail(string $username): void
{
    $state = throttle_state();
    $k     = throttle_key($username);
    $cur   = $state[$k] ?? ['fails' => 0, 'last' => 0];

    // A clean window since the last attempt resets the counter.
    if (time() - (int)$cur['last'] > STJB_LOCKOUT_SECONDS) {
        $cur['fails'] = 0;
    }
    $cur['fails'] = (int)$cur['fails'] + 1;
    $cur['last']  = time();
    $state[$k]    = $cur;

    // Drop entries nobody has touched in a day.
    foreach ($state as $key => $entry) {
        if (time() - (int)($entry['last'] ?? 0) > 86400) {
            unset($state[$key]);
        }
    }
    data_save('_throttle', $state);
}

function throttle_clear(string $username): void
{
    $state = throttle_state();
    unset($state[throttle_key($username)]);
    data_save('_throttle', $state);
}

/* ------------------------------------------------------------------ *
 * Sign in / out
 * ------------------------------------------------------------------ */

/** Returns '' on success, or an error message to show the user. */
function auth_login(string $username, string $password): string
{
    auth_start();
    $username = trim($username);

    if ($username === '' || $password === '') {
        return 'Enter both your username and password.';
    }

    $wait = throttle_locked($username);
    if ($wait > 0) {
        return 'Too many failed attempts. Try again in ' . ceil($wait / 60) . ' minute(s).';
    }

    $user = user_find($username);
    // Always run a hash comparison so a wrong username and a wrong password
    // take the same amount of time.
    $hash = $user['hash'] ?? '$2y$12$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidin';

    if (!$user || !password_verify($password, (string)$hash)) {
        throttle_fail($username);
        return 'Wrong username or password.';
    }

    if (password_needs_rehash((string)$user['hash'], PASSWORD_DEFAULT)) {
        user_update((string)$user['username'], ['hash' => password_hash($password, PASSWORD_DEFAULT)]);
    }

    throttle_clear($username);
    session_regenerate_id(true);
    $_SESSION['username']  = $user['username'];
    $_SESSION['last_seen'] = time();
    unset($_SESSION['csrf']);
    user_update((string)$user['username'], ['last_login' => date('c')]);

    return '';
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

/* ------------------------------------------------------------------ *
 * CSRF
 * ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/** Kill the request unless the POST carries a valid token. */
function csrf_verify(): void
{
    $sent = (string)($_POST['_token'] ?? '');
    if ($sent === '' || empty($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], $sent)) {
        http_response_code(400);
        exit('Your session expired. Go back, reload the page, and try again.');
    }
}

/* ------------------------------------------------------------------ *
 * Flash messages
 * ------------------------------------------------------------------ */

function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_take(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Password strength rule, kept deliberately simple for non-technical users. */
function password_problem(string $pw): string
{
    if (strlen($pw) < 10) {
        return 'Password must be at least 10 characters long.';
    }
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/[0-9]/', $pw)) {
        return 'Password must contain both letters and numbers.';
    }
    return '';
}
