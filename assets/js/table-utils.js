/**
 * Modular Table Filter and Sort Utilities
 */
if (typeof window.TableUtils === 'undefined') {
class TableUtils {
    constructor(tableSelector) {
        this.table = document.querySelector(tableSelector);
        this.headers = [];
        this.sortState = {};
        this.filterState = {};
        this.init();
    }

    init() {
        if (!this.table) return;
        this.enhanceHeaders();
        this.bindEvents();
    }

    enhanceHeaders() {
        const headerCells = this.table.querySelectorAll('thead th');
        headerCells.forEach((cell, index) => {
            const originalText = cell.textContent.trim();
            const columnKey = this.generateColumnKey(originalText, index);
            
            cell.innerHTML = `
                <div class="table-header__content">
                    <span class="table-header__text">${originalText}</span>
                    <div class="table-header__controls">
                        <span class="table-header__sort" data-column="${columnKey}" data-direction="none">⇅</span>
                        <span class="table-header__filter" data-column="${columnKey}">🔍</span>
                    </div>
                </div>
                <div class="table-filter-dropdown" data-column="${columnKey}">
                    <input type="text" class="filter-input" placeholder="Search ${originalText}...">
                    <div class="filter-options"></div>
                    <div class="filter-actions">
                        <button class="filter-btn filter-btn--primary" data-action="apply">Apply</button>
                        <button class="filter-btn" data-action="clear">Clear</button>
                    </div>
                </div>
            `;
            
            cell.classList.add('table-header__cell');
            this.headers.push({
                element: cell,
                columnKey,
                originalText,
                index
            });
        });
    }

    bindEvents() {
        this.table.addEventListener('click', (e) => {
            if (e.target.classList.contains('table-header__sort')) {
                this.handleSort(e.target);
            } else if (e.target.classList.contains('table-header__filter')) {
                this.handleFilterToggle(e.target);
            } else if (e.target.classList.contains('filter-btn')) {
                this.handleFilterAction(e.target);
            }
        });

        this.table.addEventListener('input', (e) => {
            if (e.target.classList.contains('filter-input')) {
                this.handleFilterInput(e.target);
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.table-filter-dropdown') && !e.target.classList.contains('table-header__filter')) {
                this.closeAllFilters();
            }
        });
    }

    generateColumnKey(text, index) {
        return text.toLowerCase().replace(/[^a-z0-9]/g, '_') + '_' + index;
    }

    handleSort(sortButton) {
        const column = sortButton.dataset.column;
        const currentDirection = sortButton.dataset.direction;
        
        // Reset all other sort indicators
        this.table.querySelectorAll('.table-header__sort').forEach(btn => {
            if (btn !== sortButton) {
                btn.dataset.direction = 'none';
                btn.textContent = '⇅';
                btn.classList.remove('table-header__sort--active');
                const otherColumn = btn.dataset.column;
                delete this.sortState[otherColumn];
                this.updateSortIndicator(otherColumn, false);
            }
        });

        // Toggle current sort direction
        let newDirection;
        if (currentDirection === 'none' || currentDirection === 'desc') {
            newDirection = 'asc';
            sortButton.textContent = '▲';
        } else {
            newDirection = 'desc';
            sortButton.textContent = '▼';
        }
        
        sortButton.dataset.direction = newDirection;
        sortButton.classList.add('table-header__sort--active');
        
        this.sortTable(column, newDirection);
        this.sortState[column] = newDirection;
        this.updateSortIndicator(column, true);
    }

    sortTable(column, direction) {
        const tbody = this.table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const columnIndex = this.getColumnIndex(column);
        
        rows.sort((a, b) => {
            const aValue = this.getCellValue(a, columnIndex);
            const bValue = this.getCellValue(b, columnIndex);
            
            if (this.isNumeric(aValue) && this.isNumeric(bValue)) {
                return direction === 'asc' ? 
                    parseFloat(aValue) - parseFloat(bValue) : 
                    parseFloat(bValue) - parseFloat(aValue);
            }
            
            if (this.isDate(aValue) && this.isDate(bValue)) {
                const dateA = new Date(aValue);
                const dateB = new Date(bValue);
                return direction === 'asc' ? dateA - dateB : dateB - dateA;
            }
            
            return direction === 'asc' ? 
                aValue.localeCompare(bValue) : 
                bValue.localeCompare(aValue);
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }

    getCellValue(row, columnIndex) {
        const cell = row.cells[columnIndex];
        if (!cell) return '';
        
        // Try to get data attribute first
        const dataValue = cell.dataset.sortValue;
        if (dataValue) return dataValue;
        
        // Get text content, handling nested elements
        const primaryText = cell.querySelector('.cell-primary');
        if (primaryText) return primaryText.textContent.trim();
        
        return cell.textContent.trim();
    }

    isNumeric(value) {
        return !isNaN(parseFloat(value)) && isFinite(value);
    }

    isDate(value) {
        return !isNaN(Date.parse(value));
    }

    getColumnIndex(columnKey) {
        const header = this.headers.find(h => h.columnKey === columnKey);
        return header ? header.index : 0;
    }

    handleFilterToggle(filterButton) {
        const column = filterButton.dataset.column;
        const dropdown = this.table.querySelector(`.table-filter-dropdown[data-column="${column}"]`);
        
        this.closeAllFilters();
        
        if (dropdown) {
            dropdown.classList.add('table-filter-dropdown--show');
            this.populateFilterOptions(column);
        }
    }

    populateFilterOptions(column) {
        const dropdown = this.table.querySelector(`.table-filter-dropdown[data-column="${column}"]`);
        const optionsContainer = dropdown.querySelector('.filter-options');
        const columnIndex = this.getColumnIndex(column);
        
        const tbody = this.table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        const uniqueValues = new Set();
        
        rows.forEach(row => {
            const value = this.getCellValue(row, columnIndex);
            if (value && value.trim()) {
                uniqueValues.add(value.trim());
            }
        });
        
        const sortedValues = Array.from(uniqueValues).sort();
        
        optionsContainer.innerHTML = sortedValues.map(value => `
            <div class="filter-option">
                <input type="checkbox" value="${value}" ${this.isValueSelected(column, value) ? 'checked' : ''}>
                <span>${value}</span>
            </div>
        `).join('');
    }

    isValueSelected(column, value) {
        return this.filterState[column] && this.filterState[column].includes(value);
    }

    handleFilterInput(input) {
        const dropdown = input.closest('.table-filter-dropdown');
        const column = dropdown.dataset.column;
        const searchTerm = input.value.toLowerCase();
        
        const options = dropdown.querySelectorAll('.filter-option');
        options.forEach(option => {
            const text = option.textContent.toLowerCase();
            option.style.display = text.includes(searchTerm) ? 'flex' : 'none';
        });
    }

    handleFilterAction(button) {
        const action = button.dataset.action;
        const dropdown = button.closest('.table-filter-dropdown');
        const column = dropdown.dataset.column;
        
        if (action === 'apply') {
            this.applyFilter(column, dropdown);
        } else if (action === 'clear') {
            this.clearFilter(column, dropdown);
        }
        
        this.closeAllFilters();
    }

    applyFilter(column, dropdown) {
        const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
        const selectedValues = Array.from(checkboxes).map(cb => cb.value);
        
        this.filterState[column] = selectedValues;
        this.filterTable();
        this.updateFilterIndicator(column, selectedValues.length > 0);
    }

    clearFilter(column, dropdown) {
        delete this.filterState[column];
        dropdown.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        dropdown.querySelector('.filter-input').value = '';
        this.filterTable();
        this.updateFilterIndicator(column, false);
    }

    filterTable() {
        const tbody = this.table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        
        rows.forEach(row => {
            let showRow = true;
            
            Object.keys(this.filterState).forEach(column => {
                const columnIndex = this.getColumnIndex(column);
                const cellValue = this.getCellValue(row, columnIndex);
                const selectedValues = this.filterState[column];
                
                if (selectedValues && selectedValues.length > 0) {
                    if (!selectedValues.includes(cellValue)) {
                        showRow = false;
                    }
                }
            });
            
            row.style.display = showRow ? '' : 'none';
        });
    }

    updateFilterIndicator(column, isActive) {
        const filterButton = this.table.querySelector(`.table-header__filter[data-column="${column}"]`);
        const headerCell = filterButton.closest('.table-header__cell');
        
        if (isActive) {
            headerCell.classList.add('table-header__cell--filtered', 'table-header__cell--active');
            filterButton.classList.add('table-header__filter--active');
        } else {
            headerCell.classList.remove('table-header__cell--filtered');
            filterButton.classList.remove('table-header__filter--active');
            this.updateActiveState();
        }
    }

    updateSortIndicator(column, isActive) {
        const sortButton = this.table.querySelector(`.table-header__sort[data-column="${column}"]`);
        const headerCell = sortButton.closest('.table-header__cell');
        
        if (isActive) {
            headerCell.classList.add('table-header__cell--sorted', 'table-header__cell--active');
            sortButton.classList.add('table-header__sort--active');
        } else {
            headerCell.classList.remove('table-header__cell--sorted');
            sortButton.classList.remove('table-header__sort--active');
            this.updateActiveState();
        }
    }

    updateActiveState() {
        const hasActiveFilters = Object.keys(this.filterState).length > 0;
        const hasActiveSorts = Object.keys(this.sortState).length > 0;
        
        this.table.querySelectorAll('.table-header__cell').forEach(cell => {
            const hasFilter = cell.classList.contains('table-header__cell--filtered');
            const hasSort = cell.classList.contains('table-header__cell--sorted');
            
            if (!hasFilter && !hasSort) {
                cell.classList.remove('table-header__cell--active');
            }
        });
    }

    closeAllFilters() {
        this.table.querySelectorAll('.table-filter-dropdown').forEach(dropdown => {
            dropdown.classList.remove('table-filter-dropdown--show');
        });
    }

    // Public methods for external use
    clearAllFilters() {
        this.filterState = {};
        this.table.querySelectorAll('.table-header__cell--filtered').forEach(cell => {
            cell.classList.remove('table-header__cell--filtered');
        });
        this.table.querySelectorAll('.table-header__filter--active').forEach(filter => {
            filter.classList.remove('table-header__filter--active');
        });
        this.filterTable();
    }

    clearAllSorts() {
        this.sortState = {};
        this.table.querySelectorAll('.table-header__sort').forEach(sort => {
            sort.dataset.direction = 'none';
            sort.textContent = '⇅';
            sort.classList.remove('table-header__sort--active');
        });
    }
}

// Auto-initialize for tables with .table class or inside .table-responsive
document.addEventListener('DOMContentLoaded', function() {
    // Skip initialization on mobile devices
    if (window.innerWidth <= 768) {
        return;
    }
    
    document.querySelectorAll('.table, .table-responsive table').forEach((table, index) => {
        if (!table.dataset.tableUtils) {
            const selector = table.classList.contains('table') ? 
                `table.table:nth-of-type(${Array.from(document.querySelectorAll('table.table')).indexOf(table) + 1})` :
                `.table-responsive:nth-of-type(${Array.from(document.querySelectorAll('.table-responsive')).indexOf(table.closest('.table-responsive')) + 1}) table`;
            new TableUtils(selector);
            table.dataset.tableUtils = 'initialized';
        }
    });
});

// Export for manual initialization
window.TableUtils = TableUtils;
}

// Global delete function for records
function deleteRecord(type, id, name) {
    if (!confirm(`Are you sure you want to delete this ${type.slice(0, -1)}?\n\n"${name}"\n\nThis action cannot be undone.`)) {
        return;
    }
    
    fetch(`/ergon/${type}/delete/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the row from table
            const row = document.querySelector(`button[onclick*="${id}"]`)?.closest('tr');
            if (row) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
            
            // Show success message
            showNotification('success', data.message || `${type.slice(0, -1)} deleted successfully`);
        } else {
            showNotification('error', data.message || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showNotification('error', 'Network error occurred');
    });
}

// Simple notification function
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `alert alert--${type === 'success' ? 'success' : 'error'}`;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
    notification.innerHTML = `${type === 'success' ? '✅' : '❌'} ${message}`;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transition = 'opacity 0.3s ease';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}