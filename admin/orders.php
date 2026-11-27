<?php
$page_title = 'Orders'; $nav = 'orders';
require '_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'status' && in_array($_POST['status'] ?? '', ORDER_STEPS, true)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$_POST['status'], $id]);
        set_flash('success', 'Order #CH' . str_pad($id, 5, '0', STR_PAD_LEFT) . ' status updated.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
        set_flash('success', 'Order deleted.');
    }
    redirect('admin/orders.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$filter = $_GET['status'] ?? '';
if (!in_array($filter, ORDER_STEPS, true)) $filter = '';
$sql = "SELECT o.*, u.name AS user_name FROM orders o JOIN users u ON u.id = o.user_id";
$params = [];
if ($filter) { $sql .= " WHERE o.status = ?"; $params[] = $filter; }
$sql .= " ORDER BY o.id DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
?>
<div class="chips">
    <a class="chip <?= $filter === '' ? 'active' : '' ?>" href="orders.php">All</a>
    <?php foreach (ORDER_STEPS as $s): ?><a class="chip <?= $filter === $s ? 'active' : '' ?>" href="orders.php?status=<?= urlencode($s) ?>"><?= e($s) ?></a><?php endforeach; ?>
</div>
<div class="table-wrap"><table class="table">
    <tr><th>Order</th><th>Customer</th><th>Phone</th><th>Total</th><th>Payment</th><th>Date</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($rows as $o): ?>
        <tr>
            <td><a href="order_view.php?id=<?= (int)$o["id"] ?>"><b>#CH<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></b></a></td>
            <td><?= e($o['user_name']) ?></td><td><?= e($o['phone']) ?></td><td><?= money($o['total']) ?></td>
            <td><?= $o['payment_method'] === 'cod' ? 'COD' : 'Online' ?></td>
            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                <select name="status" onchange="this.form.submit()"><?php foreach (ORDER_STEPS as $s): ?><option <?= $o['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></form></td>
            <td class="actions">
                <a class="btn btn-small btn-outline" href="order_view.php?id=<?= (int)$o["id"] ?>">View</a>
                <form method="post" onsubmit="return confirm('Delete this order?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                    <button class="btn btn-small btn-danger">Delete</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="8" class="muted">No orders.</td></tr><?php endif; ?>
</table></div>
<?php require '_footer.php'; ?>
