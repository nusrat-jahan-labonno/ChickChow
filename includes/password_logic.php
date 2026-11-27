<?php
// Validates and saves a password change for the logged-in user (user or admin).
// Returns array of errors (empty = success).
function change_password(PDO $pdo, int $uid, string $cur, string $new, string $conf): array {
    $errors = [];
    $st = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $st->execute([$uid]);
    $row = $st->fetch();
    if (!$row || !password_verify($cur, $row['password'])) $errors['current'] = 'Current password is incorrect.';
    if (strlen($new) < 6) $errors['new'] = 'New password must be at least 6 characters.';
    elseif ($cur !== '' && $new === $cur) $errors['new'] = 'New password must be different from the current one.';
    if ($new !== $conf) $errors['confirm'] = 'Passwords do not match.';
    if (!$errors) {
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        session_regenerate_id(true);
    }
    return $errors;
}
