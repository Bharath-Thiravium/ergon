/* JavaScript Error Fixes */

// Fix gridView null error
document.addEventListener('DOMContentLoaded', function() {
  // Override switchView function to prevent null errors
  if (typeof window.switchView === 'function') {
    const originalSwitchView = window.switchView;
    window.switchView = function(view) {
      const gridView = document.getElementById('gridView');
      if (gridView) {
        originalSwitchView(view);
      }
    };
  } else {
    // Create switchView function if it doesn't exist
    window.switchView = function(view) {
      const gridView = document.getElementById('gridView');
      if (gridView) {
        gridView.style.display = view === 'grid' ? 'block' : 'none';
      }
    };
  }
});