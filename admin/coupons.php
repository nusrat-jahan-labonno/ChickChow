<?php
$page_title = 'Coupons'; $nav = 'coupons';
require '_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);   // orders keep the coupon code (coupon_id becomes NULL)
        set_flash('success', 'Coupon deleted.');
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $pdo->prepare("UPDATE coupons SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
        set_flash('success', 'Coupon status changed.');
    }
    redirect('admin/coupons.php');
}
$rows = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM orders o WHERE o.coupon_id = c.id) AS used FROM coupons c ORDER BY c.id DESC")->fetchAll();
?>
<div class="admin-bar"><span class="muted small"><?= count($rows) ?> coupons</span><a class="btn btn-small" href="coupon_form.php">+ Add Coupon</a></div>
<div class="table-wrap"><table class="table">
    <tr><th>Code</th><th>Discount</th><th>Min order</th><th>Expires</th><th>Used</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($rows as $c): $expired = $c['expires_at'] && $c['expires_at'] < date('Y-m-d'); ?>
        <tr>
            <td><b><?= e($c['code']) ?></b><br><span class="muted small"><?= e($c['description']) ?></span></td>
            <td><?= $c['type'] === 'percent' ? (int)$c['value'] . '%' . ($c['max_discount'] > 0 ? ' (max ' . money($c['max_discount']) . ')' : '') : money($c['value']) ?></td>
            <td><?= money($c['min_order']) ?></td>
            <td><?= $c['expires_at'] ? e(date('d M Y', strtotime($c['expires_at']))) : 'Never' ?><?= $expired ? ' <span class="status status-unavailable">Expired</span>' : '' ?></td>
            <td><?= (int)$c['used'] ?></td>
            <td><span class="status <?= $c['is_active'] ? 'status-available' : 'status-unavailable' ?>"><?= $c['is_active'] ? 'Active' : 'Disabled' ?></span></td>
            <td class="actions">
                <a class="btn btn-small btn-outline" href="coupon_form.php?id=<?= (int)$c['id'] ?>">Edit</a>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-small btn-outline">Toggle</button></form>
                <form method="post" onsubmit="return confirm('Delete this coupon?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-small btn-danger">Delete</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="muted">No coupons yet.</td></tr><?php endif; ?>
</table></div>
<?php require '_footer.php'; ?>
