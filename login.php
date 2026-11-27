<?php
require_once 'includes/functions.php';
if (is_logged_in()) redirect('index.php');

$page_title = 'Login';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(clean($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password']) && $user['role'] === 'admin') {
            $error = 'This is an admin account. Please sign in from the Admin Panel.';
        } elseif ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            // Re-hash if the stored hash uses an old algorithm
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $up = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $up->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }

            $next = $_SESSION['after_login'] ?? '';
            unset($_SESSION['after_login']);
            set_flash('success', 'Welcome back, ' . $user['name'] . '!');
            header('Location: ' . safe_return($next));
            exit;
        }
        if (!$error) $error = 'Incorrect email or password.';
    }
}

require 'includes/header.php';
?>
<div class="container">
    <div class="auth-wrap form-card">
        <img class="auth-mascot" src="<?= BASE_URL ?>/assets/images/mascot-wave.png" alt="">
        <h1>Login</h1>
        <p class="auth-sub">Welcome back! Please login to your account.</p>

        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <form method="post" novalidate data-validate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button class="btn btn-block btn-large" type="submit">Login</button>
        </form>
        <p class="auth-foot">Don't have an account? <a href="<?= BASE_URL ?>/register.php">Register</a></p>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
