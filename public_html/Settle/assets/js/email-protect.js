// ─── Email obfuscation: click-to-mail (public side) ─────────────────────────
// Reverses the XOR-hex encoding produced by \Settle\EmailObfuscator (see
// settle-private/src/EmailObfuscator.php). The first two hex chars are the
// key; the remaining bytes are the address XORed against that key.
//
// PUBLIC-SIDE POLICY: click-only. On the public site we deliberately do NOT
// reveal the plaintext address on the page — staff/ministry links render with
// a visible label (e.g. "Email") and the real address lives only in the
// encoded data-email attribute, so neither visitors nor naive scrapers ever
// see it in the rendered text or the HTML source. Clicking the link decodes
// the address on the fly and opens the visitor's mail client.
//
// (admin.js keeps a second behaviour that swaps the placeholder text for the
// decoded address, because staff working in the admin panel should see the
// addresses. That reveal is intentionally absent here.)
//
// Kept in sync with the decode() in admin.js: if the EmailObfuscator encoding
// ever changes, update both.

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

  // Click handler — build mailto: on demand and navigate. Delegated on the
  // document so it works for any .protected-email link, present or future.
  document.addEventListener('click', function (e) {
    var link = e.target.closest && e.target.closest('a.protected-email');
    if (!link) return;
    e.preventDefault();
    var addr = decode(link.getAttribute('data-email') || '');
    if (addr) {
      window.location.href = 'mailto:' + addr;
    }
  });
})();
