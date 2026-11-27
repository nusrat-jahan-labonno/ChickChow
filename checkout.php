<?php
require_once 'includes/functions.php';
require_login();

$sum = cart_summary($pdo);
$items = $sum['items'];
if (!$items) { set_flash('error', 'Your cart is empty. Add some food first.'); redirect('menu.php'); }

$subtotal = $sum['subtotal'];
$discount = $sum['discount'];
$after    = $sum['after'];          // food total after the coupon

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();
$cur = current_delivery_address($pdo);

$errors = [];
$old = [
    'full_name' => $user['name'],
    'phone'     => $user['phone'] ?? '',
    'area'      => $cur['area'] ?? '',
    'address'   => $cur['address'] ?? '',
    'payment'   => 'cod',
];

// Delivery charge for every area (used for the live total on this page)
$quotes = [];
foreach (array_keys(DHAKA_AREAS) as $a) $quotes[$a] = delivery_quote($items, $a);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['full_name'] = clean($_POST['full_name'] ?? '');
    $old['phone']     = clean($_POST['phone'] ?? '');
    $old['area']      = $_POST['area'] ?? '';
    $old['address']   = clean($_POST['address'] ?? '');
    $old['payment']   = $_POST['payment'] ?? 'cod';

    if (mb_strlen($old['full_name']) < 2)   $errors['full_name'] = 'Name is required.';
    if (!valid_phone($old['phone']))        $errors['phone'] = 'Enter a valid phone number (01712345678).';
    if (!valid_area($old['area']))          $errors['area'] = 'Please select your delivery area.';
    if (mb_strlen($old['address']) < 3)     $errors['address'] = 'Enter house / road details (at least 3 characters).';
    if (!in_array($old['payment'], ['cod', 'online'], true)) $errors['payment'] = 'Select a payment method.';

    if (!$errors) {
        $delivery = $quotes[$old['area']]['total'];
        $total = $after + $delivery;
        $ccode = $sum['coupon'] ? $sum['coupon']['code'] : null;
        $cid   = $sum['coupon'] ? (int)$sum['coupon']['id'] : null;
        try {
            $pdo->beginTransaction();
            $ins = $pdo->prepare("INSERT INTO orders (user_id, full_name, phone, delivery_area, address, subtotal, discount, coupon_id, coupon_code, delivery_charge, total, payment_method)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$_SESSION['user_id'], $old['full_name'], $old['phone'], $old['area'], $old['address'],
                           $subtotal, $discount, $cid, $ccode, $delivery, $total, $old['payment']]);
            $order_id = (int)$pdo->lastInsertId();

            $oi = $pdo->prepare("INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($items as $it) {
                $oi->execute([$order_id, $it['id'], $it['qty'], $it['unit']]);   // discounted price is saved
            }
            $pdo->commit();
            unset($_SESSION['cart'], $_SESSION['coupon']);
            redirect('order_success.php?id=' . $order_id);
        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors['general'] = 'Could not place the order. Please try again.';
        }
    }
}

$q0 = $old['area'] !== '' ? $quotes[$old['area']] : null;
$page_title = 'Checkout';
require 'includes/header.php';
?>
<div class="container page-wrap">
    <h1 class="page-title">Checkout</h1>
    <p class="breadcrumb"><a href="<?= BASE_URL ?>/cart.php">Cart</a> &rsaquo; <b>Checkout</b></p>

    <?php if (isset($errors['general'])): ?><div class="alert alert-error"><?= e($errors['general']) ?></div><?php endif; ?>

    <form method="post" novalidate data-validate class="two-col" id="checkoutForm" data-quotes="<?= e(json_encode($quotes)) ?>" data-subtotal="<?= (float)$after ?>" data-cur="<?= e(CURRENCY) ?>">
        <?= csrf_field() ?>
        <div class="form-card">
            <h2>Delivery Information</h2>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required minlength="2">
                <?php if (isset($errors['full_name'])): ?><div class="field-error"><?= e($errors['full_name']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" required>
                <?php if (isset($errors['phone'])): ?><div class="field-error"><?= e($errors['phone']) ?></div><?php endif; ?>
            </div>
            <?php if ($addresses): ?>
            <div class="form-group">
                <label for="saved">Saved Address <a class="link-small" href="<?= BASE_URL ?>/profile.php">(manage)</a></label>
                <select id="saved">
                    <option value="">-- Choose saved address --</option>
                    <?php foreach ($addresses as $a): ?>
                        <option value="<?= (int)$a['id'] ?>" data-area="<?= e($a['area']) ?>" data-addr="<?= e($a['address']) ?>"><?= e($a['label']) ?>: <?= e($a['area']) ?>, <?= e($a['address']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="area">Delivery Area</label>
                <select id="area" name="area" required><?= area_options($old['area'], '-- Select your area --') ?></select>
                <?php if (isset($errors['area'])): ?><div class="field-error"><?= e($errors['area']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="address">House / Road</label>
                <textarea id="address" name="address" required minlength="3" placeholder="House 12, Road 5"><?= e($old['address']) ?></textarea>
                <?php if (isset($errors['address'])): ?><div class="field-error"><?= e($errors['address']) ?></div><?php endif; ?>
            </div>
        </div>

        <aside class="summary card">
            <h2>Order Summary</h2>
            <?php foreach ($items as $it): ?>
                <div class="sum-row"><span><?= e($it['name']) ?> <small class="muted">x<?= (int)$it['qty'] ?></small></span><span><?= money($it['total']) ?><?php if ($it['discount_percent'] > 0): ?> <s class="old-price"><?= money($it['total_old']) ?></s><?php endif; ?></span></div>
            <?php endforeach; ?>
            <hr>
            <div class="sum-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
            <?php if ($sum['menu_saving'] > 0): ?><div class="sum-row save-row"><span>Menu discounts</span><span>You saved <?= money($sum['menu_saving']) ?></span></div><?php endif; ?>
            <?php if ($discount > 0): ?><div class="sum-row save-row"><span>Coupon <?= e($sum['coupon']['code']) ?></span><span>&minus;<?= money($discount) ?></span></div><?php endif; ?>
            <div class="sum-row"><span>Delivery Charge</span><span id="delFee"><?= $q0 ? money($q0['total']) : '&mdash;' ?></span></div>
            <div class="del-parts" id="delParts"><?php if ($q0): foreach ($q0['parts'] as $pt): ?><div><?= e($pt['name']) ?> &middot; <?= e($pt['km']) ?> km &middot; <?= money($pt['fee']) ?></div><?php endforeach; else: ?>Select your area to see the delivery charge.<?php endif; ?></div>
            <div class="sum-row sum-total"><span>Total</span><span id="grandTotal"><?= $q0 ? money($after + $q0['total']) : money($after) ?></span></div>

            <div class="coupon-box">
            <?php if ($sum['coupon']): ?>
                <div class="coupon-applied"><span>&#127903; <b><?= e($sum['coupon']['code']) ?></b> applied</span>
                    <button class="link-btn" type="submit" form="couponRemoveForm">Remove</button></div>
            <?php else: ?>
                <div class="coupon-form"><input type="text" name="code" form="couponApplyForm" placeholder="Coupon code" maxlength="30" value="<?= e($sum['coupon_code']) ?>" autocomplete="off" aria-label="Coupon code"><button class="btn btn-small" type="submit" form="couponApplyForm">Apply</button></div>
                <?php if ($sum['coupon_note']): ?><div class="field-error"><?= e($sum['coupon_note']) ?></div><?php endif; ?>
            <?php endif; ?>
            </div>

            <h3 style="margin:16px 0 8px">Payment Method</h3>
            <label class="radio"><input type="radio" name="payment" value="cod" <?= $old['payment'] === 'cod' ? 'checked' : '' ?>> Cash on Delivery</label>
            <label class="radio"><input type="radio" name="payment" value="online" <?= $old['payment'] === 'online' ? 'checked' : '' ?>> Online Payment <small class="muted">(Bkash, Nagad, Card)</small></label>
            <?php if (isset($errors['payment'])): ?><div class="field-error"><?= e($errors['payment']) ?></div><?php endif; ?>

            <button class="btn btn-block btn-large" type="submit" style="margin-top:16px">Confirm Order</button>
        </aside>
    </form>
</div>
<?php /* separate tiny forms for the coupon box (forms cannot be nested) */ ?>
<form id="couponApplyForm" method="post" action="<?= BASE_URL ?>/cart.php"><?= csrf_field() ?><input type="hidden" name="action" value="coupon_apply"><input type="hidden" name="return" value="<?= BASE_URL ?>/checkout.php"></form>
<form id="couponRemoveForm" method="post" action="<?= BASE_URL ?>/cart.php"><?= csrf_field() ?><input type="hidden" name="action" value="coupon_remove"><input type="hidden" name="return" value="<?= BASE_URL ?>/checkout.php"></form>
<?php require 'includes/footer.php'; ?>
