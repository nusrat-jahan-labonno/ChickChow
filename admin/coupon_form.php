<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$c = ['code' => '', 'description' => '', 'type' => 'percent', 'value' => '', 'min_order' => 0, 'max_discount' => 0, 'expires_at' => '', 'is_active' => 1];
if ($id) {
    $st = $pdo->prepare("SELECT * FROM coupons WHERE id = ?"); $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) { set_flash('error', 'Coupon not found.'); redirect('admin/coupons.php'); }
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $c['code']         = strtoupper(clean($_POST['code'] ?? ''));
    $c['description']  = clean($_POST['description'] ?? '');
    $c['type']         = ($_POST['type'] ?? '') === 'fixed' ? 'fixed' : 'percent';
    $c['value']        = clean($_POST['value'] ?? '');
    $c['min_order']    = clean($_POST['min_order'] ?? '0');
    $c['max_discount'] = clean($_POST['max_discount'] ?? '0');
    $c['expires_at']   = clean($_POST['expires_at'] ?? '');
    $c['is_active']    = isset($_POST['is_active']) ? 1 : 0;

    if (!preg_match('/^[A-Z0-9]{3,20}$/', $c['code'])) $errors['code'] = 'Code must be 3 to 20 letters/numbers (no spaces).';
    else {
        $dup = $pdo->prepare("SELECT id FROM coupons WHERE code = ? AND id <> ?"); $dup->execute([$c['code'], $id]);
        if ($dup->fetch()) $errors['code'] = 'This code already exists.';
    }
    if (mb_strlen($c['description']) > 150) $errors['description'] = 'Description max 150 characters.';
    if (!ctype_digit($c['value']) || (int)$c['value'] < 1 || ($c['type'] === 'percent' && (int)$c['value'] > 90)) $errors['value'] = $c['type'] === 'percent' ? 'Percent must be a whole number from 1 to 90.' : 'Enter a valid amount (whole number, 1 or more).';
    if (!ctype_digit($c['min_order'])) $errors['min_order'] = 'Minimum order must be 0 or more.';
    if (!ctype_digit($c['max_discount'])) $errors['max_discount'] = 'Max discount must be 0 or more (0 = no limit).';
    if ($c['expires_at'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $c['expires_at'])) $errors['expires_at'] = 'Enter a valid date.';

    if (!$errors) {
        $vals = [$c['code'], $c['description'], $c['type'], (int)$c['value'], (int)$c['min_order'], (int)$c['max_discount'], $c['expires_at'] !== '' ? $c['expires_at'] : null, $c['is_active']];
        if ($id) {
            $pdo->prepare("UPDATE coupons SET code=?, description=?, type=?, value=?, min_order=?, max_discount=?, expires_at=?, is_active=? WHERE id=?")->execute([...$vals, $id]);
            set_flash('success', 'Coupon updated.');
        } else {
            $pdo->prepare("INSERT INTO coupons (code, description, type, value, min_order, max_discount, expires_at, is_active) VALUES (?,?,?,?,?,?,?,?)")->execute($vals);
            set_flash('success', 'Coupon added.');
        }
        redirect('admin/coupons.php');
    }
}
$page_title = $id ? 'Edit Coupon' : 'Add Coupon'; $nav = 'coupons';
require '_header.php';
?>
<div class="form-card" style="max-width:640px">
<form method="post" novalidate data-validate>
    <?= csrf_field() ?>
    <div class="form-group"><label>Coupon Code</label><input type="text" name="code" value="<?= e($c['code']) ?>" required maxlength="20" style="text-transform:uppercase">
        <?php if (isset($errors['code'])): ?><div class="field-error"><?= e($errors['code']) ?></div><?php endif; ?></div>
    <div class="form-group"><label>Description (shown on the home page)</label><input type="text" name="description" value="<?= e($c['description']) ?>" maxlength="150" placeholder="10% off on orders above 300">
        <?php if (isset($errors['description'])): ?><div class="field-error"><?= e($errors['description']) ?></div><?php endif; ?></div>
    <div class="grid grid-2">
        <div class="form-group"><label>Type</label><select name="type"><option value="percent" <?= $c['type'] === 'percent' ? 'selected' : '' ?>>Percent (%)</option><option value="fixed" <?= $c['type'] === 'fixed' ? 'selected' : '' ?>>Fixed amount (<?= CURRENCY ?>)</option></select></div>
        <div class="form-group"><label>Value</label><input type="number" name="value" value="<?= e($c['value']) ?>" min="1" required>
            <?php if (isset($errors['value'])): ?><div class="field-error"><?= e($errors['value']) ?></div><?php endif; ?></div>
        <div class="form-group"><label>Minimum order (<?= CURRENCY ?>)</label><input type="number" name="min_order" value="<?= e($c['min_order']) ?>" min="0">
            <?php if (isset($errors['min_order'])): ?><div class="field-error"><?= e($errors['min_order']) ?></div><?php endif; ?></div>
        <div class="form-group"><label>Max discount (<?= CURRENCY ?>, percent type, 0 = no limit)</label><input type="number" name="max_discount" value="<?= e($c['max_discount']) ?>" min="0">
            <?php if (isset($errors['max_discount'])): ?><div class="field-error"><?= e($errors['max_discount']) ?></div><?php endif; ?></div>
        <div class="form-group"><label>Expiry date (empty = never)</label><input type="date" name="expires_at" value="<?= e($c['expires_at']) ?>">
            <?php if (isset($errors['expires_at'])): ?><div class="field-error"><?= e($errors['expires_at']) ?></div><?php endif; ?></div>
    </div>
    <label class="radio"><input type="checkbox" name="is_active" value="1" <?= $c['is_active'] ? 'checked' : '' ?>> Active</label>
    <p style="margin-top:14px"><button class="btn">Save</button> <a class="btn btn-outline" href="coupons.php">Cancel</a></p>
</form></div>
<?php require '_footer.php'; ?>
