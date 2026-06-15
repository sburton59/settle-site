<?php
/**
 * Admin help doc (roadmap #14) — full single-page view.
 *
 * @var callable $e          HTML-escape helper (from View::render)
 * @var array    $sections   all help sections (from \Settle\Help::sections())
 * @var array    $roleLabels role key => human label
 *
 * NB: safe keys only — never data/template/layout/content/e (§13.9).
 * Section bodies and titles are trusted, pre-escaped HTML and are echoed raw.
 */

use Settle\Help;

// Column order for the capability matrix, left to right.
$roleOrder = ['author', 'editor', 'admin'];

// Render one access cell: symbol + caption, coloured by level.
$cell = static function (string $level) use ($e): string {
    $m = Help::levelMeta($level);
    return '<span class="help-cell help-cell--' . $e($level) . '" title="' . $e($m['caption']) . '">'
         . $e($m['symbol']) . '</span>';
};
?>
<?php require __DIR__ . '/_styles.php'; ?>

<div class="help-wrap">
  <h1>Admin Help</h1>
  <p class="help-lead">How to use the website admin panel. Use your browser's
     print option to save or print this guide; each section starts on its own
     page, or print a single section with the link beside its heading.</p>

  <div class="help-toolbar help-noprint">
    <button type="button" class="help-btn" onclick="window.print()">Print this guide</button>
  </div>

  <nav class="help-toc help-noprint" aria-label="Help contents">
    <h2>Contents</h2>
    <ol>
      <?php foreach ($sections as $s): ?>
        <li><a href="#section-<?= $e($s['slug']) ?>"><?= $s['title'] ?></a></li>
      <?php endforeach; ?>
    </ol>
  </nav>

  <?php foreach ($sections as $s): ?>
    <section class="help-section" id="section-<?= $e($s['slug']) ?>">
      <div class="help-section__head">
        <h2><?= $s['title'] ?></h2>
        <a class="help-print-one help-noprint" href="/admin/help/<?= $e($s['slug']) ?>">Print just this section &rsaquo;</a>
      </div>

      <?php if (!empty($s['matrix']) && !empty($s['access'])): ?>
        <div class="help-who">
          <strong>Who can use this</strong>
          <ul>
            <?php foreach ($roleOrder as $r): ?>
              <?php
                $lvl  = $s['access'][$r][0] ?? Help::NONE;
                $note = trim((string)($s['access'][$r][1] ?? ''));
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
        <?= $s['body'] ?>
      </div>

      <?php /* The full capability matrix lives inside the Roles section. */ ?>
      <?php if ($s['slug'] === 'roles'): ?>
        <table class="help-matrix">
          <thead>
            <tr>
              <th>Section</th>
              <?php foreach ($roleOrder as $r): ?>
                <th class="help-mc"><?= $e($roleLabels[$r]) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sections as $row): ?>
              <?php if (empty($row['matrix'])) continue; ?>
              <tr>
                <td><a href="#section-<?= $e($row['slug']) ?>"><?= $row['title'] ?></a></td>
                <?php foreach ($roleOrder as $r): ?>
                  <?php
                    $lvl  = $row['access'][$r][0] ?? Help::NONE;
                    $note = trim((string)($row['access'][$r][1] ?? ''));
                  ?>
                  <td class="help-mc">
                    <?= $cell($lvl) ?>
                    <?php if ($note !== ''): ?><span class="help-note"><?= $e($note) ?></span><?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="help-legend">
          <span><span class="help-cell help-cell--full"><?= $e(Help::levelMeta(Help::FULL)['symbol']) ?></span> Full access</span>
          <span><span class="help-cell help-cell--partial"><?= $e(Help::levelMeta(Help::PARTIAL)['symbol']) ?></span> Limited access</span>
          <span><span class="help-cell help-cell--none"><?= $e(Help::levelMeta(Help::NONE)['symbol']) ?></span> No access</span>
        </p>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>
