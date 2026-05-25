// Settle Admin — small UX helpers.
// Keep this file tiny on purpose. No frameworks for now.

(function () {
  'use strict';

  // Auto-dismiss flash messages after 5 seconds (but not errors).
  document.querySelectorAll('.flash:not(.flash-error)').forEach(function (el) {
    setTimeout(function () { el.style.opacity = '0'; }, 5000);
    setTimeout(function () { el.remove(); }, 5400);
  });

  // Warn before leaving a form with unsaved changes.
  document.querySelectorAll('form[data-warn-unsaved]').forEach(function (form) {
    var dirty = false;
    form.addEventListener('change', function () { dirty = true; });
    form.addEventListener('input',  function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
      if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
  });

  // Confirm destructive actions.
  document.querySelectorAll('[data-confirm]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!confirm(btn.dataset.confirm)) e.preventDefault();
    });
  });
})();
// ─── Email obfuscation decoder ─────────────────────────────────────
// Reverses the XOR-hex encoding from \Settle\EmailObfuscator. The first
// two hex chars are the key; remaining bytes are the address XORed
// against that key.
//
// We do TWO things:
//   1. On click, build the mailto: URL on the fly so the link works.
//   2. After a short delay (or once the page is idle), swap the
//      "[email protected]" placeholder text inside .protected-email-text
//      with the decoded address so it's visible to humans copying it.
//
// The delay matters: naive scrapers grab the initial HTML and leave,
// so they never see the decoded text. JavaScript-aware scrapers exist
// but are far rarer and more expensive to run at scale.

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

  // Reveal decoded text after page load. Done in a setTimeout so we
  // don't block first paint and so naive scrape-on-load tools miss it.
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
