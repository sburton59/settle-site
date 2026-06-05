<?php
/** @var array $users */
/** @var array|null $_user */

$meId = (int)($_user['id'] ?? 0);

$roleLabel = ['admin' => 'Administrator', 'editor' => 'Editor', 'author' => 'Author'];

$fmtDt = static function (?string $dt): string {
    if (!$dt) return 'Never';
    $ts = strtotime($dt);
    return $ts ? date('M j, Y g:i a', $ts) : 'Never';
};
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">Users</h1>
  <a href="/admin/users/new" class="btn-primary" style="text-decoration:none;">+ Add User</a>
</div>

<p class="muted" style="margin-bottom:1em;">
  Staff logins for the admin panel. Deactivating a user blocks their sign-in immediately and is the
  safe way to remove access &mdash; a user who has authored posts, pages, photos, or calendar notes
  can&rsquo;t be deleted.
</p>

<?php if (empty($users)): ?>
  <div style="background:#fff; padding:2em; text-align:center; border-radius:4px;">
    <p class="muted">No users yet. Click &ldquo;+ Add User&rdquo; to create the first one.</p>
  </div>
<?php else: ?>
  <table class="list">
    <thead>
      <tr>
        <th>Name</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Last login</th>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <?php
        $uid    = (int)$u['id'];
        $isSelf = ($uid === $meId);
        $active = ((int)$u['is_active'] === 1);
      ?>
      <tr<?= $active ? '' : ' style="opacity:0.6;"' ?>>
        <td>
          <strong><?= htmlspecialchars($u['display_name'], ENT_QUOTES) ?></strong>
          <?php if ($isSelf): ?>
            <span class="muted">(you)</span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($u['username'], ENT_QUOTES) ?></td>
        <td><?= htmlspecialchars($u['email'], ENT_QUOTES) ?></td>
        <td><?= htmlspecialchars($roleLabel[$u['role']] ?? $u['role'], ENT_QUOTES) ?></td>
        <td>
          <?php if ($active): ?>
            <span style="color:#2e7d32; font-weight:500;">&#10003; Active</span>
          <?php else: ?>
            <span class="muted">&mdash; Inactive</span>
          <?php endif; ?>
        </td>
        <td class="muted"><?= htmlspecialchars($fmtDt($u['last_login_at'] ?? null), ENT_QUOTES) ?></td>
        <td style="text-align:right; white-space:nowrap;">
          <a href="/admin/users/<?= $uid ?>/edit" style="text-decoration:none; padding:0.3em 0.6em;">Edit</a>

          <?php if (!$isSelf): ?>
            <form method="post" action="/admin/users/<?= $uid ?>/toggle" style="display:inline; margin:0;">
              <?= \Settle\Csrf::field() ?>
              <button type="submit" class="linklike" style="padding:0.3em 0.6em;">
                <?= $active ? 'Deactivate' : 'Activate' ?>
              </button>
            </form>

            <form method="post" action="/admin/users/<?= $uid ?>/delete" style="display:inline; margin:0;">
              <?= \Settle\Csrf::field() ?>
              <button type="submit" class="linklike" style="color:var(--error); padding:0.3em 0.6em;"
                      data-confirm="Delete this user? This can&rsquo;t be undone. If they have authored content, deletion will be blocked &mdash; deactivate instead.">
                Delete
              </button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
