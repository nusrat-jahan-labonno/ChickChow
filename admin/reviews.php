<?php
$page_title = 'Reviews'; $nav = 'reviews';
require '_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
    set_flash('success', 'Review deleted.');
    redirect('admin/reviews.php');
}
$rows = $pdo->query("SELECT rv.*, u.name AS user_name, u.email FROM reviews rv JOIN users u ON u.id = rv.user_id ORDER BY rv.id DESC")->fetchAll();
$avg = $pdo->query("SELECT AVG(rating) FROM reviews")->fetchColumn();
?>
<div class="admin-bar"><span class="muted small"><?= count($rows) ?> reviews<?= $rows ? ' &middot; average ' . number_format((float)$avg, 1) . ' / 5' : '' ?></span></div>
<div class="table-wrap"><table class="table">
    <tr><th>Order</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Date</th><th>Action</th></tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><a href="order_view.php?id=<?= (int)$r['order_id'] ?>">#CH<?= str_pad($r['order_id'], 5, '0', STR_PAD_LEFT) ?></a></td>
            <td><?= e($r['user_name']) ?><br><span class="muted small"><?= e($r['email']) ?></span></td>
            <td><span class="stars"><?= stars($r['rating']) ?></span></td>
            <td style="max-width:320px"><?= e($r['comment']) ?></td>
            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            <td class="actions"><form method="post" onsubmit="return confirm('Delete this review?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-small btn-danger">Delete</button></form></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="muted">No reviews yet. Customers can review an order after it is Delivered.</td></tr><?php endif; ?>
</table></div>
<?php require '_footer.php'; ?>
