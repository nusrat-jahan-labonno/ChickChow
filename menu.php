<?php
require_once 'includes/functions.php';
$page_title = 'Menu';
$active = $deals_flag = isset($_GET['deals']) ? 'deals' : 'menu';

$q   = clean($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);
$rid = (int)($_GET['rest'] ?? 0);
$deals = isset($_GET['deals']);
if ($q !== '') { $cat = 0; $rid = 0; }   // search overrides the category/restaurant filters

$categories  = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$restaurants = $pdo->query("SELECT id, name FROM restaurants ORDER BY name")->fetchAll();

$sql = "SELECT f.*, r.name AS restaurant_name FROM food_items f
        JOIN restaurants r ON r.id = f.restaurant_id
        JOIN categories c ON c.id = f.category_id WHERE 1=1";
$params = [];
if ($q !== '') {
    // Every word must match the food name, description, restaurant, cuisine or category
    foreach (preg_split('/\s+/', $q) as $word) {
        if ($word === '') continue;
        $sql .= " AND (f.name LIKE ? OR f.description LIKE ? OR r.name LIKE ? OR r.cuisine LIKE ? OR r.area LIKE ? OR c.name LIKE ?)";
        array_push($params, "%$word%", "%$word%", "%$word%", "%$word%", "%$word%", "%$word%");
    }
}
if ($cat > 0) { $sql .= " AND f.category_id = ?"; $params[] = $cat; }
if ($rid > 0) { $sql .= " AND f.restaurant_id = ?"; $params[] = $rid; }
if ($deals) $sql .= " AND f.discount_percent > 0";
$sql .= $deals ? " ORDER BY f.discount_percent DESC, f.name" : " ORDER BY f.status, f.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$foods = $stmt->fetchAll();

function menu_link($cat, $rid) {
    $qs = http_build_query(array_filter(['cat' => $cat, 'rest' => $rid]));
    return BASE_URL . '/menu.php' . ($qs ? '?' . $qs : '');
}

require 'includes/header.php';
?>
<div class="container page-wrap">
    <h1 class="page-title"><?= $deals ? '&#128293; Hot Deals' : 'Our Menu' ?></h1>
    <p class="page-sub"><?= $deals ? 'Foods with a special discount right now.' : 'Freshly prepared. Always delicious!' ?></p>

    <form class="big-search" method="get">
        <input type="text" name="q" placeholder="Search food, restaurant or category..." value="<?= e($q) ?>">
        <button class="btn btn-small" type="submit">Search</button>
    </form>

    <div class="chips">
        <a class="chip <?= ($cat === 0 && $q === '' && !$deals) ? 'active' : '' ?>" href="<?= menu_link(0, $rid) ?>">All</a>
        <a class="chip chip-deal <?= $deals ? 'active' : '' ?>" href="<?= BASE_URL ?>/menu.php?deals=1">&#128293; Deals</a>
        <?php foreach ($categories as $c): ?>
            <a class="chip <?= $cat === (int)$c['id'] ? 'active' : '' ?>" href="<?= menu_link($c['id'], $rid) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <form method="get" class="filter-row">
        <?php if ($cat): ?><input type="hidden" name="cat" value="<?= $cat ?>"><?php endif; ?>
        <?php if ($deals): ?><input type="hidden" name="deals" value="1"><?php endif; ?>
        <label for="rest" class="muted small">Restaurant:</label>
        <select name="rest" id="rest" onchange="this.form.submit()">
            <option value="0">All restaurants</option>
            <?php foreach ($restaurants as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= $rid === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <span class="muted small"><?= count($foods) ?> items</span>
        <?php if ($q !== ''): ?><span class="search-note">Results for &ldquo;<?= e($q) ?>&rdquo; <a href="<?= BASE_URL ?>/menu.php">Clear</a></span><?php endif; ?>
    </form>

    <div class="grid grid-4">
        <?php foreach ($foods as $f): ?>
            <?php require 'includes/food_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php if (!$foods): ?>
        <div class="empty"><img src="<?= BASE_URL ?>/assets/images/mascot-sad.png" alt=""><p>No food found. Try a different search.</p></div>
    <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
