<?php
require_once 'includes/functions.php';
if (is_logged_in()) redirect('index.php');

$page_title = 'Register';
$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['name']  = clean($_POST['name'] ?? '');
    $old['email'] = strtolower(clean($_POST['email'] ?? ''));
    $old['phone'] = clean($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) $errors['name'] = 'Name must be 2 to 100 characters.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))             $errors['email'] = 'Please enter a valid email.';
    if (!valid_phone($old['phone']))                                    $errors['phone'] = 'Enter a valid phone number (e.g. 01712345678).';
    if (strlen($password) < 6)                                          $errors['password'] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                                         $errors['confirm'] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt->execute([$old['name'], $old['email'], $old['phone'], $hash]);
        set_flash('success', 'Account created! Please login.');
        redirect('login.php');
    }
}

require 'includes/header.php';
?>
<div class="container">
    <div class="auth-wrap form-card">
        <img class="auth-mascot" src="<?= BASE_URL ?>/assets/images/mascot-wave.png" alt="">
        <h1>Register</h1>
        <p class="auth-sub">Create your account and start ordering!</p>

        <form method="post" novalidate data-validate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required minlength="2" maxlength="100" placeholder="Enter your full name">
                <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required placeholder="Enter your email">
                <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" required placeholder="01712345678">
                <?php if (isset($errors['phone'])): ?><div class="field-error"><?= e($errors['phone']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6" placeholder="At least 6 characters">
                <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" required placeholder="Re-enter password">
                <?php if (isset($errors['confirm'])): ?><div class="field-error"><?= e($errors['confirm']) ?></div><?php endif; ?>
            </div>
            <button class="btn btn-block btn-large" type="submit">Register</button>
        </form>
        <p class="auth-foot">Already have an account? <a href="<?= BASE_URL ?>/login.php">Login</a></p>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
