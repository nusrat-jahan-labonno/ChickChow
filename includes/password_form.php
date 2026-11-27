<?php
// Shared "change password" form (user profile + admin panel).
// Needs: $pw_action (form action URL), $errors (array)
$err = $errors ?? [];
?>
<form method="post" action="<?= e($pw_action) ?>" class="pw-form" novalidate data-validate autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="action" value="password">

    <div class="form-group"><label for="pw_cur">Current Password</label>
        <div class="pw-wrap"><input type="password" id="pw_cur" name="current" required autocomplete="current-password" placeholder="Enter current password">
            <button type="button" class="pw-eye" aria-label="Show password"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
        <?php if (isset($err['current'])): ?><div class="field-error"><?= e($err['current']) ?></div><?php endif; ?></div>

    <div class="form-group"><label for="pw_new">New Password</label>
        <div class="pw-wrap"><input type="password" id="pw_new" name="new" required minlength="6" autocomplete="new-password" placeholder="At least 6 characters" data-strength="pwBar">
            <button type="button" class="pw-eye" aria-label="Show password"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
        <div class="pw-bar" id="pwBar" data-level="0"><span></span><span></span><span></span><span></span><small></small></div>
        <?php if (isset($err['new'])): ?><div class="field-error"><?= e($err['new']) ?></div><?php endif; ?></div>

    <div class="form-group"><label for="pw_conf">Confirm New Password</label>
        <div class="pw-wrap"><input type="password" id="pw_conf" name="confirm" required autocomplete="new-password" placeholder="Type the new password again">
            <button type="button" class="pw-eye" aria-label="Show password"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
        <?php if (isset($err['confirm'])): ?><div class="field-error"><?= e($err['confirm']) ?></div><?php endif; ?></div>

    <button class="btn btn-block" type="submit">Change Password</button>
</form>
