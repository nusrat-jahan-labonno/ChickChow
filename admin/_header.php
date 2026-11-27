<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$page_title = $page_title ?? 'Admin';
$nav = $nav ?? '';
$flash_ok  = flash('flash_success');
$flash_err = flash('flash_error');
$links = [
    'dashboard'   => ['index.php', 'Dashboard'],
    'foods'       => ['foods.php', 'Food Items'],
    'restaurants' => ['restaurants.php', 'Restaurants'],
    'categories'  => ['categories.php', 'Categories'],
    'orders'      => ['orders.php', 'Orders'],
    'coupons'     => ['coupons.php', 'Coupons'],
    'reviews'     => ['reviews.php', 'Reviews'],
    'users'       => ['users.php', 'Users'],
    'password'    => ['password.php', 'Change Password'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | ChickChow Admin</title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-wrap">
    <aside class="admin-side" id="adminSide">
        <a class="admin-brand" href="<?= BASE_URL ?>/admin/index.php">
            <img src="<?= BASE_URL ?>/assets/images/favicon.png" alt=""><span>ChickChow <small>Admin</small></span>
        </a>
        <nav>
            <?php foreach ($links as $key => [$href, $label]): ?>
                <a href="<?= BASE_URL ?>/admin/<?= $href ?>" class="<?= $nav === $key ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>/index.php">&larr; View Website</a>
            <a href="<?= BASE_URL ?>/admin/logout.php">Logout</a>
        </nav>
    </aside>
    <div class="admin-main">
        <div class="admin-top">
            <button class="nav-toggle" id="adminToggle" aria-label="Menu">&#9776;</button>
            <h1><?= e($page_title) ?></h1>
            <a class="admin-hi" href="<?= BASE_URL ?>/admin/password.php" title="Change password">Hi, <?= e($_SESSION['name'] ?? 'Admin') ?></a>
        </div>
        <div class="admin-content">
        <?php if ($flash_ok): ?><div class="alert alert-success"><?= e($flash_ok) ?></div><?php endif; ?>
        <?php if ($flash_err): ?><div class="alert alert-error"><?= e($flash_err) ?></div><?php endif; ?>
