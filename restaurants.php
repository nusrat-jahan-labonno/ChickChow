<?php
require_once 'includes/functions.php';
$page_title = 'Restaurants';
$active = 'restaurants';

$q = clean($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE name LIKE ? OR cuisine LIKE ? OR area LIKE ?
                           OR id IN (SELECT restaurant_id FROM food_items WHERE name LIKE ?) ORDER BY rating DESC");
    $stmt->execute(["%$q%", "%$q%", "%$q%", "%$q%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM restaurants ORDER BY rating DESC");
}
$restaurants = $stmt->fetchAll();

require 'includes/header.php';
?>
<div class="container page-wrap">
    <h1 class="page-title">Restaurants</h1>
    <p class="page-sub">Pick a restaurant and see its menu</p>

    <form class="big-search" method="get">
        <input type="text" name="q" placeholder="Search restaurant, area, cuisine or food..." value="<?= e($q) ?>">
        <button class="btn btn-small" type="submit">Search</button>
    </form>

    <div class="grid grid-4" style="margin-top:24px">
        <?php foreach ($restaurants as $r): ?>
            <?php require 'includes/rest_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php if (!$restaurants): ?>
        <div class="empty"><img src="<?= BASE_URL ?>/assets/images/mascot-sad.png" alt=""><p>No restaurant found.</p></div>
    <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
