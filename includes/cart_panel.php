<?php
/* Cart contents used inside the pop-out drawer. Needs $pdo.
   Re-rendered by cart.php after every AJAX action. */
$sum = cart_summary($pdo);
$offers = $pdo->query("SELECT code FROM coupons WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY min_order LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
$ret = 'cart.php';
?>
<?php if (!$sum['items']): ?>
    <div class="drawer-empty">
        <img src="<?= BASE_URL ?>/assets/images/mascot-sad.png" alt="">
        <h3>Your cart is empty</h3>
        <p class="muted small">Add some tasty food to get started!</p>
        <a class="btn btn-small" href="<?= BASE_URL ?>/menu.php">Browse Menu</a>
    </div>
<?php else: ?>
    <div class="drawer-items">
    <?php foreach ($sum['items'] as $it): ?>
        <div class="d-item">
            <img src="<?= img_url($it['image']) ?>" alt="">
            <div class="d-info">
                <b><?= e($it['name']) ?></b>
                <span class="muted small"><?= e($it['restaurant_name']) ?></span>
                <span class="d-price"><?= money($it['unit']) ?><?php if ($it['discount_percent'] > 0): ?> <s class="old-price"><?= money($it['price']) ?></s> <span class="off-tag"><?= (int)$it['discount_percent'] ?>% OFF</span><?php endif; ?></span>
            </div>
            <div class="d-side">
                <div class="qty-box small-qty">
                    <form method="post" action="<?= BASE_URL ?>/cart.php" data-panel-form><?= csrf_field() ?>
                        <input type="hidden" name="action" value="update"><input type="hidden" name="food_id" value="<?= (int)$it['id'] ?>"><input type="hidden" name="qty" value="<?= $it['qty'] - 1 ?>">
                        <button class="qty-btn" aria-label="Decrease">&minus;</button></form>
                    <span><?= (int)$it['qty'] ?></span>
                    <form method="post" action="<?= BASE_URL ?>/cart.php" data-panel-form><?= csrf_field() ?>
                        <input type="hidden" name="action" value="update"><input type="hidden" name="food_id" value="<?= (int)$it['id'] ?>"><input type="hidden" name="qty" value="<?= $it['qty'] + 1 ?>">
                        <button class="qty-btn" aria-label="Increase">+</button></form>
                </div>
                <b class="d-line"><?= money($it['total']) ?></b>
                <form method="post" action="<?= BASE_URL ?>/cart.php" data-panel-form><?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove"><input type="hidden" name="food_id" value="<?= (int)$it['id'] ?>">
                    <button class="d-remove" title="Remove" aria-label="Remove">Remove</button></form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="drawer-foot">
        <?php include __DIR__ . '/coupon_box.php'; ?>
        <div class="sum-row"><span>Subtotal</span><span><?= money($sum['subtotal']) ?></span></div>
        <?php if ($sum['menu_saving'] > 0): ?><div class="sum-row save-row"><span>Menu discounts</span><span>You saved <?= money($sum['menu_saving']) ?></span></div><?php endif; ?>
        <?php if ($sum['discount'] > 0): ?><div class="sum-row save-row"><span>Coupon <?= e($sum['coupon']['code']) ?></span><span>&minus;<?= money($sum['discount']) ?></span></div><?php endif; ?>
        <div class="sum-row sum-total"><span>Total (without delivery)</span><span><?= money($sum['after']) ?></span></div>
        <p class="small muted" style="margin:-8px 0 10px">Delivery charge is added at checkout by your area.</p>
        <a class="btn btn-block" href="<?= BASE_URL ?>/checkout.php">Checkout &rarr;</a>
        <a class="btn btn-block btn-outline" style="margin-top:8px" href="<?= BASE_URL ?>/cart.php">View full cart</a>
    </div>
<?php endif; ?>
