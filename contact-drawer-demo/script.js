/**
 * Contact side-drawer: opens from the right with a dimmed backdrop.
 * Toggles the shared class name `.is-open` on backdrop + drawer (and locks body scroll).
 */

(function () {
  'use strict';

  var openBtn = document.getElementById('contact-open');
  var closeBtn = document.getElementById('contact-close');
  var backdrop = document.getElementById('contact-backdrop');
  var drawer = document.getElementById('contact-drawer');
  var form = document.getElementById('contact-form');

  if (!openBtn || !backdrop || !drawer) {
    return;
  }

  /** Tracks which element had focus before opening (for focus return on close). */
  var lastFocused = null;

  /**
   * @param {boolean} open - true to show drawer, false to hide
   */
  function setOpen(open) {
    if (open) {
      lastFocused = document.activeElement;
      backdrop.classList.add('is-open');
      drawer.classList.add('is-open');
      document.body.classList.add('drawer-open');
      backdrop.setAttribute('aria-hidden', 'false');
      drawer.setAttribute('aria-hidden', 'false');
      // Move keyboard focus into the panel for screen readers
      var focusTarget = closeBtn || drawer;
      if (focusTarget && typeof focusTarget.focus === 'function') {
        focusTarget.focus();
      }
    } else {
      backdrop.classList.remove('is-open');
      drawer.classList.remove('is-open');
      document.body.classList.remove('drawer-open');
      backdrop.setAttribute('aria-hidden', 'true');
      drawer.setAttribute('aria-hidden', 'true');
      if (lastFocused && typeof lastFocused.focus === 'function') {
        lastFocused.focus();
      }
      lastFocused = null;
    }
  }

  function isOpen() {
    return drawer.classList.contains('is-open');
  }

  openBtn.addEventListener('click', function () {
    setOpen(true);
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      setOpen(false);
    });
  }

  /**
   * Close only when the user clicks the backdrop itself, not bubbled events
   * from children (there are none on the backdrop, but this pattern is safe).
   */
  backdrop.addEventListener('click', function (event) {
    if (event.target === backdrop) {
      setOpen(false);
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && isOpen()) {
      event.preventDefault();
      setOpen(false);
    }
  });

  /**
   * Demo: stop real navigation; replace with fetch() or form action to your server.
   */
  if (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      // Example only — remove preventDefault when posting to a real endpoint
      if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
        return;
      }
      setOpen(false);
    });
  }
})();
