<?php
/** @var array $m  The contact_messages row */

$name    = (string)($m['sender_name'] ?? '');
$email   = (string)($m['sender_email'] ?? '');
$phone   = (string)($m['sender_phone'] ?? '');
$reply   = (string)($m['reply_method'] ?? 'email');
$body    = (string)($m['message_text'] ?? '');
$ip      = (string)($m['ip_address'] ?? '');
$when    = (string)($m['submitted_at'] ?? '');
$isRead  = ((int)($m['is_read'] ?? 0) === 1);
$id      = (int)$m['id'];

$replyLabel = match ($reply) {
    'phone'  => 'Phone preferred',
    'either' => 'Email or phone',
    default  => 'Email preferred',
};

$canDelete = \Settle\Auth::hasRole('admin');
?>
<div style="max-width:780px;">
  <p style="margin-bottom:0.5em;">
    <a href="/admin/contact" style="color:#9E2A2B;">&larr; Back to inbox</a>
  </p>

  <div style="background:#fff; border:1px solid #ddd; border-radius:4px;
              padding:1.5em; margin-top:1em;">

    <!-- Header: name + meta -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start;
                gap:1em; flex-wrap:wrap; margin-bottom:1em;
                border-bottom:1px solid #eee; padding-bottom:1em;">
      <div>
        <h1 style="margin:0 0 0.2em 0;">
          <?= htmlspecialchars($name, ENT_QUOTES) ?>
        </h1>
        <div class="muted" style="font-size:0.9em;">
          <?php if ($when !== ''): ?>
            <?= htmlspecialchars(date('l, F j, Y \a\t g:i a', strtotime($when)), ENT_QUOTES) ?>
          <?php endif; ?>
        </div>
      </div>
      <div style="text-align:right; font-size:0.9em;">
        <div style="display:inline-block; padding:0.2em 0.7em;
                    background:<?= $isRead ? '#e6f4ea' : '#fffceb' ?>;
                    color:<?= $isRead ? '#1e8e3e' : '#7a5a00' ?>;
                    border-radius:10px; font-weight:600;">
          <?= $isRead ? 'Read' : 'Unread' ?>
        </div>
        <div class="muted" style="margin-top:0.4em;">
          <?= htmlspecialchars($replyLabel, ENT_QUOTES) ?>
        </div>
      </div>
    </div>

    <!-- Contact details -->
    <div style="margin-bottom:1.5em;">
      <table style="font-size:0.95em;">
        <?php if ($email !== ''): ?>
        <tr>
          <td style="padding:0.25em 1em 0.25em 0; color:#666; vertical-align:top;">Email:</td>
          <td style="padding:0.25em 0;">
            <?= \Settle\EmailObfuscator::link($email) ?>
          </td>
        </tr>
        <?php endif; ?>
        <?php if ($phone !== ''): ?>
        <tr>
          <td style="padding:0.25em 1em 0.25em 0; color:#666; vertical-align:top;">Phone:</td>
          <td style="padding:0.25em 0;">
            <?php
              $phoneDisplay = \Settle\PhoneFormatter::formatUs($phone);
              $phoneHref    = \Settle\PhoneFormatter::telHref($phone);
            ?>
            <?php if ($phoneHref !== ''): ?>
              <a href="<?= htmlspecialchars($phoneHref, ENT_QUOTES) ?>">
                <?= htmlspecialchars($phoneDisplay, ENT_QUOTES) ?>
              </a>
            <?php else: ?>
              <?= htmlspecialchars($phoneDisplay, ENT_QUOTES) ?>
            <?php endif; ?>
          </td>
        </tr>
        <?php endif; ?>
        <?php if ($ip !== ''): ?>
        <tr>
          <td style="padding:0.25em 1em 0.25em 0; color:#666; vertical-align:top;">IP address:</td>
          <td style="padding:0.25em 0; color:#666; font-family:monospace; font-size:0.9em;">
            <?= htmlspecialchars($ip, ENT_QUOTES) ?>
          </td>
        </tr>
        <?php endif; ?>
      </table>
    </div>

    <!-- Message body -->
    <div style="background:#fafafa; border:1px solid #eee; border-radius:3px;
                padding:1em 1.2em; white-space:pre-wrap; line-height:1.5;
                font-size:1em;">
      <?= htmlspecialchars($body, ENT_QUOTES) ?>
    </div>

    <!-- Actions -->
    <div style="margin-top:1.5em; padding-top:1em; border-top:1px solid #eee;
                display:flex; gap:0.5em; flex-wrap:wrap;">

      <?php if ($isRead): ?>
        <form method="post" action="/admin/contact/<?= $id ?>/unread" style="display:inline;">
          <?= \Settle\Csrf::field() ?>
          <button type="submit"
                  style="padding:0.5em 1em; cursor:pointer;
                         background:#fff; border:1px solid #ccc; border-radius:3px;">
            Mark as unread
          </button>
        </form>
      <?php else: ?>
        <form method="post" action="/admin/contact/<?= $id ?>/read" style="display:inline;">
          <?= \Settle\Csrf::field() ?>
          <button type="submit"
                  style="padding:0.5em 1em; cursor:pointer;
                         background:#fff; border:1px solid #ccc; border-radius:3px;">
            Mark as read
          </button>
        </form>
      <?php endif; ?>

      <?php if ($canDelete): ?>
        <form method="post" action="/admin/contact/<?= $id ?>/delete"
              style="display:inline; margin-left:auto;"
              onsubmit="return confirm('Delete this message permanently? This cannot be undone.');">
          <?= \Settle\Csrf::field() ?>
          <button type="submit"
                  style="padding:0.5em 1em; cursor:pointer;
                         background:#fff; border:1px solid #c66; color:#9E2A2B;
                         border-radius:3px;">
            Delete permanently
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
