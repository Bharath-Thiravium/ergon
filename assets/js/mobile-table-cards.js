/**
 * Mobile Table to Card Converter
 * Converts desktop tables to mobile-friendly card layouts
 */

(function() {
    'use strict';
    
    function convertTablesToCards() {
        if (window.innerWidth > 768) return;
        
        const tables = document.querySelectorAll('.table-responsive .table');
        
        tables.forEach(table => {
            const cardList = createCardList(table);
            if (cardList) {
                table.parentNode.appendChild(cardList);
            }
        });
    }
    
    function createCardList(table) {
        const tbody = table.querySelector('tbody');
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => {
            // Clean up header text by removing sorting controls
            const headerTextElement = th.querySelector('.table-header__text');
            if (headerTextElement) {
                return headerTextElement.textContent.trim();
            }
            // Fallback: remove common sorting symbols
            return th.textContent.replace(/[⇅🔍]/g, '').replace(/Apply|Clear/g, '').trim();
        });
        
        if (!tbody || headers.length === 0) return null;
        
        const cardList = document.createElement('div');
        cardList.className = 'mobile-card-list';
        
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            const card = createCard(row, headers);
            if (card) cardList.appendChild(card);
        });
        
        return cardList;
    }
    
    function createCard(row, headers) {
        const cells = row.querySelectorAll('td');
        if (cells.length === 0) return null;
        
        const card = document.createElement('div');
        card.className = 'mobile-card';
        
        // Main content area
        const main = document.createElement('div');
        main.className = 'mobile-card__main';
        
        const title = document.createElement('div');
        title.className = 'mobile-card__title';
        title.innerHTML = cells[0].innerHTML;
        main.appendChild(title);
        
        // Find and add status badge
        for (let i = cells.length - 2; i >= Math.max(0, cells.length - 3); i--) {
            const badge = cells[i].querySelector('.badge');
            if (badge) {
                const statusDiv = document.createElement('div');
                statusDiv.className = 'mobile-card__status';
                statusDiv.appendChild(badge.cloneNode(true));
                main.appendChild(statusDiv);
                break;
            }
        }
        
        card.appendChild(main);
        
        // Meta information (key details only)
        const meta = document.createElement('div');
        meta.className = 'mobile-card__meta';
        
        // For attendance tables, show time information
        if (headers.some(h => h.toLowerCase().includes('time') || h.toLowerCase().includes('in') || h.toLowerCase().includes('out'))) {
            // Show check-in and check-out times
            for (let i = 1; i < cells.length - 1; i++) {
                if (!cells[i].querySelector('.badge') && !cells[i].querySelector('.ab-container')) {
                    const metaItem = document.createElement('div');
                    metaItem.className = 'mobile-card__meta-item';
                    
                    const cleanText = cells[i].textContent.trim();
                    const headerText = headers[i] || '';
                    
                    if (cleanText && cleanText !== 'N/A' && cleanText !== '-') {
                        metaItem.innerHTML = `<strong>${headerText}:</strong> ${cleanText}`;
                        meta.appendChild(metaItem);
                    } else if (headerText.toLowerCase().includes('out')) {
                        metaItem.innerHTML = `<strong>${headerText}:</strong> <span style="color: #6b7280;">Not clocked out</span>`;
                        meta.appendChild(metaItem);
                    }
                }
            }
        } else {
            // Default behavior for other tables
            const essentialColumns = [1, 2];
            essentialColumns.forEach(i => {
                if (i < cells.length - 1 && !cells[i].querySelector('.badge')) {
                    const metaItem = document.createElement('div');
                    metaItem.className = 'mobile-card__meta-item';
                    
                    const cleanText = cells[i].textContent.trim();
                    if (cleanText && cleanText !== 'N/A' && cleanText !== '-') {
                        metaItem.textContent = cleanText;
                        meta.appendChild(metaItem);
                    }
                }
            });
        }
        
        if (meta.children.length > 0) {
            card.appendChild(meta);
        }
        
        // Actions
        const actionsCell = cells[cells.length - 1];
        const actionButtons = actionsCell.querySelector('.ab-container');
        if (actionButtons) {
            const actions = document.createElement('div');
            actions.className = 'mobile-card__actions';
            actions.appendChild(actionButtons.cloneNode(true));
            card.appendChild(actions);
        }
        
        return card;
    }
    
    // Initialize on DOM ready and resize
    document.addEventListener('DOMContentLoaded', convertTablesToCards);
    window.addEventListener('resize', function() {
        // Remove existing card lists
        document.querySelectorAll('.mobile-card-list').forEach(list => list.remove());
        // Recreate if mobile
        setTimeout(convertTablesToCards, 100);
    });
    
})();