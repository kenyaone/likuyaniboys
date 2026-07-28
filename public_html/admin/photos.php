<?php
/** Photo management: galleries plus the two large feature images. */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/sitelib/admin_layout.php';

$user = auth_require();

const GALLERIES = [
    'students'   => ['Student Life photos', 'These appear near the top of the Student Life section.'],
    'facilities' => ['Facilities photos', 'These appear under "Our Facilities" at the end of Student Life.'],
];

$gallery = data_load('gallery');
foreach (array_keys(GALLERIES) as $k) {
    if (!isset($gallery[$k]) || !is_array($gallery[$k])) {
        $gallery[$k] = [];
    }
}

/** Find an item's position in a gallery, or null. */
function find_index(array $list, string $id): ?int
{
    foreach ($list as $i => $item) {
        if (($item['id'] ?? '') === $id) {
            return $i;
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');
    $which  = (string)($_POST['gallery'] ?? '');

    if ($action !== 'feature_images' && !array_key_exists($which, GALLERIES)) {
        flash('Unknown photo gallery.', 'bad');
        header('Location: photos.php');
        exit;
    }

    switch ($action) {

        case 'add':
            $result = store_upload($_FILES['photo'] ?? []);
            if (!$result['ok']) {
                flash($result['error'], 'bad');
                break;
            }
            $gallery[$which][] = [
                'id'      => new_id('g'),
                'file'    => $result['path'],
                'title'   => trim((string)($_POST['title'] ?? '')),
                'caption' => trim((string)($_POST['caption'] ?? '')),
            ];
            if (data_save('gallery', $gallery)) {
                flash('Photo added.');
            } else {
                delete_upload($result['path']);
                flash('Could not save the photo. Check folder permissions.', 'bad');
            }
            break;

        case 'save_captions':
            $titles   = (array)($_POST['title'] ?? []);
            $captions = (array)($_POST['caption'] ?? []);
            foreach ($gallery[$which] as $i => $item) {
                $id = (string)($item['id'] ?? '');
                if (isset($titles[$id])) {
                    $gallery[$which][$i]['title'] = trim((string)$titles[$id]);
                }
                if (isset($captions[$id])) {
                    $gallery[$which][$i]['caption'] = trim((string)$captions[$id]);
                }
            }
            if (data_save('gallery', $gallery)) {
                flash('Captions saved.');
            } else {
                flash('Could not save changes.', 'bad');
            }
            break;

        case 'delete':
            $idx = find_index($gallery[$which], (string)($_POST['id'] ?? ''));
            if ($idx === null) {
                flash('That photo was already removed.', 'warn');
                break;
            }
            $file = (string)($gallery[$which][$idx]['file'] ?? '');
            array_splice($gallery[$which], $idx, 1);
            if (data_save('gallery', $gallery)) {
                // Only delete the file itself once nothing else points at it.
                if (!image_in_use($file)) {
                    delete_upload($file);
                }
                flash('Photo removed.');
            } else {
                flash('Could not remove the photo.', 'bad');
            }
            break;

        case 'move':
            $idx = find_index($gallery[$which], (string)($_POST['id'] ?? ''));
            $dir = ((string)($_POST['dir'] ?? '')) === 'up' ? -1 : 1;
            $new = $idx === null ? null : $idx + $dir;
            if ($idx !== null && $new !== null && $new >= 0 && $new < count($gallery[$which])) {
                [$gallery[$which][$idx], $gallery[$which][$new]] = [$gallery[$which][$new], $gallery[$which][$idx]];
                data_save('gallery', $gallery);
                flash('Order updated.');
            }
            break;

        case 'feature_images':
            $content = data_load('content');
            $changed = false;
            $slots = [
                'about_image' => ['about', 'image'],
                'admin_block' => ['administration', 'block_image'],
            ];
            foreach ($slots as $field => [$sec, $key]) {
                if (!empty($_FILES[$field]['name'])) {
                    $r = store_upload($_FILES[$field]);
                    if (!$r['ok']) {
                        flash($r['error'], 'bad');
                        continue;
                    }
                    $old = (string)($content[$sec][$key] ?? '');
                    $content[$sec][$key] = $r['path'];
                    $changed = true;
                    if ($old !== '' && !image_in_use($old, ['file' => 'content'])) {
                        delete_upload($old);
                    }
                }
            }
            $caption = trim((string)($_POST['admin_block_caption'] ?? ''));
            if ($caption !== '' && $caption !== ($content['administration']['block_caption'] ?? '')) {
                $content['administration']['block_caption'] = $caption;
                $changed = true;
            }
            if ($changed) {
                if (data_save('content', $content)) {
                    flash('Main images updated.');
                } else {
                    flash('Could not save the changes.', 'bad');
                }
            } else {
                flash('Nothing to change — choose a photo first.', 'warn');
            }
            break;
    }

    header('Location: photos.php');
    exit;
}

$content = data_load('content');

admin_head('Photos', $user);
admin_title('Photos', 'Add photos to the website, give each one a heading and a caption, and put them in the order you want.');
?>

<?php foreach (GALLERIES as $key => [$heading, $blurb]): ?>
<div class="card">
    <h2><?= e($heading) ?></h2>
    <p class="card-note"><?= e($blurb) ?></p>

    <!-- Add a new photo -->
    <form method="post" enctype="multipart/form-data" style="border:1px dashed var(--line);border-radius:10px;padding:1.1rem;background:#FCFAF3;margin-bottom:1.4rem">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="gallery" value="<?= e($key) ?>">
        <div class="row2">
            <div class="field">
                <label class="lbl">Choose a photo</label>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" required>
                <span class="hint">JPG, PNG or WebP, up to 8 MB. Landscape photos look best.</span>
            </div>
            <div class="field">
                <label class="lbl">Heading</label>
                <input type="text" name="title" maxlength="120" placeholder="e.g. Form 4 Prize Giving">
            </div>
        </div>
        <div class="field">
            <label class="lbl">Caption</label>
            <input type="text" name="caption" maxlength="300" placeholder="One short sentence describing the photo">
        </div>
        <button class="btn gold" type="submit">Upload photo</button>
    </form>

<?php if (empty($gallery[$key])): ?>
    <p class="empty">No photos here yet. Use the form above to add the first one.</p>
<?php else: ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_captions">
        <input type="hidden" name="gallery" value="<?= e($key) ?>">
        <div class="photo-grid">
<?php foreach ($gallery[$key] as $i => $photo):
    $id = (string)($photo['id'] ?? ''); ?>
            <div class="photo-item">
                <img src="<?= e_url('../' . ($photo['file'] ?? '')) ?>" alt="">
                <div class="pi-body">
                    <input type="text" name="title[<?= e($id) ?>]" value="<?= e($photo['title'] ?? '') ?>" placeholder="Heading" maxlength="120">
                    <input type="text" name="caption[<?= e($id) ?>]" value="<?= e($photo['caption'] ?? '') ?>" placeholder="Caption" maxlength="300">
                    <div class="pi-actions">
                        <button class="btn ghost small" type="submit" form="mv-<?= e($id) ?>-up" <?= $i === 0 ? 'disabled' : '' ?> title="Move earlier">↑</button>
                        <button class="btn ghost small" type="submit" form="mv-<?= e($id) ?>-down" <?= $i === count($gallery[$key]) - 1 ? 'disabled' : '' ?> title="Move later">↓</button>
                        <button class="btn danger small" type="submit" form="del-<?= e($id) ?>"
                                onclick="return confirm('Remove this photo from the website?')" style="margin-left:auto">Delete</button>
                    </div>
                </div>
            </div>
<?php endforeach; ?>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Save headings &amp; captions</button>
        </div>
    </form>

    <?php /* Separate forms so the buttons above can live inside the captions form. */ ?>
<?php foreach ($gallery[$key] as $photo): $id = (string)($photo['id'] ?? ''); ?>
    <form id="mv-<?= e($id) ?>-up" method="post" hidden><?= csrf_field() ?>
        <input type="hidden" name="action" value="move"><input type="hidden" name="gallery" value="<?= e($key) ?>">
        <input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="dir" value="up"></form>
    <form id="mv-<?= e($id) ?>-down" method="post" hidden><?= csrf_field() ?>
        <input type="hidden" name="action" value="move"><input type="hidden" name="gallery" value="<?= e($key) ?>">
        <input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="dir" value="down"></form>
    <form id="del-<?= e($id) ?>" method="post" hidden><?= csrf_field() ?>
        <input type="hidden" name="action" value="delete"><input type="hidden" name="gallery" value="<?= e($key) ?>">
        <input type="hidden" name="id" value="<?= e($id) ?>"></form>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php endforeach; ?>

<div class="card">
    <h2>Main page images</h2>
    <p class="card-note">The two large photos used in the About and Administration sections.</p>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="feature_images">
        <?php photo_field('about_image', (string)cfg($content, 'about.image'), 'About section photo', false); ?>
        <?php photo_field('admin_block', (string)cfg($content, 'administration.block_image'), 'Administration section photo', false); ?>
        <div class="field">
            <label class="lbl">Caption under the administration photo</label>
            <input type="text" name="admin_block_caption" maxlength="120" value="<?= e(cfg($content, 'administration.block_caption')) ?>">
        </div>
        <div class="actions"><button class="btn" type="submit">Save main images</button></div>
    </form>
</div>
<?php
admin_foot();
