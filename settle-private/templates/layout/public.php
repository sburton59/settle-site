<?php
/**
 * Public-facing layout.
 *
 * Receives from \Settle\PublicView::render():
 *   string $content     — rendered template HTML
 *   array  $settings    — full settings table as [key => value]
 *   array  $menu_tree   — nested array of active menu items
 *   string $page_title  — <title> text (defaults to church_name)
 *   Closure $e          — htmlspecialchars helper
 *
 * Per PROJECT_HANDOFF.md §9: zero hardcoded church identity. Every
 * value comes from $settings or $menu_tree.
 *
 * Per PROJECT_HANDOFF.md §14.5: nav HTML lives in this template
 * (per-site concern); the menu data structure comes from core.
 *
 * @var string  $content
 * @var array   $settings
 * @var array   $menu_tree
 * @var string  $page_title
 * @var Closure $e
 */

// Convenience accessor for settings with default fallback.
$s = static function (string $key, string $default = '') use ($settings): string {
    return isset($settings[$key]) && $settings[$key] !== ''
        ? (string) $settings[$key]
        : $default;
};

// Build address line for footer.
$addressCity  = $s('church_address_city');
$addressState = $s('church_address_state');
$addressZip   = $s('church_address_zip');
$addressCityLine = trim($addressCity
    . ($addressState !== '' ? ', ' . $addressState : '')
    . ($addressZip !== '' ? ' ' . $addressZip : ''));

// Recursive nav renderer for the desktop menu.
$renderDesktopMenu = static function (array $items, Closure $e, int $depth = 0) use (&$renderDesktopMenu): string {
    if ($items === []) {
        return '';
    }
    $listClass = $depth === 0 ? 'site-nav__list' : 'site-nav__submenu';
    $out = '<ul class="' . $listClass . '">';
    foreach ($items as $item) {
        $hasChildren = !empty($item['children']);
        $url   = (string) ($item['url'] ?? '');
        $label = (string) ($item['label'] ?? '');
        $target = ($item['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';

        $out .= '<li class="site-nav__item">';
        if ($url !== '') {
            $out .= '<a class="site-nav__link" href="' . $e($url) . '"' . $target . '>'
                  . $e($label) . '</a>';
        } else {
            // Parent-only items (no URL) render as non-link spans.
            $out .= '<span class="site-nav__link" tabindex="0">' . $e($label) . '</span>';
        }
        if ($hasChildren && $depth === 0) {
            $out .= $renderDesktopMenu($item['children'], $e, $depth + 1);
        }
        $out .= '</li>';
    }
    $out .= '</ul>';
    return $out;
};

// Recursive nav renderer for the mobile drawer (flatter; no hover submenu).
$renderMobileMenu = static function (array $items, Closure $e) use (&$renderMobileMenu): string {
    if ($items === []) {
        return '';
    }
    $out = '<ul class="site-nav-mobile__list">';
    foreach ($items as $item) {
        $url   = (string) ($item['url'] ?? '');
        $label = (string) ($item['label'] ?? '');
        $target = ($item['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';

        $out .= '<li>';
        if ($url !== '') {
            $out .= '<a class="site-nav-mobile__link" href="' . $e($url) . '"' . $target . '>'
                  . $e($label) . '</a>';
        } else {
            $out .= '<span class="site-nav-mobile__link">' . $e($label) . '</span>';
        }
        if (!empty($item['children'])) {
            $sub = '<ul class="site-nav-mobile__sublist">';
            foreach ($item['children'] as $child) {
                $curl   = (string) ($child['url'] ?? '');
                $clabel = (string) ($child['label'] ?? '');
                $ctarget = ($child['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';
                $sub .= '<li>';
                if ($curl !== '') {
                    $sub .= '<a class="site-nav-mobile__link" href="' . $e($curl) . '"' . $ctarget . '>'
                          . $e($clabel) . '</a>';
                } else {
                    $sub .= '<span class="site-nav-mobile__link">' . $e($clabel) . '</span>';
                }
                $sub .= '</li>';
            }
            $sub .= '</ul>';
            $out .= $sub;
        }
        $out .= '</li>';
    }
    $out .= '</ul>';
    return $out;
};
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($page_title) ?></title>
<?php if ($s('meta_description') !== ''): ?>
<meta name="description" content="<?= $e($s('meta_description')) ?>">
<?php endif; ?>

<?php if ($s('brand_favicon_url') !== ''): ?>
<link rel="icon" type="image/png" href="<?= $e($s('brand_favicon_url')) ?>">
<?php endif; ?>
<?php if ($s('brand_apple_icon_url') !== ''): ?>
<link rel="apple-touch-icon" href="<?= $e($s('brand_apple_icon_url')) ?>">
<?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Lato:wght@400;700&display=swap">

<link rel="stylesheet" href="/assets/css/theme.css">
<?php
/*
 * Brand-color override (Settings UI / Branding, roadmap #4).
 *
 * Emit a small inline <style> that overrides theme.css's :root brand
 * variables from the DB-backed settings, so an admin can recolor the
 * public site without touching CSS. Loaded AFTER theme.css so the
 * cascade lets these win.
 *
 * SECURITY: each value is re-validated against a strict 6-digit hex
 * pattern HERE, at emit time — never trust the stored value. This is
 * defense in depth on top of SettingsController's validation: even a
 * value placed directly in the DB via raw SQL cannot break out of the
 * declaration or inject CSS. A blank/invalid value is omitted, so
 * theme.css's own default for that variable stands.
 *
 * v1 overrides only the two base variables (--brand-primary,
 * --brand-ink). The derived shades (--brand-primary-dark/-darker,
 * --brand-ink-soft) keep their theme.css values for now; coherent
 * shade derivation is deferred to the #4c design pass (per the
 * approved plan, Option A).
 */
$brandOverrides = [];
foreach (['brand_primary' => '--brand-primary', 'brand_ink' => '--brand-ink'] as $key => $cssVar) {
    $val = $s($key);
    if ($val !== '' && preg_match('/^#[0-9a-fA-F]{6}$/', $val)) {
        $brandOverrides[] = $cssVar . ':' . $val;
    }
}
if ($brandOverrides !== []):
?>
<style>:root{<?= implode(';', $brandOverrides) ?>}</style>
<?php endif; ?>
</head>
<body class="public">

<header class="site-header">
  <div class="container site-header__inner">

    <a class="site-header__brand" href="/">
      <?php if ($s('brand_logo_url') !== ''): ?>
        <img src="<?= $e($s('brand_logo_url')) ?>" alt="<?= $e($s('church_name', 'Home')) ?>">
      <?php else: ?>
        <span class="site-header__brand-text"><?= $e($s('church_short_name', $s('church_name'))) ?></span>
      <?php endif; ?>
    </a>

    <!-- Desktop nav -->
    <nav class="site-nav" aria-label="Primary">
      <?= $renderDesktopMenu($menu_tree, $e) ?>
    </nav>

    <!-- Mobile nav toggle -->
    <input class="site-nav-toggle" type="checkbox" id="site-nav-toggle" aria-controls="site-nav-mobile">
    <label class="site-nav-toggle-label" for="site-nav-toggle" aria-label="Toggle menu">Menu</label>

    <!-- Mobile drawer (CSS-only; tied to the checkbox above) -->
    <nav class="site-nav-mobile" id="site-nav-mobile" aria-label="Primary (mobile)">
      <?= $renderMobileMenu($menu_tree, $e) ?>
    </nav>

  </div>
</header>

<main>
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="site-footer__grid">

      <div>
        <div class="site-footer__brand-line"><?= $e($s('church_name')) ?></div>
        <address class="site-footer__address">
          <?php if ($s('church_address_line1') !== ''): ?>
            <strong><?= $e($s('church_address_line1')) ?></strong><br>
          <?php endif; ?>
          <?php if ($addressCityLine !== ''): ?>
            <?= $e($addressCityLine) ?><br>
          <?php endif; ?>
          <?php if ($s('church_mailing') !== ''): ?>
            <span style="opacity: 0.75; font-size: 0.85rem;">Mailing: <?= $e($s('church_mailing')) ?></span><br>
          <?php endif; ?>
          <?php if ($s('church_phone') !== ''): ?>
            <a href="tel:<?= $e(preg_replace('/\D+/', '', $s('church_phone'))) ?>"><?= $e($s('church_phone')) ?></a>
          <?php endif; ?>
        </address>
      </div>

      <div>
        <h4>Worship</h4>
        <?php if ($s('worship_traditional') !== ''): ?>
          <div><strong>Traditional Worship</strong> — <?= $e($s('worship_traditional')) ?></div>
        <?php endif; ?>
        <?php if ($s('worship_contemporary') !== ''): ?>
          <div><strong>Contemporary Worship</strong> — <?= $e($s('worship_contemporary')) ?></div>
        <?php endif; ?>
        <?php if ($s('worship_sunday_school') !== ''): ?>
          <div><strong>Sunday School</strong> — <?= $e($s('worship_sunday_school')) ?></div>
        <?php endif; ?>
        <?php if ($s('church_office_hours') !== ''): ?>
          <h4 style="margin-top: 1.5rem;">Office</h4>
          <div><?= $e($s('church_office_hours')) ?></div>
        <?php endif; ?>
      </div>

      <div>
        <h4>Connect</h4>
        <div class="site-footer__social">
          <?php if ($s('social_facebook') !== ''): ?>
            <a href="<?= $e($s('social_facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook">FB</a>
          <?php endif; ?>
          <?php if ($s('social_instagram') !== ''): ?>
            <a href="<?= $e($s('social_instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram">IG</a>
          <?php endif; ?>
          <?php if ($s('social_youtube') !== ''): ?>
            <a href="<?= $e($s('social_youtube')) ?>" target="_blank" rel="noopener" aria-label="YouTube">YT</a>
          <?php endif; ?>
        </div>
        <div style="margin-top: 1.5rem;">
          <a href="/prayer">Prayer requests</a><br>
          <a href="/contact">Contact us</a>
        </div>
      </div>

    </div>

    <div class="site-footer__bottom">
      &copy; <?= date('Y') ?> <?= $e($s('meta_copyright_holder', $s('church_name'))) ?>. All rights reserved.
    </div>
  </div>
</footer>

</body>
</html>
