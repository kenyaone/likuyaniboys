<?php
/** Change your own password. Also the forced first-sign-in step. */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/sitelib/admin_layout.php';

$user  = auth_require();
$first = !empty($user['must_change']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $current = (string)($_POST['current'] ?? '');
    $new     = (string)($_POST['new'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    if (!password_verify($current, (string)$user['hash'])) {
        flash('Your current password is not correct.', 'bad');
    } elseif ($new !== $confirm) {
        flash('The two new passwords do not match.', 'bad');
    } elseif (($problem = password_problem($new)) !== '') {
        flash($problem, 'bad');
    } elseif (password_verify($new, (string)$user['hash'])) {
        flash('Please choose a password different from your current one.', 'bad');
    } elseif (user_update((string)$user['username'], [
        'hash'        => password_hash($new, PASSWORD_DEFAULT),
        'must_change' => false,
    ])) {
        flash('Password changed.');
        header('Location: dashboard.php');
        exit;
    } else {
        flash('Could not save the new password.', 'bad');
    }

    header('Location: password.php');
    exit;
}

admin_head('My Password', $user, !$first);

if ($first) {
    echo '<div class="login-card">';
    echo '<div class="login-head"><div class="mark">🔒</div><h1>Choose your password</h1>'
       . '<p>Signed in as ' . e((string)$user['username']) . '. Set your own password before you continue.</p></div>';
} else {
    admin_title('My Password', 'Change the password you use to sign in here.');
    echo '<div class="card">';
}
?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="username" value="<?= e((string)$user['username']) ?>" autocomplete="username">
        <div class="field">
            <label class="lbl" for="current"><?= $first ? 'The password you were given' : 'Current password' ?></label>
            <input type="password" id="current" name="current" required autocomplete="current-password" autofocus>
        </div>
        <div class="field">
            <label class="lbl" for="new">New password</label>
            <input type="password" id="new" name="new" required minlength="10" autocomplete="new-password">
            <span class="hint">At least 10 characters, including letters and numbers. Do not reuse a password from anywhere else.</span>
        </div>
        <div class="field">
            <label class="lbl" for="confirm">New password again</label>
            <input type="password" id="confirm" name="confirm" required minlength="10" autocomplete="new-password">
        </div>
        <button class="btn" type="submit" <?= $first ? 'style="width:100%"' : '' ?>>Save new password</button>
<?php if ($first): ?>
        <p style="text-align:center;margin-top:1rem"><a href="logout.php" style="color:var(--muted);font-size:.85rem">Sign out instead</a></p>
<?php endif; ?>
    </form>
</div>
<?php
admin_foot();
