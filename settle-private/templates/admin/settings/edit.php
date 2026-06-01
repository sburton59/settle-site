<?php
/**
 * Admin Settings form.
 *
 * @var array $groups  Field schema from SettingsController::groups()
 * @var array $values  [key => current/echoed-back value]
 * @var array $errors  [key => message]
 *
 * Field rendering is driven entirely by the schema in $groups, so adding
 * a setting is a one-line change in the controller — no template edits.
 *
 * The Media Library picker reuses /admin/media/picker, which posts
 *   { mceAction:'insertImage', url, alt, mediaId }
 * to window.parent — the exact contract the blog and slideshow editors
 * use. Settings store the image URL (not a media id), so we save d.url.
 */
$errors = $errors ?? [];
$values = $values ?? [];

$val = static fn(string $k): string => (string) ($values[$k] ?? '');
$err = static function (string $k) use ($errors): string {
    return empty($errors[$k]) ? ''
        : '<small style="color:var(--error); display:block; margin-top:0.25em;">'
          . htmlspecialchars($errors[$k], ENT_QUOTES) . '</small>';
};
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">Settings</h1>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error">Please fix the highlighted fields — nothing was saved.</div>
<?php endif; ?>

<form method="post" action="/admin/settings" data-warn-unsaved id="settings-form">
  <?= \Settle\Csrf::field() ?>

  <?php foreach ($groups as $group): ?>
    <section style="margin-bottom:2em; padding-bottom:1.25em; border-bottom:1px solid var(--gray-200);">
      <h2 style="margin:0 0 0.25em;"><?= htmlspecialchars($group['title'], ENT_QUOTES) ?></h2>
      <?php if (!empty($group['intro'])): ?>
        <p class="muted" style="margin:0 0 1em;"><?= htmlspecialchars($group['intro'], ENT_QUOTES) ?></p>
      <?php endif; ?>

      <?php foreach ($group['fields'] as $f):
          $key   = $f['key'];
          $type  = $f['type'];
          $label = htmlspecialchars($f['label'], ENT_QUOTES);
          $value = $val($key);
          $max   = (int) ($f['max'] ?? 255);
          $help  = !empty($f['help'])
              ? '<small class="muted" style="display:block;">' . htmlspecialchars($f['help'], ENT_QUOTES) . '</small>'
              : '';
          $short = !empty($f['short']);
      ?>

        <?php if ($type === 'media'): ?>
          <div style="margin-bottom:1.1em;">
            <label style="display:block; margin-bottom:0.3em; font-weight:600;"><?= $label ?></label>
            <?= $help ?>
            <input type="hidden" name="<?= $key ?>" id="f-<?= $key ?>"
                   value="<?= htmlspecialchars($value, ENT_QUOTES) ?>">
            <div class="media-preview" id="prev-<?= $key ?>"
                 style="background:var(--gray-100); border-radius:4px; padding:0.75em; min-height:64px;
                        display:flex; align-items:center; gap:0.75em;">
              <?php if ($value !== ''): ?>
                <img src="<?= htmlspecialchars($value, ENT_QUOTES) ?>" alt=""
                     style="max-height:64px; max-width:200px;">
                <code style="word-break:break-all; font-size:0.8em;"><?= htmlspecialchars($value, ENT_QUOTES) ?></code>
              <?php else: ?>
                <span class="muted">No image selected.</span>
              <?php endif; ?>
            </div>
            <div style="margin-top:0.4em;">
              <button type="button" class="btn-primary pick-media"
                      data-target="f-<?= $key ?>" data-preview="prev-<?= $key ?>">
                <?= $value !== '' ? 'Change' : 'Choose' ?>
              </button>
              <button type="button" class="linklike clear-media"
                      data-target="f-<?= $key ?>" data-preview="prev-<?= $key ?>"
                      style="margin-left:0.6em; <?= $value === '' ? 'display:none;' : '' ?>">Remove</button>
            </div>
            <?= $err($key) ?>
          </div>

        <?php elseif ($type === 'color'):
            $hexValid = (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $value);
            $swatch   = $hexValid ? $value : ($f['default'] ?? '#000000');
        ?>
          <label>
            <span style="font-weight:600;"><?= $label ?></span>
            <?= $help ?>
            <span style="display:flex; align-items:center; gap:0.6em; margin-top:0.25em;">
              <input type="color" class="color-swatch" data-text="f-<?= $key ?>"
                     value="<?= htmlspecialchars($swatch, ENT_QUOTES) ?>"
                     style="width:3em; height:2.4em; padding:0; border:1px solid var(--gray-200); border-radius:4px; cursor:pointer;">
              <input type="text" name="<?= $key ?>" id="f-<?= $key ?>" class="color-text"
                     value="<?= htmlspecialchars($value, ENT_QUOTES) ?>" maxlength="7"
                     placeholder="(theme default)"
                     style="max-width:10em; font-family:monospace; text-transform:uppercase;">
            </span>
            <?= $err($key) ?>
          </label>

        <?php elseif ($type === 'textarea'): ?>
          <label>
            <span style="font-weight:600;"><?= $label ?></span>
            <?= $help ?>
            <textarea name="<?= $key ?>" maxlength="<?= $max ?>"
                      style="min-height:5em;"><?= htmlspecialchars($value, ENT_QUOTES) ?></textarea>
            <?= $err($key) ?>
          </label>

        <?php else:
            // text / email / url. URL is validated server-side; we use a
            // text input so admin.css styles it (input[type=url] isn't in
            // the stylesheet's selector list).
            $inputType = $type === 'email' ? 'email' : 'text';
            $style     = $short ? ' style="max-width:14em;"' : '';
        ?>
          <label>
            <span style="font-weight:600;"><?= $label ?></span>
            <?= $help ?>
            <input type="<?= $inputType ?>" name="<?= $key ?>" maxlength="<?= $max ?>"
                   value="<?= htmlspecialchars($value, ENT_QUOTES) ?>"<?= $style ?>>
            <?= $err($key) ?>
          </label>
        <?php endif; ?>

      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>

  <div style="margin:1.5em 0;">
    <button type="submit" class="btn-primary">Save Settings</button>
  </div>
</form>

<!-- Shared Media Library picker modal (reuses /admin/media/picker, same
     contract as the blog and slideshow editors). -->
<div id="picker-modal"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
            z-index:1000; align-items:center; justify-content:center;">
  <div style="background:#fff; width:90%; max-width:900px; height:80vh; border-radius:6px;
              display:flex; flex-direction:column; overflow:hidden;">
    <div style="display:flex; align-items:center; justify-content:space-between;
                padding:0.75em 1em; border-bottom:1px solid var(--gray-200);">
      <strong>Choose an image</strong>
      <button type="button" id="picker-close" class="linklike"
              style="font-size:1.4em; line-height:1;">×</button>
    </div>
    <iframe id="picker-frame" title="Media Library"
            style="border:0; flex-grow:1; width:100%;"></iframe>
  </div>
</div>

<script>
(function () {
  // ---- Brand color: keep the swatch and hex text input in sync ----
  document.querySelectorAll('.color-swatch').forEach(function (sw) {
    var text = document.getElementById(sw.getAttribute('data-text'));
    if (!text) return;
    sw.addEventListener('input', function () { text.value = sw.value.toUpperCase(); });
    text.addEventListener('input', function () {
      if (/^#[0-9a-fA-F]{6}$/.test(text.value)) { sw.value = text.value; }
    });
  });

  // ---- Media Library picker (logo / favicon / apple icon) ----
  var modal    = document.getElementById('picker-modal');
  var frame    = document.getElementById('picker-frame');
  var closeBtn = document.getElementById('picker-close');
  var active   = null; // { input, preview, button }

  function openPicker(input, preview, button) {
    active = { input: input, preview: preview, button: button };
    frame.src = '/admin/media/picker';
    modal.style.display = 'flex';
  }
  function closePicker() {
    active = null;
    modal.style.display = 'none';
    frame.src = 'about:blank';
  }
  if (closeBtn) closeBtn.addEventListener('click', closePicker);
  if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closePicker(); });

  document.querySelectorAll('.pick-media').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openPicker(
        document.getElementById(btn.getAttribute('data-target')),
        document.getElementById(btn.getAttribute('data-preview')),
        btn
      );
    });
  });

  document.querySelectorAll('.clear-media').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input   = document.getElementById(btn.getAttribute('data-target'));
      var preview = document.getElementById(btn.getAttribute('data-preview'));
      if (input) input.value = '';
      if (preview) preview.innerHTML = '<span class="muted">No image selected.</span>';
      var pick = document.querySelector('.pick-media[data-target="' + btn.getAttribute('data-target') + '"]');
      if (pick) pick.textContent = 'Choose';
      btn.style.display = 'none';
    });
  });

  window.addEventListener('message', function (ev) {
    if (!active) return;                                   // ignore stray messages
    var d = ev.data;
    if (!d || d.mceAction !== 'insertImage' || !d.url) return;

    if (active.input) active.input.value = d.url;
    if (active.preview) {
      active.preview.innerHTML = '';
      var img = document.createElement('img');
      img.src = d.url; img.alt = d.alt || '';
      img.style.maxHeight = '64px'; img.style.maxWidth = '200px';
      var code = document.createElement('code');
      code.style.wordBreak = 'break-all'; code.style.fontSize = '0.8em';
      code.textContent = d.url;
      active.preview.appendChild(img);
      active.preview.appendChild(code);
    }
    if (active.button) active.button.textContent = 'Change';
    var clear = active.input
        ? document.querySelector('.clear-media[data-target="' + active.input.id + '"]')
        : null;
    if (clear) clear.style.display = '';
    closePicker();
  });
})();
</script>
