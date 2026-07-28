<?php
/** Staff editor: leadership, department heads, Board of Management, key contacts. */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/sitelib/admin_layout.php';

$user  = auth_require();
$staff = data_load('staff');

foreach (['leadership', 'staff', 'bom', 'key_contacts'] as $k) {
    if (!isset($staff[$k]) || !is_array($staff[$k])) {
        $staff[$k] = [];
    }
}

/**
 * Handle one person's photo: an upload replaces it, the clear box removes it.
 * Returns the path to store.
 */
function resolve_photo(string $field, string $current): string
{
    if (!empty($_FILES[$field]['name'] ?? '')) {
        $r = store_upload($_FILES[$field]);
        if ($r['ok']) {
            if ($current !== '' && !image_in_use($current, ['file' => 'staff'])) {
                delete_upload($current);
            }
            return $r['path'];
        }
        flash($r['error'], 'bad');
        return $current;
    }
    if (!empty($_POST[$field . '_clear'])) {
        if ($current !== '' && !image_in_use($current, ['file' => 'staff'])) {
            delete_upload($current);
        }
        return '';
    }
    return $current;
}

function post_str(string $group, string $id, string $key): string
{
    return trim((string)($_POST[$group][$id][$key] ?? ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        foreach ($staff['leadership'] as $i => $p) {
            $id = (string)$p['id'];
            $staff['leadership'][$i] = array_merge($p, [
                'badge' => post_str('lead', $id, 'badge'),
                'name'  => post_str('lead', $id, 'name'),
                'title' => post_str('lead', $id, 'title'),
                'quote' => post_str('lead', $id, 'quote'),
                'phone' => post_str('lead', $id, 'phone'),
                'email' => post_str('lead', $id, 'email'),
                'photo' => resolve_photo('lead_photo_' . $id, (string)($p['photo'] ?? '')),
            ]);
        }
        foreach ($staff['staff'] as $i => $p) {
            $id = (string)$p['id'];
            $staff['staff'][$i] = array_merge($p, [
                'name'  => post_str('st', $id, 'name'),
                'role'  => post_str('st', $id, 'role'),
                'icon'  => post_str('st', $id, 'icon') ?: '👤',
                'photo' => resolve_photo('st_photo_' . $id, (string)($p['photo'] ?? '')),
            ]);
        }
        foreach ($staff['bom'] as $i => $p) {
            $id = (string)$p['id'];
            $staff['bom'][$i] = array_merge($p, [
                'name'  => post_str('bm', $id, 'name'),
                'role'  => post_str('bm', $id, 'role'),
                'photo' => resolve_photo('bm_photo_' . $id, (string)($p['photo'] ?? '')),
            ]);
        }
        foreach ($staff['key_contacts'] as $i => $p) {
            $id = (string)$p['id'];
            $staff['key_contacts'][$i] = array_merge($p, [
                'name'  => post_str('kc', $id, 'name'),
                'role'  => post_str('kc', $id, 'role'),
                'phone' => post_str('kc', $id, 'phone'),
                'email' => post_str('kc', $id, 'email'),
                'photo' => resolve_photo('kc_photo_' . $id, (string)($p['photo'] ?? '')),
            ]);
        }
        $staff['bom_title'] = trim((string)($_POST['bom_title'] ?? 'Board of Management'));

        if (data_save('staff', $staff)) {
            flash('Staff details saved.');
        } else {
            flash('Could not save changes.', 'bad');
        }

    } elseif ($action === 'add') {
        $group = (string)($_POST['group'] ?? '');
        $map   = ['staff' => 'st', 'bom' => 'bm', 'key_contacts' => 'kc', 'leadership' => 'lead'];
        if (!isset($map[$group])) {
            flash('Unknown section.', 'bad');
        } else {
            $blank = [
                'id' => new_id($map[$group]), 'name' => 'New entry', 'role' => '',
                'photo' => '', 'focus' => 'center', 'icon' => '👤',
            ];
            if ($group === 'leadership') {
                $blank += ['badge' => '', 'title' => '', 'quote' => '', 'phone' => '', 'email' => ''];
            }
            if ($group === 'key_contacts') {
                $blank += ['phone' => '', 'email' => ''];
            }
            $staff[$group][] = $blank;
            if (data_save('staff', $staff)) {
                flash('Entry added — fill in the details and press Save.');
            } else {
                flash('Could not add the entry.', 'bad');
            }
        }

    } elseif ($action === 'remove') {
        $group = (string)($_POST['group'] ?? '');
        $id    = (string)($_POST['id'] ?? '');
        if (isset($staff[$group]) && is_array($staff[$group])) {
            foreach ($staff[$group] as $i => $p) {
                if (($p['id'] ?? '') === $id) {
                    $photo = (string)($p['photo'] ?? '');
                    array_splice($staff[$group], $i, 1);
                    if (data_save('staff', $staff)) {
                        if (!image_in_use($photo)) {
                            delete_upload($photo);
                        }
                        flash('Entry removed.');
                    } else {
                        flash('Could not remove the entry.', 'bad');
                    }
                    break;
                }
            }
        }
    }

    header('Location: staff.php');
    exit;
}

/** Small "remove this person" button that posts its own form. */
function remove_btn(string $group, string $id): void
{
    $fid = 'rm-' . $group . '-' . $id;
    echo '<button class="btn danger small" type="submit" form="' . e($fid) . '"'
       . ' onclick="return confirm(\'Remove this entry from the website?\')">Remove</button>';
}

function remove_form(string $group, string $id): void
{
    $fid = 'rm-' . $group . '-' . $id;
    echo '<form id="' . e($fid) . '" method="post" hidden>' . csrf_field()
       . '<input type="hidden" name="action" value="remove">'
       . '<input type="hidden" name="group" value="' . e($group) . '">'
       . '<input type="hidden" name="id" value="' . e($id) . '"></form>';
}

admin_head('Staff', $user);
admin_title('Staff & Leadership', 'Names, titles, photos and contact details shown in the Administration and Contact sections.');
?>

<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">

    <div class="card">
        <h2>Principal &amp; Deputy</h2>
        <p class="card-note">The two large cards at the top of the Administration section.</p>
<?php foreach ($staff['leadership'] as $p): $id = (string)$p['id']; ?>
        <div class="item">
            <div class="item-head">
                <strong><?= e($p['name'] ?: 'Unnamed') ?></strong>
                <?php remove_btn('leadership', $id); ?>
            </div>
            <div class="row2">
                <div class="field">
                    <label class="lbl">Full name</label>
                    <input type="text" name="lead[<?= e($id) ?>][name]" value="<?= e($p['name'] ?? '') ?>" maxlength="120">
                </div>
                <div class="field">
                    <label class="lbl">Title</label>
                    <input type="text" name="lead[<?= e($id) ?>][title]" value="<?= e($p['title'] ?? '') ?>" maxlength="120">
                    <span class="hint">Shown under the name, e.g. "Principal".</span>
                </div>
            </div>
            <div class="field">
                <label class="lbl">Corner label</label>
                <input type="text" name="lead[<?= e($id) ?>][badge]" value="<?= e($p['badge'] ?? '') ?>" maxlength="60">
                <span class="hint">The small gold tag on the photo.</span>
            </div>
            <div class="field">
                <label class="lbl">Message / quote</label>
                <textarea name="lead[<?= e($id) ?>][quote]" rows="4" maxlength="2000"><?= e($p['quote'] ?? '') ?></textarea>
            </div>
            <div class="row2">
                <div class="field">
                    <label class="lbl">Phone</label>
                    <input type="text" name="lead[<?= e($id) ?>][phone]" value="<?= e($p['phone'] ?? '') ?>" maxlength="40" placeholder="0722 000 000">
                </div>
                <div class="field">
                    <label class="lbl">Email</label>
                    <input type="text" name="lead[<?= e($id) ?>][email]" value="<?= e($p['email'] ?? '') ?>" maxlength="160">
                </div>
            </div>
            <?php photo_field('lead_photo_' . $id, (string)($p['photo'] ?? ''), 'Photo'); ?>
        </div>
<?php endforeach; ?>
    </div>

    <div class="card">
        <h2>Department heads</h2>
        <p class="card-note">The small cards under the Principal and Deputy. Leave the photo empty to show the symbol instead.</p>
<?php foreach ($staff['staff'] as $p): $id = (string)$p['id']; ?>
        <div class="item">
            <div class="item-head">
                <strong><?= e($p['name'] ?: 'Unnamed') ?></strong>
                <?php remove_btn('staff', $id); ?>
            </div>
            <div class="row3">
                <div class="field">
                    <label class="lbl">Name or role</label>
                    <input type="text" name="st[<?= e($id) ?>][name]" value="<?= e($p['name'] ?? '') ?>" maxlength="120">
                </div>
                <div class="field">
                    <label class="lbl">Description</label>
                    <input type="text" name="st[<?= e($id) ?>][role]" value="<?= e($p['role'] ?? '') ?>" maxlength="120">
                </div>
                <div class="field">
                    <label class="lbl">Symbol</label>
                    <input type="text" name="st[<?= e($id) ?>][icon]" value="<?= e($p['icon'] ?? '👤') ?>" maxlength="8">
                    <span class="hint">Used only when there is no photo.</span>
                </div>
            </div>
            <?php photo_field('st_photo_' . $id, (string)($p['photo'] ?? ''), 'Photo (optional)'); ?>
        </div>
<?php endforeach; ?>
        <button class="btn ghost small" type="submit" form="add-staff">+ Add another department head</button>
    </div>

    <div class="card">
        <h2>Board of Management</h2>
        <div class="field">
            <label class="lbl">Section heading</label>
            <input type="text" name="bom_title" value="<?= e((string)($staff['bom_title'] ?? 'Board of Management')) ?>" maxlength="120">
        </div>
<?php foreach ($staff['bom'] as $p): $id = (string)$p['id']; ?>
        <div class="item">
            <div class="item-head">
                <strong><?= e($p['name'] ?: 'Unnamed') ?></strong>
                <?php remove_btn('bom', $id); ?>
            </div>
            <div class="row2">
                <div class="field">
                    <label class="lbl">Full name</label>
                    <input type="text" name="bm[<?= e($id) ?>][name]" value="<?= e($p['name'] ?? '') ?>" maxlength="120">
                </div>
                <div class="field">
                    <label class="lbl">Position</label>
                    <input type="text" name="bm[<?= e($id) ?>][role]" value="<?= e($p['role'] ?? '') ?>" maxlength="120">
                </div>
            </div>
            <?php photo_field('bm_photo_' . $id, (string)($p['photo'] ?? ''), 'Photo (optional)'); ?>
        </div>
<?php endforeach; ?>
        <button class="btn ghost small" type="submit" form="add-bom">+ Add another board member</button>
    </div>

    <div class="card">
        <h2>Key contacts</h2>
        <p class="card-note">The three people shown at the bottom of the Contact section.</p>
<?php foreach ($staff['key_contacts'] as $p): $id = (string)$p['id']; ?>
        <div class="item">
            <div class="item-head">
                <strong><?= e($p['name'] ?: 'Unnamed') ?></strong>
                <?php remove_btn('key_contacts', $id); ?>
            </div>
            <div class="row2">
                <div class="field">
                    <label class="lbl">Name</label>
                    <input type="text" name="kc[<?= e($id) ?>][name]" value="<?= e($p['name'] ?? '') ?>" maxlength="120">
                </div>
                <div class="field">
                    <label class="lbl">Role</label>
                    <input type="text" name="kc[<?= e($id) ?>][role]" value="<?= e($p['role'] ?? '') ?>" maxlength="120">
                </div>
            </div>
            <div class="row2">
                <div class="field">
                    <label class="lbl">Phone</label>
                    <input type="text" name="kc[<?= e($id) ?>][phone]" value="<?= e($p['phone'] ?? '') ?>" maxlength="40">
                </div>
                <div class="field">
                    <label class="lbl">Email</label>
                    <input type="text" name="kc[<?= e($id) ?>][email]" value="<?= e($p['email'] ?? '') ?>" maxlength="160">
                    <span class="hint">Shown only if there is no phone number.</span>
                </div>
            </div>
            <?php photo_field('kc_photo_' . $id, (string)($p['photo'] ?? ''), 'Photo (optional)'); ?>
        </div>
<?php endforeach; ?>
        <button class="btn ghost small" type="submit" form="add-kc">+ Add another contact</button>
    </div>

    <div class="save-bar">
        <button class="btn" type="submit">Save all staff details</button>
        <span class="hint" style="margin:0">Photos upload when you save.</span>
    </div>
</form>

<?php
foreach ($staff['leadership']   as $p) { remove_form('leadership',   (string)$p['id']); }
foreach ($staff['staff']        as $p) { remove_form('staff',        (string)$p['id']); }
foreach ($staff['bom']          as $p) { remove_form('bom',          (string)$p['id']); }
foreach ($staff['key_contacts'] as $p) { remove_form('key_contacts', (string)$p['id']); }
?>
<form id="add-staff" method="post" hidden><?= csrf_field() ?>
    <input type="hidden" name="action" value="add"><input type="hidden" name="group" value="staff"></form>
<form id="add-bom" method="post" hidden><?= csrf_field() ?>
    <input type="hidden" name="action" value="add"><input type="hidden" name="group" value="bom"></form>
<form id="add-kc" method="post" hidden><?= csrf_field() ?>
    <input type="hidden" name="action" value="add"><input type="hidden" name="group" value="key_contacts"></form>
<?php
admin_foot();
