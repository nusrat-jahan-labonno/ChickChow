<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$r = ['name' => '', 'cuisine' => '', 'area' => '', 'rating' => '4.0', 'image' => null];
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?"); $stmt->execute([$id]);
    $r = $stmt->fetch();
    if (!$r) { set_flash('error', 'Restaurant not found.'); redirect('admin/restaurants.php'); }
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $r['name'] = clean($_POST['name'] ?? ''); $r['cuisine'] = clean($_POST['cuisine'] ?? '');
    $r['area'] = $_POST['area'] ?? '';
    $r['rating'] = clean($_POST['rating'] ?? '');

    if ($r['name'] === '' || mb_strlen($r['name']) > 120) $errors['name'] = 'Name is required (max 120 characters).';
    if ($r['cuisine'] === '' || mb_strlen($r['cuisine']) > 60) $errors['cuisine'] = 'Cuisine is required (max 60 characters).';
    if (!valid_area($r['area'])) $errors['area'] = 'Select the restaurant area.';
    if (!is_numeric($r['rating']) || $r['rating'] < 0 || $r['rating'] > 5) $errors['rating'] = 'Rating must be between 0 and 5.';

    [$image, $imgErr] = handle_image_upload('image', $r['image']);
    if ($imgErr) $errors['image'] = $imgErr;

    if (!$errors) {
        if ($id) {
            $pdo->prepare("UPDATE restaurants SET name=?, cuisine=?, area=?, rating=?, image=? WHERE id=?")
                ->execute([$r['name'], $r['cuisine'], $r['area'], $r['rating'], $image ?: 'placeholder.jpg', $id]);
            set_flash('success', 'Restaurant updated.');
        } else {
            $pdo->prepare("INSERT INTO restaurants (name, cuisine, area, rating, image) VALUES (?,?,?,?,?)")
                ->execute([$r['name'], $r['cuisine'], $r['area'], $r['rating'], $image ?: 'placeholder.jpg']);
            set_flash('success', 'Restaurant added.');
        }
        redirect('admin/restaurants.php');
    }
}

$page_title = $id ? 'Edit Restaurant' : 'Add Restaurant'; $nav = 'restaurants';
require '_header.php';
?>
<div class="form-card" style="max-width:640px">
<form method="post" enctype="multipart/form-data" novalidate data-validate>
    <?= csrf_field() ?>
    <div class="form-group"><label>Name</label><input type="text" name="name" value="<?= e($r['name']) ?>" required maxlength="120">
        <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?></div>
    <div class="form-group"><label>Cuisine</label><input type="text" name="cuisine" value="<?= e($r['cuisine']) ?>" required maxlength="60" placeholder="Italian, Fast Food...">
        <?php if (isset($errors['cuisine'])): ?><div class="field-error"><?= e($errors['cuisine']) ?></div><?php endif; ?></div>
    <div class="grid grid-2">
        <div class="form-group"><label>Area (Dhaka)</label>
            <select name="area" required><?= area_options($r['area']) ?></select>
            <?php if (isset($errors['area'])): ?><div class="field-error"><?= e($errors['area']) ?></div><?php endif; ?></div>
        <div class="form-group"><label>Rating (0-5)</label><input type="number" name="rating" value="<?= e($r['rating']) ?>" step="0.1" min="0" max="5" required>
            <?php if (isset($errors['rating'])): ?><div class="field-error"><?= e($errors['rating']) ?></div><?php endif; ?></div>
    </div>
    <div class="form-group"><label>Image (JPG/PNG/WEBP, max 2MB)</label>
        <?php if ($r['image']): ?><img class="thumb-lg" src="<?= img_url($r['image']) ?>" alt=""><?php endif; ?>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <?php if (isset($errors['image'])): ?><div class="field-error"><?= e($errors['image']) ?></div><?php endif; ?></div>
    <button class="btn">Save</button> <a class="btn btn-outline" href="restaurants.php">Cancel</a>
</form></div>
<?php require '_footer.php'; ?>
