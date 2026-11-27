<?php
$page_title = 'Dashboard'; $nav = 'dashboard';
require '_header.php';

$count = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$stats = [
    ['Total Orders', $count("SELECT COUNT(*) FROM orders")],
    ['Total Users', $count("SELECT COUNT(*) FROM users WHERE role='user'")],
    ['Restaurants', $count("SELECT COUNT(*) FROM restaurants")],
    ['Food Items', $count("SELECT COUNT(*) FROM food_items")],
    ['Pending Orders', $count("SELECT COUNT(*) FROM orders WHERE status='Pending'")],
    ['Active Coupons', $count("SELECT COUNT(*) FROM coupons WHERE is_active = 1")],
    ['Reviews', $count("SELECT COUNT(*) FROM reviews")],
];
$revenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='Delivered'")->fetchColumn();
$recent = $pdo->query("SELECT o.*, u.name AS user_name FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT 8")->fetchAll();
?>
<div class="stat-grid">
    <?php foreach ($stats as [$label, $val]): ?>
        <div class="stat card"><span class="muted small"><?= e($label) ?></span><b><?= $val ?></b></div>
    <?php endforeach; ?>
    <div class="stat card"><span class="muted small">Revenue (Delivered)</span><b><?= money($revenue) ?></b></div>
</div>

<h2 style="margin:24px 0 12px">Recent Orders</h2>
<div class="table-wrap"><table class="table">
    <tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr>
    <?php foreach ($recent as $o): ?>
        <tr>
            <td>#CH<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
            <td><?= e($o['user_name']) ?></td>
            <td><?= money($o['total']) ?></td>
            <td><span class="status <?= status_class($o['status']) ?>"><?= e($o['status']) ?></span></td>
            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td><a class="btn btn-small btn-outline" href="orders.php">Manage</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="6" class="muted">No orders yet.</td></tr><?php endif; ?>
</table></div>
<?php require '_footer.php'; ?>
