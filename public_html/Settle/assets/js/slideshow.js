/**
 * Settle public slideshow.
 *
 * Initializes any element matching [data-slideshow]. Auto-rotates
 * through child .slideshow__slide elements at the interval specified
 * by data-interval (default 6000ms). Dot indicators (any
 * [data-slide-target] inside [data-slideshow-dots]) jump to specific
 * slides.
 *
 * Pauses on hover/focus. Keyboard left/right arrows advance when the
 * slideshow has focus. Honors prefers-reduced-motion: users who set
 * that preference get a static first slide with no auto-rotation
 * (but dots still work).
 */
(function () {
  'use strict';

  const ACTIVE = 'is-active';
  const reducedMotion = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function init(root) {
    const slides = Array.from(root.querySelectorAll('.slideshow__slide'));
    if (slides.length <= 1) {
      // Single slide (or none) — nothing to rotate. Leave the first
      // slide active and exit.
      return;
    }

    const dotsContainer = root.querySelector('[data-slideshow-dots]');
    const dots = dotsContainer
      ? Array.from(dotsContainer.querySelectorAll('[data-slide-target]'))
      : [];

    let currentIndex = slides.findIndex(s => s.classList.contains(ACTIVE));
    if (currentIndex < 0) currentIndex = 0;

    const interval = parseInt(root.getAttribute('data-interval'), 10) || 6000;
    let timerId = null;
    let isPaused = false;

    function show(nextIndex) {
      if (nextIndex === currentIndex) return;
      const prev = slides[currentIndex];
      const next = slides[nextIndex];
      prev.classList.remove(ACTIVE);
      prev.setAttribute('aria-hidden', 'true');
      next.classList.add(ACTIVE);
      next.setAttribute('aria-hidden', 'false');
      if (dots.length) {
        if (dots[currentIndex]) dots[currentIndex].classList.remove(ACTIVE);
        if (dots[nextIndex]) dots[nextIndex].classList.add(ACTIVE);
      }
      currentIndex = nextIndex;
    }

    function advance() {
      const next = (currentIndex + 1) % slides.length;
      show(next);
    }

    function start() {
      if (timerId !== null) return;
      if (reducedMotion) return; // no auto-rotation
      timerId = window.setInterval(function () {
        if (!isPaused) advance();
      }, interval);
    }

    function stop() {
      if (timerId === null) return;
      window.clearInterval(timerId);
      timerId = null;
    }

    // Pause on hover/focus.
    root.addEventListener('mouseenter', function () { isPaused = true; });
    root.addEventListener('mouseleave', function () { isPaused = false; });
    root.addEventListener('focusin',    function () { isPaused = true; });
    root.addEventListener('focusout',   function () { isPaused = false; });

    // Pause when the tab is hidden (saves battery, avoids surprise jumps).
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) stop();
      else start();
    });

    // Dot clicks.
    dots.forEach(function (dot, i) {
      dot.addEventListener('click', function () {
        show(i);
      });
    });

    // Keyboard nav when the slideshow contains focus.
    root.setAttribute('tabindex', root.getAttribute('tabindex') || '0');
    root.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        advance();
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        const prev = (currentIndex - 1 + slides.length) % slides.length;
        show(prev);
      }
    });

    start();
  }

  function initAll() {
    const roots = document.querySelectorAll('[data-slideshow]');
    roots.forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
