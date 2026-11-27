<?php
require_once 'includes/functions.php';

// Renders the drawer content (used for AJAX replies)
function render_panel(PDO $pdo) {
    ob_start();
    include __DIR__ . '/includes/cart_panel.php';
    return ob_get_clean();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action  = $_POST['action'] ?? '';
    $food_id = (int)($_POST['food_id'] ?? 0);
    $return  = safe_return($_POST['return'] ?? '', 'cart.php');
    $msg = ''; $ok = true;

    if ($action === 'add' && $food_id > 0) {
        $stmt = $pdo->prepare("SELECT name FROM food_items WHERE id = ? AND status = 'available'");
        $stmt->execute([$food_id]);
        if ($row = $stmt->fetch()) {
            $_SESSION['cart'][$food_id] = min(20, ($_SESSION['cart'][$food_id] ?? 0) + 1);
            $msg = $row['name'] . ' added to cart';
        } else { $ok = false; $msg = 'This item is not available right now.'; }
    } elseif ($action === 'update' && $food_id > 0 && isset($_SESSION['cart'][$food_id])) {
        $qty = (int)($_POST['qty'] ?? 1);
        if ($qty <= 0) unset($_SESSION['cart'][$food_id]);
        else $_SESSION['cart'][$food_id] = min(20, $qty);
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$food_id]);
    } elseif ($action === 'clear') {
        unset($_SESSION['cart'], $_SESSION['coupon']);
    } elseif ($action === 'coupon_apply') {
        $sub = cart_summary($pdo)['subtotal'];
        [$cok, $cmsg, $c, $d] = check_coupon($pdo, $_POST['code'] ?? '', $sub);
        if ($c) $_SESSION['coupon'] = $c['code']; else unset($_SESSION['coupon']);
        $ok = $cok; $msg = $cmsg;
    } elseif ($action === 'coupon_remove') {
        unset($_SESSION['coupon']);
        $msg = 'Coupon removed.';
    }

    if (is_ajax()) {
        $out = ['ok' => $ok, 'message' => $msg, 'count' => cart_count()];
        if (($_POST['want_panel'] ?? '') === '1' || $action !== 'add') $out['panel'] = render_panel($pdo);
        json_out($out);
    }
    if ($msg !== '') set_flash($ok ? 'success' : 'error', $msg);
    header('Location: ' . $return);
    exit;
}

// Drawer content on demand
if (is_ajax() && isset($_GET['panel'])) {
    json_out(['ok' => true, 'count' => cart_count(), 'panel' => render_panel($pdo)]);
}

$page_title = 'Your Cart';
$sum = cart_summary($pdo);
$items = $sum['items'];
$cur_addr = is_logged_in() ? current_delivery_address($pdo) : null;
$quote = ($items && $cur_addr) ? delivery_quote($items, $cur_addr['area']) : null;
$delivery = $quote ? $quote['total'] : 0;
$total = $sum['after'] + $delivery;
$offers = $pdo->query("SELECT code FROM coupons WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY min_order LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
$ret = 'cart.php'; $ajax = false;

require 'includes/header.php';
?>
<div class="container page-wrap">
    <h1 class="page-title">Your Cart</h1>
    <p class="page-sub"><?= cart_count() ?> items</p>

    <?php if (!$items): ?>
        <div class="empty">
            <img src="<?= BASE_URL ?>/assets/images/mascot-sad.png" alt="">
            <h2>Your cart is empty</h2>
            <p class="muted">Add some food to get started!</p>
            <a class="btn" href="<?= BASE_URL ?>/menu.php">Browse Menu</a>
        </div>
    <?php else: ?>
    <div class="two-col">
        <div class="cart-list">
            <?php foreach ($items as $it): ?>
                <div class="cart-item card">
                    <img src="<?= img_url($it['image']) ?>" alt="<?= e($it['name']) ?>">
                    <div class="cart-info">
                        <h3><?= e($it['name']) ?></h3>
                        <p class="muted small"><?= e($it['restaurant_name']) ?></p>
                        <p class="price"><?= money($it['unit']) ?><?php if ($it['discount_percent'] > 0): ?> <s class="old-price"><?= money($it['price']) ?></s> <span class="off-tag"><?= (int)$it['discount_percent'] ?>% OFF</span><?php endif; ?></p>
                    </div>
                    <div class="qty-box">
                        <form method="post" class="keep-scroll"><?= csrf_field() ?>
                            <input type="hidden" name="action" value="update"><input type="hidden" name="food_id" value="<?= (int)$it['id'] ?>">
                            <input type="hidden" name="qty" value="<?= $it['qty'] - 1 ?>"><button class="qty-btn" aria-label="Decrease">&minus;</button>
                        </form>
                        <span><?= (int)$it['qty'] ?></span>
                        <form method="post" class="keep-scroll"><?= csrf_field() ?>
                            <input type="hidden" name="action" value="update"><input type="hidden" name="food_id" value="<?= (int)$it['id'] ?>">
                            <input type="hidden" name="qty" value="<?= $it['qty'] + 1 ?>"><button class="qty-btn" aria-label="Increase">+</button>
                        </form>
                    </div>
                    <div class="cart-total"><?= money($it['total']) ?></div>
                    <form method="post" class="keep-scroll"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove"><input type="hidden" name="food_id" value="<?= (int)$it['id'] ?>">
                        <button class="icon-btn" title="Remove" aria-label="Remove">&#128465;</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <div class="cart-actions">
                <a href="<?= BASE_URL ?>/menu.php" class="btn btn-outline">Continue Shopping</a>
                <form method="post" onsubmit="return confirm('Clear the whole cart?')"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="clear"><button class="btn btn-outline btn-danger-outline">Clear Cart</button>
                </form>
            </div>
        </div>

        <aside class="summary card">
            <h2>Order Summary</h2>
            <?php include 'includes/coupon_box.php'; ?>
            <div class="sum-row"><span>Subtotal</span><span><?= money($sum['subtotal']) ?></span></div>
            <?php if ($sum['menu_saving'] > 0): ?><div class="sum-row save-row"><span>Menu discounts</span><span>You saved <?= money($sum['menu_saving']) ?></span></div><?php endif; ?>
            <?php if ($sum['discount'] > 0): ?><div class="sum-row save-row"><span>Coupon <?= e($sum['coupon']['code']) ?></span><span>&minus;<?= money($sum['discount']) ?></span></div><?php endif; ?>
            <div class="sum-row"><span>Delivery Charge</span><span><?= $quote ? money($delivery) : 'At checkout' ?></span></div>
            <?php if ($quote): ?><div class="del-parts"><?php foreach ($quote['parts'] as $pt): ?><div><?= e($pt['name']) ?> &rarr; <?= e($cur_addr['area']) ?> &middot; <?= e($pt['km']) ?> km &middot; <?= money($pt['fee']) ?></div><?php endforeach; ?></div><?php else: ?><div class="del-parts">Charge depends on your area and the restaurant location.</div><?php endif; ?>
            <div class="sum-row sum-total"><span>Total</span><span><?= money($total) ?></span></div>
            <a class="btn btn-block" href="<?= BASE_URL ?>/checkout.php">Proceed to Checkout &rarr;</a>
        </aside>
    </div>
    <?php endif; ?>
</div>
<?php $no_drawer = true; require 'includes/footer.php'; ?>
