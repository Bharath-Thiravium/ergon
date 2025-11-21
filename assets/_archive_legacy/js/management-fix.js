// Management Page Data Persistence Fix
(function() {
    if (!window.location.pathname.includes('/admin/management')) return;
    
    let originalData = null;
    
    function preserveData() {
        const listView = document.getElementById('listView');
        const gridView = document.getElementById('gridView');
        
        if (listView && gridView && !originalData) {
            originalData = {
                list: listView.innerHTML,
                grid: gridView.innerHTML
            };
        }
    }
    
    function restoreData() {
        if (!originalData) return;
        
        const listView = document.getElementById('listView');
        const gridView = document.getElementById('gridView');
        
        if (listView && (!listView.innerHTML.trim() || listView.innerHTML.includes('No Users Found'))) {
            listView.innerHTML = originalData.list;
        }
        
        if (gridView && (!gridView.innerHTML.trim() || gridView.innerHTML.includes('No Users Found'))) {
            gridView.innerHTML = originalData.grid;
        }
    }
    
    // Preserve data immediately
    document.addEventListener('DOMContentLoaded', preserveData);
    if (document.readyState !== 'loading') preserveData();
    
    // Restore data on any changes
    setInterval(restoreData, 1000);
    
    // Override toggleView to preserve data
    const originalToggleView = window.toggleView;
    if (originalToggleView) {
        window.toggleView = function() {
            preserveData();
            originalToggleView();
            setTimeout(restoreData, 100);
        };
    }
})();