<?php
require_once 'includes/functions.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) redirect('orders.php');

$page_title = 'Order Placed';
require 'includes/header.php';
?>
<div class="container">
    <div class="auth-wrap form-card center" style="max-width:520px">
        <img class="auth-mascot" style="width:150px" src="<?= BASE_URL ?>/assets/images/mascot-happy.png" alt="">
        <h1>Order Placed!</h1>
        <p class="auth-sub">Thank you, <?= e($order['full_name']) ?>. Your order #CH<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?> has been sent to the kitchen.</p>
        <p><b>Total:</b> <?= money($order['total']) ?> (<?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?>)</p>
        <p style="margin-top:20px">
            <a class="btn" href="<?= BASE_URL ?>/track.php?id=<?= (int)$order['id'] ?>">Track Order</a>
            <a class="btn btn-outline" href="<?= BASE_URL ?>/menu.php">Order More</a>
        </p>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
