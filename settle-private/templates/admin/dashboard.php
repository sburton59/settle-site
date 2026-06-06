<?php
/**
 * Admin dashboard / "Welcome back" landing (roadmap #8b).
 *
 * Every block guards on the data the controller passed (which is already
 * role- and Features-gated), so a disabled module or a lower-privileged
 * viewer simply renders fewer widgets. Admin templates escape inline with
 * htmlspecialchars (no injected $e helper on this side), so we use a local
 * $h closure.
 *
 * @var array      $_user
 * @var bool       $is_editor
 * @var bool       $is_admin
 * @var bool|null  $rate_limiter_ok
 * @var array      $posts            ['counts'=>..., 'recent'=>...]  (blog on)
 * @var int        $media_total
 * @var array      $prayer_counts    (editor+, prayer on)
 * @var array      $prayer_recent
 * @var int        $contact_unread   (editor+, contact on)
 * @var array      $contact_recent
 * @var int        $events_upcoming  (editor+, calendar on)
 * @var int        $pages_total
 * @var int        $staff_total
 * @var array      $audit_recent     (admin only)
 */
$h   = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmt = static function ($dt): string {
    $ts = is_string($dt) ? strtotime($dt) : false;
    return $ts ? date('M j, Y g:i a', $ts) : '';
};

$rate_limiter_ok = $rate_limiter_ok ?? null;
$is_editor = $is_editor ?? false;
$is_admin  = $is_admin ?? false;
?>
<h1>Welcome back, <?= $h($_user['display'] ?? '') ?></h1>

<?php if ($rate_limiter_ok === false): ?>
  <div class="flash flash-warning">
    <strong>Login protection is not active.</strong>
    The login rate-limiter can&rsquo;t reach its <code>login_attempts</code> table,
    so repeated failed sign-ins are <em>not</em> being throttled. Apply
    <code>sql/migrations/0004_add_login_attempts.sql</code> to the database, then
    reload this page. See <code>storage/logs/php-error.log</code> for details.
  </div>
<?php endif; ?>

<?php
// ---------------------------------------------------------------------------
// At a glance — count cards
// ---------------------------------------------------------------------------
$cards = [];

if (isset($posts)) {
    $c = $posts['counts'];
    $sub = [];
    if ($c['draft'] > 0)     { $sub[] = $c['draft'] . ' draft' . ($c['draft'] === 1 ? '' : 's'); }
    if ($c['scheduled'] > 0) { $sub[] = $c['scheduled'] . ' scheduled'; }
    $cards[] = [
        'num'   => $c['published'],
        'label' => $is_editor ? 'Published posts' : 'My published posts',
        'sub'   => $sub ? implode(' · ', $sub) : null,
        'href'  => '/admin/posts',
    ];
}
if (isset($events_upcoming)) {
    $cards[] = ['num' => $events_upcoming, 'label' => 'Upcoming events', 'href' => '/admin/calendar'];
}
if (isset($prayer_counts)) {
    $cards[] = ['num' => (int) ($prayer_counts['new'] ?? 0), 'label' => 'New prayer requests', 'href' => '/admin/prayer', 'alert' => ((int) ($prayer_counts['new'] ?? 0) > 0)];
}
if (isset($contact_unread)) {
    $cards[] = ['num' => $contact_unread, 'label' => 'Unread messages', 'href' => '/admin/contact', 'alert' => ($contact_unread > 0)];
}
if (isset($media_total)) {
    $cards[] = ['num' => $media_total, 'label' => 'Media items', 'href' => '/admin/media'];
}
if (isset($pages_total)) {
    $cards[] = ['num' => $pages_total, 'label' => 'Pages', 'href' => '/admin/pages'];
}
if (isset($staff_total)) {
    $cards[] = ['num' => $staff_total, 'label' => 'Staff', 'href' => '/admin/staff'];
}
?>
<?php if ($cards): ?>
  <section class="dash-section">
    <h2 class="dash-h2">At a glance</h2>
    <div class="dash-cards">
      <?php foreach ($cards as $card): ?>
        <a class="dash-card<?= !empty($card['alert']) ? ' dash-card--alert' : '' ?>" href="<?= $h($card['href']) ?>">
          <span class="dash-card__num"><?= (int) $card['num'] ?></span>
          <span class="dash-card__label"><?= $h($card['label']) ?></span>
          <?php if (!empty($card['sub'])): ?>
            <span class="dash-card__sub"><?= $h($card['sub']) ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<?php
// ---------------------------------------------------------------------------
// Recent activity
// ---------------------------------------------------------------------------
$hasRecent = !empty($posts['recent'])
          || !empty($prayer_recent)
          || !empty($contact_recent)
          || !empty($audit_recent);
?>
<?php if ($hasRecent): ?>
  <section class="dash-section">
    <h2 class="dash-h2">Recent activity</h2>
    <div class="dash-grid">

      <?php if (isset($posts)): ?>
        <div class="dash-panel">
          <h3 class="dash-panel__title">Recent posts</h3>
          <?php if (empty($posts['recent'])): ?>
            <p class="dash-empty">No posts yet. <a href="/admin/posts">Write the first one →</a></p>
          <?php else: ?>
            <ul class="dash-list">
              <?php foreach ($posts['recent'] as $p): ?>
                <li class="dash-list__item">
                  <a class="dash-list__main" href="/admin/posts/<?= (int) $p['id'] ?>/edit"><?= $h($p['title'] !== '' ? $p['title'] : '(untitled)') ?></a>
                  <span class="dash-badge dash-badge--<?= $h(strtolower($p['state'])) ?>"><?= $h($p['state']) ?></span>
                  <span class="dash-list__meta">
                    <?= $h($fmt($p['when'])) ?><?php if ($is_editor && $p['author_name'] !== ''): ?> · <?= $h($p['author_name']) ?><?php endif; ?>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (isset($prayer_recent)): ?>
        <div class="dash-panel">
          <h3 class="dash-panel__title">New prayer requests</h3>
          <?php if (empty($prayer_recent)): ?>
            <p class="dash-empty">Nothing new to review.</p>
          <?php else: ?>
            <ul class="dash-list">
              <?php foreach ($prayer_recent as $pr): ?>
                <li class="dash-list__item">
                  <a class="dash-list__main" href="/admin/prayer">
                    <?= !empty($pr['is_private']) ? 'Private request' : $h($pr['submitter_name'] !== '' ? $pr['submitter_name'] : 'Anonymous') ?>
                  </a>
                  <span class="dash-list__meta"><?= $h($fmt($pr['submitted_at'])) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (isset($contact_recent)): ?>
        <div class="dash-panel">
          <h3 class="dash-panel__title">Unread messages</h3>
          <?php if (empty($contact_recent)): ?>
            <p class="dash-empty">No unread messages.</p>
          <?php else: ?>
            <ul class="dash-list">
              <?php foreach ($contact_recent as $cm): ?>
                <?php $snippet = trim((string) ($cm['message_text'] ?? '')); ?>
                <li class="dash-list__item">
                  <a class="dash-list__main" href="/admin/contact"><?= $h($cm['sender_name'] !== '' ? $cm['sender_name'] : 'Someone') ?></a>
                  <?php if ($snippet !== ''): ?>
                    <span class="dash-list__snippet"><?= $h(mb_strimwidth($snippet, 0, 70, '…')) ?></span>
                  <?php endif; ?>
                  <span class="dash-list__meta"><?= $h($fmt($cm['submitted_at'])) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (isset($audit_recent)): ?>
        <div class="dash-panel">
          <h3 class="dash-panel__title">Recent activity log</h3>
          <?php if (empty($audit_recent)): ?>
            <p class="dash-empty">No activity recorded yet.</p>
          <?php else: ?>
            <ul class="dash-list">
              <?php foreach ($audit_recent as $a): ?>
                <li class="dash-list__item">
                  <span class="dash-list__main"><?= $h($a['action']) ?></span>
                  <span class="dash-list__meta">
                    <?= $h($a['actor_name'] !== null && $a['actor_name'] !== '' ? $a['actor_name'] : '—') ?>
                    <?php if (!empty($a['entity_type'])): ?> · <?= $h($a['entity_type']) ?><?php if ($a['entity_id'] !== null): ?>#<?= (int) $a['entity_id'] ?><?php endif; ?><?php endif; ?>
                    · <?= $h($fmt($a['created_at'])) ?>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
            <p class="dash-panel__more"><a href="/admin/audit">View full audit log →</a></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </section>
<?php endif; ?>

<?php
// ---------------------------------------------------------------------------
// Quick actions
// ---------------------------------------------------------------------------
$actions = [];
if (isset($posts))            { $actions[] = ['Write a post', '/admin/posts']; }
if (isset($media_total))      { $actions[] = ['Upload media', '/admin/media']; }
if (isset($events_upcoming))  { $actions[] = ['Calendar overrides', '/admin/calendar']; }
if (isset($prayer_counts))    { $actions[] = ['Review prayer requests', '/admin/prayer']; }
if (isset($contact_unread))   { $actions[] = ['Review messages', '/admin/contact']; }
if ($is_admin)                { $actions[] = ['Site settings', '/admin/settings']; }
?>
<?php if ($actions): ?>
  <section class="dash-section">
    <h2 class="dash-h2">Quick actions</h2>
    <div class="dash-actions">
      <?php foreach ($actions as [$label, $href]): ?>
        <a class="btn-primary" href="<?= $h($href) ?>"><?= $h($label) ?></a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
