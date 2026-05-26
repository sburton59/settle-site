<?php
/** @var array $messages List of contact_messages rows */
/** @var int   $unread   Total unread count (for tab badge) */
/** @var string $filter  Active filter: 'unread' | 'read' | 'all' */

/** Format a phone number for display, falling back to the raw value. */
$fmtPhone = static function (?string $raw): string {
    if ($raw === null || $raw === '') {
        return '';
    }
    return \Settle\PhoneFormatter::formatUs($raw);
};

/** Short preview of the message body for the list view. */
$preview = static function (string $body, int $len = 120): string {
    $body = trim(preg_replace('/\s+/', ' ', $body) ?? '');
    if (mb_strlen($body) <= $len) {
        return $body;
    }
    return mb_substr($body, 0, $len - 1) . '…';
};

/** Build a tab link preserving the filter parameter. */
$tab = static function (string $key, string $label, string $current, ?int $badge = null): string {
    $isActive = ($current === $key);
    $href = '/admin/contact?filter=' . urlencode($key);
    $style = 'padding:0.5em 1em; text-decoration:none; border-radius:3px 3px 0 0;';
    if ($isActive) {
        $style .= ' background:#fff; border:1px solid #ddd; border-bottom-color:#fff;
                    font-weight:600; color:#222; position:relative; top:1px;';
    } else {
        $style .= ' color:#555;';
    }
    $out = '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '" style="' . $style . '">'
         . htmlspecialchars($label, ENT_QUOTES);
    if ($badge !== null && $badge > 0) {
        $out .= ' <span style="background:#9E2A2B; color:#fff; font-size:0.75em;
                               padding:0.1em 0.5em; border-radius:10px; margin-left:0.3em;">'
             . (int)$badge . '</span>';
    }
    return $out . '</a>';
};
?>
<div style="max-width:1000px;">
  <h1 style="margin-bottom:0.5em;">Contact Messages</h1>
  <p class="muted" style="margin-top:0;">
    Messages submitted through the public contact form.
  </p>

  <!-- Tabs -->
  <div style="border-bottom:1px solid #ddd; margin-top:1.5em;
              display:flex; gap:0.25em;">
    <?= $tab('unread', 'Unread', $filter, $unread) ?>
    <?= $tab('read',   'Read',   $filter) ?>
    <?= $tab('all',    'All',    $filter) ?>
  </div>

  <?php if (empty($messages)): ?>
    <div style="background:#fff; padding:2em; border:1px solid #ddd;
                border-top:none; text-align:center; color:#666;">
      <?php if ($filter === 'unread'): ?>
        No unread messages. 🎉
      <?php elseif ($filter === 'read'): ?>
        No read messages yet.
      <?php else: ?>
        No contact messages yet.
      <?php endif; ?>
    </div>
  <?php else: ?>
    <table style="width:100%; border-collapse:collapse; background:#fff;
                  border:1px solid #ddd; border-top:none;">
      <thead>
        <tr style="background:#f8f8f8; text-align:left;">
          <th style="padding:0.6em 0.8em; border-bottom:1px solid #ddd; width:1em;"></th>
          <th style="padding:0.6em 0.8em; border-bottom:1px solid #ddd;">From</th>
          <th style="padding:0.6em 0.8em; border-bottom:1px solid #ddd;">Message</th>
          <th style="padding:0.6em 0.8em; border-bottom:1px solid #ddd; white-space:nowrap;">Reply by</th>
          <th style="padding:0.6em 0.8em; border-bottom:1px solid #ddd; white-space:nowrap;">Received</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($messages as $m):
            $isUnread = ((int)$m['is_read'] === 0);
            $rowStyle = $isUnread ? 'background:#fffceb;' : '';
            $weight   = $isUnread ? 'font-weight:600;' : '';
            $contact  = [];
            if (!empty($m['sender_email'])) {
                $contact[] = htmlspecialchars((string)$m['sender_email'], ENT_QUOTES);
            }
            if (!empty($m['sender_phone'])) {
                $contact[] = htmlspecialchars($fmtPhone((string)$m['sender_phone']), ENT_QUOTES);
            }
            $detailUrl = '/admin/contact/' . (int)$m['id'];
        ?>
        <tr style="<?= $rowStyle ?> border-top:1px solid #eee;">
          <td style="padding:0.6em 0.8em; text-align:center;">
            <?php if ($isUnread): ?>
              <span title="Unread"
                    style="display:inline-block; width:0.6em; height:0.6em;
                           background:#9E2A2B; border-radius:50%;"></span>
            <?php endif; ?>
          </td>
          <td style="padding:0.6em 0.8em; <?= $weight ?>">
            <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES) ?>"
               style="color:#222; text-decoration:none;">
              <?= htmlspecialchars((string)$m['sender_name'], ENT_QUOTES) ?>
            </a>
            <?php if ($contact): ?>
              <div class="muted" style="font-size:0.85em; font-weight:normal; margin-top:0.2em;">
                <?= implode(' &middot; ', $contact) ?>
              </div>
            <?php endif; ?>
          </td>
          <td style="padding:0.6em 0.8em; <?= $weight ?>">
            <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES) ?>"
               style="color:#222; text-decoration:none;">
              <?= htmlspecialchars($preview((string)$m['message_text']), ENT_QUOTES) ?>
            </a>
          </td>
          <td style="padding:0.6em 0.8em; white-space:nowrap;">
            <?php
              $label = match ((string)$m['reply_method']) {
                  'phone'  => 'Phone',
                  'either' => 'Either',
                  default  => 'Email',
              };
              echo htmlspecialchars($label, ENT_QUOTES);
            ?>
          </td>
          <td style="padding:0.6em 0.8em; white-space:nowrap; color:#666; font-size:0.9em;">
            <?= htmlspecialchars(date('M j, Y g:i a', strtotime((string)$m['submitted_at'])), ENT_QUOTES) ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
