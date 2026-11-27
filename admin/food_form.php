<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$f = ['restaurant_id' => (int)($_GET['rest'] ?? 0), 'category_id' => 0, 'name' => '', 'description' => '', 'price' => '', 'discount_percent' => 0, 'image' => null, 'status' => 'available'];
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM food_items WHERE id = ?"); $stmt->execute([$id]);
    $f = $stmt->fetch();
    if (!$f) { set_flash('error', 'Food not found.'); redirect('admin/foods.php'); }
}
$restaurants = $pdo->query("SELECT id, name FROM restaurants ORDER BY name")->fetchAll();
$categories  = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $f['restaurant_id'] = (int)($_POST['restaurant_id'] ?? 0);
    $f['category_id']   = (int)($_POST['category_id'] ?? 0);
    $f['name']          = clean($_POST['name'] ?? '');
    $f['description']   = clean($_POST['description'] ?? '');
    $f['price']         = clean($_POST['price'] ?? '');
    $f['discount_percent'] = clean($_POST['discount_percent'] ?? '0');
    $f['status']        = ($_POST['status'] ?? '') === 'unavailable' ? 'unavailable' : 'available';

    if (!in_array($f['restaurant_id'], array_column($restaurants, 'id'))) $errors['restaurant_id'] = 'Select a restaurant.';
    if (!in_array($f['category_id'], array_column($categories, 'id')))    $errors['category_id'] = 'Select a category.';
    if ($f['name'] === '' || mb_strlen($f['name']) > 120)                 $errors['name'] = 'Name is required (max 120 characters).';
    if (!is_numeric($f['price']) || $f['price'] <= 0 || $f['price'] > 99999) $errors['price'] = 'Enter a valid price.';
    if (!ctype_digit((string)$f['discount_percent']) || (int)$f['discount_percent'] > 90) $errors['discount_percent'] = 'Discount must be a whole number from 0 to 90.';
    if (mb_strlen($f['description']) > 500)                               $errors['description'] = 'Description max 500 akkhor.';

    [$image, $imgErr] = handle_image_upload('image', $f['image']);
    if ($imgErr) $errors['image'] = $imgErr;

    if (!$errors) {
        $vals = [$f['restaurant_id'], $f['category_id'], $f['name'], $f['description'], $f['price'], (int)$f['discount_percent'], $image ?: 'placeholder.jpg', $f['status']];
        if ($id) {
            $pdo->prepare("UPDATE food_items SET restaurant_id=?, category_id=?, name=?, description=?, price=?, discount_percent=?, image=?, status=? WHERE id=?")->execute([...$vals, $id]);
            set_flash('success', 'Food updated.');
        } else {
            $pdo->prepare("INSERT INTO food_items (restaurant_id, category_id, name, description, price, discount_percent, image, status) VALUES (?,?,?,?,?,?,?,?)")->execute($vals);
            set_flash('success', 'Food added.');
        }
        redirect('admin/foods.php?rest=' . $f['restaurant_id']);
    }
}

$page_title = $id ? 'Edit Food' : 'Add Food'; $nav = 'foods';
require '_header.php';
?>
<div class="form-card" style="max-width:640px">
<form method="post" enctype="multipart/form-data" novalidate data-validate>
    <?= csrf_field() ?>
    <div class="form-group"><label>Restaurant</label>
        <select name="restaurant_id" required><option value="">-- Select --</option>
            <?php foreach ($restaurants as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (int)$f['restaurant_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select>
        <?php if (isset($errors['restaurant_id'])): ?><div class="field-error"><?= e($errors['restaurant_id']) ?></div><?php endif; ?></div>
    <div class="form-group"><label>Category</label>
        <select name="category_id" required><option value="">-- Select --</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$f['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
        <?php if (isset($errors['category_id'])): ?><div class="field-error"><?= e($errors['category_id']) ?></div><?php endif; ?></div>
    <div class="form-group"><label>Food Name</label><input type="text" name="name" value="<?= e($f['name']) ?>" required maxlength="120">
        <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?></div>
    <div class="form-group"><label>Description</label><textarea name="description" maxlength="500"><?= e($f['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><div class="field-error"><?= e($errors['description']) ?></div><?php endif; ?></div>
    <div class="grid grid-2">
        <div class="form-group"><label>Price (<?= CURRENCY ?>)</label><input type="number" name="price" value="<?= e($f['price']) ?>" step="0.01" min="1" required>
            <?php if (isset($errors['price'])): ?><div class="field-error"><?= e($errors['price']) ?></div><?php endif; ?></div>
        <div class="form-group"><label>Discount (% off, 0 = none)</label><input type="number" name="discount_percent" value="<?= e($f['discount_percent']) ?>" min="0" max="90" step="1">
            <?php if (isset($errors['discount_percent'])): ?><div class="field-error"><?= e($errors['discount_percent']) ?></div><?php endif; ?></div>
    </div>
    <div class="grid grid-2">
        <div class="form-group"><label>Status</label>
            <select name="status"><option value="available" <?= $f['status'] === 'available' ? 'selected' : '' ?>>Available</option><option value="unavailable" <?= $f['status'] === 'unavailable' ? 'selected' : '' ?>>Unavailable</option></select></div>
    </div>
    <div class="form-group"><label>Image (JPG/PNG/WEBP, max 2MB)</label>
        <?php if ($f['image']): ?><img class="thumb-lg" src="<?= img_url($f['image']) ?>" alt=""><?php endif; ?>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <?php if (isset($errors['image'])): ?><div class="field-error"><?= e($errors['image']) ?></div><?php endif; ?></div>
    <button class="btn">Save</button> <a class="btn btn-outline" href="foods.php">Cancel</a>
</form></div>
<?php require '_footer.php'; ?>
