<?php
/** Admin sign-in. */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/sitelib/admin_layout.php';

auth_start();

if (auth_check()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $error = auth_login((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''));
    if ($error === '') {
        header('Location: dashboard.php');
        exit;
    }
}

admin_head('Sign in', null, false);
?>
<div class="login-card">
    <div class="login-head">
        <div class="mark">✝</div>
        <h1>School Website Admin</h1>
        <p>St. John the Baptist Likuyani Boys High School</p>
    </div>

<?php if ($error !== ''): ?>
    <div class="flash bad"><?= e($error) ?></div>
<?php endif; ?>

    <form method="post" autocomplete="on">
        <?= csrf_field() ?>
        <div class="field">
            <label class="lbl" for="username">Username</label>
            <input type="text" id="username" name="username" autocapitalize="none" autocorrect="off"
                   autocomplete="username" required autofocus
                   value="<?= e((string)($_POST['username'] ?? '')) ?>">
        </div>
        <div class="field">
            <label class="lbl" for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn" type="submit" style="width:100%">Sign in</button>
    </form>
</div>
<?php
admin_foot();
