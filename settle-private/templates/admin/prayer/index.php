<?php
/** @var array $requests   List of prayer_requests rows */
/** @var array $counts     ['new'=>n,'prayed'=>n,'archived'=>n,'total'=>n] */
/** @var string $status    Currently-selected filter: 'new'|'prayed'|'archived'|'all' */
/** @var bool   $canReveal True if current user is editor or admin */

$statusLabels = [
    'new'      => 'New',
    'prayed'   => 'Prayed',
    'archived' => 'Archived',
    'all'      => 'All',
];
?>

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
    <h1 style="margin:0;">Prayer Requests</h1>
</div>

<!-- Status tabs -->
<div class="tabs" style="display:flex; gap:0.5em; margin-bottom:1em; flex-wrap:wrap;">
    <?php foreach (['new','prayed','archived','all'] as $s):
        $isActive = ($s === $status);
        $count = $s === 'all' ? $counts['total'] : ($counts[$s] ?? 0);
    ?>
        <a href="/admin/prayer?status=<?= htmlspecialchars($s, ENT_QUOTES) ?>"
           style="text-decoration:none; padding:0.4em 0.9em; border-radius:4px;
                  <?= $isActive
                        ? 'background:#9E2A2B; color:#fff; font-weight:500;'
                        : 'background:#fff; color:#333; border:1px solid #ddd;' ?>">
            <?= htmlspecialchars($statusLabels[$s], ENT_QUOTES) ?>
            <span style="margin-left:0.4em; font-size:0.85em; opacity:0.85;">
                <?= (int)$count ?>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($requests)): ?>
    <div style="background:#fff; padding:2em; text-align:center; border-radius:4px;">
        <p class="muted">
            <?php if ($status === 'new'): ?>
                No new prayer requests. 🙏
            <?php else: ?>
                No requests in this view.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <ul style="list-style:none; padding:0; margin:0; display:grid; gap:0.75em;">
    <?php foreach ($requests as $r):
        $isPrivate = ((int)$r['is_private'] === 1);
        $displayName = trim((string)($r['submitter_name'] ?? ''));
        if ($displayName === '') {
            $displayName = '(anonymous)';
        }
        $submittedAt = strtotime((string)$r['submitted_at']) ?: time();
    ?>
        <li style="background:#fff; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.05);
                   padding:0.75em 1em; display:flex; gap:1em; align-items:flex-start;
                   <?= $isPrivate ? 'border-left:3px solid #9E2A2B;' : '' ?>">

            <!-- Status pill -->
            <div style="flex-shrink:0; min-width:5em;">
                <span style="display:inline-block; font-size:0.75em; padding:0.2em 0.6em;
                             border-radius:2em; font-weight:500; text-transform:uppercase;
                             letter-spacing:0.04em;
                             <?php
                                switch ($r['status']) {
                                    case 'new':      echo 'background:#fff3cd; color:#856404;'; break;
                                    case 'prayed':   echo 'background:#d4edda; color:#155724;'; break;
                                    case 'archived': echo 'background:#e2e3e5; color:#383d41;'; break;
                                }
                              ?>">
                    <?= htmlspecialchars((string)$r['status'], ENT_QUOTES) ?>
                </span>
            </div>

            <!-- Body -->
            <div style="flex-grow:1; min-width:0;">
                <div style="display:flex; gap:0.5em; align-items:center; margin-bottom:0.3em;">
                    <strong><?= htmlspecialchars($displayName, ENT_QUOTES) ?></strong>
                    <?php if ($isPrivate): ?>
                        <span title="Private request" style="font-size:0.85em;">🔒 Private</span>
                    <?php endif; ?>
                    <span class="muted" style="font-size:0.85em;">
                        · <?= htmlspecialchars(date('M j, Y g:i a', $submittedAt), ENT_QUOTES) ?>
                    </span>
                </div>

                <div style="color:#555; font-size:0.95em; line-height:1.4;
                            overflow:hidden; display:-webkit-box;
                            -webkit-line-clamp:2; -webkit-box-orient:vertical;">
                    <?php if ($isPrivate && !$canReveal): ?>
                        <em class="muted">[Private — request text is hidden]</em>
                    <?php else: ?>
                        <?php
                        // Two-line preview. For private requests visible to editors+,
                        // we still show the preview here — the click-to-reveal is on
                        // the detail page, which is the "intentional act" moment.
                        // If a stronger gate is wanted later, replace this line.
                        $preview = (string)$r['request_text'];
                        if (mb_strlen($preview) > 240) {
                            $preview = mb_substr($preview, 0, 240) . '…';
                        }
                        echo htmlspecialchars($preview, ENT_QUOTES);
                        ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div style="flex-shrink:0;">
                <a href="/admin/prayer/<?= (int)$r['id'] ?>"
                   style="text-decoration:none; padding:0.3em 0.7em;
                          background:#f4f4f4; border-radius:3px;">
                    View
                </a>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
