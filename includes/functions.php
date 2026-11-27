<?php
// The website (user) and the admin panel use separate sessions, so one
// browser can be logged in as a user in one tab and as admin in another.
// Running on Vercel? (Vercel sets the VERCEL environment variable)
define('ON_VERCEL', (bool)(getenv('VERCEL') ?: ($_SERVER['VERCEL'] ?? false)));
date_default_timezone_set('Asia/Dhaka');

$__script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
define('IN_ADMIN', strpos($__script, '/admin/') !== false);
if (session_status() === PHP_SESSION_NONE) {
    if (ON_VERCEL) {                       // sessions are kept in the database on Vercel
        require_once __DIR__ . '/../config/db.php';
        require_once __DIR__ . '/session_db.php';
        session_set_save_handler(new CkDbSessionHandler($pdo), true);
    }
    session_name(IN_ADMIN ? 'CHICKCHOW_ADMIN' : 'CHICKCHOW_USER');
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/areas.php';

// ---------- Base URL (detected automatically from the folder name) ----------
if (!defined('BASE_URL') && ON_VERCEL) {
    define('BASE_URL', '');          // site is served from the domain root on Vercel
}
if (!defined('BASE_URL')) {
    $root = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim((string)realpath($_SERVER['DOCUMENT_ROOT']), '/\\')) : '';
    $proj = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
    if ($root !== '' && stripos($proj, $root) === 0) {
        define('BASE_URL', rtrim(substr($proj, strlen($root)), '/'));
    } else {
        define('BASE_URL', '/chickchow');
    }
}

define('CURRENCY', '৳');          // use '$' for dollars

// ---------- Basic helpers ----------
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    if (!preg_match('#^(https?:)?//#', $path) && $path !== '' && $path[0] !== '/') {
        $path = BASE_URL . '/' . $path;
    }
    header('Location: ' . $path);
    exit;
}

// Safe redirect path (prevents open redirects)
function safe_return($path, $default = 'index.php') {
    if (is_string($path) && $path !== '' && $path[0] === '/' && substr($path, 0, 2) !== '//' && strpos($path, "\n") === false && strpos($path, "\r") === false) {
        return $path;
    }
    return BASE_URL . '/' . $default;
}

function money($amount) {
    return CURRENCY . number_format((float)$amount, 0);
}

function is_logged_in() {
    if (!isset($_SESSION['user_id'])) return false;
    // If the database was re-imported, an old session may point to a user that no longer exists.
    static $checked = false;
    if (!$checked) {
        $checked = true;
        global $pdo;
        $st = $pdo->prepare("SELECT 1 FROM users WHERE id = ?");
        $st->execute([(int)$_SESSION['user_id']]);
        if (!$st->fetchColumn()) {
            unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['name'], $_SESSION['deliver_addr']);
            return false;
        }
    }
    return true;
}
function is_admin()     { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please login first.';
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('login.php');
    }
}

function require_admin() {
    if (!is_logged_in() || !is_admin()) {
        $_SESSION['flash_error'] = 'Please login as admin first.';
        redirect('admin/login.php');
    }
}

// ---------- CSRF ----------
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
}
function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
function verify_csrf() {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        if (is_ajax()) json_out(['ok' => false, 'message' => 'Session expired. Please reload the page.'], 400);
        http_response_code(400);
        die('Invalid request (CSRF). Please reload the page and try again.');
    }
}

// ---------- Flash ----------
function flash($key) {
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}
function set_flash($type, $msg) {
    $_SESSION['flash_' . $type] = $msg;
}

// ---------- Cart: $_SESSION['cart'][food_id] = quantity ----------
function cart_count() {
    return isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
}

// Cart items are loaded from the database (prices never come from the session)
function cart_items(PDO $pdo) {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return [];
    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT f.*, r.name AS restaurant_name, r.area AS restaurant_area FROM food_items f
                           JOIN restaurants r ON r.id = f.restaurant_id
                           WHERE f.id IN ($in) AND f.status = 'available'");
    $stmt->execute($ids);
    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $row['qty']       = (int)$cart[$row['id']];
        $row['unit']      = food_price($row);               // price after the food's own % discount
        $row['total']     = $row['qty'] * $row['unit'];
        $row['total_old'] = $row['qty'] * $row['price'];    // price before discount
        $items[] = $row;
    }
    return $items;
}

// ---------- Images ----------
function img_url($file) {
    $file = $file ?: 'placeholder.jpg';
    if (file_exists(__DIR__ . '/../uploads/' . $file)) {
        return BASE_URL . '/uploads/' . rawurlencode($file);
    }
    if (file_exists(__DIR__ . '/../assets/images/' . $file)) {
        return BASE_URL . '/assets/images/' . rawurlencode($file);
    }
    return BASE_URL . '/assets/images/placeholder.jpg';
}

// Image upload (admin). Return: [filename|null, error|null]
function handle_image_upload($field, $current = null) {
    if (empty($_FILES[$field]['name'])) {
        return [$current, null];
    }
    if (ON_VERCEL) {
        return [$current, 'Image upload is not available on the online (Vercel) version. Upload images from the local version.'];
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return [$current, 'Image upload failed. Please try again.'];
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        return [$current, 'Image must be smaller than 2MB.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        return [$current, 'Only JPG, PNG or WEBP images are allowed.'];
    }
    $dir = __DIR__ . '/../uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) {
        return [$current, 'Could not save the image.'];
    }
    // delete the old uploaded image
    if ($current && preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $current) && file_exists($dir . $current)) {
        @unlink($dir . $current);
    }
    return [$name, null];
}

// ---------- Validation helpers ----------
function valid_phone($p) {
    return (bool)preg_match('/^(\+?88)?01[3-9][0-9]{8}$/', preg_replace('/[\s-]/', '', $p));
}
function clean($v) {
    return trim((string)$v);
}

// ---------- Order status ----------
const ORDER_STEPS = ['Pending', 'Preparing', 'Out for Delivery', 'Delivered'];

function status_class($s) {
    return 'status-' . strtolower(str_replace(' ', '-', $s));
}

// The saved address the customer selected on the home page (or the first one). Returns the row or null.
function current_delivery_address(PDO $pdo) {
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    if (is_logged_in()) {
        $st = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id");
        $st->execute([$_SESSION['user_id']]);
        $rows = $st->fetchAll();
        foreach ($rows as $r) {
            if ((int)($_SESSION['deliver_addr'] ?? 0) === (int)$r['id']) { $cache = $r; break; }
        }
        if ($cache === null && $rows) $cache = $rows[0];
    }
    return $cache;
}


// ---------- Food discounts ----------
// Price after the food's own % discount (food_items.discount_percent)
function food_price($f) {
    $d = (int)($f['discount_percent'] ?? 0);
    return $d > 0 ? (int)round($f['price'] * (100 - $d) / 100) : (int)round($f['price']);
}
// Price block for cards: new price, struck-through old price and the % OFF tag
function price_html($f) {
    $d = (int)($f['discount_percent'] ?? 0);
    if ($d <= 0) return '<span class="price">' . money($f['price']) . '</span>';
    return '<span class="price-wrap"><span class="price">' . money(food_price($f)) . '</span> <s class="old-price">' . money($f['price']) . '</s></span>';
}

// ---------- Coupons ----------
// Returns [ok, message, coupon row|null, discount amount]
function check_coupon(PDO $pdo, $code, $subtotal) {
    $code = strtoupper(trim((string)$code));
    if ($code === '') return [false, 'Enter a coupon code.', null, 0];
    $st = $pdo->prepare("SELECT * FROM coupons WHERE code = ?");
    $st->execute([$code]);
    $c = $st->fetch();
    if (!$c || !$c['is_active']) return [false, 'This coupon code is not valid.', null, 0];
    if ($c['expires_at'] && $c['expires_at'] < date('Y-m-d')) return [false, 'This coupon has expired.', null, 0];
    if ($subtotal < $c['min_order']) return [false, 'Add ' . money($c['min_order'] - $subtotal) . ' more food to use ' . $code . ' (minimum order ' . money($c['min_order']) . ').', $c, 0];
    if ($c['type'] === 'percent') {
        $d = (int)floor($subtotal * $c['value'] / 100);
        if ($c['max_discount'] > 0) $d = min($d, (int)$c['max_discount']);
    } else {
        $d = (int)$c['value'];
    }
    $d = min($d, (int)$subtotal);
    return [true, $code . ' applied! You save ' . money($d) . '.', $c, $d];
}

// Everything the cart page, the cart drawer and checkout need (always calculated on the server)
function cart_summary(PDO $pdo) {
    $items = cart_items($pdo);
    // Remove items that no longer exist or are unavailable
    foreach (array_keys($_SESSION['cart'] ?? []) as $cid) {
        if (!in_array($cid, array_column($items, 'id'))) unset($_SESSION['cart'][$cid]);
    }
    $sub  = array_sum(array_column($items, 'total'));
    $old  = array_sum(array_column($items, 'total_old'));
    $coupon = null; $disc = 0; $note = '';
    if (!empty($_SESSION['coupon'])) {
        [$ok, $note, $coupon, $disc] = check_coupon($pdo, $_SESSION['coupon'], $sub);
        if (!$ok) {
            $disc = 0;
            // keep the code only while the minimum order is the only problem
            if (!$items || !$coupon) unset($_SESSION['coupon']);
            $coupon = null;
        } else { $note = ''; }
    }
    return ['items' => $items, 'count' => cart_count(), 'subtotal' => $sub, 'menu_saving' => $old - $sub,
            'coupon' => $coupon, 'coupon_code' => $_SESSION['coupon'] ?? '', 'coupon_note' => $note,
            'discount' => $disc, 'after' => $sub - $disc];
}

// All saved addresses of the logged in user
function user_addresses(PDO $pdo) {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    if (is_logged_in()) {
        $st = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id");
        $st->execute([$_SESSION['user_id']]);
        $cache = $st->fetchAll();
    }
    return $cache;
}

function stars($n) { $n = max(0, min(5, (int)$n)); return str_repeat('★', $n) . str_repeat('☆', 5 - $n); }
function order_code($id) { return '#CH' . str_pad((string)$id, 5, '0', STR_PAD_LEFT); }
