<?php
require_once 'includes/functions.php';

$page_title = 'Home';
$active = 'home';

$restaurants = $pdo->query("SELECT * FROM restaurants ORDER BY rating DESC, id LIMIT 10")->fetchAll();
$popular = $pdo->query("SELECT f.*, r.name AS restaurant_name FROM food_items f
                        JOIN restaurants r ON r.id = f.restaurant_id
                        WHERE f.status='available' ORDER BY f.id DESC LIMIT 10")->fetchAll();
$deals = $pdo->query("SELECT f.*, r.name AS restaurant_name FROM food_items f
                      JOIN restaurants r ON r.id = f.restaurant_id
                      WHERE f.status='available' AND f.discount_percent > 0 ORDER BY f.discount_percent DESC, f.id LIMIT 10")->fetchAll();
$coupons = $pdo->query("SELECT * FROM coupons WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY min_order LIMIT 4")->fetchAll();
$reviews = $pdo->query("SELECT rv.*, u.name AS user_name FROM reviews rv JOIN users u ON u.id = rv.user_id ORDER BY rv.id DESC LIMIT 6")->fetchAll();
$rv_stats = $pdo->query("SELECT COUNT(*) c, AVG(rating) a FROM reviews")->fetch();

require 'includes/header.php';
?>

<section class="deliver-bar">
    <div class="container">
    <?php if (!is_logged_in()): ?>
        <div class="deliver-row"><span>&#128205; <b>Deliver to:</b> <a href="<?= BASE_URL ?>/login.php">Login</a> to choose your delivery location</span></div>
    <?php else: ?>
        <div class="deliver-row">
            <span>&#128205; <b>Deliver to:</b>
                <?php $cur_loc = current_delivery_address($pdo); if ($cur_loc): ?><?= e($cur_loc['label']) ?> &ndash; <b><?= e($cur_loc['area']) ?></b> <span class="muted">(<?= e($cur_loc['address']) ?>)</span>
                <?php else: ?><span class="muted">No location saved yet</span><?php endif; ?>
            </span>
            <details class="deliver-edit">
                <summary class="btn btn-small btn-outline">Change location</summary>
                <div class="deliver-panel card"><?php $loc_id = 'home'; require 'includes/location_box.php'; ?></div>
            </details>
        </div>
    <?php endif; ?>
    </div>
</section>

<section class="hero">
    <div class="container hero-inner">
        <div class="hero-text">
            <p class="hero-small">Welcome to</p>
            <h1>Chick<span>Chow</span></h1>
            <p class="hero-tagline">Delicious Food, Delivered with Love!</p>
            <p class="hero-points">Fresh food <i></i> Fast delivery <i></i> Best restaurants</p>
            <form class="hero-search" action="<?= BASE_URL ?>/menu.php" method="get" role="search">
                <input type="text" name="q" placeholder="What are you craving? Biryani, pizza, burger..." aria-label="Search food or restaurant">
                <button class="btn" type="submit">Search</button>
            </form>
            <div class="hero-quick">
                <?php foreach (['Biryani', 'Burger', 'Pizza', 'Chicken', 'Pasta'] as $kw): ?><a href="<?= BASE_URL ?>/menu.php?q=<?= urlencode($kw) ?>"><?= e($kw) ?></a><?php endforeach; ?>
            </div>
        </div>
        <div class="hero-art">
            <img src="<?= BASE_URL ?>/assets/images/hero-mascot.png" alt="ChickChow chef mascot">
        </div>
    </div>
</section>

<section class="container">
    <div class="features">
        <div class="feature"><span class="feature-icon">&#128757;</span><div><strong>Fast Delivery</strong><small>Within 30 mins</small></div></div>
        <div class="feature"><span class="feature-icon">&#127775;</span><div><strong>Trusted Restaurants</strong><small>Top rated &amp; hygienic</small></div></div>
        <div class="feature"><span class="feature-icon">&#128179;</span><div><strong>Easy Payment</strong><small>Secure &amp; safe</small></div></div>
        <div class="feature"><span class="feature-icon">&#127911;</span><div><strong>24/7 Support</strong><small>We are here for you</small></div></div>
    </div>
</section>

<?php if ($coupons): ?>
<section class="container section coupon-strip-wrap">
    <div class="section-head"><h2>&#127903; Coupons &amp; Offers</h2><span class="muted small">Tap a code to copy it, then use it in your cart</span></div>
    <div class="coupon-strip">
        <?php foreach ($coupons as $c): ?>
            <div class="coupon-card">
                <div class="cc-main">
                    <b class="cc-off"><?= $c['type'] === 'percent' ? (int)$c['value'] . '% OFF' : money($c['value']) . ' OFF' ?></b>
                    <span class="cc-desc"><?= e($c['description']) ?></span>
                    <small class="muted">Min order <?= money($c['min_order']) ?><?= $c['type'] === 'percent' && $c['max_discount'] > 0 ? ' &middot; up to ' . money($c['max_discount']) : '' ?></small>
                </div>
                <button type="button" class="cc-code" data-copy="<?= e($c['code']) ?>" title="Copy code"><?= e($c['code']) ?> <span>Copy</span></button>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($deals): ?>
<section class="container section">
    <div class="section-head">
        <h2>&#128293; Hot Deals</h2>
        <a href="<?= BASE_URL ?>/menu.php?deals=1" class="link-small">All Deals &rarr;</a>
    </div>
    <div class="grid grid-4">
        <?php foreach ($deals as $f): ?>
            <?php require 'includes/food_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="container section">
    <div class="section-head">
        <h2>Popular Restaurants</h2>
        <a href="<?= BASE_URL ?>/restaurants.php" class="link-small">View All &rarr;</a>
    </div>
    <div class="grid grid-4">
        <?php foreach ($restaurants as $r): ?>
            <?php require 'includes/rest_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <div class="section-more"><a href="<?= BASE_URL ?>/restaurants.php" class="btn btn-large">Explore All Restaurants &rarr;</a></div>
</section>

<section class="container section">
    <div class="section-head">
        <h2>Fresh on the Menu</h2>
        <a href="<?= BASE_URL ?>/menu.php" class="link-small">Full Menu &rarr;</a>
    </div>
    <div class="grid grid-4">
        <?php foreach ($popular as $f): ?>
            <?php require 'includes/food_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <div class="section-more"><a href="<?= BASE_URL ?>/menu.php" class="btn btn-large">Explore Full Menu &rarr;</a></div>
</section>

<?php if ($reviews): ?>
<section class="container section">
    <div class="section-head">
        <h2>&#11088; What Our Customers Say</h2>
        <span class="muted small"><?= number_format((float)$rv_stats['a'], 1) ?> average from <?= (int)$rv_stats['c'] ?> review<?= $rv_stats['c'] == 1 ? '' : 's' ?></span>
    </div>
    <div class="review-grid">
        <?php foreach ($reviews as $rv): ?>
            <div class="review-card card">
                <div class="rc-top"><span class="avatar"><?= e(strtoupper(substr($rv['user_name'], 0, 1))) ?></span>
                    <div><b><?= e($rv['user_name']) ?></b><br><span class="stars"><?= stars($rv['rating']) ?></span></div></div>
                <p><?= $rv['comment'] !== '' ? e($rv['comment']) : '<span class="muted">Rated this order.</span>' ?></p>
                <small class="muted"><?= date('d M Y', strtotime($rv['created_at'])) ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
