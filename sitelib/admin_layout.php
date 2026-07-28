<?php
/**
 * Shared chrome for every admin page.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function admin_nav_items(): array
{
    return [
        'dashboard.php' => ['🏠', 'Home'],
        'photos.php'    => ['🖼️', 'Photos'],
        'news.php'      => ['📣', 'News'],
        'staff.php'     => ['👤', 'Staff'],
        'content.php'   => ['📝', 'Page Text'],
        'users.php'     => ['🔑', 'Users'],
    ];
}

function admin_head(string $title, ?array $user = null, bool $chrome = true): void
{
    $self = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — School Website Admin</title>
    <link rel="stylesheet" href="admin.css?v=<?= (int)@filemtime(STJB_WEB . '/admin/admin.css') ?>">
</head>
<body class="<?= $chrome ? 'has-chrome' : 'bare' ?>">
<?php if ($chrome && $user): ?>
<header class="topbar">
    <div class="topbar-in">
        <div class="brand">
            <span class="brand-mark">✝</span>
            <div>
                <strong>School Website Admin</strong>
                <small>St. John the Baptist Likuyani Boys</small>
            </div>
        </div>
        <div class="topbar-right">
            <a class="view-site" href="../index.php" target="_blank" rel="noopener">View site ↗</a>
            <span class="whoami">👤 <?= e((string)($user['name'] ?: $user['username'])) ?></span>
            <a class="signout" href="logout.php">Sign out</a>
        </div>
    </div>
    <nav class="tabs">
<?php foreach (admin_nav_items() as $file => [$icon, $label]): ?>
        <a href="<?= e($file) ?>" class="<?= $self === $file ? 'active' : '' ?>"><span><?= $icon ?></span><?= e($label) ?></a>
<?php endforeach; ?>
    </nav>
</header>
<?php endif; ?>
<main class="wrap">
<?php
    $f = flash_take();
    if ($f) {
        printf(
            '<div class="flash %s">%s</div>',
            e($f['type'] === 'ok' ? 'ok' : ($f['type'] === 'warn' ? 'warn' : 'bad')),
            e((string)$f['msg'])
        );
    }
}

function admin_foot(): void
{
    ?>
</main>
<footer class="foot">Changes appear on the website immediately after you save.</footer>
</body>
</html>
<?php
}

/** Page heading with an optional short explanation underneath. */
function admin_title(string $title, string $help = ''): void
{
    echo '<h1 class="page-title">' . e($title) . '</h1>';
    if ($help !== '') {
        echo '<p class="page-help">' . e($help) . '</p>';
    }
}

/**
 * Render a photo picker: shows the current image, lets the editor upload a
 * replacement, and (optionally) clear it.
 */
function photo_field(string $name, string $current, string $label, bool $allowClear = true): void
{
    $webRoot = '../';
    ?>
    <div class="photo-field">
        <label class="lbl"><?= e($label) ?></label>
        <div class="photo-field-row">
            <div class="thumb">
<?php if (trim($current) !== ''): ?>
                <img src="<?= e_url($webRoot . $current) ?>" alt="">
<?php else: ?>
                <span class="thumb-empty">No photo</span>
<?php endif; ?>
            </div>
            <div class="photo-field-controls">
                <input type="file" name="<?= e($name) ?>" accept="image/jpeg,image/png,image/webp,image/gif">
                <small>JPG, PNG or WebP. Up to 8 MB. Leave empty to keep the current photo.</small>
<?php if ($allowClear && trim($current) !== ''): ?>
                <label class="chk"><input type="checkbox" name="<?= e($name) ?>_clear" value="1"> Remove this photo</label>
<?php endif; ?>
            </div>
        </div>
    </div>
<?php
}
