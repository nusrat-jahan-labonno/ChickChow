<?php
$page_title = 'Order Details'; $nav = 'orders';
require '_header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?");
$stmt->execute([$id]);
$o = $stmt->fetch();
if (!$o) { set_flash('error', 'Order not found.'); redirect('admin/orders.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['status'] ?? '', ORDER_STEPS, true)) {
    verify_csrf();
    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$_POST['status'], $id]);
    set_flash('success', 'Order status updated.');
    redirect('admin/order_view.php?id=' . $id);
}

$stmt = $pdo->prepare("SELECT oi.*, f.name, f.image FROM order_items oi JOIN food_items f ON f.id = oi.food_id WHERE oi.order_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>
<div class="two-col">
    <div class="table-wrap"><table class="table">
        <tr><th>Image</th><th>Food</th><th>Price</th><th>Qty</th><th>Total</th></tr>
        <?php foreach ($items as $it): ?>
            <tr><td><img class="thumb" src="<?= img_url($it['image']) ?>" alt=""></td><td><b><?= e($it['name']) ?></b></td>
                <td><?= money($it['price']) ?></td><td><?= (int)$it['quantity'] ?></td><td><?= money($it['price'] * $it['quantity']) ?></td></tr>
        <?php endforeach; ?>
    </table></div>
    <aside class="summary card">
        <h2>Order #CH<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></h2>
        <p class="small muted"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></p>
        <form method="post" class="inline-form" style="margin:12px 0"><?= csrf_field() ?>
            <select name="status"><?php foreach (ORDER_STEPS as $s): ?><option <?= $o['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
            <button class="btn btn-small">Update</button>
        </form>
        <div class="sum-row"><span>Subtotal</span><span><?= money($o['subtotal']) ?></span></div>
        <?php if ($o['discount'] > 0): ?><div class="sum-row save-row"><span>Coupon <?= e($o['coupon_code']) ?></span><span>&minus;<?= money($o['discount']) ?></span></div><?php endif; ?>
        <div class="sum-row"><span>Delivery</span><span><?= money($o['delivery_charge']) ?></span></div>
        <div class="sum-row sum-total"><span>Total</span><span><?= money($o['total']) ?></span></div>
        <p class="small"><b>Customer:</b> <?= e($o['user_name']) ?> (<?= e($o['user_email']) ?>)<br>
        <b>Deliver to:</b> <?= e($o['full_name']) ?>, <?= e($o['phone']) ?><br><?= nl2br(e($o['address'])) ?>, <?= e($o['delivery_area']) ?></p>
        <p class="small"><b>Payment:</b> <?= $o['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?></p>
        <?php $rvq = $pdo->prepare("SELECT * FROM reviews WHERE order_id = ?"); $rvq->execute([$id]); if ($rv = $rvq->fetch()): ?>
            <p class="small"><b>Customer review:</b> <span class="stars"><?= stars($rv['rating']) ?></span><br><?= e($rv['comment']) ?></p>
        <?php endif; ?>
        <a class="btn btn-small btn-outline" href="orders.php">&larr; All orders</a>
    </aside>
</div>
<?php require '_footer.php'; ?>
