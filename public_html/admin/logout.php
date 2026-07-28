<?php
/** Sign out. */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/sitelib/auth.php';

auth_start();
auth_logout();

header('Location: index.php');
exit;
