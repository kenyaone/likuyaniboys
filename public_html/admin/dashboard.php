<?php
/** Admin home — the hub every editor lands on. */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/sitelib/admin_layout.php';

$user = auth_require();

$gallery = data_load('gallery');
$news    = data_load('news');
$staff   = data_load('staff');

$photoCount = count((array)($gallery['students'] ?? [])) + count((array)($gallery['facilities'] ?? []));
$newsCount  = count((array)($news['items'] ?? []));
$staffCount = count((array)($staff['leadership'] ?? []))
            + count((array)($staff['staff'] ?? []))
            + count((array)($staff['bom'] ?? []));

admin_head('Home', $user);
admin_title(
    'Welcome, ' . ($user['name'] ?: $user['username']),
    'Pick what you want to change. Everything you save here goes live on the website straight away.'
);
?>

<div class="tiles">
    <a class="tile" href="photos.php">
        <div class="tile-icon">🖼️</div>
        <h3>Photos</h3>
        <p>Add, caption, reorder or remove photos in the Student Life and Facilities galleries. <strong><?= (int)$photoCount ?></strong> on the site now.</p>
    </a>
    <a class="tile" href="news.php">
        <div class="tile-icon">📣</div>
        <h3>News &amp; Announcements</h3>
        <p>Post notices for parents and students. <strong><?= (int)$newsCount ?></strong> published.</p>
    </a>
    <a class="tile" href="staff.php">
        <div class="tile-icon">👤</div>
        <h3>Staff &amp; Leadership</h3>
        <p>Names, titles, photos and contacts for the Principal, staff and Board. <strong><?= (int)$staffCount ?></strong> people listed.</p>
    </a>
    <a class="tile" href="content.php">
        <div class="tile-icon">📝</div>
        <h3>Page Text</h3>
        <p>Headings and wording across the whole page — About, fees, calendar, contacts and more.</p>
    </a>
    <a class="tile" href="users.php">
        <div class="tile-icon">🔑</div>
        <h3>Users</h3>
        <p>Add or remove people who can sign in here.</p>
    </a>
    <a class="tile" href="password.php">
        <div class="tile-icon">🔒</div>
        <h3>My Password</h3>
        <p>Change the password you use to sign in.</p>
    </a>
</div>

<div class="card" style="margin-top:1.5rem">
    <h2>Before you start</h2>
    <ul style="padding-left:1.2rem;color:var(--muted);line-height:2">
        <li>Photos should be landscape (wider than tall) and under 8 MB. JPG works best.</li>
        <li>Press <strong>Save</strong> at the bottom of a page — changes are not kept until you do.</li>
        <li>Open <a href="../index.php" target="_blank" rel="noopener">the website</a> in another tab to check your work.</li>
        <li>Every save keeps a backup, so a mistake can always be undone.</li>
    </ul>
</div>
<?php
admin_foot();
