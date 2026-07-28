<?php
/**
 * Create (or reset) an admin account for the school website.
 *
 * sitedata/users.json is deliberately NOT in version control, so a fresh
 * deployment starts with no accounts. Run this once to create the first one:
 *
 *     php tools/create-admin.php <username> [full name]
 *
 * A strong password is generated and printed. The account is flagged so the
 * person must choose their own password the first time they sign in.
 *
 * Re-running with an existing username resets that account's password.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("This script only runs from the command line.\n");
}

require_once dirname(__DIR__) . '/sitelib/auth.php';

$username = strtolower(trim((string)($argv[1] ?? '')));
$fullName = trim((string)($argv[2] ?? '')) ?: 'School Administrator';

if (!preg_match('/^[a-z0-9._-]{3,32}$/', $username)) {
    fwrite(STDERR, "Usage: php tools/create-admin.php <username> [full name]\n");
    fwrite(STDERR, "Username: 3-32 characters, letters/numbers/dot/dash/underscore.\n");
    exit(1);
}

// Avoid look-alike characters so the password can be read out over the phone.
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
$password = '';
for ($i = 0; $i < 14; $i++) {
    $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
}

$users    = users_all();
$existing = false;
foreach ($users as $i => $u) {
    if (strcasecmp((string)$u['username'], $username) === 0) {
        $users[$i]['hash']        = password_hash($password, PASSWORD_DEFAULT);
        $users[$i]['must_change'] = true;
        $existing = true;
        break;
    }
}

if (!$existing) {
    $users[] = [
        'username'    => $username,
        'name'        => $fullName,
        'hash'        => password_hash($password, PASSWORD_DEFAULT),
        'created'     => date('c'),
        'must_change' => true,
        'last_login'  => null,
    ];
}

if (!users_save($users)) {
    fwrite(STDERR, "Could not write sitedata/users.json — check permissions.\n");
    exit(1);
}
@chmod(data_path('users'), 0640);

echo $existing ? "Password reset.\n\n" : "Account created.\n\n";
echo "  username: {$username}\n";
echo "  password: {$password}\n\n";
echo "Give this to the person by phone or in person, not by email.\n";
echo "They will be asked to choose their own password when they first sign in.\n";
