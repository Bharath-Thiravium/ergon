// Minimal modal utilities: show/hide modal and manage body scroll-lock
(function(){
  // Unified selector to find modal-like elements across the app
  var MODAL_SELECTOR = '.modal, .modal-overlay, .dialog, .dialog-overlay, .message-modal, [id$="Modal"]';

  // Show a modal by id or element and ensure no other modal remains visible
  window.showModalById = function(id){
    var el = typeof id === 'string' ? document.getElementById(id) : id;
    if(!el) return;

    // Hide any other visible modals to avoid stacking
    Array.prototype.slice.call(document.querySelectorAll(MODAL_SELECTOR)).forEach(function(m){
      try{
        if(m !== el) m.style.display = 'none';
      }catch(e){}
    });

    el.style.display = 'flex';
    document.body.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
  }

  // Hide a modal and only clear body scroll-lock if no other modal is visible
  window.hideModalById = function(id){
    var el = typeof id === 'string' ? document.getElementById(id) : id;
    if(!el) return;
    try{ el.style.display = 'none'; }catch(e){}

    var anyVisible = Array.prototype.slice.call(document.querySelectorAll(MODAL_SELECTOR)).some(function(m){
      try{ return m !== el && window.getComputedStyle(m).display !== 'none'; }catch(e){ return false; }
    });
    if(!anyVisible){
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
    }
  }

  // Hide the closest modal-like ancestor of the passed element
  window.hideClosestModal = function(el){
    if(!el) return;
    var m = el.closest(MODAL_SELECTOR);
    if(!m) return;
    if(m.id) return hideModalById(m.id);
    try{ m.style.display = 'none'; }catch(e){}

    var anyVisible = Array.prototype.slice.call(document.querySelectorAll(MODAL_SELECTOR)).some(function(x){
      try{ return x !== m && window.getComputedStyle(x).display !== 'none'; }catch(e){ return false; }
    });
    if(!anyVisible){
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
    }
  }
})();
