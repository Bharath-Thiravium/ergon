/* ========================================
   MODAL SYSTEM — SINGLE SOURCE OF TRUTH
   ======================================== */

(function () {
  'use strict';

  /**
   * Move the overlay to <body> the first time it is shown.
   * This makes it immune to any ancestor stacking context
   * (overflow:hidden, transform, z-index, filter, etc.)
   * regardless of where it was placed in the source HTML.
   */
  function teleportToBody(modal) {
    if (modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }
  }

  window.showModal = function (id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    teleportToBody(modal);
    modal.dataset.visible = 'true';
    document.body.classList.add('modal-open');
  };

  window.hideModal = function (id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.dataset.visible = 'false';
    if (!document.querySelector('.modal-overlay[data-visible="true"]')) {
      document.body.classList.remove('modal-open');
    }
  };

  // Close on backdrop click
  document.addEventListener('click', function (e) {
    if (
      e.target.classList.contains('modal-overlay') &&
      e.target.dataset.visible === 'true'
    ) {
      hideModal(e.target.id);
    }
  });

  // Close on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      const visible = document.querySelector(
        '.modal-overlay[data-visible="true"]'
      );
      if (visible) hideModal(visible.id);
    }
  });
})();
