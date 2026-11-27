<?php
$page_title = 'Food Items'; $nav = 'foods';
require '_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        try {
            $pdo->prepare("DELETE FROM food_items WHERE id = ?")->execute([$id]);
            set_flash('success', 'Food item deleted.');
        } catch (PDOException $ex) {
            set_flash('error', 'Cannot delete: this item is part of an existing order. Mark it "Unavailable" instead.');
        }
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $pdo->prepare("UPDATE food_items SET status = IF(status='available','unavailable','available') WHERE id = ?")->execute([$id]);
        set_flash('success', 'Status changed.');
    }
    redirect('admin/foods.php' . (isset($_GET['rest']) ? '?rest=' . (int)$_GET['rest'] : ''));
}

$rid = (int)($_GET['rest'] ?? 0);
$restaurants = $pdo->query("SELECT id, name FROM restaurants ORDER BY name")->fetchAll();
$sql = "SELECT f.*, r.name AS restaurant_name, c.name AS category_name FROM food_items f
        JOIN restaurants r ON r.id = f.restaurant_id JOIN categories c ON c.id = f.category_id";
$params = [];
if ($rid) { $sql .= " WHERE f.restaurant_id = ?"; $params[] = $rid; }
$sql .= " ORDER BY r.name, f.name";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
?>
<div class="admin-bar">
    <form method="get" class="filter-row"><select name="rest" onchange="this.form.submit()">
        <option value="0">All restaurants</option>
        <?php foreach ($restaurants as $r): ?><option value="<?= (int)$r['id'] ?>" <?= $rid === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?>
    </select> <span class="muted small"><?= count($rows) ?> items</span></form>
    <a class="btn btn-small" href="food_form.php<?= $rid ? '?rest=' . $rid : '' ?>">+ Add New Food</a>
</div>
<div class="table-wrap"><table class="table">
    <tr><th>Image</th><th>Name</th><th>Restaurant</th><th>Category</th><th>Price</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($rows as $f): ?>
        <tr>
            <td><img class="thumb" src="<?= img_url($f['image']) ?>" alt=""></td>
            <td><b><?= e($f['name']) ?></b></td><td><?= e($f['restaurant_name']) ?></td><td><?= e($f['category_name']) ?></td>
            <td><?= price_html($f) ?><?php if ($f['discount_percent'] > 0): ?> <span class="off-tag"><?= (int)$f['discount_percent'] ?>% OFF</span><?php endif; ?></td>
            <td><span class="status status-<?= e($f['status']) ?>"><?= e(ucfirst($f['status'])) ?></span></td>
            <td class="actions">
                <a class="btn btn-small btn-outline" href="food_form.php?id=<?= (int)$f['id'] ?>">Edit</a>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <button class="btn btn-small btn-outline">Toggle</button></form>
                <form method="post" onsubmit="return confirm('Delete this item?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <button class="btn btn-small btn-danger">Delete</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
</table></div>
<?php require '_footer.php'; ?>
