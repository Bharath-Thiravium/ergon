/**
 * User Management Data Persistence
 * Ensures user data remains visible across mobile/desktop view switches
 */

(function() {
    'use strict';
    
    let userData = null;
    let currentView = 'list';
    let isInitialized = false;
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.pathname.includes('/admin/management')) {
            initUserManagement();
        }
    });
    
    function initUserManagement() {
        if (isInitialized) return;
        isInitialized = true;
        
        // Store initial user data
        storeUserData();
        
        // Override the existing toggleView function
        if (window.toggleView) {
            const originalToggleView = window.toggleView;
            window.toggleView = function() {
                storeUserData(); // Store before switching
                originalToggleView();
                restoreUserData(); // Restore after switching
            };
        }
        
        // Handle viewport changes
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                storeUserData();
                handleViewportChange();
                restoreUserData();
            }, 100);
        });
        
        // Handle orientation changes
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                storeUserData();
                handleViewportChange();
                restoreUserData();
            }, 200);
        });
        
        // Prevent mobile table conversion from hiding user data
        if (window.convertTablesToCards) {
            const originalConvert = window.convertTablesToCards;
            window.convertTablesToCards = function() {
                storeUserData();
                originalConvert();
                restoreUserData();
            };
        }
    }
    
    function storeUserData() {
        try {
            const listView = document.getElementById('listView');
            const gridView = document.getElementById('gridView');
            
            if (listView && gridView) {
                userData = {
                    listHTML: listView.innerHTML,
                    gridHTML: gridView.innerHTML,
                    listVisible: !listView.classList.contains('view--hidden'),
                    gridVisible: !gridView.classList.contains('view--hidden'),
                    timestamp: Date.now()
                };
                
                // Also store in sessionStorage as backup
                sessionStorage.setItem('userManagementData', JSON.stringify(userData));
            }
        } catch (error) {
            console.warn('Failed to store user data:', error);
        }
    }
    
    function restoreUserData() {
        try {
            // Try to use in-memory data first, then sessionStorage
            let dataToRestore = userData;
            if (!dataToRestore) {
                const stored = sessionStorage.getItem('userManagementData');
                if (stored) {
                    dataToRestore = JSON.parse(stored);
                }
            }
            
            if (!dataToRestore) return;
            
            const listView = document.getElementById('listView');
            const gridView = document.getElementById('gridView');
            
            if (listView && gridView) {
                // Restore content if it's missing or empty
                if (!listView.innerHTML.trim() || listView.innerHTML.includes('No Users Found')) {
                    listView.innerHTML = dataToRestore.listHTML;
                }
                
                if (!gridView.innerHTML.trim() || gridView.innerHTML.includes('No Users Found')) {
                    gridView.innerHTML = dataToRestore.gridHTML;
                }
                
                // Ensure proper visibility
                ensureProperVisibility();
            }
        } catch (error) {
            console.warn('Failed to restore user data:', error);
        }
    }
    
    function handleViewportChange() {
        const isMobile = window.innerWidth <= 768;
        const listView = document.getElementById('listView');
        const gridView = document.getElementById('gridView');
        
        if (!listView || !gridView) return;
        
        // Ensure data is always visible regardless of viewport
        if (isMobile) {
            // On mobile, ensure both views have data
            ensureDataInBothViews();
        } else {
            // On desktop, ensure both views have data
            ensureDataInBothViews();
        }
        
        ensureProperVisibility();
    }
    
    function ensureDataInBothViews() {
        const listView = document.getElementById('listView');
        const gridView = document.getElementById('gridView');
        
        if (!listView || !gridView) return;
        
        // If one view is empty but the other has data, copy it
        const listHasData = listView.innerHTML.trim() && !listView.innerHTML.includes('No Users Found');
        const gridHasData = gridView.innerHTML.trim() && !gridView.innerHTML.includes('No Users Found');
        
        if (listHasData && !gridHasData && userData) {
            gridView.innerHTML = userData.gridHTML;
        } else if (gridHasData && !listHasData && userData) {
            listView.innerHTML = userData.listHTML;
        }
        
        // If both are empty, try to restore from sessionStorage
        if (!listHasData && !gridHasData) {
            const stored = sessionStorage.getItem('userManagementData');
            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    listView.innerHTML = data.listHTML;
                    gridView.innerHTML = data.gridHTML;
                } catch (error) {
                    console.warn('Failed to restore from sessionStorage:', error);
                }
            }
        }
    }
    
    function ensureProperVisibility() {
        const listView = document.getElementById('listView');
        const gridView = document.getElementById('gridView');
        
        if (!listView || !gridView) return;
        
        // Determine which view should be visible
        const listVisible = !listView.classList.contains('view--hidden');
        const gridVisible = !gridView.classList.contains('view--hidden');
        
        // If both are hidden or both are visible, fix it
        if ((!listVisible && !gridVisible) || (listVisible && gridVisible)) {
            // Default to list view
            listView.classList.remove('view--hidden');
            listView.classList.add('view--active');
            gridView.classList.remove('view--active');
            gridView.classList.add('view--hidden');
            
            // Update toggle button
            const toggleIcon = document.getElementById('viewToggle');
            const toggleText = document.getElementById('viewText');
            if (toggleIcon && toggleText) {
                toggleIcon.textContent = '🔲';
                toggleText.textContent = 'Grid View';
            }
            currentView = 'list';
        }
    }
    
    // Prevent data loss during AJAX operations
    function interceptAjaxOperations() {
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            storeUserData();
            return originalFetch.apply(this, args).then(response => {
                // Restore data after a short delay to allow DOM updates
                setTimeout(restoreUserData, 100);
                return response;
            });
        };
    }
    
    // Initialize AJAX interception
    interceptAjaxOperations();
    
    // Periodic data check to ensure persistence
    setInterval(function() {
        if (window.location.pathname.includes('/admin/management')) {
            const listView = document.getElementById('listView');
            const gridView = document.getElementById('gridView');
            
            if (listView && gridView) {
                const listHasData = listView.innerHTML.trim() && !listView.innerHTML.includes('No Users Found');
                const gridHasData = gridView.innerHTML.trim() && !gridView.innerHTML.includes('No Users Found');
                
                if (!listHasData && !gridHasData) {
                    console.log('Data loss detected, attempting restore...');
                    restoreUserData();
                }
            }
        }
    }, 2000);
    
    // Export functions for debugging
    window.userManagementPersistence = {
        storeUserData,
        restoreUserData,
        ensureDataInBothViews,
        ensureProperVisibility
    };
    
})();