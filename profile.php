<?php
require_once 'includes/functions.php';
require_login();
$page_title = 'My Account';
$uid = (int)$_SESSION['user_id'];
$errors = [];

// Which section is open: profile | password | addresses
$tab = $_GET['tab'] ?? 'profile';
if (!in_array($tab, ['profile', 'password', 'addresses'], true)) $tab = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $tab = 'profile';
        $name  = clean($_POST['name'] ?? '');
        $phone = clean($_POST['phone'] ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors['name'] = 'Name must be 2 to 100 characters.';
        if (!valid_phone($phone)) $errors['phone'] = 'Enter a valid phone number.';
        if (!$errors) {
            $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?")->execute([$name, $phone, $uid]);
            $_SESSION['name'] = $name;
            set_flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    } elseif ($action === 'password') {
        $tab = 'password';
        require_once __DIR__ . '/includes/password_logic.php';
        $errors = change_password($pdo, $uid, $_POST['current'] ?? '', $_POST['new'] ?? '', $_POST['confirm'] ?? '');
        if (!$errors) {
            set_flash('success', 'Password changed successfully.');
            redirect('profile.php?tab=password');
        }
    } elseif ($action === 'addr_add' || $action === 'addr_edit') {
        $tab = 'addresses';
        $label = clean($_POST['label'] ?? ''); $addr = clean($_POST['address'] ?? ''); $area = $_POST['area'] ?? '';
        if ($label === '' || mb_strlen($label) > 30) $errors['label'] = 'Enter a label (e.g. Home, Work).';
        if (!valid_area($area)) $errors['area'] = 'Please select your area.';
        if (mb_strlen($addr) < 3 || mb_strlen($addr) > 255) $errors['address'] = 'Enter house / road details (at least 3 characters).';
        if (!$errors) {
            if ($action === 'addr_add') {
                $pdo->prepare("INSERT INTO addresses (user_id, label, area, address) VALUES (?, ?, ?, ?)")->execute([$uid, $label, $area, $addr]);
                set_flash('success', 'Address added.');
            } else {
                $pdo->prepare("UPDATE addresses SET label = ?, area = ?, address = ? WHERE id = ? AND user_id = ?")->execute([$label, $area, $addr, (int)$_POST['id'], $uid]);
                set_flash('success', 'Address updated.');
            }
            redirect('profile.php?tab=addresses');
        }
    } elseif ($action === 'addr_delete') {
        $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?")->execute([(int)$_POST['id'], $uid]);
        set_flash('success', 'Address deleted.');
        redirect('profile.php?tab=addresses');
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$uid]); $user = $stmt->fetch();
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id"); $stmt->execute([$uid]); $addresses = $stmt->fetchAll();
$edit_id = (int)($_GET['edit'] ?? 0);

$tabs = ['profile' => 'User Profile', 'password' => 'Change Password', 'addresses' => 'Saved Addresses'];
require 'includes/header.php';
?>
<div class="container page-wrap">
    <?php if ($tab === 'password'): ?>
        <a class="back-link" href="<?= BASE_URL ?>/profile.php">&larr; Back to profile</a>
        <h1 class="page-title">Change Password</h1>
    <?php else: ?>
        <h1 class="page-title"><?= e($tabs[$tab]) ?></h1>
        <div class="chips">
            <?php foreach (['profile', 'addresses'] as $key): ?>
                <a class="chip <?= $tab === $key ? 'active' : '' ?>" href="<?= BASE_URL ?>/profile.php<?= $key === 'profile' ? '' : '?tab=' . $key ?>"><?= e($tabs[$key]) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="form-card profile-card <?= $tab === 'password' ? 'pw-card' : '' ?>">

    <?php if ($tab === 'profile'): ?>
        <div class="profile-top"><div class="avatar big"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></div>
            <div><b><?= e($user['name']) ?></b><br><span class="muted small"><?= e($user['email']) ?></span></div></div>
        <h2>Profile Information</h2>
        <form method="post" novalidate data-validate>
            <?= csrf_field() ?><input type="hidden" name="action" value="profile">
            <div class="form-group"><label>Full Name</label>
                <input type="text" name="name" value="<?= e($_POST['name'] ?? $user['name']) ?>" required minlength="2">
                <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?></div>
            <div class="form-group"><label>Email</label><input type="email" value="<?= e($user['email']) ?>" disabled></div>
            <div class="form-group"><label>Phone</label>
                <input type="tel" name="phone" value="<?= e($_POST['phone'] ?? $user['phone']) ?>" required>
                <?php if (isset($errors['phone'])): ?><div class="field-error"><?= e($errors['phone']) ?></div><?php endif; ?></div>
            <button class="btn btn-block">Update Profile</button>
        </form>

    <?php elseif ($tab === 'password'): ?>
        <div class="pw-head"><span class="pw-ico">&#128274;</span>
            <div><h2>Change your password</h2><p class="muted small">Use a strong password you do not use anywhere else.</p></div></div>
        <?php $pw_action = BASE_URL . '/profile.php?tab=password'; require __DIR__ . '/includes/password_form.php'; ?>

    <?php else: ?>
        <h2>Saved Addresses</h2>
        <?php foreach ($addresses as $a): ?>
            <?php if ($edit_id === (int)$a['id']): ?>
                <form method="post" action="<?= BASE_URL ?>/profile.php?tab=addresses" class="addr-edit" novalidate data-validate>
                    <?= csrf_field() ?><input type="hidden" name="action" value="addr_edit"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <div class="form-group"><input type="text" name="label" value="<?= e($a['label']) ?>" required maxlength="30"></div>
                    <div class="form-group"><select name="area" required><?= area_options($a['area']) ?></select></div>
                    <div class="form-group"><textarea name="address" required minlength="3"><?= e($a['address']) ?></textarea></div>
                    <button class="btn btn-small">Save</button> <a class="btn btn-small btn-outline" href="<?= BASE_URL ?>/profile.php?tab=addresses">Cancel</a>
                </form>
            <?php else: ?>
                <div class="addr-row">
                    <div><b><?= e($a['label']) ?></b> &ndash; <?= e($a['area']) ?><br><span class="muted small"><?= e($a['address']) ?></span></div>
                    <div class="addr-actions">
                        <a class="link-small" href="<?= BASE_URL ?>/profile.php?tab=addresses&amp;edit=<?= (int)$a['id'] ?>">Edit</a>
                        <form method="post" action="<?= BASE_URL ?>/profile.php?tab=addresses" onsubmit="return confirm('Delete this address?')"><?= csrf_field() ?>
                            <input type="hidden" name="action" value="addr_delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                            <button class="link-btn">Delete</button></form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$addresses): ?><p class="muted">No saved addresses yet.</p><?php endif; ?>

        <h3 style="margin:22px 0 10px">Add New Address</h3>
        <form method="post" action="<?= BASE_URL ?>/profile.php?tab=addresses" novalidate data-validate>
            <?= csrf_field() ?><input type="hidden" name="action" value="addr_add">
            <div class="form-group"><label>Label</label><input type="text" name="label" placeholder="Home / Work" required maxlength="30">
                <?php if (isset($errors['label'])): ?><div class="field-error"><?= e($errors['label']) ?></div><?php endif; ?></div>
            <div class="form-group"><label>Area</label><select name="area" required><?= area_options($_POST['area'] ?? '') ?></select>
                <?php if (isset($errors['area'])): ?><div class="field-error"><?= e($errors['area']) ?></div><?php endif; ?></div>
            <div class="form-group"><label>House / Road</label><textarea name="address" required minlength="3" placeholder="House 12, Road 5"></textarea>
                <?php if (isset($errors['address'])): ?><div class="field-error"><?= e($errors['address']) ?></div><?php endif; ?></div>
            <button class="btn btn-block">Add Address</button>
        </form>
    <?php endif; ?>

    </div>
</div>
<?php require 'includes/footer.php'; ?>
