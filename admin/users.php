<?php
$page_title = 'Users'; $nav = 'users';
require '_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$_SESSION['user_id']) {
        set_flash('error', 'You cannot do this on your own account.');
    } elseif (($_POST['action'] ?? '') === 'role' && in_array($_POST['role'] ?? '', ['user', 'admin'], true)) {
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$_POST['role'], $id]);
        set_flash('success', 'Role updated.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        set_flash('success', 'User deleted (tar order o muche geche).');
    }
    redirect('admin/users.php');
}
$rows = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders FROM users u ORDER BY u.id")->fetchAll();
?>
<div class="table-wrap"><table class="table">
    <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Joined</th><th>Role</th><th>Action</th></tr>
    <?php foreach ($rows as $u): $me = (int)$u['id'] === (int)$_SESSION['user_id']; ?>
        <tr>
            <td><?= (int)$u['id'] ?></td><td><b><?= e($u['name']) ?></b><?= $me ? ' <small class="muted">(you)</small>' : '' ?></td>
            <td><?= e($u['email']) ?></td><td><?= e($u['phone']) ?></td><td><?= (int)$u['orders'] ?></td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <select name="role" onchange="this.form.submit()" <?= $me ? 'disabled' : '' ?>><option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>user</option><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option></select></form></td>
            <td class="actions"><?php if (!$me): ?>
                <form method="post" onsubmit="return confirm('This user and all their orders will be deleted. Are you sure?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button class="btn btn-small btn-danger">Delete</button></form><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
</table></div>
<?php require '_footer.php'; ?>
