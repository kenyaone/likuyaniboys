<?php
/**
 * St. John the Baptist Likuyani Boys High School — site core.
 * Shared by the public site (public_html/index.php) and the admin panel.
 * Lives outside public_html so it can never be downloaded over the web.
 */

declare(strict_types=1);

define('STJB_HOME', dirname(__DIR__));            // /home/tizapdoz
define('STJB_LIB',  STJB_HOME . '/sitelib');
define('STJB_DATA', STJB_HOME . '/sitedata');
define('STJB_WEB',  STJB_HOME . '/public_html');
define('STJB_UPLOAD_DIR', STJB_WEB . '/images/uploads');
define('STJB_UPLOAD_URL', 'images/uploads');
define('STJB_MAX_UPLOAD', 8 * 1024 * 1024);       // 8 MB per photo
define('STJB_MAX_WIDTH', 2000);                   // photos are downscaled to this
define('STJB_KEEP_REVISIONS', 20);

date_default_timezone_set('Africa/Nairobi');

/* ------------------------------------------------------------------ *
 * Escaping helpers
 * ------------------------------------------------------------------ */

/** Escape for HTML output. Use on EVERY value that came from data files. */
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape, then turn newlines into <br> — for multi-line textarea fields. */
function e_nl(?string $s): string
{
    return nl2br(e($s), false);
}

/** Escape for use inside a HTML attribute that is a URL/path. */
function e_url(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Turn a Kenyan phone number as typed ("0722 232 528") into a tel: href.
 * Leaves anything already in +… form alone.
 */
function tel_href(?string $phone): string
{
    $p = preg_replace('/[^0-9+]/', '', (string)$phone);
    if ($p === '' || $p === null) {
        return '';
    }
    if (str_starts_with($p, '+')) {
        return $p;
    }
    if (str_starts_with($p, '0')) {
        return '+254' . substr($p, 1);
    }
    if (str_starts_with($p, '254')) {
        return '+' . $p;
    }
    return $p;
}

/** Format an ISO date (YYYY-MM-DD) for display; returns '' if unparseable. */
function nice_date(?string $iso): string
{
    if (!$iso) {
        return '';
    }
    $ts = strtotime($iso);
    return $ts ? date('j F Y', $ts) : '';
}

/* ------------------------------------------------------------------ *
 * JSON data store
 * ------------------------------------------------------------------ */

function data_path(string $name): string
{
    if (!preg_match('/^[a-z0-9_]+$/', $name)) {
        throw new InvalidArgumentException('Bad data file name: ' . $name);
    }
    return STJB_DATA . '/' . $name . '.json';
}

/** Read a data file. Returns $fallback if it is missing or corrupt. */
function data_load(string $name, array $fallback = []): array
{
    $path = data_path($name);
    if (!is_readable($path)) {
        return $fallback;
    }
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return $fallback;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $fallback;
}

/**
 * Write a data file atomically, keeping a timestamped revision first so a
 * bad edit can always be rolled back.
 */
function data_save(string $name, array $data): bool
{
    $path = data_path($name);

    // Keep a revision of what is there now.
    if (is_file($path)) {
        $revDir = STJB_DATA . '/_revisions';
        if (!is_dir($revDir)) {
            @mkdir($revDir, 0750, true);
        }
        if (is_dir($revDir)) {
            @copy($path, $revDir . '/' . $name . '.' . date('Ymd-His') . '.json');
            $old = glob($revDir . '/' . $name . '.*.json') ?: [];
            sort($old);
            foreach (array_slice($old, 0, max(0, count($old) - STJB_KEEP_REVISIONS)) as $stale) {
                @unlink($stale);
            }
        }
    }

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    if ($json === false) {
        return false;
    }

    // Unique temp name per write; getmypid() is disabled on this host.
    $tmp = $path . '.tmp.' . bin2hex(random_bytes(6));
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }
    @chmod($tmp, 0644);
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/** Fetch a nested value: cfg($content, 'hero.subtitle', 'fallback'). */
function cfg(array $data, string $dotted, $default = '')
{
    $node = $data;
    foreach (explode('.', $dotted) as $key) {
        if (!is_array($node) || !array_key_exists($key, $node)) {
            return $default;
        }
        $node = $node[$key];
    }
    return $node;
}

/** Generate a short unique id for a new list item. */
function new_id(string $prefix = 'i'): string
{
    return $prefix . bin2hex(random_bytes(5));
}

/* ------------------------------------------------------------------ *
 * Image handling
 * ------------------------------------------------------------------ */

/**
 * Validate and store an uploaded photo.
 * Returns ['ok' => true, 'path' => 'images/uploads/xxx.jpg'] or
 *         ['ok' => false, 'error' => 'message'].
 */
function store_upload(array $file): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['ok' => false, 'error' => 'No file was chosen.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['ok' => false, 'error' => 'That photo is too large. Maximum is 8 MB.'];
        default:
            return ['ok' => false, 'error' => 'Upload failed. Please try again.'];
    }

    if ($file['size'] > STJB_MAX_UPLOAD) {
        return ['ok' => false, 'error' => 'That photo is too large. Maximum is 8 MB.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Upload could not be verified.'];
    }

    // Trust the actual image bytes, never the filename or the browser's type.
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'error' => 'That file is not a photo. Use JPG, PNG, or WebP.'];
    }

    // Value is the extension we WRITE, which is not always the one we read:
    // a GIF is re-saved as a still PNG, so it must not keep a .gif name.
    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF  => 'png',
    ];
    if (!isset($allowed[$info[2]])) {
        return ['ok' => false, 'error' => 'Unsupported image type. Use JPG, PNG, WebP, or GIF.'];
    }
    $ext = $allowed[$info[2]];

    // Guard against decompression bombs before handing anything to GD.
    if ((int)$info[0] * (int)$info[1] > 50_000_000) {
        return ['ok' => false, 'error' => 'That photo has too many pixels. Resize it and try again.'];
    }

    if (!is_dir(STJB_UPLOAD_DIR) && !@mkdir(STJB_UPLOAD_DIR, 0755, true)) {
        return ['ok' => false, 'error' => 'Photo folder is not writable. Contact your administrator.'];
    }

    // Filename comes from us, not from the uploader.
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = STJB_UPLOAD_DIR . '/' . $name;

    // Re-encode rather than storing the uploaded bytes. A file can be a valid
    // image AND carry script source in the same bytes; redrawing it through GD
    // keeps only the pixels, so nothing executable can survive the trip.
    $written = reencode_image($file['tmp_name'], $info[2], $dest, $ext);
    if ($written === false) {
        @unlink($dest);
        return ['ok' => false, 'error' => 'That photo could not be processed. Try saving it as a JPG first.'];
    }
    @chmod($dest, 0644);

    return ['ok' => true, 'path' => STJB_UPLOAD_URL . '/' . $name];
}

/**
 * Redraw an image through GD and write it out, downscaling anything wider than
 * STJB_MAX_WIDTH so the site stays fast on slow connections.
 * Returns the final extension, or false on failure.
 */
function reencode_image(string $src, int $type, string $dest, string $ext)
{
    $loaders = [
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG  => 'imagecreatefrompng',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
        IMAGETYPE_GIF  => 'imagecreatefromgif',
    ];
    if (!isset($loaders[$type]) || !function_exists($loaders[$type])) {
        return false;
    }

    $img = @$loaders[$type]($src);
    if (!$img) {
        return false;
    }

    // Downscale wide photos.
    $w = imagesx($img);
    if ($w > STJB_MAX_WIDTH) {
        $scaled = @imagescale($img, STJB_MAX_WIDTH);
        if ($scaled) {
            imagedestroy($img);
            $img = $scaled;
        }
    }

    $ok = false;
    switch ($ext) {
        case 'png':
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $ok = @imagepng($img, $dest, 6);
            break;
        case 'webp':
            $ok = @imagewebp($img, $dest, 82);
            break;
        default:
            $ok = @imagejpeg($img, $dest, 85);
    }
    imagedestroy($img);

    return $ok ? $ext : false;
}

/**
 * Delete an uploaded photo from disk.
 * Only ever touches files inside images/uploads — original site images and
 * anything outside that folder are left alone.
 */
function delete_upload(?string $webPath): void
{
    if (!$webPath || !str_starts_with($webPath, STJB_UPLOAD_URL . '/')) {
        return;
    }
    $real = realpath(STJB_WEB . '/' . $webPath);
    $dir  = realpath(STJB_UPLOAD_DIR);
    if ($real && $dir && str_starts_with($real, $dir . DIRECTORY_SEPARATOR) && is_file($real)) {
        @unlink($real);
    }
}

/** True if a photo path is still referenced anywhere in the site data. */
function image_in_use(string $webPath, ?array $skip = null): bool
{
    foreach (['content', 'staff', 'gallery', 'news'] as $file) {
        if ($skip && $skip['file'] === $file) {
            continue;
        }
        $blob = json_encode(data_load($file));
        if ($blob !== false && str_contains($blob, $webPath)) {
            return true;
        }
    }
    return false;
}
