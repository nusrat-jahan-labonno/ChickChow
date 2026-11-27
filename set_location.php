<?php
// Handles "choose saved location" and "add new location" (profile dropdown + home page bar)
require_once 'includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf();

$uid    = (int)$_SESSION['user_id'];
$return = safe_return($_POST['return'] ?? '', 'index.php');
$op     = $_POST['op'] ?? '';

if ($op === 'select') {
    $st = $pdo->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
    $st->execute([(int)($_POST['address_id'] ?? 0), $uid]);
    if ($row = $st->fetch()) {
        $_SESSION['deliver_addr'] = (int)$row['id'];
        set_flash('success', 'Delivery location updated.');
    } else {
        set_flash('error', 'Location not found.');
    }
} elseif ($op === 'add') {
    $label = clean($_POST['label'] ?? ''); $addr = clean($_POST['address'] ?? ''); $area = $_POST['area'] ?? '';
    if ($label === '' || mb_strlen($label) > 30)             set_flash('error', 'Enter a label (e.g. Home, Work).');
    elseif (!valid_area($area))                               set_flash('error', 'Please select your area.');
    elseif (mb_strlen($addr) < 3 || mb_strlen($addr) > 255)   set_flash('error', 'Enter house / road details (at least 3 characters).');
    else {
        $pdo->prepare("INSERT INTO addresses (user_id, label, area, address) VALUES (?, ?, ?, ?)")->execute([$uid, $label, $area, $addr]);
        $_SESSION['deliver_addr'] = (int)$pdo->lastInsertId();
        set_flash('success', 'New location saved and selected.');
    }
}
header('Location: ' . $return);
exit;
