<?php /* expects $r (restaurant row) */ ?>
<a class="card rest-card" href="<?= BASE_URL ?>/restaurant.php?id=<?= (int)$r['id'] ?>">
    <div class="card-img"><img src="<?= img_url($r['image']) ?>" alt="<?= e($r['name']) ?>" loading="lazy"></div>
    <div class="card-body">
        <h3><?= e($r['name']) ?></h3>
        <p class="muted small"><?= e($r['cuisine']) ?></p>
        <p class="rest-loc">&#128205; <?= e($r['area']) ?><?php $ca = current_delivery_address($pdo); if ($ca && ($km = area_distance_km($r['area'], $ca['area'])) !== null): ?> <i class="dot"></i> <?= $km ?> km from you<?php endif; ?></p>
        <p class="rating">&#9733; <?= number_format((float)$r['rating'], 1) ?></p>
    </div>
</a>
