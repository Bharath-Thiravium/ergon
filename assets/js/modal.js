/* ========================================
   SIMPLE MODAL SYSTEM - SINGLE SOURCE OF TRUTH
   ======================================== */

(function() {
  'use strict';

  // Show modal
  window.showModal = function(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    
    modal.dataset.visible = 'true';
    document.body.classList.add('modal-open');
  };

  // Hide modal
  window.hideModal = function(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    
    modal.dataset.visible = 'false';
    document.body.classList.remove('modal-open');
  };

  // Close on overlay click
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay') && e.target.dataset.visible === 'true') {
      hideModal(e.target.id);
    }
  });

  // Close on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const visibleModal = document.querySelector('.modal-overlay[data-visible="true"]');
      if (visibleModal) hideModal(visibleModal.id);
    }
  });
})();
