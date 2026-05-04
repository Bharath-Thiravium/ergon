/**
 * Table Headers Fix - Universal Solution
 * Automatically fixes corrupted sort and filter icons across all table views
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        sortIcon: '^v',         // Simple up-down arrows
        filterIcon: '...',      // Three dots
        selectors: {
            corruptedSort: '.table-header__sort:contains("▲▼")',
            corruptedFilter: 'i.bi-funnel.table-header__filter',
            allSortIcons: '.table-header__sort',
            allFilterIcons: '.table-header__filter'
        }
    };

    /**
     * Fix corrupted table header icons
     */
    function fixTableHeaders() {
        // Fix sort icons
        document.querySelectorAll('.table-header__sort').forEach(sortEl => {
            if (sortEl.textContent.includes('▲▼') || sortEl.textContent.includes('▲') || sortEl.textContent.includes('▼')) {
                sortEl.textContent = CONFIG.sortIcon;
            }
        });

        // Fix filter icons - replace Bootstrap icons with Unicode
        document.querySelectorAll('i.bi-funnel.table-header__filter').forEach(filterEl => {
            const span = document.createElement('span');
            span.className = 'table-header__filter';
            span.textContent = CONFIG.filterIcon;
            
            // Copy any data attributes
            Array.from(filterEl.attributes).forEach(attr => {
                if (attr.name.startsWith('data-') || attr.name === 'title') {
                    span.setAttribute(attr.name, attr.value);
                }
            });
            
            filterEl.parentNode.replaceChild(span, filterEl);
        });

        console.log('Table headers fixed successfully');
    }

    /**
     * Update JavaScript event handlers to work with new structure
     */
    function updateEventHandlers() {
        // Remove old event listeners and add new ones
        document.addEventListener('click', function(e) {
            const cell = e.target.closest('.table-header__cell');
            if (!cell) return;
            
            const sortEl = cell.querySelector('.table-header__sort');
            const filterEl = cell.querySelector('.table-header__filter');
            
            // Sort click
            if (e.target.closest('.table-header__sort') && sortEl) {
                const sortField = cell.dataset.sort;
                const currentDir = sortEl.dataset.direction || 'asc';
                const newDir = currentDir === 'asc' ? 'desc' : 'asc';
                
                // Update visual state
                document.querySelectorAll('.table-header__cell').forEach(c => {
                    c.classList.remove('table-header__cell--sorted');
                    const s = c.querySelector('.table-header__sort');
                    if (s) s.dataset.direction = 'asc';
                });
                cell.classList.add('table-header__cell--sorted');
                sortEl.dataset.direction = newDir;
                
                // Trigger existing sort functions if available
                if (typeof sortClients === 'function') {
                    sortClients(sortField, newDir);
                } else if (typeof sortTable === 'function') {
                    sortTable(sortField, newDir);
                }
                
                console.log(`Sort ${sortField} ${newDir}`);
                return;
            }
            
            // Filter click
            if (e.target.closest('.table-header__filter') && filterEl) {
                const dropdown = cell.querySelector('.table-filter-dropdown');
                if (dropdown) {
                    dropdown.classList.toggle('table-filter-dropdown--show');
                    cell.classList.toggle('table-header__cell--filtered');
                }
                console.log('Filter clicked');
            }
        });
    }

    /**
     * Load CSS if not already loaded
     */
    function loadCSS() {
        if (!document.querySelector('link[href*="table-headers-fix.css"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '/ergon/assets/css/table-headers-fix.css';
            document.head.appendChild(link);
        }
    }

    /**
     * Initialize the fix
     */
    function init() {
        // Load CSS
        loadCSS();
        
        // Fix existing headers
        fixTableHeaders();
        
        // Update event handlers
        updateEventHandlers();
        
        // Watch for dynamically added tables
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && (
                                node.querySelector && (
                                    node.querySelector('.table-header__cell') ||
                                    node.classList.contains('table-header__cell')
                                )
                            )) {
                                setTimeout(fixTableHeaders, 100);
                            }
                        });
                    }
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose global function for manual fixing
    window.fixTableHeaders = fixTableHeaders;

})();