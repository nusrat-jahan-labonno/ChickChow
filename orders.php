<?php
require_once 'includes/functions.php';
require_login();
$page_title = 'My Orders';
$active = 'orders';

$filter = $_GET['status'] ?? '';
if (!in_array($filter, ORDER_STEPS, true)) $filter = '';

$sql = "SELECT o.*, (SELECT f.image FROM order_items oi JOIN food_items f ON f.id = oi.food_id WHERE oi.order_id = o.id ORDER BY oi.id LIMIT 1) AS thumb,
               (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count,
               (SELECT rv.rating FROM reviews rv WHERE rv.order_id = o.id) AS my_rating
        FROM orders o WHERE o.user_id = ?";
$params = [$_SESSION['user_id']];
if ($filter) { $sql .= " AND o.status = ?"; $params[] = $filter; }
$sql .= " ORDER BY o.created_at DESC, o.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require 'includes/header.php';
?>
<div class="container page-wrap">
    <h1 class="page-title">My Orders</h1>
    <div class="chips">
        <a class="chip <?= $filter === '' ? 'active' : '' ?>" href="<?= BASE_URL ?>/orders.php">All</a>
        <?php foreach (ORDER_STEPS as $s): ?>
            <a class="chip <?= $filter === $s ? 'active' : '' ?>" href="<?= BASE_URL ?>/orders.php?status=<?= urlencode($s) ?>"><?= e($s) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$orders): ?>
        <div class="empty"><img src="<?= BASE_URL ?>/assets/images/mascot-sad.png" alt=""><p>No orders yet.</p>
            <a class="btn" href="<?= BASE_URL ?>/menu.php">Order Now</a></div>
    <?php else: ?>
        <div class="order-list">
        <?php foreach ($orders as $o): ?>
            <div class="order-row card">
                <img src="<?= img_url($o['thumb']) ?>" alt="">
                <div class="order-meta">
                    <b>#CH<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></b>
                    <span class="muted small"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?> &middot; <?= (int)$o['item_count'] ?> item(s)</span>
                </div>
                <div class="order-total"><?= money($o['total']) ?></div>
                <span class="status <?= status_class($o['status']) ?>"><?= e($o['status']) ?></span>
                <div class="order-btns">
                    <a class="btn btn-small btn-outline" href="<?= BASE_URL ?>/track.php?id=<?= (int)$o['id'] ?>">View Details</a>
                    <?php if ($o['status'] === 'Delivered'): ?>
                        <?php if ($o['my_rating']): ?><a class="rated" href="<?= BASE_URL ?>/track.php?id=<?= (int)$o['id'] ?>#review" title="Your rating"><?= stars($o['my_rating']) ?></a>
                        <?php else: ?><a class="btn btn-small" href="<?= BASE_URL ?>/track.php?id=<?= (int)$o['id'] ?>#review">&#9733; Rate order</a><?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
