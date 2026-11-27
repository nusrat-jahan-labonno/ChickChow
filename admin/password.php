<?php
$page_title = 'Change Password'; $nav = 'password';
require '_header.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_once __DIR__ . '/../includes/password_logic.php';
    $errors = change_password($pdo, (int)$_SESSION['user_id'], $_POST['current'] ?? '', $_POST['new'] ?? '', $_POST['confirm'] ?? '');
    if (!$errors) {
        set_flash('success', 'Admin password changed successfully.');
        redirect('admin/password.php');
    }
}
?>
<div class="form-card pw-card admin-pw">
    <div class="pw-head"><span class="pw-ico">&#128274;</span>
        <div><h2>Change admin password</h2><p class="muted small">Logged in as <b><?= e($_SESSION['name'] ?? 'Admin') ?></b>. Use a strong password.</p></div></div>
    <?php $pw_action = BASE_URL . '/admin/password.php'; require __DIR__ . '/../includes/password_form.php'; ?>
</div>
<?php require '_footer.php'; ?>
