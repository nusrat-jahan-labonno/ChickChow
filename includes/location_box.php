<?php
/* Delivery location chooser (used in the profile dropdown and on the home page).
   Needs $pdo. Optional: $loc_id (unique suffix for element ids). */
$loc_id    = $loc_id ?? 'x';
$loc_list  = user_addresses($pdo);
$loc_cur   = current_delivery_address($pdo);
$loc_return = $_SERVER['REQUEST_URI'] ?? '/';
?>
<div class="loc-box">
    <div class="loc-current">
        &#128205;
        <?php if ($loc_cur): ?>
            <span><b><?= e($loc_cur['label']) ?></b> &ndash; <?= e($loc_cur['area']) ?><br><small class="muted"><?= e($loc_cur['address']) ?></small></span>
        <?php else: ?>
            <span class="muted">No location saved yet. Add one below.</span>
        <?php endif; ?>
    </div>

    <?php if ($loc_list): ?>
    <form method="post" action="<?= BASE_URL ?>/set_location.php" class="loc-select">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="select">
        <input type="hidden" name="return" value="<?= e($loc_return) ?>">
        <select name="address_id" aria-label="Choose saved location" onchange="this.form.submit()">
            <?php foreach ($loc_list as $a): ?>
                <option value="<?= (int)$a['id'] ?>" <?= $loc_cur && (int)$loc_cur['id'] === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['label']) ?>: <?= e($a['area']) ?>, <?= e($a['address']) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button class="btn btn-small">Use</button></noscript>
    </form>
    <?php endif; ?>

    <details class="loc-add">
        <summary>+ Add / update location</summary>
        <form method="post" action="<?= BASE_URL ?>/set_location.php" novalidate data-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="op" value="add">
            <input type="hidden" name="return" value="<?= e($loc_return) ?>">
            <div class="form-group"><input type="text" name="label" placeholder="Label (Home, Work, Friend...)" required maxlength="30"></div>
            <div class="form-group"><select name="area" required><?= area_options('', '-- Select your area --') ?></select></div>
            <div class="form-group"><textarea name="address" rows="2" placeholder="House / Road / Flat (e.g. House 12, Road 5)" required minlength="3"></textarea></div>
            <button class="btn btn-small btn-block">Save &amp; use this location</button>
        </form>
    </details>
</div>
