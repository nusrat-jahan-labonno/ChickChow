<?php
$page_title = 'Categories'; $nav = 'categories';
require '_header.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0); $name = clean($_POST['name'] ?? '');
    try {
        if ($action === 'add' || $action === 'edit') {
            if ($name === '' || mb_strlen($name) > 60) { $error = 'Category name is required (max 60 characters).'; }
            elseif ($action === 'add') { $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]); set_flash('success', 'Category added.'); redirect('admin/categories.php'); }
            else { $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?")->execute([$name, $id]); set_flash('success', 'Category updated.'); redirect('admin/categories.php'); }
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            set_flash('success', 'Category deleted.'); redirect('admin/categories.php');
        }
    } catch (PDOException $ex) {
        $error = $action === 'delete' ? 'This category has food items. Move them to another category first.' : 'A category with this name already exists.';
    }
}
$rows = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM food_items f WHERE f.category_id = c.id) AS foods FROM categories c ORDER BY c.name")->fetchAll();
$edit = (int)($_GET['edit'] ?? 0);
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="form-card" style="max-width:520px;margin-bottom:20px">
    <form method="post" class="inline-form" novalidate data-validate><?= csrf_field() ?><input type="hidden" name="action" value="add">
        <input type="text" name="name" placeholder="New category name" required maxlength="60"><button class="btn btn-small">+ Add</button></form>
</div>
<div class="table-wrap"><table class="table">
    <tr><th>#</th><th>Name</th><th>Foods</th><th>Action</th></tr>
    <?php foreach ($rows as $c): ?>
        <tr>
            <td><?= (int)$c['id'] ?></td>
            <td><?php if ($edit === (int)$c['id']): ?>
                <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <input type="text" name="name" value="<?= e($c['name']) ?>" required maxlength="60"><button class="btn btn-small">Save</button></form>
                <?php else: ?><b><?= e($c['name']) ?></b><?php endif; ?></td>
            <td><?= (int)$c['foods'] ?></td>
            <td class="actions">
                <a class="btn btn-small btn-outline" href="categories.php?edit=<?= (int)$c['id'] ?>">Edit</a>
                <form method="post" onsubmit="return confirm('Delete this item?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button class="btn btn-small btn-danger">Delete</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
</table></div>
<?php require '_footer.php'; ?>
