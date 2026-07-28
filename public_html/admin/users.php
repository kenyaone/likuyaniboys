<?php
/** Manage who can sign in to the admin panel. */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/sitelib/admin_layout.php';

$user  = auth_require();
$me    = strtolower((string)$user['username']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');
    $users  = users_all();

    if ($action === 'add') {
        $username = strtolower(trim((string)($_POST['username'] ?? '')));
        $name     = trim((string)($_POST['name'] ?? ''));
        $pw       = (string)($_POST['password'] ?? '');

        if (!preg_match('/^[a-z0-9._-]{3,32}$/', $username)) {
            flash('Username must be 3–32 characters: letters, numbers, dot, dash or underscore.', 'bad');
        } elseif (user_find($username)) {
            flash('That username is already taken.', 'bad');
        } elseif (($problem = password_problem($pw)) !== '') {
            flash($problem, 'bad');
        } else {
            $users[] = [
                'username'    => $username,
                'name'        => $name !== '' ? $name : $username,
                'hash'        => password_hash($pw, PASSWORD_DEFAULT),
                'created'     => date('c'),
                'must_change' => true,
                'last_login'  => null,
            ];
            if (users_save($users)) {
                flash('User added. They will be asked to choose their own password when they first sign in.');
            } else {
                flash('Could not add the user.', 'bad');
            }
        }

    } elseif ($action === 'remove') {
        $target = strtolower(trim((string)($_POST['username'] ?? '')));
        if ($target === $me) {
            flash('You cannot remove your own account.', 'bad');
        } elseif (count($users) <= 1) {
            flash('There must always be at least one user.', 'bad');
        } else {
            $left = array_values(array_filter(
                $users,
                static fn($u) => strtolower((string)$u['username']) !== $target
            ));
            if (count($left) === count($users)) {
                flash('That user no longer exists.', 'warn');
            } elseif (users_save($left)) {
                flash('User removed.');
            } else {
                flash('Could not remove the user.', 'bad');
            }
        }

    } elseif ($action === 'reset') {
        $target = strtolower(trim((string)($_POST['username'] ?? '')));
        $pw     = (string)($_POST['password'] ?? '');
        if (!user_find($target)) {
            flash('That user no longer exists.', 'warn');
        } elseif (($problem = password_problem($pw)) !== '') {
            flash($problem, 'bad');
        } elseif (user_update($target, [
            'hash'        => password_hash($pw, PASSWORD_DEFAULT),
            'must_change' => true,
        ])) {
            flash('Password reset. Give them the new password — they will be asked to change it.');
        } else {
            flash('Could not reset the password.', 'bad');
        }
    }

    header('Location: users.php');
    exit;
}

$users = users_all();

admin_head('Users', $user);
admin_title('Users', 'Everyone listed here can sign in and change the website. Give each person their own account — never share one login.');
?>

<div class="card">
    <h2>People with access</h2>
    <table class="list">
        <tr><th>Username</th><th>Name</th><th>Last signed in</th><th class="right">Actions</th></tr>
<?php foreach ($users as $u):
    $uname = (string)$u['username'];
    $isMe  = strtolower($uname) === $me; ?>
        <tr>
            <td><strong><?= e($uname) ?></strong><?= $isMe ? ' <span style="color:var(--muted);font-weight:400">(you)</span>' : '' ?></td>
            <td><?= e((string)($u['name'] ?? '')) ?></td>
            <td><?= e($u['last_login'] ? date('j M Y, H:i', strtotime((string)$u['last_login'])) : 'Never') ?></td>
            <td class="right">
<?php if ($isMe): ?>
                <a class="btn ghost small" href="password.php">Change my password</a>
<?php else: ?>
                <form method="post" style="display:inline-flex;gap:.4rem;align-items:center;flex-wrap:wrap;justify-content:flex-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="username" value="<?= e($uname) ?>">
                    <input type="password" name="password" placeholder="New password" style="width:11rem" required minlength="10">
                    <button class="btn ghost small" type="submit">Reset</button>
                </form>
                <form method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="username" value="<?= e($uname) ?>">
                    <button class="btn danger small" type="submit"
                            onclick="return confirm('Remove <?= e($uname) ?>? They will no longer be able to sign in.')">Remove</button>
                </form>
<?php endif; ?>
            </td>
        </tr>
<?php endforeach; ?>
    </table>
</div>

<div class="card">
    <h2>Add someone</h2>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <div class="row3">
            <div class="field">
                <label class="lbl">Username</label>
                <input type="text" name="username" required maxlength="32" autocapitalize="none" placeholder="e.g. jkamau">
                <span class="hint">Letters and numbers, no spaces.</span>
            </div>
            <div class="field">
                <label class="lbl">Full name</label>
                <input type="text" name="name" maxlength="120" placeholder="e.g. Jane Kamau">
            </div>
            <div class="field">
                <label class="lbl">Starting password</label>
                <input type="password" name="password" required minlength="10" autocomplete="new-password">
                <span class="hint">At least 10 characters, with letters and numbers.</span>
            </div>
        </div>
        <div class="actions"><button class="btn gold" type="submit">Add user</button></div>
        <p class="card-note" style="margin-top:1rem">Tell the new user this password in person or by phone — not by email. They will be asked to set their own the first time they sign in.</p>
    </form>
</div>
<?php
admin_foot();
