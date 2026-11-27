<?php
require_once 'includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) { set_flash('error', 'Order not found.'); redirect('orders.php'); }

$stmt = $pdo->prepare("SELECT oi.*, f.name, f.image FROM order_items oi JOIN food_items f ON f.id = oi.food_id WHERE oi.order_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$rv = $pdo->prepare("SELECT * FROM reviews WHERE order_id = ?");
$rv->execute([$id]);
$review = $rv->fetch();

$step = array_search($order['status'], ORDER_STEPS, true);
$mascots = ['Pending' => 'mascot-wave.png', 'Preparing' => 'mascot-cooking.png', 'Out for Delivery' => 'scooter.png', 'Delivered' => 'mascot-happy.png'];
$messages = [
    'Pending' => 'We have received your order and are waiting for the restaurant to confirm.',
    'Preparing' => 'Your food is being prepared.',
    'Out for Delivery' => 'Your food is on the way and will arrive soon!',
    'Delivered' => 'Your food has been delivered. Bon appetit!',
];

$page_title = 'Track Order';
require 'includes/header.php';
?>
<div class="container page-wrap">
    <h1 class="page-title">Track Your Order</h1>
    <p class="page-sub"><?= e($messages[$order['status']]) ?></p>

    <div class="two-col">
        <div>
            <div class="card track-card">
                <div class="track-head">
                    <div><b>Order <?= order_code($order['id']) ?></b><br>
                        <span class="muted small">Placed on: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span></div>
                    <span class="status <?= status_class($order['status']) ?>"><?= e($order['status']) ?></span>
                </div>

                <ol class="stepper" id="stepper" data-order="<?= (int)$order['id'] ?>" data-step="<?= (int)$step ?>">
                    <?php foreach (ORDER_STEPS as $i => $s): ?>
                        <li class="<?= $i < $step ? 'done' : ($i === $step ? 'current' : '') ?>">
                            <span class="dot-step"><?= $i <= $step ? '&#10003;' : $i + 1 ?></span>
                            <span class="step-label"><?= e($s) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <div class="track-mascot"><img src="<?= BASE_URL ?>/assets/images/<?= $mascots[$order['status']] ?>" alt=""></div>
            </div>

            <?php if ($order['status'] === 'Delivered'): ?>
            <div class="card review-card-box" id="review">
                <img class="review-mascot" src="<?= BASE_URL ?>/assets/images/review-mascot.png" alt="Leave a review">
                <div class="review-body">
                <?php if ($review): ?>
                    <h2>Your review</h2>
                    <div class="stars big-stars"><?= stars($review['rating']) ?></div>
                    <p class="review-text"><?= $review['comment'] !== '' ? nl2br(e($review['comment'])) : '<span class="muted">No comment.</span>' ?></p>
                    <small class="muted">Thank you! Posted on <?= date('d M Y', strtotime($review['created_at'])) ?></small>
                <?php else: ?>
                    <h2>How was your order?</h2>
                    <p class="muted small">Rate your food and delivery. Your review helps other customers.</p>
                    <form method="post" action="<?= BASE_URL ?>/review_submit.php" novalidate id="reviewForm">
                        <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                        <div class="star-input" role="radiogroup" aria-label="Rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>"><label for="star<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">&#9733;</label>
                            <?php endfor; ?>
                        </div>
                        <div class="field-error" id="ratingErr" hidden>Please choose a star rating.</div>
                        <div class="form-group"><textarea name="comment" maxlength="500" placeholder="Write a comment (optional)..."></textarea></div>
                        <button class="btn">Submit Review</button>
                    </form>
                <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <aside class="summary card">
            <h2>Order Details</h2>
            <?php foreach ($items as $it): ?>
                <div class="sum-row"><span><?= e($it['name']) ?> <small class="muted">x<?= (int)$it['quantity'] ?></small></span><span><?= money($it['price'] * $it['quantity']) ?></span></div>
            <?php endforeach; ?>
            <hr>
            <div class="sum-row"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
            <?php if ($order['discount'] > 0): ?><div class="sum-row save-row"><span>Coupon <?= e($order['coupon_code']) ?></span><span>&minus;<?= money($order['discount']) ?></span></div><?php endif; ?>
            <div class="sum-row"><span>Delivery</span><span><?= money($order['delivery_charge']) ?></span></div>
            <div class="sum-row sum-total"><span>Total</span><span><?= money($order['total']) ?></span></div>
            <hr>
            <p class="small"><b>Deliver to:</b><br><?= e($order['full_name']) ?><br><?= e($order['phone']) ?><br><?= nl2br(e($order['address'])) ?>, <?= e($order['delivery_area']) ?></p>
            <p class="small"><b>Payment:</b> <?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?></p>
        </aside>
    </div>
</div>
<script>
(function () {
    var st = document.getElementById('stepper'); if (!st) return;
    var key = 'ck_step_' + st.dataset.order, now = parseInt(st.dataset.step, 10), prev = -1;
    try { var v = localStorage.getItem(key); if (v !== null) prev = parseInt(v, 10); localStorage.setItem(key, now); } catch (e) {}
    // The line leading to the newly reached step fills with an animation
    st.querySelectorAll('li').forEach(function (li, i) { if (i >= prev && i < now) li.classList.add('fresh'); if (i === now && prev !== now) li.classList.add('pop'); });
    // Auto refresh every 15 seconds until delivered
    if (now < 3) setTimeout(function () { location.reload(); }, 15000);
})();
</script>
<?php require 'includes/footer.php'; ?>
