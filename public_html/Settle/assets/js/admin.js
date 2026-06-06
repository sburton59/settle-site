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

// ─── Media Library drag-and-drop / multi-file uploader (roadmap #9) ──────────
// Progressive enhancement for /admin/media. Inert unless #media-uploader is on
// the page. Reveals the drop zone, hides the plain single-file form, and posts
// ONE file per request to /admin/media/upload-ajax so a single bad file doesn't
// sink the batch and each file shows its own progress. The CSRF token is read
// from the simple form's hidden _csrf field and sent as an X-CSRF-Token header
// (the router accepts either a form field or that header).

(function () {
  'use strict';

  var root = document.getElementById('media-uploader');
  if (!root) return;

  var tokenEl = document.querySelector('input[name="_csrf"]');
  var token = tokenEl ? tokenEl.value : '';
  var uploadUrl = root.getAttribute('data-upload-url') || '/admin/media/upload-ajax';

  var dropZone = root.querySelector('.uploader__drop');
  var input    = document.getElementById('media-uploader__input');
  var list     = document.getElementById('media-uploader__list');
  var simpleForm = document.getElementById('media-simple-form');

  // Enhance: show the rich uploader, retire the plain fallback form.
  root.hidden = false;
  if (simpleForm) simpleForm.style.display = 'none';

  var pending = 0;   // in-flight + queued uploads
  var added   = 0;   // successful uploads this batch

  function escapeText(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function uploadOne(file) {
    pending++;

    var item = document.createElement('li');
    item.className = 'uploader__item';
    item.innerHTML =
      '<span class="uploader__name">' + escapeText(file.name) + '</span>' +
      '<span class="uploader__bar"><span class="uploader__bar-fill" style="width:0%"></span></span>' +
      '<span class="uploader__status">Uploading…</span>';
    list.appendChild(item);

    var fill   = item.querySelector('.uploader__bar-fill');
    var status = item.querySelector('.uploader__status');

    var data = new FormData();
    data.append('file', file);
    data.append('_csrf', token); // belt-and-suspenders alongside the header

    var xhr = new XMLHttpRequest();
    xhr.open('POST', uploadUrl, true);
    xhr.setRequestHeader('X-CSRF-Token', token);

    if (xhr.upload) {
      xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
          fill.style.width = Math.round((e.loaded / e.total) * 100) + '%';
        }
      });
    }

    function finish(ok, message) {
      item.classList.add(ok ? 'is-done' : 'is-error');
      fill.style.width = '100%';
      status.textContent = ok ? '✓ Added' : ('✗ ' + (message || 'Failed'));
      pending--;
      if (ok) added++;
      if (pending === 0 && added > 0) {
        // Reload to page 1 so the new files appear in the grid.
        status.textContent = '✓ Added';
        setTimeout(function () { window.location.href = '/admin/media'; }, 700);
      }
    }

    xhr.addEventListener('load', function () {
      var resp = null;
      try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = null; }
      if (xhr.status >= 200 && xhr.status < 300 && resp && resp.ok) {
        finish(true);
      } else {
        finish(false, resp && resp.error ? resp.error : ('Error ' + xhr.status));
      }
    });
    xhr.addEventListener('error', function () { finish(false, 'Network error'); });

    xhr.send(data);
  }

  function handleFiles(fileList) {
    if (!fileList || !fileList.length) return;
    for (var i = 0; i < fileList.length; i++) {
      uploadOne(fileList[i]);
    }
  }

  input.addEventListener('change', function () {
    handleFiles(input.files);
    input.value = ''; // allow re-selecting the same file
  });

  ['dragenter', 'dragover'].forEach(function (ev) {
    dropZone.addEventListener(ev, function (e) {
      e.preventDefault();
      e.stopPropagation();
      dropZone.classList.add('is-dragover');
    });
  });
  ['dragleave', 'dragend', 'drop'].forEach(function (ev) {
    dropZone.addEventListener(ev, function (e) {
      e.preventDefault();
      e.stopPropagation();
      dropZone.classList.remove('is-dragover');
    });
  });
  dropZone.addEventListener('drop', function (e) {
    if (e.dataTransfer && e.dataTransfer.files) {
      handleFiles(e.dataTransfer.files);
    }
  });
})();
