<?php /* expects $f (food row incl. restaurant_name) */ ?>
<div class="card food-card">
    <div class="card-img"><?php if (!empty($f['discount_percent'])): ?><span class="off-badge"><?= (int)$f['discount_percent'] ?>% OFF</span><?php endif; ?><img src="<?= img_url($f['image']) ?>" alt="<?= e($f['name']) ?>" loading="lazy"></div>
    <div class="card-body">
        <h3><?= e($f['name']) ?></h3>
        <p class="muted small"><?= e($f['restaurant_name'] ?? '') ?></p>
        <div class="food-foot">
            <?= price_html($f) ?>
            <?php if ($f['status'] === 'available'): ?>
                <form method="post" action="<?= BASE_URL ?>/cart.php" data-ajax-cart>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="food_id" value="<?= (int)$f['id'] ?>">
                    <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                    <button type="submit" class="add-btn" title="Add to cart">+</button>
                </form>
            <?php else: ?>
                <span class="status status-unavailable">Unavailable</span>
            <?php endif; ?>
        </div>
    </div>
</div>
