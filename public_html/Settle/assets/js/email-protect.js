// ─── Email obfuscation decoder (public side) ───────────────────────────────
// Reverses the XOR-hex encoding produced by \Settle\EmailObfuscator (see
// settle-private/src/EmailObfuscator.php). The first two hex chars are the
// key; the remaining bytes are the address XORed against that key.
//
// This is the public-page counterpart of the decoder that already ships in
// admin.js. Public templates (e.g. the Staff directory) render addresses via
// EmailObfuscator::link()/::text(), which deliberately keep no plaintext in
// the HTML — so without this script the links read "[email protected]" and a
// click does nothing. The public layout never loads admin.js, which is why
// the links were inert; loading this restores them.
//
// Two behaviours, matching admin.js:
//   1. On click of an <a class="protected-email">, build the mailto: URL on
//      the fly and navigate.
//   2. After load (slight delay), swap the "[email protected]" placeholder
//      inside .protected-email-text with the decoded address so humans can
//      read/copy it. The delay keeps the plaintext out of naive scrape-on-load
//      tools.
//
// Kept intentionally in sync with the decoder block in admin.js — if the
// EmailObfuscator encoding ever changes, update both.

(function () {
  'use strict';

  function decode(encoded) {
    if (!encoded || encoded.length < 4) return '';
    var key = parseInt(encoded.substr(0, 2), 16);
    if (isNaN(key)) return '';
    var out = '';
    for (var i = 2; i < encoded.length; i += 2) {
      var byte = parseInt(encoded.substr(i, 2), 16);
      if (isNaN(byte)) return '';
      out += String.fromCharCode(byte ^ key);
    }
    return out;
  }

  // Click handler — builds mailto: on demand and navigates.
  document.addEventListener('click', function (e) {
    var link = e.target.closest && e.target.closest('a.protected-email');
    if (!link) return;
    e.preventDefault();
    var addr = decode(link.getAttribute('data-email') || '');
    if (addr) {
      window.location.href = 'mailto:' + addr;
    }
  });

  // Reveal decoded text after page load. setTimeout so we don't block first
  // paint and so naive scrape-on-load tools miss the plaintext.
  function revealAll() {
    document.querySelectorAll('.protected-email-text').forEach(function (el) {
      var addr = decode(el.getAttribute('data-email') || '');
      if (addr) {
        el.textContent = addr;
      }
    });
  }

  if (document.readyState === 'complete') {
    setTimeout(revealAll, 100);
  } else {
    window.addEventListener('load', function () {
      setTimeout(revealAll, 100);
    });
  }
})();
