<?php
/**
 * Public homepage. All wording, photos, staff and news come from the JSON
 * files in /home/tizapdoz/sitedata, which the admin panel edits.
 *
 * The markup below deliberately mirrors the original hand-built index.html
 * (including the vision-mission section wrapping the administration section)
 * so the design renders exactly as before.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/sitelib/bootstrap.php';

$c       = data_load('content');
$staff   = data_load('staff');
$gallery = data_load('gallery');
$news    = data_load('news');

$newsItems = array_values(array_filter(
    (array)($news['items'] ?? []),
    static fn($n) => trim((string)($n['title'] ?? '')) !== ''
));
// Pinned first, then newest date first.
usort($newsItems, static function ($a, $b) {
    $pin = (int)!empty($b['pinned']) <=> (int)!empty($a['pinned']);
    return $pin !== 0 ? $pin : strcmp((string)($b['date'] ?? ''), (string)($a['date'] ?? ''));
});
$hasNews = count($newsItems) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="St. John the Baptist Likuyani Boys High School - A prestigious Catholic boys' boarding school in Kakamega County, Kenya. Excellence in academics, moral formation, and spiritual growth since 1972.">
    <meta name="keywords" content="Likuyani Boys, high school Kenya, boarding school, Catholic education, KCSE, Kakamega County">
    <meta name="author" content="St. John the Baptist Likuyani Boys High School">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://likuyaniboys.edu.ke/">

    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?= e(cfg($c, 'site.title')) ?>">
    <meta property="og:description" content="Nurturing young men of excellence through rigorous academics, moral formation, and spiritual growth since 1972.">
    <meta property="og:image" content="https://likuyaniboys.edu.ke/images/adminstration_block.jpeg">
    <meta property="og:url" content="https://likuyaniboys.edu.ke/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(cfg($c, 'site.title')) ?>">

    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e(cfg($c, 'site.title')) ?>">
    <meta name="twitter:description" content="A prestigious Catholic boys' boarding school in Kakamega County. Excellence in academics, character development, and spiritual growth.">
    <meta name="twitter:image" content="https://likuyaniboys.edu.ke/images/adminstration_block.jpeg">

    <!-- Additional SEO Meta Tags -->
    <meta name="language" content="English">
    <meta name="revisit-after" content="7 days">
    <meta name="googlebot" content="index, follow">

    <title><?= e(cfg($c, 'site.title')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= (int)@filemtime(__DIR__ . '/assets/style.css') ?>">

    <!-- Structured Data (Schema.org JSON-LD) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "EducationalOrganization",
        "name": "<?= e(cfg($c, 'site.title')) ?>",
        "url": "https://likuyaniboys.edu.ke",
        "logo": "https://likuyaniboys.edu.ke/assets/logo.png",
        "description": "A prestigious Catholic boys' boarding school in Kakamega County, Kenya. Excellence in academics, moral formation, and spiritual growth since 1972.",
        "founded": "1972",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Kitale-Eldoret Highway, Likuyani Sub-County",
            "addressLocality": "Kakamega",
            "addressRegion": "Kakamega County",
            "addressCountry": "KE",
            "postalCode": "50200"
        },
        "telephone": ["0722232528", "0722645854"],
        "email": "stjblikuyaniboys@gmail.com",
        "sameAs": [],
        "image": "https://likuyaniboys.edu.ke/images/adminstration_block.jpeg",
        "slogan": "Discipline, Effort and Success",
        "potentialAction": {
            "@type": "ApplyAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "https://likuyaniboys.edu.ke/#admissions"
            }
        },
        "educationalLevel": "Secondary",
        "totalScore": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "bestRating": "5",
            "worstRating": "1",
            "ratingCount": "127"
        }
    }
    </script>

    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "https://likuyaniboys.edu.ke"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "About",
                "item": "https://likuyaniboys.edu.ke/#about"
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "Admissions",
                "item": "https://likuyaniboys.edu.ke/#admissions"
            }
        ]
    }
    </script>
</head>
<body>
    <!-- Navigation -->
    <nav class="nav" id="nav">
        <a href="#" class="nav-logo">
            <div class="nav-logo-icon">✝</div>
            <div class="nav-logo-text">
                <?= e(cfg($c, 'site.name_line1')) ?>
                <span><?= e(cfg($c, 'site.name_line2')) ?></span>
            </div>
        </a>
        <ul class="nav-links">
            <li><a href="#about">About</a></li>
<?php if ($hasNews): ?>
            <li><a href="#news">News</a></li>
<?php endif; ?>
            <li><a href="#administration">Administration</a></li>
            <li><a href="#academics">Academics</a></li>
            <li><a href="#admissions">Admissions</a></li>
            <li><a href="#life">Student Life</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        <a href="#admissions" class="nav-cta">Apply Now</a>
        <div class="mobile-menu" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-pattern"></div>
        <div class="hero-grid"></div>
        <div class="hero-content">
            <div class="hero-badge">
                <span>✝</span> <?= e(cfg($c, 'hero.badge')) ?>
            </div>
            <h1 class="hero-title">
                <?= e(cfg($c, 'hero.title_line1')) ?><br>
                <span><?= e(cfg($c, 'hero.title_line2')) ?></span>
            </h1>
            <p class="hero-subtitle">
                <?= e(cfg($c, 'hero.subtitle')) ?>
            </p>
            <p class="hero-motto"><?= e(cfg($c, 'hero.motto')) ?></p>
            <div class="hero-buttons">
                <a href="#admissions" class="btn btn-primary"><?= e(cfg($c, 'hero.btn_primary')) ?></a>
                <a href="#about" class="btn btn-secondary"><?= e(cfg($c, 'hero.btn_secondary')) ?></a>
            </div>
        </div>
        <div class="hero-scroll">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12l7 7 7-7"/>
            </svg>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stats-grid">
<?php foreach ((array)($c['stats'] ?? []) as $stat): ?>
                <div class="stat-item">
                    <div class="stat-number"><?= e($stat['number'] ?? '') ?></div>
                    <div class="stat-label"><?= e($stat['label'] ?? '') ?></div>
                </div>
<?php endforeach; ?>
            </div>
        </div>
    </section>

<?php if ($hasNews): ?>
    <!-- News & Announcements -->
    <section class="section news" id="news">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge"><?= e((string)($news['badge'] ?? 'Latest')) ?></span>
                <h2 class="section-title"><?= e((string)($news['title'] ?? 'News &amp; Announcements')) ?></h2>
<?php if (trim((string)($news['subtitle'] ?? '')) !== ''): ?>
                <p class="section-subtitle"><?= e((string)$news['subtitle']) ?></p>
<?php endif; ?>
            </div>
            <div class="news-grid animate-on-scroll">
<?php foreach ($newsItems as $item): ?>
                <article class="news-card<?= !empty($item['pinned']) ? ' pinned' : '' ?>">
<?php if (!empty($item['pinned'])): ?>
                    <span class="news-pin">📌 Pinned</span>
<?php endif; ?>
<?php if (nice_date($item['date'] ?? '') !== ''): ?>
                    <div class="news-date"><?= e(nice_date($item['date'])) ?></div>
<?php endif; ?>
                    <h3 class="news-title"><?= e((string)$item['title']) ?></h3>
<?php if (trim((string)($item['body'] ?? '')) !== ''): ?>
                    <p class="news-body"><?= e_nl((string)$item['body']) ?></p>
<?php endif; ?>
                </article>
<?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

    <!-- About Section -->
    <section class="section about" id="about">
        <div class="container">
            <div class="about-grid">
                <div class="about-content animate-on-scroll">
                    <span class="section-badge"><?= e(cfg($c, 'about.badge')) ?></span>
                    <h3><?= e(cfg($c, 'about.heading')) ?></h3>
<?php foreach ((array)cfg($c, 'about.paragraphs', []) as $para): ?>
                    <p>
                        <?= e((string)$para) ?>
                    </p>
<?php endforeach; ?>
                    <div class="about-features">
<?php foreach ((array)cfg($c, 'about.features', []) as $f): ?>
                        <div class="about-feature">
                            <div class="about-feature-icon"><?= e($f['icon'] ?? '') ?></div>
                            <span><?= e($f['label'] ?? '') ?></span>
                        </div>
<?php endforeach; ?>
                    </div>
                </div>
                <div class="about-image animate-on-scroll">
                    <div style="border-radius: 12px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.15);">
                        <img src="<?= e_url(cfg($c, 'about.image')) ?>" alt="School Campus" style="width: 100%; height: 300px; object-fit: cover; object-position: center;">
                    </div>
                    <div class="about-image-badge">
                        <div class="number"><?= e(cfg($c, 'about.badge_number')) ?></div>
                        <div class="label"><?= e(cfg($c, 'about.badge_label')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Vision, Mission, Motto -->
    <section class="section vision-mission">

    <!-- Administration Section -->
    <section class="section administration" id="administration">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge"><?= e(cfg($c, 'administration.badge')) ?></span>
                <h2 class="section-title"><?= e(cfg($c, 'administration.title')) ?></h2>
                <p class="section-subtitle">
                    <?= e(cfg($c, 'administration.subtitle')) ?>
                </p>
            </div>

<?php if (trim((string)cfg($c, 'administration.block_image')) !== ''): ?>
            <!-- Administration Block Image -->
            <div class="animate-on-scroll" style="margin-bottom: 3rem;">
                <div style="border-radius: 12px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.15);">
                    <img src="<?= e_url(cfg($c, 'administration.block_image')) ?>" alt="<?= e(cfg($c, 'administration.block_caption')) ?>" style="width: 100%; max-height: 450px; object-fit: cover; object-position: center;">
                    <div style="padding: 1rem; background: linear-gradient(135deg, var(--burgundy) 0%, var(--burgundy-dark) 100%); color: var(--white); text-align: center;">
                        <h4 style="margin: 0;"><?= e(cfg($c, 'administration.block_caption')) ?></h4>
                    </div>
                </div>
            </div>
<?php endif; ?>

            <div class="admin-grid">
<?php foreach ((array)($staff['leadership'] ?? []) as $leader): ?>
                <div class="admin-card animate-on-scroll">
                    <div class="admin-photo">
                        <span class="admin-badge"><?= e($leader['badge'] ?? '') ?></span>
<?php if (trim((string)($leader['photo'] ?? '')) !== ''): ?>
                        <img src="<?= e_url($leader['photo']) ?>" alt="<?= e(($leader['name'] ?? '') . ' - ' . ($leader['title'] ?? '')) ?>" style="width:100%;height:350px;object-fit:cover;object-position:<?= e($leader['focus'] ?? 'center') ?>;border-radius:12px;">
<?php else: ?>
                        <div class="admin-photo-placeholder">
                            <span>👤</span>
                            <small><?= e($leader['title'] ?? '') ?></small>
                        </div>
<?php endif; ?>
                    </div>
                    <div class="admin-content">
                        <h3 class="admin-name"><?= e($leader['name'] ?? '') ?></h3>
                        <p class="admin-title"><?= e($leader['title'] ?? '') ?></p>
<?php if (trim((string)($leader['quote'] ?? '')) !== ''): ?>
                        <div class="admin-quote">
                            <p><?= e_nl((string)$leader['quote']) ?></p>
                        </div>
<?php endif; ?>
                        <div class="admin-contact">
<?php if (trim((string)($leader['phone'] ?? '')) !== ''): ?>
                            <a href="tel:<?= e_url(tel_href($leader['phone'])) ?>">📞 <?= e($leader['phone']) ?></a>
<?php endif; ?>
<?php if (trim((string)($leader['email'] ?? '')) !== ''): ?>
                            <a href="mailto:<?= e_url($leader['email']) ?>">✉️ Email</a>
<?php endif; ?>
                        </div>
                    </div>
                </div>
<?php endforeach; ?>
            </div>

            <!-- Additional Staff -->
            <div class="staff-grid animate-on-scroll">
<?php foreach ((array)($staff['staff'] ?? []) as $person): ?>
                <div class="staff-card">
                    <div class="staff-photo">
<?php if (trim((string)($person['photo'] ?? '')) !== ''): ?>
                        <img src="<?= e_url($person['photo']) ?>" alt="<?= e($person['name'] ?? '') ?>">
<?php else: ?>
                        <?= e($person['icon'] ?? '👤') ?>
<?php endif; ?>
                    </div>
                    <h4><?= e($person['name'] ?? '') ?></h4>
                    <p><?= e($person['role'] ?? '') ?></p>
                </div>
<?php endforeach; ?>
            </div>

<?php if (!empty($staff['bom'])): ?>
            <!-- Board of Management -->
            <div style="margin-top: 4rem;">
                <h3 style="text-align: center; color: var(--burgundy); font-size: 1.8rem; margin-bottom: 2rem;"><?= e((string)($staff['bom_title'] ?? 'Board of Management')) ?></h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;" class="animate-on-scroll">
<?php foreach ((array)$staff['bom'] as $member): ?>
                    <div style="background: var(--cream); padding: 2rem; border-radius: 12px; text-align: center;">
<?php if (trim((string)($member['photo'] ?? '')) !== ''): ?>
                        <div style="width: 70px; height: 70px; border-radius: 50%; overflow: hidden; margin: 0 auto 1rem;"><img src="<?= e_url($member['photo']) ?>" alt="<?= e($member['name'] ?? '') ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: <?= e($member['focus'] ?? 'center') ?>;"></div>
<?php else: ?>
                        <div style="width: 70px; height: 70px; background: var(--burgundy); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: var(--gold); font-size: 1.8rem;">👤</div>
<?php endif; ?>
                        <h4 style="color: var(--burgundy); margin-bottom: 0.25rem;"><?= e($member['name'] ?? '') ?></h4>
                        <p style="color: var(--gold); font-weight: 600; font-size: 0.85rem;"><?= e($member['role'] ?? '') ?></p>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
<?php endif; ?>
        </div>
    </section>

    <!-- Vision, Mission, Motto -->
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge"><?= e(cfg($c, 'vision.badge')) ?></span>
                <h2 class="section-title"><?= e(cfg($c, 'vision.title')) ?></h2>
            </div>
            <div class="vm-grid">
<?php foreach ((array)cfg($c, 'vision.cards', []) as $card): ?>
                <div class="vm-card animate-on-scroll">
                    <div class="vm-icon"><?= e($card['icon'] ?? '') ?></div>
                    <h3><?= e($card['heading'] ?? '') ?></h3>
                    <p><?= e($card['text'] ?? '') ?></p>
                </div>
<?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Academics Section -->
    <section class="section academics" id="academics">
        <div class="container academics-content">
            <div class="section-header animate-on-scroll">
                <span class="section-badge"><?= e(cfg($c, 'academics.badge')) ?></span>
                <h2 class="section-title"><?= e(cfg($c, 'academics.title')) ?></h2>
                <p class="section-subtitle">
                    <?= e(cfg($c, 'academics.subtitle')) ?>
                </p>
            </div>

            <div class="subjects-grid animate-on-scroll">
<?php foreach ((array)cfg($c, 'academics.subjects', []) as $subject): ?>
                <div class="subject-item"><span>✓ <?= e((string)$subject) ?></span></div>
<?php endforeach; ?>
            </div>

            <div class="results-highlight animate-on-scroll">
                <div class="results-title">
                    <h3><?= e(cfg($c, 'academics.results_title')) ?></h3>
                </div>
                <div class="results-stats">
<?php foreach ((array)cfg($c, 'academics.results', []) as $r): ?>
                    <div class="result-stat">
                        <div class="value"><?= e($r['value'] ?? '') ?></div>
                        <div class="label"><?= e($r['label'] ?? '') ?></div>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Admissions Section -->
    <section class="section admissions" id="admissions">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge"><?= e(cfg($c, 'admissions.badge')) ?></span>
                <h2 class="section-title"><?= e(cfg($c, 'admissions.title')) ?></h2>
                <p class="section-subtitle">
                    <?= e(cfg($c, 'admissions.subtitle')) ?>
                </p>
            </div>

            <div class="admissions-grid">
                <div class="admission-steps animate-on-scroll">
<?php foreach ((array)cfg($c, 'admissions.steps', []) as $i => $step): ?>
                    <div class="step">
                        <div class="step-number"><?= (int)$i + 1 ?></div>
                        <div class="step-content">
                            <h4><?= e($step['heading'] ?? '') ?></h4>
                            <p><?= e($step['text'] ?? '') ?></p>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>

                <div class="animate-on-scroll">
                    <div class="fees-card">
                        <div class="fees-header">
                            <h3><?= e(cfg($c, 'admissions.fees_title')) ?></h3>
                            <span><?= e(cfg($c, 'admissions.fees_subtitle')) ?></span>
                        </div>
                        <div class="fees-body">
<?php foreach ((array)cfg($c, 'admissions.fees', []) as $fee): ?>
                            <div class="fee-row">
                                <span><?= e($fee['item'] ?? '') ?></span>
                                <span><?= e($fee['amount'] ?? '') ?></span>
                            </div>
<?php endforeach; ?>
                            <div class="fee-row" style="border-top: 2px solid var(--gold); padding-top: 10px; margin-top: 10px;">
                                <span><strong><?= e(cfg($c, 'admissions.fees_total_label')) ?></strong></span>
                                <span><strong><?= e(cfg($c, 'admissions.fees_total_amount')) ?></strong></span>
                            </div>
                        </div>
                        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--cream-dark); border-radius: 8px;">
                            <h4 style="color: var(--burgundy); margin-bottom: 0.5rem;"><?= e(cfg($c, 'admissions.term_fees_title')) ?></h4>
<?php foreach ((array)cfg($c, 'admissions.term_fees', []) as $t): ?>
                            <div class="fee-row"><span><?= e($t['item'] ?? '') ?></span><span><?= e($t['amount'] ?? '') ?></span></div>
<?php endforeach; ?>
                        </div>
<?php if (trim((string)cfg($c, 'admissions.fees_note')) !== ''): ?>
                        <p style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted);"><em><?= e(cfg($c, 'admissions.fees_note')) ?></em></p>
<?php endif; ?>
                    </div>

                    <div class="bank-info">
                        <h4><?= e(cfg($c, 'admissions.bank.heading')) ?></h4>
                        <p><strong>Bank:</strong> <?= e(cfg($c, 'admissions.bank.bank_name')) ?></p>
                        <p><strong>Account Name:</strong> <?= e(cfg($c, 'admissions.bank.account_name')) ?></p>
                        <p class="account-number"><?= e(cfg($c, 'admissions.bank.account_number')) ?></p>
                        <p><strong>Branch:</strong> <?= e(cfg($c, 'admissions.bank.branch')) ?></p>
                        <hr style="margin: 1rem 0; border-color: var(--gold);">
                        <h4><?= e(cfg($c, 'admissions.bank.paybill_heading')) ?></h4>
                        <p><strong>Business No:</strong> <?= e(cfg($c, 'admissions.bank.paybill_business')) ?></p>
                        <p><strong>Account No:</strong> <?= e(cfg($c, 'admissions.bank.paybill_account')) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Student Life Section -->
    <section class="section student-life" id="life">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge"><?= e(cfg($c, 'life.badge')) ?></span>
                <h2 class="section-title"><?= e(cfg($c, 'life.title')) ?></h2>
                <p class="section-subtitle">
                    <?= e(cfg($c, 'life.subtitle')) ?>
                </p>
            </div>

<?php if (!empty($gallery['students'])): ?>
            <!-- Student Photos Gallery -->
            <div class="animate-on-scroll" style="margin-bottom: 3rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
<?php foreach ((array)$gallery['students'] as $photo): ?>
                    <div style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                        <img src="<?= e_url($photo['file'] ?? '') ?>" alt="<?= e($photo['title'] ?? '') ?>" style="width: 100%; height: 250px; object-fit: cover;">
                        <div style="padding: 1rem; background: var(--white);">
                            <h4 style="color: var(--burgundy); margin-bottom: 0.5rem;"><?= e($photo['title'] ?? '') ?></h4>
                            <p style="color: var(--text-muted); font-size: 0.9rem;"><?= e($photo['caption'] ?? '') ?></p>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
<?php endif; ?>

            <div class="life-grid animate-on-scroll">
<?php foreach ((array)cfg($c, 'life.sports', []) as $sport): ?>
                <div class="life-card">
                    <div class="life-icon"><?= e($sport['icon'] ?? '') ?></div>
                    <h4><?= e($sport['heading'] ?? '') ?></h4>
                    <p><?= e($sport['text'] ?? '') ?></p>
                </div>
<?php endforeach; ?>
            </div>

            <div class="clubs-grid animate-on-scroll">
<?php foreach ((array)cfg($c, 'life.clubs', []) as $club): ?>
                <div class="club-item">
                    <h5><?= e($club['heading'] ?? '') ?></h5>
                    <p><?= e($club['text'] ?? '') ?></p>
                </div>
<?php endforeach; ?>
            </div>

<?php if (!empty($gallery['facilities'])): ?>
            <!-- Facilities Section -->
            <div class="animate-on-scroll" style="margin-top: 3rem;">
                <h3 style="text-align: center; color: var(--burgundy); font-size: 1.8rem; margin-bottom: 2rem;"><?= e(cfg($c, 'life.facilities_title')) ?></h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
<?php foreach ((array)$gallery['facilities'] as $photo): ?>
                    <div style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                        <img src="<?= e_url($photo['file'] ?? '') ?>" alt="<?= e($photo['title'] ?? '') ?>" style="width: 100%; height: 250px; object-fit: cover;">
                        <div style="padding: 1rem; background: var(--white);">
                            <h4 style="color: var(--burgundy); margin-bottom: 0.5rem;"><?= e($photo['title'] ?? '') ?></h4>
                            <p style="color: var(--text-muted); font-size: 0.9rem;"><?= e($photo['caption'] ?? '') ?></p>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
<?php endif; ?>
        </div>
    </section>

    <!-- Academic Calendar -->
    <section class="section calendar">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge"><?= e(cfg($c, 'calendar.badge')) ?></span>
                <h2 class="section-title"><?= e(cfg($c, 'calendar.title')) ?></h2>
            </div>

            <div class="calendar-grid animate-on-scroll">
<?php foreach ((array)cfg($c, 'calendar.terms', []) as $term): ?>
                <div class="term-card">
                    <div class="term-header">
                        <h3><?= e($term['name'] ?? '') ?></h3>
                    </div>
                    <div class="term-body">
<?php foreach ((array)($term['dates'] ?? []) as $d): ?>
                        <div class="term-date">
                            <strong><?= e($d['label'] ?? '') ?></strong>
                            <span><?= e($d['value'] ?? '') ?></span>
                        </div>
<?php endforeach; ?>
                    </div>
                </div>
<?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section contact" id="contact">
        <div class="container contact-content">
            <div class="section-header animate-on-scroll">
                <span class="section-badge"><?= e(cfg($c, 'contact.badge')) ?></span>
                <h2 class="section-title"><?= e(cfg($c, 'contact.title')) ?></h2>
            </div>

            <div class="contact-grid animate-on-scroll">
                <div class="contact-card">
                    <div class="contact-icon">📍</div>
                    <h4>Location</h4>
                    <p><?= e_nl(cfg($c, 'contact.location')) ?></p>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">📞</div>
                    <h4>Phone</h4>
                    <p><a href="tel:<?= e_url(tel_href(cfg($c, 'contact.phone_primary'))) ?>"><?= e(cfg($c, 'contact.phone_primary')) ?></a> (<?= e(cfg($c, 'contact.phone_primary_label')) ?>)<br><a href="tel:<?= e_url(tel_href(cfg($c, 'contact.phone_secondary'))) ?>"><?= e(cfg($c, 'contact.phone_secondary')) ?></a> (<?= e(cfg($c, 'contact.phone_secondary_label')) ?>)</p>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">✉️</div>
                    <h4>Email</h4>
                    <p><a href="mailto:<?= e_url(cfg($c, 'contact.email')) ?>"><?= e(cfg($c, 'contact.email')) ?></a></p>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">📬</div>
                    <h4>Postal Address</h4>
                    <p><?= e_nl(cfg($c, 'contact.postal')) ?></p>
                </div>
            </div>

            <div class="key-contacts animate-on-scroll">
<?php foreach ((array)($staff['key_contacts'] ?? []) as $kc): ?>
                <div class="key-contact">
                    <div class="key-contact-avatar">
<?php if (trim((string)($kc['photo'] ?? '')) !== ''): ?>
<img src="<?= e_url($kc['photo']) ?>" alt="<?= e($kc['name'] ?? '') ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;object-position:<?= e($kc['focus'] ?? 'center') ?>;">
<?php else: ?>
<?= e($kc['icon'] ?? '👤') ?>
<?php endif; ?>
                    </div>
                    <h4><?= e($kc['name'] ?? '') ?></h4>
                    <p class="role"><?= e($kc['role'] ?? '') ?></p>
<?php if (trim((string)($kc['phone'] ?? '')) !== ''): ?>
                    <p class="phone">📞 <?= e($kc['phone']) ?></p>
<?php elseif (trim((string)($kc['email'] ?? '')) !== ''): ?>
                    <p class="phone">✉️ <a href="mailto:<?= e_url($kc['email']) ?>"><?= e($kc['email']) ?></a></p>
<?php endif; ?>
                </div>
<?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Banner -->
    <section class="cta-banner">
        <div class="cta-content">
            <h2><?= e(cfg($c, 'cta.heading')) ?></h2>
            <p><?= e(cfg($c, 'cta.text')) ?></p>
            <div class="cta-buttons">
                <a href="#admissions" class="btn cta-btn-dark"><?= e(cfg($c, 'cta.btn_primary')) ?></a>
                <a href="tel:<?= e_url(tel_href(cfg($c, 'contact.phone_primary'))) ?>" class="btn cta-btn-light"><?= e(cfg($c, 'cta.btn_secondary')) ?></a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3><?= e(cfg($c, 'site.title')) ?></h3>
                    <p><?= e(cfg($c, 'footer.brand_text')) ?></p>
                    <p><em><?= e(cfg($c, 'footer.brand_motto')) ?></em></p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#about">About Us</a></li>
<?php if ($hasNews): ?>
                        <li><a href="#news">News</a></li>
<?php endif; ?>
                        <li><a href="#administration">Administration</a></li>
                        <li><a href="#academics">Academics</a></li>
                        <li><a href="#admissions">Admissions</a></li>
                        <li><a href="#life">Student Life</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Academics</h4>
                    <ul class="footer-links">
                        <li><a href="#academics">8-4-4 / CBE Curriculum</a></li>
                        <li><a href="#academics">KCSE Results</a></li>
                        <li><a href="#">Academic Calendar</a></li>
                        <li><a href="#administration">Administration</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <ul class="footer-links">
                        <li><?= e(cfg($c, 'footer.address_line')) ?></li>
                        <li><a href="tel:<?= e_url(tel_href(cfg($c, 'contact.phone_primary'))) ?>"><?= e(cfg($c, 'contact.phone_primary')) ?></a></li>
                        <li><a href="mailto:<?= e_url(cfg($c, 'contact.email')) ?>"><?= e(cfg($c, 'contact.email')) ?></a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p><?= e(cfg($c, 'footer.copyright')) ?></p>
                <p><?= e(cfg($c, 'footer.diocese')) ?></p>
            </div>
        </div>
    </footer>

    <script src="assets/site.js?v=<?= (int)@filemtime(__DIR__ . '/assets/site.js') ?>"></script>
</body>
</html>
