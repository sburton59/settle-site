<?php
/** @var array $r          The prayer_request row */
/** @var bool  $canReveal  True if current user is editor+ (and can see private text) */

$isPrivate = ((int)$r['is_private'] === 1);
$displayName = trim((string)($r['submitter_name'] ?? ''));
if ($displayName === '') { $displayName = '(anonymous)'; }

$submittedAt = strtotime((string)$r['submitted_at']) ?: time();
$bodyVisible = !$isPrivate || $canReveal;
$isAdmin     = \Settle\Auth::hasRole('admin');
?>

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
    <h1 style="margin:0;">
        Prayer Request
        <?php if ($isPrivate): ?>
            <span style="font-size:0.6em; vertical-align:middle; color:#9E2A2B;">🔒 Private</span>
        <?php endif; ?>
    </h1>
    <a href="/admin/prayer" style="text-decoration:none;">&larr; Back to inbox</a>
</div>

<div style="background:#fff; padding:1.5em; border-radius:4px;
            <?= $isPrivate ? 'border-left:3px solid #9E2A2B;' : '' ?>">

    <!-- Metadata -->
    <dl style="display:grid; grid-template-columns:max-content 1fr; gap:0.4em 1em; margin:0 0 1.5em 0;">
        <dt class="muted">From:</dt>
        <dd style="margin:0;"><?= htmlspecialchars($displayName, ENT_QUOTES) ?></dd>

        <?php if (!empty($r['submitter_email'])): ?>
            <dt class="muted">Email:</dt>
            <dd style="margin:0;">
                <?php if ($canReveal): ?>
                    <?= \Settle\EmailObfuscator::link((string)$r['submitter_email']) ?>
                <?php else: ?>
                    <em class="muted">(hidden)</em>
                <?php endif; ?>
            </dd>
        <?php endif; ?>

        <dt class="muted">Submitted:</dt>
        <dd style="margin:0;"><?= htmlspecialchars(date('l, F j Y \a\t g:i a', $submittedAt), ENT_QUOTES) ?></dd>

        <dt class="muted">Status:</dt>
        <dd style="margin:0;">
            <span style="display:inline-block; font-size:0.8em; padding:0.2em 0.6em;
                         border-radius:2em; font-weight:500; text-transform:uppercase;
                         <?php
                            switch ($r['status']) {
                                case 'new':      echo 'background:#fff3cd; color:#856404;'; break;
                                case 'prayed':   echo 'background:#d4edda; color:#155724;'; break;
                                case 'archived': echo 'background:#e2e3e5; color:#383d41;'; break;
                            }
                          ?>">
                <?= htmlspecialchars((string)$r['status'], ENT_QUOTES) ?>
            </span>
        </dd>

        <dt class="muted">Prayer chain:</dt>
        <dd style="margin:0;">
            <?php if ($isPrivate): ?>
                <em class="muted">N/A (private request)</em>
            <?php elseif ((int)($r['allow_prayer_chain'] ?? 0) === 1): ?>
                <strong>Yes</strong> — sender opted in to share with prayer-chain volunteers
            <?php else: ?>
                No
            <?php endif; ?>
        </dd>

        <?php if ($isAdmin && !empty($r['ip_address'])): ?>
            <dt class="muted">IP:</dt>
            <dd style="margin:0; font-family:monospace; font-size:0.9em;">
                <?= htmlspecialchars((string)$r['ip_address'], ENT_QUOTES) ?>
            </dd>
        <?php endif; ?>
    </dl>

    <!-- Request body -->
    <h2 style="margin-top:0; font-size:1em; text-transform:uppercase;
               letter-spacing:0.05em; color:#666;">Request</h2>

    <?php if (!$bodyVisible): ?>
        <!-- Author role: the body is NOT in the HTML at all. -->
        <div style="background:#f8f8f8; padding:1.2em; border-radius:3px; text-align:center;">
            <p class="muted" style="margin:0;">
                <strong>🔒 This is a private prayer request.</strong><br>
                The request text is visible only to pastoral staff (editor or admin role).
            </p>
        </div>
    <?php elseif ($isPrivate): ?>
        <!-- Private + viewer is editor/admin: click-to-reveal toggle. -->
        <div id="prayer-private-wrapper">
            <div id="prayer-private-curtain"
                 style="background:#f8f8f8; padding:1.2em; border-radius:3px; text-align:center;">
                <p style="margin:0 0 1em 0; color:#666;">
                    <strong>🔒 This is a private prayer request.</strong>
                </p>
                <button type="button" id="prayer-reveal-btn" class="btn-primary"
                        style="cursor:pointer;">
                    Reveal request text
                </button>
            </div>
            <div id="prayer-private-body"
                 style="display:none; white-space:pre-wrap; line-height:1.5;
                        padding:1em; background:#fff; border:1px solid #eee; border-radius:3px;">
                <?= htmlspecialchars((string)$r['request_text'], ENT_QUOTES) ?>
            </div>
        </div>
        <script>
            (function () {
                'use strict';
                var btn = document.getElementById('prayer-reveal-btn');
                if (!btn) return;
                btn.addEventListener('click', function () {
                    var curtain = document.getElementById('prayer-private-curtain');
                    var body    = document.getElementById('prayer-private-body');
                    if (curtain) curtain.style.display = 'none';
                    if (body)    body.style.display    = 'block';
                });
            })();
        </script>
    <?php else: ?>
        <!-- Non-private: just show the text. -->
        <div style="white-space:pre-wrap; line-height:1.5; padding:1em;
                    background:#fff; border:1px solid #eee; border-radius:3px;">
            <?= htmlspecialchars((string)$r['request_text'], ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <!-- Status actions -->
    <div style="margin-top:1.5em; display:flex; gap:0.5em; flex-wrap:wrap;
                padding-top:1.5em; border-top:1px solid #eee;">
        <?php
        // Available status transitions from the current status.
        // 'new' → prayed | archived
        // 'prayed' → archived | new (back to inbox)
        // 'archived' → new (revive)
        $transitions = [
            'new'      => [['prayed','Mark as Prayed'], ['archived','Archive']],
            'prayed'   => [['archived','Archive'],      ['new','Return to inbox']],
            'archived' => [['new','Return to inbox']],
        ];
        foreach ($transitions[$r['status']] ?? [] as [$target, $label]):
        ?>
            <form method="post" action="/admin/prayer/<?= (int)$r['id'] ?>/status"
                  style="display:inline; margin:0;">
                <?= \Settle\Csrf::field() ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($target, ENT_QUOTES) ?>">
                <button type="submit" class="btn-primary"
                        style="cursor:pointer;">
                    <?= htmlspecialchars($label, ENT_QUOTES) ?>
                </button>
            </form>
        <?php endforeach; ?>

        <?php if ($isAdmin): ?>
            <form method="post" action="/admin/prayer/<?= (int)$r['id'] ?>/delete"
                  style="display:inline; margin:0; margin-left:auto;">
                <?= \Settle\Csrf::field() ?>
                <button type="submit" class="linklike"
                        style="color:var(--error); cursor:pointer;"
                        data-confirm="Permanently delete this prayer request? This cannot be undone.">
                    Delete permanently
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
