// ─── Staff bio modal (public side) ──────────────────────────────────────────
// Behaviour B from the staff-card display fix: keep the card grid perfectly
// uniform (bios are CSS-clamped to four lines) and surface the full bio in a
// centred modal on demand. The card grid never reflows on click.
//
// Progressive enhancement: if this script never runs, the cards still render
// neatly (clamped previews), the "Read more" buttons stay hidden (they ship
// with the `hidden` attribute and are only un-hidden here), and the full bio
// text simply lives unseen in each card's .staff-card__bio-full node.
//
// No dependencies. The contact block and full bio are cloned out of the
// clicked card, so the obfuscated mailto/tel links keep working unchanged.

(function () {
  'use strict';

  var modal = document.getElementById('staff-modal');
  if (!modal) return;

  var dialog    = modal.querySelector('.staff-modal__dialog');
  var elPhoto   = document.getElementById('staff-modal-photo');
  var elName    = document.getElementById('staff-modal-name');
  var elTitle   = document.getElementById('staff-modal-title');
  var elBio     = document.getElementById('staff-modal-bio');
  var elContact = document.getElementById('staff-modal-contact');

  var lastFocused = null;

  // ── Truncation test (WebKit-safe) ─────────────────────────────────────────
  // The obvious test — scrollHeight > clientHeight on the clamped element —
  // works in Blink (Chrome) but NOT in WebKit (all iOS browsers): Safari
  // reports scrollHeight === clientHeight on a -webkit-line-clamp box, so the
  // button would never appear on iPad/iPhone. Instead, briefly drop the clamp
  // (.is-measuring) and read the element's natural height, comparing it to the
  // clamped height. The add/read/remove is synchronous, so nothing repaints
  // and there's no visible flicker.
  function isTruncated(prev) {
    var clampedH = prev.clientHeight;
    prev.classList.add('is-measuring');
    var fullH = prev.scrollHeight;   // forces a synchronous reflow
    prev.classList.remove('is-measuring');
    return fullH > clampedH + 1;     // +1 absorbs sub-pixel rounding
  }

  // Show "Read more" only where the preview is actually clipped. Idempotent —
  // safe to call again after fonts load, on window load, and on resize /
  // orientation change (when the column width, and therefore the line count,
  // can change).
  function syncMoreButtons() {
    var cards = document.querySelectorAll('.staff-card');
    Array.prototype.forEach.call(cards, function (card) {
      var btn  = card.querySelector('.staff-card__more');
      var prev = card.querySelector('.staff-card__bio');
      if (!btn || !prev) return;
      btn.hidden = !isTruncated(prev);
    });
  }

  function openFor(card) {
    lastFocused = document.activeElement;

    var name  = card.getAttribute('data-staff-name')  || '';
    var title = card.getAttribute('data-staff-title') || '';
    var photo = card.getAttribute('data-staff-photo') || '';

    elName.textContent = name;
    elPhoto.style.backgroundImage = photo ? "url('" + photo.replace(/'/g, "\\'") + "')" : '';
    elPhoto.setAttribute('aria-label', name);

    if (title) {
      elTitle.textContent = title;
      elTitle.hidden = false;
    } else {
      elTitle.textContent = '';
      elTitle.hidden = true;
    }

    // Full bio: clone the hidden node's markup (admin-authored, trusted).
    var fullBio = card.querySelector('.staff-card__bio-full');
    elBio.innerHTML = fullBio ? fullBio.innerHTML : '';

    // Contact: clone the card's contact block so the existing mailto/tel
    // links carry over verbatim. Drop the card-specific class on the clone.
    var contact = card.querySelector('.staff-card__contact');
    elContact.innerHTML = '';
    if (contact) {
      var clone = contact.cloneNode(true);
      clone.className = '';
      while (clone.firstChild) { elContact.appendChild(clone.firstChild); }
      elContact.hidden = false;
    } else {
      elContact.hidden = true;
    }

    modal.hidden = false;
    // Next frame so the opacity/transform transition actually runs.
    requestAnimationFrame(function () { modal.classList.add('is-open'); });
    document.body.classList.add('staff-modal-open');
    document.addEventListener('keydown', onKeydown);
    dialog.focus();
  }

  function close() {
    modal.classList.remove('is-open');
    document.body.classList.remove('staff-modal-open');
    document.removeEventListener('keydown', onKeydown);

    var finish = function () {
      modal.hidden = true;
      dialog.removeEventListener('transitionend', finish);
    };
    // If transitions are disabled (reduced motion), transitionend won't fire.
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce) {
      finish();
    } else {
      dialog.addEventListener('transitionend', finish);
    }

    if (lastFocused && typeof lastFocused.focus === 'function') {
      lastFocused.focus();
    }
  }

  function onKeydown(e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
      e.preventDefault();
      close();
      return;
    }
    if (e.key === 'Tab') { trapFocus(e); }
  }

  // ── Simple focus trap: keep Tab cycling within the dialog. ────────────────
  function trapFocus(e) {
    var focusable = dialog.querySelectorAll(
      'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
    );
    if (focusable.length === 0) { e.preventDefault(); dialog.focus(); return; }
    var first = focusable[0];
    var last  = focusable[focusable.length - 1];
    var active = document.activeElement;

    if (e.shiftKey && (active === first || active === dialog)) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && active === last) {
      e.preventDefault();
      first.focus();
    }
  }

  // ── Open: one delegated handler (no per-button binding, so re-running the
  // truncation sync can never double-bind or miss a button on iOS). ─────────
  document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest ? e.target.closest('.staff-card__more') : null;
    if (!btn) return;
    var card = btn.closest('.staff-card');
    if (card) { openFor(card); }
  });

  // Close on the X button or a click/tap on the dimmed backdrop.
  modal.addEventListener('click', function (e) {
    var t = e.target;
    if (t && t.closest && t.closest('[data-staff-modal-close]')) {
      close();
    }
  });

  // ── Run truncation detection now, then again once the layout has settled.
  // Lato is a web font: on first paint the system fallback renders, so a check
  // that runs before the font swaps in measures the wrong metrics. Re-running
  // after fonts load / on window load / on rotation keeps the buttons correct.
  syncMoreButtons();
  if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
    document.fonts.ready.then(syncMoreButtons);
  }
  window.addEventListener('load', syncMoreButtons);

  var resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(syncMoreButtons, 150);
  });
  window.addEventListener('orientationchange', function () {
    setTimeout(syncMoreButtons, 200);
  });
})();
