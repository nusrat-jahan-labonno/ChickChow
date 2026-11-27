<?php
/* Coupon apply / remove box. Needs $sum (cart_summary), $ret (return page).
   $ajax (bool) makes the forms update the drawer without reloading. */
$ajax = $ajax ?? true;
$attr = $ajax ? ' data-panel-form' : '';
?>
<div class="coupon-box">
    <?php if ($sum['coupon']): ?>
        <div class="coupon-applied">
            <span>&#127903; <b><?= e($sum['coupon']['code']) ?></b> applied &ndash; you save <?= money($sum['discount']) ?></span>
            <form method="post" action="<?= BASE_URL ?>/cart.php"<?= $attr ?>><?= csrf_field() ?>
                <input type="hidden" name="action" value="coupon_remove"><input type="hidden" name="return" value="<?= e($ret) ?>">
                <button class="link-btn">Remove</button></form>
        </div>
    <?php else: ?>
        <form method="post" action="<?= BASE_URL ?>/cart.php" class="coupon-form"<?= $attr ?>><?= csrf_field() ?>
            <input type="hidden" name="action" value="coupon_apply"><input type="hidden" name="return" value="<?= e($ret) ?>">
            <input type="text" name="code" placeholder="Coupon code" maxlength="30" value="<?= e($sum['coupon_code']) ?>" autocomplete="off" aria-label="Coupon code">
            <button class="btn btn-small">Apply</button>
        </form>
        <?php if ($sum['coupon_note']): ?><div class="field-error"><?= e($sum['coupon_note']) ?></div><?php endif; ?>
        <?php if (!empty($offers)): ?><div class="coupon-hints">Try: <?php foreach ($offers as $oc): ?><button type="button" class="hint-chip" data-code="<?= e($oc) ?>"><?= e($oc) ?></button><?php endforeach; ?></div><?php endif; ?>
    <?php endif; ?>
</div>
