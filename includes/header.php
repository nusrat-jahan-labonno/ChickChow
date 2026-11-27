<?php
require_once __DIR__ . '/functions.php';
$page_title = $page_title ?? 'ChickChow';
$active     = $active ?? '';
$flash_ok   = flash('flash_success');
$flash_err  = flash('flash_error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | ChickChow</title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body data-base="<?= BASE_URL ?>">

<?php
$hdr_email = '';
if (is_logged_in()) {
    $st = $pdo->prepare("SELECT email FROM users WHERE id = ?"); $st->execute([$_SESSION['user_id']]);
    $hdr_email = (string)$st->fetchColumn();
}
?>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= BASE_URL ?>/index.php" class="brand">
            <img src="<?= BASE_URL ?>/assets/images/favicon.png" alt="ChickChow logo">
            <span>Chick<b>Chow</b></span>
        </a>

        <form class="search-box search-center" action="<?= BASE_URL ?>/menu.php" method="get" role="search">
            <span class="s-ico">&#128269;</span>
            <input type="text" name="q" placeholder="Search for food, restaurant or category..." value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search">
            <button type="submit">Search</button>
        </form>

        <div class="nav-actions">
            <a href="<?= BASE_URL ?>/cart.php" class="cart-link" id="cartLink" aria-label="Open cart">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h8.1a2 2 0 0 0 2-1.5L21.5 8H6"/></svg>
                <?php if (cart_count() > 0): ?><span class="badge"><?= cart_count() ?></span><?php endif; ?>
            </a>

            <?php if (is_logged_in()): ?>
                <div class="user-menu" id="userMenu">
                    <button type="button" class="avatar" id="userBtn" aria-haspopup="true" aria-expanded="false" title="<?= e($_SESSION['name'] ?? 'Account') ?>">
                        <?= e(strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1))) ?>
                    </button>
                    <div class="dd" id="userDd">
                        <div class="dd-head">
                            <span class="avatar big"><?= e(strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1))) ?></span>
                            <div><b><?= e($_SESSION['name'] ?? '') ?></b><small><?= e($hdr_email) ?></small></div>
                        </div>
                        <div class="dd-section">
                            <div class="dd-title">Delivery location</div>
                            <?php $loc_id = 'dd'; require __DIR__ . '/location_box.php'; ?>
                        </div>
                        <a class="dd-link" href="<?= BASE_URL ?>/profile.php"><i>&#128100;</i> User Profile</a>
                        <a class="dd-link" href="<?= BASE_URL ?>/profile.php?tab=addresses"><i>&#127968;</i> Saved Addresses</a>
                        <a class="dd-link" href="<?= BASE_URL ?>/profile.php?tab=password"><i>&#128274;</i> Change Password</a>
                        <a class="dd-link" href="<?= BASE_URL ?>/orders.php"><i>&#128230;</i> My Orders</a>
                        <a class="dd-link dd-logout" href="<?= BASE_URL ?>/logout.php"><i>&#10162;</i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-small btn-outline">Login</a>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-small hide-sm">Register</a>
            <?php endif; ?>
            <button class="nav-toggle" id="navToggle" aria-label="Menu">&#9776;</button>
        </div>
    </div>

    <nav class="sub-nav" id="mainNav">
        <div class="container">
            <a href="<?= BASE_URL ?>/index.php" class="<?= $active === 'home' ? 'active' : '' ?>">Home</a>
            <a href="<?= BASE_URL ?>/menu.php" class="<?= $active === 'menu' ? 'active' : '' ?>">Menu</a>
            <a href="<?= BASE_URL ?>/restaurants.php" class="<?= $active === 'restaurants' ? 'active' : '' ?>">Restaurants</a>
            <a href="<?= BASE_URL ?>/menu.php?deals=1" class="<?= $active === 'deals' ? 'active' : '' ?> deal-link">&#128293; Deals</a>
            <?php if (is_logged_in()): ?><a href="<?= BASE_URL ?>/orders.php" class="<?= $active === 'orders' ? 'active' : '' ?>">My Orders</a><?php else: ?><a href="<?= BASE_URL ?>/register.php" class="show-sm">Register</a><?php endif; ?>
        </div>
    </nav>
</header>

<?php if ($flash_ok || $flash_err): ?>
    <div class="container">
        <?php if ($flash_ok): ?><div class="alert alert-success"><?= e($flash_ok) ?></div><?php endif; ?>
        <?php if ($flash_err): ?><div class="alert alert-error"><?= e($flash_err) ?></div><?php endif; ?>
    </div>
<?php endif; ?>

<main>
