<?php
/** News & announcements editor. */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/sitelib/admin_layout.php';

$user = auth_require();
$news = data_load('news');
if (!isset($news['items']) || !is_array($news['items'])) {
    $news['items'] = [];
}

function news_index(array $items, string $id): ?int
{
    foreach ($items as $i => $item) {
        if (($item['id'] ?? '') === $id) {
            return $i;
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    switch ((string)($_POST['action'] ?? '')) {

        case 'add':
            $title = trim((string)($_POST['title'] ?? ''));
            if ($title === '') {
                flash('An announcement needs a title.', 'bad');
                break;
            }
            $date = trim((string)($_POST['date'] ?? ''));
            array_unshift($news['items'], [
                'id'     => new_id('n'),
                'title'  => $title,
                'date'   => $date !== '' ? $date : date('Y-m-d'),
                'body'   => trim((string)($_POST['body'] ?? '')),
                'pinned' => !empty($_POST['pinned']),
            ]);
            if (data_save('news', $news)) {
                flash('Announcement published.');
            } else {
                flash('Could not save the announcement.', 'bad');
            }
            break;

        case 'save_all':
            $titles  = (array)($_POST['title'] ?? []);
            $dates   = (array)($_POST['date'] ?? []);
            $bodies  = (array)($_POST['body'] ?? []);
            $pins    = (array)($_POST['pinned'] ?? []);
            foreach ($news['items'] as $i => $item) {
                $id = (string)($item['id'] ?? '');
                if (isset($titles[$id]) && trim((string)$titles[$id]) !== '') {
                    $news['items'][$i]['title'] = trim((string)$titles[$id]);
                }
                if (isset($dates[$id])) {
                    $news['items'][$i]['date'] = trim((string)$dates[$id]);
                }
                if (isset($bodies[$id])) {
                    $news['items'][$i]['body'] = trim((string)$bodies[$id]);
                }
                $news['items'][$i]['pinned'] = isset($pins[$id]);
            }
            $news['badge']    = trim((string)($_POST['section_badge'] ?? 'Latest'));
            $news['title']    = trim((string)($_POST['section_title'] ?? 'News & Announcements'));
            $news['subtitle'] = trim((string)($_POST['section_subtitle'] ?? ''));
            if (data_save('news', $news)) {
                flash('Announcements saved.');
            } else {
                flash('Could not save changes.', 'bad');
            }
            break;

        case 'delete':
            $idx = news_index($news['items'], (string)($_POST['id'] ?? ''));
            if ($idx === null) {
                flash('That announcement was already removed.', 'warn');
                break;
            }
            array_splice($news['items'], $idx, 1);
            if (data_save('news', $news)) {
                flash('Announcement removed.');
            } else {
                flash('Could not remove it.', 'bad');
            }
            break;
    }

    header('Location: news.php');
    exit;
}

// Newest first for editing convenience.
$items = $news['items'];
usort($items, static fn($a, $b) => strcmp((string)($b['date'] ?? ''), (string)($a['date'] ?? '')));

admin_head('News', $user);
admin_title(
    'News & Announcements',
    'Post notices for parents and students. They show on the homepage, newest first — pinned notices always come first. If there are no announcements, the whole section is hidden from the website.'
);
?>

<div class="card">
    <h2>New announcement</h2>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <div class="row2">
            <div class="field">
                <label class="lbl">Title</label>
                <input type="text" name="title" maxlength="160" required placeholder="e.g. Half Term Break Dates">
            </div>
            <div class="field">
                <label class="lbl">Date</label>
                <input type="date" name="date" value="<?= e(date('Y-m-d')) ?>">
                <span class="hint">Shown on the announcement.</span>
            </div>
        </div>
        <div class="field">
            <label class="lbl">Details</label>
            <textarea name="body" rows="4" maxlength="3000" placeholder="Write the full notice here. Press Enter for a new line."></textarea>
        </div>
        <label class="chk"><input type="checkbox" name="pinned" value="1"> Pin this to the top</label>
        <div class="actions"><button class="btn gold" type="submit">Publish announcement</button></div>
    </form>
</div>

<div class="card">
    <h2>Published announcements</h2>
<?php if (!$items): ?>
    <p class="empty">Nothing published yet. The News section is hidden from the website until you add one.</p>
<?php else: ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_all">

<?php foreach ($items as $item): $id = (string)($item['id'] ?? ''); ?>
        <div class="item">
            <div class="item-head">
                <strong><?= e($item['title'] ?? '') ?></strong>
                <button class="btn danger small" type="submit" form="ndel-<?= e($id) ?>"
                        onclick="return confirm('Delete this announcement?')">Delete</button>
            </div>
            <div class="row2">
                <div class="field">
                    <label class="lbl">Title</label>
                    <input type="text" name="title[<?= e($id) ?>]" value="<?= e($item['title'] ?? '') ?>" maxlength="160">
                </div>
                <div class="field">
                    <label class="lbl">Date</label>
                    <input type="date" name="date[<?= e($id) ?>]" value="<?= e($item['date'] ?? '') ?>">
                </div>
            </div>
            <div class="field">
                <label class="lbl">Details</label>
                <textarea name="body[<?= e($id) ?>]" rows="3" maxlength="3000"><?= e($item['body'] ?? '') ?></textarea>
            </div>
            <label class="chk">
                <input type="checkbox" name="pinned[<?= e($id) ?>]" value="1" <?= !empty($item['pinned']) ? 'checked' : '' ?>>
                Pin to the top
            </label>
        </div>
<?php endforeach; ?>

        <h3>Section wording</h3>
        <div class="row3">
            <div class="field">
                <label class="lbl">Small label</label>
                <input type="text" name="section_badge" maxlength="40" value="<?= e((string)($news['badge'] ?? 'Latest')) ?>">
            </div>
            <div class="field">
                <label class="lbl">Section heading</label>
                <input type="text" name="section_title" maxlength="120" value="<?= e((string)($news['title'] ?? '')) ?>">
            </div>
            <div class="field">
                <label class="lbl">Sub-heading</label>
                <input type="text" name="section_subtitle" maxlength="300" value="<?= e((string)($news['subtitle'] ?? '')) ?>">
            </div>
        </div>

        <div class="save-bar"><button class="btn" type="submit">Save all announcements</button></div>
    </form>

<?php foreach ($items as $item): $id = (string)($item['id'] ?? ''); ?>
    <form id="ndel-<?= e($id) ?>" method="post" hidden><?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= e($id) ?>"></form>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php
admin_foot();
