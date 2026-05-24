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