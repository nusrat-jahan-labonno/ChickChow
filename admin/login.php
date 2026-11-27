<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in() && is_admin()) redirect('admin/index.php');

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(clean($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = 'admin';
        redirect('admin/index.php');
    }
    $error = 'Incorrect email or password, or this account is not an admin.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | ChickChow</title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="admin-body">
<div class="container">
    <div class="auth-wrap form-card">
        <img class="auth-mascot" src="<?= BASE_URL ?>/assets/images/logo.png" alt="">
        <h1>Admin Login</h1>
        <p class="auth-sub">ChickChow Admin Panel</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" novalidate data-validate>
            <?= csrf_field() ?>
            <div class="form-group"><label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required placeholder="Admin email"></div>
            <div class="form-group"><label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Password"></div>
            <button class="btn btn-block btn-large" type="submit">Login</button>
        </form>
        <p class="auth-foot"><a href="<?= BASE_URL ?>/index.php">&larr; Back to website</a></p>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
