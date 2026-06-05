<?php
/** @var array $person */
/** @var bool $isNew */
/** @var array $errors */
/** @var array|null $_user */

$errors = $errors ?? [];
$action = $isNew ? '/admin/users' : '/admin/users/' . (int)$person['id'];

$meId   = (int)($_user['id'] ?? 0);
$isSelf = (!$isNew && (int)($person['id'] ?? 0) === $meId);

$roleOptions = ['author' => 'Author', 'editor' => 'Editor', 'admin' => 'Administrator'];
$curRole     = $person['role'] ?? 'author';
$curActive   = ((int)($person['is_active'] ?? 1) === 1);

$err = static function (array $errors, string $key): string {
    return empty($errors[$key])
        ? ''
        : '<small style="color:var(--error); display:block; margin-top:0.25em;">'
            . htmlspecialchars($errors[$key], ENT_QUOTES) . '</small>';
};
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">
    <?= $isNew ? 'Add User' : 'Edit: ' . htmlspecialchars($person['display_name'] ?? '', ENT_QUOTES) ?>
  </h1>
  <a href="/admin/users">&larr; Back to Users</a>
</div>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES) ?>" data-warn-unsaved
      style="max-width:560px;">
  <?= \Settle\Csrf::field() ?>

  <label>Display name
    <input type="text" name="display_name" required maxlength="100"
           value="<?= htmlspecialchars($person['display_name'] ?? '', ENT_QUOTES) ?>">
    <?= $err($errors, 'display_name') ?>
  </label>

  <label>Username <span class="muted">(used to sign in)</span>
    <input type="text" name="username" required maxlength="50"
           autocapitalize="none" autocomplete="off" spellcheck="false"
           value="<?= htmlspecialchars($person['username'] ?? '', ENT_QUOTES) ?>">
    <?= $err($errors, 'username') ?>
  </label>

  <label>Email
    <input type="email" name="email" required maxlength="190"
           value="<?= htmlspecialchars($person['email'] ?? '', ENT_QUOTES) ?>">
    <?= $err($errors, 'email') ?>
  </label>

  <label>Role
    <?php if ($isSelf): ?>
      <select name="role" disabled>
        <?php foreach ($roleOptions as $val => $text): ?>
          <option value="<?= htmlspecialchars($val, ENT_QUOTES) ?>" <?= $val === $curRole ? 'selected' : '' ?>>
            <?= htmlspecialchars($text, ENT_QUOTES) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small class="muted" style="display:block; margin-top:0.25em;">
        You can&rsquo;t change your own role. Ask another administrator if it needs to change.
      </small>
    <?php else: ?>
      <select name="role">
        <?php foreach ($roleOptions as $val => $text): ?>
          <option value="<?= htmlspecialchars($val, ENT_QUOTES) ?>" <?= $val === $curRole ? 'selected' : '' ?>>
            <?= htmlspecialchars($text, ENT_QUOTES) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small class="muted" style="display:block; margin-top:0.25em;">
        Authors write their own blog posts. Editors manage all content. Administrators also manage users and settings.
      </small>
    <?php endif; ?>
    <?= $err($errors, 'role') ?>
  </label>

  <?php if ($isSelf): ?>
    <p class="muted" style="margin:1em 0;">
      &#10003; Active &mdash; this is your own account, so it can&rsquo;t be deactivated here.
    </p>
  <?php else: ?>
    <label style="display:flex; align-items:center; gap:0.5em; margin:1em 0;">
      <input type="checkbox" name="is_active" value="1" <?= $curActive ? 'checked' : '' ?>
             style="width:auto; margin:0;">
      <span>Active <span class="muted">(an inactive user can&rsquo;t sign in)</span></span>
    </label>
    <?= $err($errors, 'is_active') ?>
  <?php endif; ?>

  <hr style="border:0; border-top:1px solid var(--gray-100); margin:1.5em 0;">

  <?php if ($isNew): ?>
    <h2 style="font-size:1.1em; margin:0 0 0.5em;">Initial password</h2>
    <p class="muted" style="margin-top:0;">
      Set a temporary password and share it with the user securely; ask them to change it after first sign-in.
      At least 12 characters.
    </p>
  <?php else: ?>
    <h2 style="font-size:1.1em; margin:0 0 0.5em;">Set a new password</h2>
    <p class="muted" style="margin-top:0;">Leave both fields blank to keep the current password.</p>
  <?php endif; ?>

  <label>Password
    <input type="password" name="password" autocomplete="new-password"
           <?= $isNew ? 'required' : '' ?> minlength="12" maxlength="200">
    <?= $err($errors, 'password') ?>
  </label>

  <label>Confirm password
    <input type="password" name="password_confirm" autocomplete="new-password"
           <?= $isNew ? 'required' : '' ?> minlength="12" maxlength="200">
  </label>

  <div style="margin-top:1.5em; display:flex; gap:0.75em; align-items:center;">
    <button type="submit" class="btn-primary"><?= $isNew ? 'Create User' : 'Save Changes' ?></button>
    <a href="/admin/users">Cancel</a>
  </div>
</form>
