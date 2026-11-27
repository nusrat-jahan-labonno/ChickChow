<?php
// Vercel entry point: every page request comes here and is forwarded to the
// matching PHP file of the website (index.php, menu.php, admin/login.php ...).
$__root = dirname(__DIR__);
$__uri  = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$__rel  = ltrim($__uri, '/');

if ($__rel === '' || strpos($__rel, 'api/index.php') === 0) {
    $__rel = 'index.php';
} elseif (substr($__rel, -1) === '/') {
    $__rel .= 'index.php';
} elseif (!preg_match('/\.php$/', $__rel) && is_dir($__root . '/' . $__rel)) {
    $__rel .= '/index.php';
}

// Only the public pages may be opened: top-level pages and admin pages.
// (includes/, config/ and files starting with "_" are never reachable.)
if (!preg_match('#^(admin/)?[a-z0-9][a-z0-9_]*\.php$#i', $__rel) || !is_file($__root . '/' . $__rel)) {
    http_response_code(404);
    echo '<!DOCTYPE html><title>404</title><div style="font-family:sans-serif;text-align:center;margin-top:15vh"><h1>404</h1><p>Page not found.</p><a href="/">Back to ChickChow</a></div>';
    exit;
}

$__file = $__root . '/' . $__rel;
$_SERVER['SCRIPT_NAME']     = '/' . $__rel;
$_SERVER['PHP_SELF']        = '/' . $__rel;
$_SERVER['SCRIPT_FILENAME'] = $__file;
chdir(dirname($__file));
require $__file;
