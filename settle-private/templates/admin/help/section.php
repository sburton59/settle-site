<?php
/**
 * Admin help doc (roadmap #14) — single-section view.
 *
 * Same content source as the full doc, rendered for printing one section at
 * a time. Reached at /admin/help/{slug}.
 *
 * @var callable $e           HTML-escape helper (from View::render)
 * @var array    $section     the one section to show
 * @var array    $allSections all sections (for the "other sections" jump list)
 * @var array    $roleLabels  role key => human label
 *
 * NB: safe keys only — never data/template/layout/content/e (§13.9).
 * The section body/title are trusted, pre-escaped HTML and are echoed raw.
 */

use Settle\Help;

$roleOrder = ['author', 'editor', 'admin'];
?>
<?php require __DIR__ . '/_styles.php'; ?>

<div class="help-wrap">
  <p class="help-lead help-noprint">
    <a href="/admin/help">&lsaquo; Back to the full guide</a>
  </p>

  <div class="help-toolbar help-noprint">
    <button type="button" class="help-btn" onclick="window.print()">Print this section</button>
  </div>

  <section class="help-section" id="section-<?= $e($section['slug']) ?>" style="break-before:auto; border-bottom:0;">
    <h1><?= $section['title'] ?></h1>

    <?php if (!empty($section['matrix']) && !empty($section['access'])): ?>
      <div class="help-who">
        <strong>Who can use this</strong>
        <ul>
          <?php foreach ($roleOrder as $r): ?>
            <?php
              $lvl  = $section['access'][$r][0] ?? Help::NONE;
              $note = trim((string)($section['access'][$r][1] ?? ''));
              $meta = Help::levelMeta($lvl);
            ?>
            <li>
              <span class="help-cell help-cell--<?= $e($lvl) ?>"><?= $e($meta['symbol']) ?></span>
              <strong style="display:inline; font-weight:600;"><?= $e($roleLabels[$r]) ?>:</strong>
              <?= $note !== '' ? $e($note) : $e($meta['caption']) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="help-body">
      <?= $section['body'] ?>
    </div>
  </section>

  <nav class="help-toc help-noprint" aria-label="Other help sections">
    <h2>Other sections</h2>
    <ol>
      <?php foreach ($allSections as $s): ?>
        <?php if ($s['slug'] === $section['slug']) continue; ?>
        <li><a href="/admin/help/<?= $e($s['slug']) ?>"><?= $s['title'] ?></a></li>
      <?php endforeach; ?>
    </ol>
  </nav>
</div>
