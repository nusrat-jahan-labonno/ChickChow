<?php
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { http_response_code(404); set_flash('error', 'Restaurant not found.'); redirect('restaurants.php'); }

$stmt = $pdo->prepare("SELECT f.*, c.name AS category_name, ? AS restaurant_name
                       FROM food_items f JOIN categories c ON c.id = f.category_id
                       WHERE f.restaurant_id = ? ORDER BY c.name, f.name");
$stmt->execute([$r['name'], $id]);
$foods = $stmt->fetchAll();

$page_title = $r['name'];
$active = 'restaurants';
require 'includes/header.php';
?>
<section class="rest-hero">
    <img class="bg" src="<?= img_url($r['image']) ?>" alt="">
    <img class="main" src="<?= img_url($r['image']) ?>" alt="<?= e($r['name']) ?>">
</section>
<div class="container">
    <div class="rest-info card">
        <div>
            <h1><?= e($r['name']) ?></h1>
            <p class="muted"><?= e($r['cuisine']) ?> <i class="dot"></i> &#128205; <?= e($r['area']) ?><?php $ca = current_delivery_address($pdo); if ($ca && ($km = area_distance_km($r['area'], $ca['area'])) !== null): ?> <i class="dot"></i> <?= $km ?> km from you<?php endif; ?></p>
        </div>
        <div class="rest-rating">&#9733; <?= number_format((float)$r['rating'], 1) ?></div>
    </div>

    <div class="section-head" style="margin-top:28px"><h2>Menu</h2><span class="muted small"><?= count($foods) ?> items</span></div>
    <div class="grid grid-4">
        <?php foreach ($foods as $f): ?>
            <?php require 'includes/food_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php if (!$foods): ?><p class="muted">No items yet.</p><?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
