<?php
/**
 * Page text editor — every heading and paragraph on the homepage.
 *
 * Repeating lists (fees, subjects, calendar dates…) are shown as the rows that
 * exist plus a few blank spares. Filling a spare adds a row; clearing a row
 * deletes it. No buttons to learn, and it works without JavaScript.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/sitelib/admin_layout.php';

$user = auth_require();
$c    = data_load('content');

const SPARE_ROWS = 3;

/**
 * Collect a repeating group out of $_POST, dropping rows the editor left blank.
 * $keys are the sub-fields, e.g. ['item', 'amount'].
 */
function collect_rows(string $group, array $keys): array
{
    $raw  = (array)($_POST[$group] ?? []);
    $rows = [];
    foreach ($raw as $row) {
        if (!is_array($row)) {
            continue;
        }
        $clean = [];
        $any   = false;
        foreach ($keys as $k) {
            $v = trim((string)($row[$k] ?? ''));
            $clean[$k] = $v;
            if ($v !== '') {
                $any = true;
            }
        }
        if ($any) {
            $rows[] = $clean;
        }
    }
    return $rows;
}

/** One line per entry, blanks dropped. */
function collect_lines(string $field): array
{
    $text  = (string)($_POST[$field] ?? '');
    $lines = preg_split('/\R/', $text) ?: [];
    return array_values(array_filter(array_map('trim', $lines), static fn($l) => $l !== ''));
}

/** Blank line between paragraphs. */
function collect_paragraphs(string $field): array
{
    $text  = trim((string)($_POST[$field] ?? ''));
    $parts = preg_split('/\R\s*\R/', $text) ?: [];
    return array_values(array_filter(array_map(
        static fn($p) => trim(preg_replace('/\s*\R\s*/', ' ', $p) ?? ''),
        $parts
    ), static fn($p) => $p !== ''));
}

function s(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $c['site']['title']      = s('site_title');
    $c['site']['name_line1'] = s('name_line1');
    $c['site']['name_line2'] = s('name_line2');

    $c['hero'] = [
        'badge'         => s('hero_badge'),
        'title_line1'   => s('hero_title1'),
        'title_line2'   => s('hero_title2'),
        'subtitle'      => s('hero_subtitle'),
        'motto'         => s('hero_motto'),
        'btn_primary'   => s('hero_btn1'),
        'btn_secondary' => s('hero_btn2'),
    ];

    $c['stats'] = collect_rows('stats', ['number', 'label']);

    $c['about'] = array_merge((array)($c['about'] ?? []), [
        'badge'        => s('about_badge'),
        'heading'      => s('about_heading'),
        'paragraphs'   => collect_paragraphs('about_paragraphs'),
        'features'     => collect_rows('features', ['icon', 'label']),
        'badge_number' => s('about_badge_number'),
        'badge_label'  => s('about_badge_label'),
    ]);

    $c['administration'] = array_merge((array)($c['administration'] ?? []), [
        'badge'    => s('admin_badge'),
        'title'    => s('admin_title'),
        'subtitle' => s('admin_subtitle'),
    ]);

    $c['vision'] = [
        'badge' => s('vision_badge'),
        'title' => s('vision_title'),
        'cards' => collect_rows('vision', ['icon', 'heading', 'text']),
    ];

    $c['academics'] = [
        'badge'         => s('acad_badge'),
        'title'         => s('acad_title'),
        'subtitle'      => s('acad_subtitle'),
        'subjects'      => collect_lines('subjects'),
        'results_title' => s('results_title'),
        'results'       => collect_rows('results', ['value', 'label']),
    ];

    $c['admissions'] = [
        'badge'              => s('adm_badge'),
        'title'              => s('adm_title'),
        'subtitle'           => s('adm_subtitle'),
        'steps'              => collect_rows('steps', ['heading', 'text']),
        'fees_title'         => s('fees_title'),
        'fees_subtitle'      => s('fees_subtitle'),
        'fees'               => collect_rows('fees', ['item', 'amount']),
        'fees_total_label'   => s('fees_total_label'),
        'fees_total_amount'  => s('fees_total_amount'),
        'term_fees_title'    => s('term_fees_title'),
        'term_fees'          => collect_rows('termfees', ['item', 'amount']),
        'fees_note'          => s('fees_note'),
        'bank'               => [
            'heading'          => s('bank_heading'),
            'bank_name'        => s('bank_name'),
            'account_name'     => s('bank_account_name'),
            'account_number'   => s('bank_account_number'),
            'branch'           => s('bank_branch'),
            'paybill_heading'  => s('paybill_heading'),
            'paybill_business' => s('paybill_business'),
            'paybill_account'  => s('paybill_account'),
        ],
    ];

    $c['life'] = [
        'badge'            => s('life_badge'),
        'title'            => s('life_title'),
        'subtitle'         => s('life_subtitle'),
        'sports'           => collect_rows('sports', ['icon', 'heading', 'text']),
        'clubs'            => collect_rows('clubs', ['heading', 'text']),
        'facilities_title' => s('facilities_title'),
    ];

    // Calendar: each term keeps its own list of dated rows.
    $terms = [];
    foreach ((array)($_POST['terms'] ?? []) as $ti => $term) {
        $name = trim((string)($term['name'] ?? ''));
        $dates = [];
        foreach ((array)($term['dates'] ?? []) as $d) {
            $label = trim((string)($d['label'] ?? ''));
            $value = trim((string)($d['value'] ?? ''));
            if ($label !== '' || $value !== '') {
                $dates[] = ['label' => $label, 'value' => $value];
            }
        }
        if ($name !== '' || $dates) {
            $terms[] = ['name' => $name, 'dates' => $dates];
        }
    }
    $c['calendar'] = [
        'badge' => s('cal_badge'),
        'title' => s('cal_title'),
        'terms' => $terms,
    ];

    $c['contact'] = [
        'badge'                  => s('contact_badge'),
        'title'                  => s('contact_title'),
        'location'               => trim((string)($_POST['contact_location'] ?? '')),
        'phone_primary'          => s('phone1'),
        'phone_primary_label'    => s('phone1_label'),
        'phone_secondary'        => s('phone2'),
        'phone_secondary_label'  => s('phone2_label'),
        'email'                  => s('contact_email'),
        'postal'                 => trim((string)($_POST['contact_postal'] ?? '')),
    ];

    $c['cta'] = [
        'heading'       => s('cta_heading'),
        'text'          => s('cta_text'),
        'btn_primary'   => s('cta_btn1'),
        'btn_secondary' => s('cta_btn2'),
    ];

    $c['footer'] = [
        'brand_text'   => s('footer_text'),
        'brand_motto'  => s('footer_motto'),
        'address_line' => s('footer_address'),
        'copyright'    => s('footer_copyright'),
        'diocese'      => s('footer_diocese'),
    ];

    if (data_save('content', $c)) {
        flash('Page text saved.');
    } else {
        flash('Could not save changes.', 'bad');
    }
    header('Location: content.php#' . preg_replace('/[^a-z]/', '', (string)($_POST['back_to'] ?? '')));
    exit;
}

/**
 * Render a repeating group: existing rows plus blank spares.
 * $cols is ['postKey' => ['Label', 'placeholder', widthFraction]].
 */
function rows_editor(string $group, array $items, array $cols, int $spares = SPARE_ROWS): void
{
    $rows = array_values($items);
    for ($i = 0; $i < $spares; $i++) {
        $rows[] = [];
    }
    $grid = 'display:grid;grid-template-columns:' . implode(' ', array_map(
        static fn($c) => ($c[2] ?? 1) . 'fr',
        $cols
    )) . ';gap:.6rem;margin-bottom:.5rem';

    echo '<div style="' . $grid . ';font-size:.78rem;font-weight:600;color:var(--burgundy)">';
    foreach ($cols as $c) {
        echo '<div>' . e($c[0]) . '</div>';
    }
    echo '</div>';

    foreach ($rows as $i => $row) {
        echo '<div style="' . $grid . '">';
        foreach ($cols as $key => $c) {
            printf(
                '<input type="text" name="%s[%d][%s]" value="%s" placeholder="%s" maxlength="300">',
                e($group), $i, e($key), e((string)($row[$key] ?? '')), e($c[1] ?? '')
            );
        }
        echo '</div>';
    }
    echo '<span class="hint">Fill a blank row to add an entry. Clear a row completely to delete it.</span>';
}

admin_head('Page Text', $user);
admin_title('Page Text', 'Every heading and paragraph on the homepage. Change what you need and press Save at the bottom.');
?>

<form method="post">
    <?= csrf_field() ?>

    <div class="card" id="top">
        <h2>School name &amp; browser title</h2>
        <div class="field">
            <label class="lbl">Browser tab title / footer heading</label>
            <input type="text" name="site_title" value="<?= e(cfg($c, 'site.title')) ?>" maxlength="200">
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Top-left name, line 1</label>
                <input type="text" name="name_line1" value="<?= e(cfg($c, 'site.name_line1')) ?>" maxlength="120">
            </div>
            <div class="field">
                <label class="lbl">Top-left name, line 2</label>
                <input type="text" name="name_line2" value="<?= e(cfg($c, 'site.name_line2')) ?>" maxlength="120">
            </div>
        </div>
    </div>

    <div class="card" id="hero">
        <h2>Top banner</h2>
        <div class="field">
            <label class="lbl">Small badge</label>
            <input type="text" name="hero_badge" value="<?= e(cfg($c, 'hero.badge')) ?>" maxlength="160">
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Big heading, line 1</label>
                <input type="text" name="hero_title1" value="<?= e(cfg($c, 'hero.title_line1')) ?>" maxlength="120">
            </div>
            <div class="field">
                <label class="lbl">Big heading, line 2</label>
                <input type="text" name="hero_title2" value="<?= e(cfg($c, 'hero.title_line2')) ?>" maxlength="120">
            </div>
        </div>
        <div class="field">
            <label class="lbl">Sentence under the heading</label>
            <textarea name="hero_subtitle" rows="2" maxlength="600"><?= e(cfg($c, 'hero.subtitle')) ?></textarea>
        </div>
        <div class="field">
            <label class="lbl">Motto line</label>
            <input type="text" name="hero_motto" value="<?= e(cfg($c, 'hero.motto')) ?>" maxlength="200">
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Left button</label>
                <input type="text" name="hero_btn1" value="<?= e(cfg($c, 'hero.btn_primary')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Right button</label>
                <input type="text" name="hero_btn2" value="<?= e(cfg($c, 'hero.btn_secondary')) ?>" maxlength="60">
            </div>
        </div>
    </div>

    <div class="card" id="stats">
        <h2>Headline numbers</h2>
        <p class="card-note">The gold strip just below the banner.</p>
        <?php rows_editor('stats', (array)($c['stats'] ?? []), [
            'number' => ['Number', 'e.g. 124', 1],
            'label'  => ['Label', 'e.g. 2025 KCSE Candidates', 2],
        ]); ?>
    </div>

    <div class="card" id="about">
        <h2>About section</h2>
        <div class="row2">
            <div class="field">
                <label class="lbl">Small label</label>
                <input type="text" name="about_badge" value="<?= e(cfg($c, 'about.badge')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Heading</label>
                <input type="text" name="about_heading" value="<?= e(cfg($c, 'about.heading')) ?>" maxlength="200">
            </div>
        </div>
        <div class="field">
            <label class="lbl">Paragraphs</label>
            <textarea name="about_paragraphs" rows="10" maxlength="6000"><?= e(implode("\n\n", (array)cfg($c, 'about.paragraphs', []))) ?></textarea>
            <span class="hint">Leave one blank line between paragraphs.</span>
        </div>
        <h3>Feature boxes</h3>
        <?php rows_editor('features', (array)cfg($c, 'about.features', []), [
            'icon'  => ['Symbol', '📚', 1],
            'label' => ['Text', 'e.g. Full Boarding', 4],
        ]); ?>
        <h3>Corner badge on the photo</h3>
        <div class="row2">
            <div class="field">
                <label class="lbl">Big number</label>
                <input type="text" name="about_badge_number" value="<?= e(cfg($c, 'about.badge_number')) ?>" maxlength="20">
            </div>
            <div class="field">
                <label class="lbl">Word under it</label>
                <input type="text" name="about_badge_label" value="<?= e(cfg($c, 'about.badge_label')) ?>" maxlength="40">
            </div>
        </div>
    </div>

    <div class="card" id="administration">
        <h2>Administration section wording</h2>
        <p class="card-note">Names and photos of staff are on the <a href="staff.php">Staff page</a>.</p>
        <div class="row2">
            <div class="field">
                <label class="lbl">Small label</label>
                <input type="text" name="admin_badge" value="<?= e(cfg($c, 'administration.badge')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Heading</label>
                <input type="text" name="admin_title" value="<?= e(cfg($c, 'administration.title')) ?>" maxlength="200">
            </div>
        </div>
        <div class="field">
            <label class="lbl">Sub-heading</label>
            <textarea name="admin_subtitle" rows="2" maxlength="600"><?= e(cfg($c, 'administration.subtitle')) ?></textarea>
        </div>
    </div>

    <div class="card" id="vision">
        <h2>Vision, Mission &amp; Motto</h2>
        <div class="row2">
            <div class="field">
                <label class="lbl">Small label</label>
                <input type="text" name="vision_badge" value="<?= e(cfg($c, 'vision.badge')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Heading</label>
                <input type="text" name="vision_title" value="<?= e(cfg($c, 'vision.title')) ?>" maxlength="200">
            </div>
        </div>
        <?php rows_editor('vision', (array)cfg($c, 'vision.cards', []), [
            'icon'    => ['Symbol', '🎯', 1],
            'heading' => ['Heading', 'Our Vision', 2],
            'text'    => ['Text', 'The statement itself', 4],
        ], 1); ?>
    </div>

    <div class="card" id="academics">
        <h2>Academics</h2>
        <div class="row2">
            <div class="field">
                <label class="lbl">Small label</label>
                <input type="text" name="acad_badge" value="<?= e(cfg($c, 'academics.badge')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Heading</label>
                <input type="text" name="acad_title" value="<?= e(cfg($c, 'academics.title')) ?>" maxlength="200">
            </div>
        </div>
        <div class="field">
            <label class="lbl">Sub-heading</label>
            <textarea name="acad_subtitle" rows="2" maxlength="600"><?= e(cfg($c, 'academics.subtitle')) ?></textarea>
        </div>
        <div class="field">
            <label class="lbl">Subjects offered</label>
            <textarea name="subjects" rows="8" maxlength="2000"><?= e(implode("\n", (array)cfg($c, 'academics.subjects', []))) ?></textarea>
            <span class="hint">One subject per line. The ✓ is added automatically.</span>
        </div>
        <div class="field">
            <label class="lbl">Results heading</label>
            <input type="text" name="results_title" value="<?= e(cfg($c, 'academics.results_title')) ?>" maxlength="200">
        </div>
        <h3>Results figures</h3>
        <?php rows_editor('results', (array)cfg($c, 'academics.results', []), [
            'value' => ['Figure', 'e.g. A-', 1],
            'label' => ['Label', 'e.g. Top Grade', 3],
        ]); ?>
    </div>

    <div class="card" id="admissions">
        <h2>Admissions</h2>
        <div class="row2">
            <div class="field">
                <label class="lbl">Small label</label>
                <input type="text" name="adm_badge" value="<?= e(cfg($c, 'admissions.badge')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Heading</label>
                <input type="text" name="adm_title" value="<?= e(cfg($c, 'admissions.title')) ?>" maxlength="200">
            </div>
        </div>
        <div class="field">
            <label class="lbl">Sub-heading</label>
            <textarea name="adm_subtitle" rows="2" maxlength="600"><?= e(cfg($c, 'admissions.subtitle')) ?></textarea>
        </div>

        <h3>Admission steps</h3>
        <p class="card-note">Numbered automatically in the order listed.</p>
        <?php rows_editor('steps', (array)cfg($c, 'admissions.steps', []), [
            'heading' => ['Step title', 'e.g. Pay Fees to Bank', 2],
            'text'    => ['Explanation', 'One or two sentences', 4],
        ], 2); ?>

        <h3>Fee structure</h3>
        <div class="row2">
            <div class="field">
                <label class="lbl">Fees card heading</label>
                <input type="text" name="fees_title" value="<?= e(cfg($c, 'admissions.fees_title')) ?>" maxlength="120">
            </div>
            <div class="field">
                <label class="lbl">Fees card sub-heading</label>
                <input type="text" name="fees_subtitle" value="<?= e(cfg($c, 'admissions.fees_subtitle')) ?>" maxlength="120">
            </div>
        </div>
        <?php rows_editor('fees', (array)cfg($c, 'admissions.fees', []), [
            'item'   => ['Item', 'e.g. Boarding', 2],
            'amount' => ['Amount', 'e.g. Ksh 30,035', 1],
        ]); ?>
        <div class="row2" style="margin-top:1rem">
            <div class="field">
                <label class="lbl">Total row label</label>
                <input type="text" name="fees_total_label" value="<?= e(cfg($c, 'admissions.fees_total_label')) ?>" maxlength="120">
            </div>
            <div class="field">
                <label class="lbl">Total amount</label>
                <input type="text" name="fees_total_amount" value="<?= e(cfg($c, 'admissions.fees_total_amount')) ?>" maxlength="60">
                <span class="hint">Not calculated — type the correct total yourself.</span>
            </div>
        </div>

        <h3>Payment per term</h3>
        <div class="field">
            <label class="lbl">Heading</label>
            <input type="text" name="term_fees_title" value="<?= e(cfg($c, 'admissions.term_fees_title')) ?>" maxlength="120">
        </div>
        <?php rows_editor('termfees', (array)cfg($c, 'admissions.term_fees', []), [
            'item'   => ['Term', 'e.g. Term 1', 2],
            'amount' => ['Amount', 'e.g. Ksh 21,535', 1],
        ], 1); ?>
        <div class="field" style="margin-top:1rem">
            <label class="lbl">Note under the fees</label>
            <input type="text" name="fees_note" value="<?= e(cfg($c, 'admissions.fees_note')) ?>" maxlength="400">
        </div>

        <h3>Bank &amp; PayBill details</h3>
        <div class="field">
            <label class="lbl">Bank box heading</label>
            <input type="text" name="bank_heading" value="<?= e(cfg($c, 'admissions.bank.heading')) ?>" maxlength="120">
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Bank</label>
                <input type="text" name="bank_name" value="<?= e(cfg($c, 'admissions.bank.bank_name')) ?>" maxlength="160">
            </div>
            <div class="field">
                <label class="lbl">Branch</label>
                <input type="text" name="bank_branch" value="<?= e(cfg($c, 'admissions.bank.branch')) ?>" maxlength="120">
            </div>
        </div>
        <div class="field">
            <label class="lbl">Account name</label>
            <input type="text" name="bank_account_name" value="<?= e(cfg($c, 'admissions.bank.account_name')) ?>" maxlength="200">
        </div>
        <div class="field">
            <label class="lbl">Account number</label>
            <input type="text" name="bank_account_number" value="<?= e(cfg($c, 'admissions.bank.account_number')) ?>" maxlength="60">
        </div>
        <div class="field">
            <label class="lbl">PayBill heading</label>
            <input type="text" name="paybill_heading" value="<?= e(cfg($c, 'admissions.bank.paybill_heading')) ?>" maxlength="120">
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Business number</label>
                <input type="text" name="paybill_business" value="<?= e(cfg($c, 'admissions.bank.paybill_business')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Account number format</label>
                <input type="text" name="paybill_account" value="<?= e(cfg($c, 'admissions.bank.paybill_account')) ?>" maxlength="120">
            </div>
        </div>
    </div>

    <div class="card" id="life">
        <h2>Student Life</h2>
        <p class="card-note">Photos in this section are on the <a href="photos.php">Photos page</a>.</p>
        <div class="row2">
            <div class="field">
                <label class="lbl">Small label</label>
                <input type="text" name="life_badge" value="<?= e(cfg($c, 'life.badge')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Heading</label>
                <input type="text" name="life_title" value="<?= e(cfg($c, 'life.title')) ?>" maxlength="200">
            </div>
        </div>
        <div class="field">
            <label class="lbl">Sub-heading</label>
            <textarea name="life_subtitle" rows="2" maxlength="600"><?= e(cfg($c, 'life.subtitle')) ?></textarea>
        </div>
        <h3>Sports</h3>
        <?php rows_editor('sports', (array)cfg($c, 'life.sports', []), [
            'icon'    => ['Symbol', '⚽', 1],
            'heading' => ['Sport', 'e.g. Football', 2],
            'text'    => ['Note', 'e.g. Inter-school competitions', 3],
        ]); ?>
        <h3>Clubs &amp; societies</h3>
        <?php rows_editor('clubs', (array)cfg($c, 'life.clubs', []), [
            'heading' => ['Club', 'e.g. Debate Club', 2],
            'text'    => ['Note', 'e.g. Public speaking', 3],
        ]); ?>
        <div class="field" style="margin-top:1rem">
            <label class="lbl">Facilities heading</label>
            <input type="text" name="facilities_title" value="<?= e(cfg($c, 'life.facilities_title')) ?>" maxlength="120">
        </div>
    </div>

    <div class="card" id="calendar">
        <h2>Academic calendar</h2>
        <div class="row2">
            <div class="field">
                <label class="lbl">Small label</label>
                <input type="text" name="cal_badge" value="<?= e(cfg($c, 'calendar.badge')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Heading</label>
                <input type="text" name="cal_title" value="<?= e(cfg($c, 'calendar.title')) ?>" maxlength="200">
            </div>
        </div>
<?php
$terms = (array)cfg($c, 'calendar.terms', []);
$terms[] = ['name' => '', 'dates' => []];   // one spare term
foreach ($terms as $ti => $term):
    $dates = (array)($term['dates'] ?? []);
    // Two blank spares. The bound must be fixed up front — testing against
    // count($dates) while appending to it never terminates.
    for ($k = 0; $k < 2; $k++) {
        $dates[] = ['label' => '', 'value' => ''];
    }
?>
        <div class="item">
            <div class="field">
                <label class="lbl">Term name</label>
                <input type="text" name="terms[<?= (int)$ti ?>][name]" value="<?= e((string)($term['name'] ?? '')) ?>" maxlength="60" placeholder="e.g. Term 1">
            </div>
<?php foreach ($dates as $di => $d): ?>
            <div class="row2">
                <input type="text" name="terms[<?= (int)$ti ?>][dates][<?= (int)$di ?>][label]" value="<?= e((string)($d['label'] ?? '')) ?>" placeholder="Opening / Half Term / Closing" maxlength="60">
                <input type="text" name="terms[<?= (int)$ti ?>][dates][<?= (int)$di ?>][value]" value="<?= e((string)($d['value'] ?? '')) ?>" placeholder="e.g. 6th January 2026" maxlength="120">
            </div>
<?php endforeach; ?>
        </div>
<?php endforeach; ?>
        <span class="hint">Fill the blank term or blank date rows to add more. Clear a row completely to delete it.</span>
    </div>

    <div class="card" id="contact">
        <h2>Contact details</h2>
        <div class="row2">
            <div class="field">
                <label class="lbl">Small label</label>
                <input type="text" name="contact_badge" value="<?= e(cfg($c, 'contact.badge')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Heading</label>
                <input type="text" name="contact_title" value="<?= e(cfg($c, 'contact.title')) ?>" maxlength="200">
            </div>
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Location</label>
                <textarea name="contact_location" rows="3" maxlength="400"><?= e(cfg($c, 'contact.location')) ?></textarea>
                <span class="hint">One line per line of the address.</span>
            </div>
            <div class="field">
                <label class="lbl">Postal address</label>
                <textarea name="contact_postal" rows="3" maxlength="400"><?= e(cfg($c, 'contact.postal')) ?></textarea>
            </div>
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Main phone</label>
                <input type="text" name="phone1" value="<?= e(cfg($c, 'contact.phone_primary')) ?>" maxlength="40">
            </div>
            <div class="field">
                <label class="lbl">Whose number is it?</label>
                <input type="text" name="phone1_label" value="<?= e(cfg($c, 'contact.phone_primary_label')) ?>" maxlength="60">
            </div>
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Second phone</label>
                <input type="text" name="phone2" value="<?= e(cfg($c, 'contact.phone_secondary')) ?>" maxlength="40">
            </div>
            <div class="field">
                <label class="lbl">Whose number is it?</label>
                <input type="text" name="phone2_label" value="<?= e(cfg($c, 'contact.phone_secondary_label')) ?>" maxlength="60">
            </div>
        </div>
        <div class="field">
            <label class="lbl">Email address</label>
            <input type="text" name="contact_email" value="<?= e(cfg($c, 'contact.email')) ?>" maxlength="160">
        </div>
    </div>

    <div class="card" id="cta">
        <h2>Closing banner</h2>
        <div class="field">
            <label class="lbl">Heading</label>
            <input type="text" name="cta_heading" value="<?= e(cfg($c, 'cta.heading')) ?>" maxlength="200">
        </div>
        <div class="field">
            <label class="lbl">Text</label>
            <textarea name="cta_text" rows="2" maxlength="600"><?= e(cfg($c, 'cta.text')) ?></textarea>
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Left button</label>
                <input type="text" name="cta_btn1" value="<?= e(cfg($c, 'cta.btn_primary')) ?>" maxlength="60">
            </div>
            <div class="field">
                <label class="lbl">Right button</label>
                <input type="text" name="cta_btn2" value="<?= e(cfg($c, 'cta.btn_secondary')) ?>" maxlength="60">
            </div>
        </div>
    </div>

    <div class="card" id="footer">
        <h2>Footer</h2>
        <div class="field">
            <label class="lbl">Description</label>
            <textarea name="footer_text" rows="2" maxlength="600"><?= e(cfg($c, 'footer.brand_text')) ?></textarea>
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Motto line</label>
                <input type="text" name="footer_motto" value="<?= e(cfg($c, 'footer.brand_motto')) ?>" maxlength="200">
            </div>
            <div class="field">
                <label class="lbl">Address in the Contact Info column</label>
                <input type="text" name="footer_address" value="<?= e(cfg($c, 'footer.address_line')) ?>" maxlength="200">
            </div>
        </div>
        <div class="row2">
            <div class="field">
                <label class="lbl">Copyright line</label>
                <input type="text" name="footer_copyright" value="<?= e(cfg($c, 'footer.copyright')) ?>" maxlength="300">
            </div>
            <div class="field">
                <label class="lbl">Bottom line</label>
                <input type="text" name="footer_diocese" value="<?= e(cfg($c, 'footer.diocese')) ?>" maxlength="200">
            </div>
        </div>
    </div>

    <div class="save-bar">
        <button class="btn" type="submit">Save page text</button>
        <span class="hint" style="margin:0">Saves everything on this page.</span>
    </div>
</form>
<?php
admin_foot();
