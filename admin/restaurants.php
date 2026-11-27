<?php
$page_title = 'Restaurants'; $nav = 'restaurants';
require '_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $id = (int)$_POST['id'];
    try {
        $pdo->prepare("DELETE FROM restaurants WHERE id = ?")->execute([$id]);
        set_flash('success', 'Restaurant deleted (its food items were removed too).');
    } catch (PDOException $ex) {
        set_flash('error', 'Cannot delete: food from this restaurant is part of an existing order.');
    }
    redirect('admin/restaurants.php');
}
$rows = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM food_items f WHERE f.restaurant_id = r.id) AS foods FROM restaurants r ORDER BY r.id DESC")->fetchAll();
?>
<div class="admin-bar"><span class="muted"><?= count($rows) ?> restaurants</span><a class="btn btn-small" href="restaurant_form.php">+ Add Restaurant</a></div>
<div class="table-wrap"><table class="table">
    <tr><th>Image</th><th>Name</th><th>Cuisine</th><th>Area</th><th>Rating</th><th>Foods</th><th>Action</th></tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><img class="thumb" src="<?= img_url($r['image']) ?>" alt=""></td>
            <td><b><?= e($r['name']) ?></b></td><td><?= e($r['cuisine']) ?></td><td><?= e($r['area']) ?></td><td>&#9733; <?= number_format((float)$r['rating'], 1) ?></td><td><?= (int)$r['foods'] ?></td>
            <td class="actions">
                <a class="btn btn-small btn-outline" href="restaurant_form.php?id=<?= (int)$r['id'] ?>">Edit</a>
                <form method="post" onsubmit="return confirm('The restaurant and all its food items will be deleted. Are you sure?')"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-small btn-danger">Delete</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
</table></div>
<?php require '_footer.php'; ?>
