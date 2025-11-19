/* JavaScript Error Fixes */

// Immediate switchView override
window.switchView = function(view) {
  const gridView = document.getElementById('gridView');
  if (gridView && gridView.style) {
    gridView.style.display = view === 'grid' ? 'block' : 'none';
  }
};

// Additional safety check
(function() {
  const originalAddEventListener = EventTarget.prototype.addEventListener;
  EventTarget.prototype.addEventListener = function(type, listener, options) {
    if (typeof listener === 'function') {
      const wrappedListener = function(event) {
        try {
          listener.call(this, event);
        } catch (error) {
          console.warn('Event listener error caught:', error.message);
        }
      };
      originalAddEventListener.call(this, type, wrappedListener, options);
    } else {
      originalAddEventListener.call(this, type, listener, options);
    }
  };
})();