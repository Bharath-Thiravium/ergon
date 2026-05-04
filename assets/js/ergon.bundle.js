class ThemeSwitcher {
    constructor() {
        this.currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        this.init();
    }
    
    init() {
        this.updateToggleButton(this.currentTheme);
        this.bindEvents();
    }
    
    applyTheme(theme) {
        this.currentTheme = theme;
        
        // Apply theme to root elements
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.className = 'theme-' + theme;
        document.body.setAttribute('data-theme', theme);
        
        // Force repaint to ensure styles are applied
        document.body.offsetHeight;
        
        // Update toggle button icon
        this.updateToggleButton(theme);
        
        // Save preference
        localStorage.setItem('ergon_theme', theme);
        
        // Trigger custom event for other components
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }
    
    updateToggleButton(theme) {
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
        }
    }
    
    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(newTheme);
    }
    
    bindEvents() {
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleTheme();
            });
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.themeSwitcher = new ThemeSwitcher();
    });
} else {
    window.themeSwitcher = new ThemeSwitcher();
}

// ergon Core JavaScript - Minified
const ergon={init:function(){this.initMobileMenu(),this.initProfileDropdown(),this.initNotifications()},initMobileMenu:function(){const e=document.querySelector(".mobile-menu-toggle"),t=document.querySelector(".sidebar");e&&e.addEventListener("click",function(){t.classList.toggle("sidebar--open")})},initProfileDropdown:function(){const e=document.querySelector(".sidebar__profile-btn"),t=document.querySelector(".profile-menu");e&&e.addEventListener("click",function(){t.style.display="block"===t.style.display?"none":"block"}),document.addEventListener("click",function(e){e.target.closest(".sidebar__profile-dropdown")||(t.style.display="none")})},initNotifications:function(){const e=document.querySelector(".sidebar__control-btn");e&&e.addEventListener("click",function(){console.log("Notifications clicked")})},showAlert:function(e,t="info"){const n=document.createElement("div");n.className=`alert alert--${t}`,n.innerHTML=`<i class="fas fa-info-circle"></i> ${e}`,document.body.appendChild(n),setTimeout(()=>{n.remove()},5e3)}};document.addEventListener("DOMContentLoaded",function(){ergon.init()});

/* ========================================
   MODAL SYSTEM â€” SINGLE SOURCE OF TRUTH
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

/**
 * Mobile Table to Card Converter
 * Automatically converts tables to mobile-friendly cards on small screens
 */

// Pages where this script should not run at all
const DISABLED_PATHS = [
  '/admin/management',
  '/ledgers/',
  '/users/view',
  '/client-ledger',
];

function isDisabledPage() {
  const path = window.location.pathname;
  return DISABLED_PATHS.some(p => path.includes(p));
}

function convertTablesToCards() {
  if (isDisabledPage()) return;
  if (window.innerWidth > 768) return;

  document.querySelectorAll('.table-responsive').forEach(container => {
    if (container.dataset.mobileConverted === '1') return;

    const tables = container.querySelectorAll('table');
    if (!tables.length) return;

    const cardContainer = document.createElement('div');
    cardContainer.className = 'mobile-card-container';

    tables.forEach(table => {
      const headers = Array.from(table.querySelectorAll('thead th'))
        .map(th => th.textContent.trim().replace(/[^v...v]/g, '').trim());

      const lastHeader = headers[headers.length - 1] || '';
      const hasActionsCol = /action|edit|manage/i.test(lastHeader)
        || table.querySelector('tbody td:last-child .ab-container') !== null;

      table.querySelectorAll('tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;
        cardContainer.appendChild(createCard(headers, cells, hasActionsCol));
      });

      table.style.display = 'none';
    });

    container.appendChild(cardContainer);
    container.dataset.mobileConverted = '1';
  });
}

function createCard(headers, cells, hasActionsCol) {
  const card = document.createElement('div');
  card.className = 'task-card';

  // Title = first cell text
  const title = cells[0]?.textContent.trim() || 'Item';

  // Fields: skip first (title) and last if it's actions col
  const fieldEnd = hasActionsCol ? cells.length - 1 : cells.length;

  let fieldsHTML = '';
  for (let i = 1; i < fieldEnd; i++) {
    if (!cells[i] || !headers[i]) continue;
    fieldsHTML += `
      <div class="task-card__field">
        <div class="task-card__label">${headers[i]}</div>
        <div class="task-card__value">${cells[i].innerHTML}</div>
      </div>`;
  }

  // Actions: only if last col is actions col
  let actionsHTML = '';
  if (hasActionsCol) {
    const lastCell = cells[cells.length - 1];
    const abContainer = lastCell?.querySelector('.ab-container');
    if (abContainer) {
      actionsHTML = `<div class="task-card__actions">${abContainer.innerHTML}</div>`;
    } else {
      // loose buttons fallback
      const btns = Array.from(lastCell?.querySelectorAll('a.btn, button.btn') || []);
      if (btns.length) {
        actionsHTML = `<div class="task-card__actions">${btns.map(b => b.outerHTML).join('')}</div>`;
      }
    }
  }

  card.innerHTML = `
    <div class="task-card__header">
      <h3 class="task-card__title">${title}</h3>
    </div>
    <div class="task-card__meta">${fieldsHTML}</div>
    ${actionsHTML}`;

  return card;
}

// Init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', convertTablesToCards);
} else {
  convertTablesToCards();
}

window.addEventListener('resize', () => {
  if (isDisabledPage()) return;
  if (window.innerWidth > 768) {
    document.querySelectorAll('.table-responsive').forEach(container => {
      container.querySelector('.mobile-card-container')?.remove();
      const table = container.querySelector('table');
      if (table) table.style.display = '';
      delete container.dataset.mobileConverted;
    });
  } else {
    convertTablesToCards();
  }
});

window.convertTablesToCards = convertTablesToCards;

/**
 * Modular Table Filter and Sort Utilities
 */
if (typeof window.TableUtils === 'undefined') {

function initTableSortFilter(table) {
    if (table.dataset.sfInit) return;
    table.dataset.sfInit = '1';

    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');
    if (!thead || !tbody) return;

    const headerRow = thead.querySelector('tr');
    if (!headerRow) return;

    // Skip if already has th-sort (manually implemented)
    if (headerRow.querySelector('.th-sort')) return;

    const ths = Array.from(headerRow.querySelectorAll('th'));
    let sortCol = -1, sortAsc = true;

    // Add sort icons to each th
    ths.forEach((th, i) => {
        const text = th.textContent.trim();
        const isNum = /amount|balance|credit|debit|total|count|qty|price|rate/i.test(text);
        th.dataset.col = i;
        th.dataset.type = isNum ? 'num' : 'str';
        th.classList.add('th-sort');
        th.innerHTML = text + ' <span class="sort-icon">&#8597;</span>';
        th.style.cursor = 'pointer';
        th.style.userSelect = 'none';
        th.style.whiteSpace = 'nowrap';

        th.addEventListener('click', function() {
            sortAsc = (sortCol === i) ? !sortAsc : true;
            sortCol = i;
            ths.forEach(t => { const s = t.querySelector('.sort-icon'); if (s) s.textContent = '⇅'; });
            const icon = th.querySelector('.sort-icon');
            if (icon) icon.textContent = sortAsc ? '↑' : '↓';
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const av = a.cells[i]?.textContent.trim() || '';
                const bv = b.cells[i]?.textContent.trim() || '';
                const cmp = isNum
                    ? (parseFloat(av.replace(/[^0-9.-]/g,'')) || 0) - (parseFloat(bv.replace(/[^0-9.-]/g,'')) || 0)
                    : av.localeCompare(bv);
                return sortAsc ? cmp : -cmp;
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });

    // Add filter row
    const filterRow = document.createElement('tr');
    filterRow.id = 'filterRow_' + Math.random().toString(36).slice(2,7);
    ths.forEach((th, i) => {
        const td = document.createElement('td');
        td.style.cssText = 'padding:3px 6px;background:#f8fafc;';
        const text = th.querySelector('.sort-icon') ? th.textContent.replace(/[↑↓⇅]/g,'').trim() : th.textContent.trim();
        const isAction = /action|edit|manage|delete/i.test(text);
        if (!isAction) {
            const input = document.createElement('input');
            input.type = 'text';
            input.placeholder = text + '...';
            input.dataset.col = i;
            input.className = 'col-filter';
            input.style.cssText = 'width:100%;padding:3px 5px;font-size:11px;border:1px solid #d1d5db;border-radius:3px;box-sizing:border-box;';
            input.addEventListener('input', applyFilters);
            input.addEventListener('click', e => e.stopPropagation());
            td.appendChild(input);
        }
        filterRow.appendChild(td);
    });
    thead.appendChild(filterRow);

    function applyFilters() {
        const filters = Array.from(filterRow.querySelectorAll('.col-filter')).map(f => ({ col: +f.dataset.col, val: f.value.toLowerCase() }));
        Array.from(tbody.querySelectorAll('tr')).forEach(row => {
            row.style.display = filters.every(f => !f.val || row.cells[f.col]?.textContent.toLowerCase().includes(f.val)) ? '' : 'none';
        });
    }
}

// Auto-initialize for all tables
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth <= 768) return;
    document.querySelectorAll('.table, .table-responsive table').forEach(table => {
        if (!table.querySelector('th.th-sort')) {
            initTableSortFilter(table);
        }
    });
});

window.TableUtils = { init: initTableSortFilter };
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
    notification.innerHTML = `${type === 'success' ? 'âœ…' : 'âŒ'} ${message}`;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transition = 'opacity 0.3s ease';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// User status check for automatic logout
(function() {
    var started = false;

    function checkUserStatus() {
        fetch('/ergon/api/check-auth.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!data.active && data.authenticated === false) {
                if (data.role_changed) {
                    alert('Your role has been changed. You will be logged out to apply new permissions.');
                } else if (data.message === 'User deactivated') {
                    alert('Your account has been deactivated. You will be logged out.');
                } else {
                    // Not authenticated (session expired) â€” redirect silently
                    window.location.href = '/ergon/logout';
                    return;
                }
                window.location.href = '/ergon/logout';
            }
        })
        .catch(function() {
            // Silent fail - network issues should never force logout
        });
    }

    // Wait 10 seconds after page load before first check
    // This prevents false logout immediately after login redirect
    setTimeout(function() {
        started = true;
        checkUserStatus();
        // Then poll every 60 seconds
        setInterval(checkUserStatus, 60000);
    }, 10000);

    // Only check on focus if the page has been open for at least 10 seconds
    window.addEventListener('focus', function() {
        if (started) {
            checkUserStatus();
        }
    });
})();

/**
 * Premium Navigation Handler
 * Handles clicks on disabled premium modules
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create tooltip element
    const tooltip = document.createElement('div');
    tooltip.className = 'premium-tooltip';
    tooltip.textContent = 'Premium feature - Contact admin to enable';
    document.body.appendChild(tooltip);
    
    // Handle clicks on disabled navigation items
    document.addEventListener('click', function(e) {
        const disabledItem = e.target.closest('.nav-dropdown-item--disabled, .sidebar__link--disabled');
        
        if (disabledItem) {
            e.preventDefault();
            e.stopPropagation();
            
            // Get module name from href
            const href = disabledItem.getAttribute('href');
            const moduleName = getModuleNameFromHref(href);
            
            showPremiumUpgradeModal(moduleName);
            return false;
        }
    });
    
    // Handle hover for disabled items
    document.addEventListener('mouseenter', function(e) {
        if (e.target && e.target.closest) {
            const disabledItem = e.target.closest('.nav-dropdown-item--disabled, .sidebar__link--disabled');
            if (disabledItem) {
                const rect = disabledItem.getBoundingClientRect();
                tooltip.style.top = (rect.bottom -10) + 'px';
                tooltip.style.left = (rect.left +180) + 'px';
                tooltip.style.right = 'auto';
                tooltip.style.display = 'block';
            }
        }
    }, true);
    
    document.addEventListener('mouseleave', function(e) {
        if (e.target && e.target.closest) {
            const disabledItem = e.target.closest('.nav-dropdown-item--disabled, .sidebar__link--disabled');
            if (disabledItem) {
                tooltip.style.display = 'none';
            }
        }
    }, true);
    
    // Add hover effects for premium icons
    document.querySelectorAll('.premium-icon').forEach(function(icon) {
        icon.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.2)';
        });
        
        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});

function getModuleNameFromHref(href) {
    if (!href) return 'Premium Feature';
    
    const moduleMap = {
        '/tasks': 'Task Management',
        '/contacts/followups': 'Follow-ups',
        '/system-admin': 'System Administration',
        '/admin/management': 'User Management',
        '/departments': 'Department Management',
        '/project-management': 'Project Management',
        '/finance': 'Finance Module',
        '/reports': 'Reports & Analytics',
        '/workflow/daily-planner': 'Daily Planner'
    };
    
    for (const [path, name] of Object.entries(moduleMap)) {
        if (href.includes(path)) {
            return name;
        }
    }
    
    return 'Premium Feature';
}

function showPremiumUpgradeModal(moduleName) {
    // Check if modal already exists
    let modal = document.getElementById('premiumUpgradeModal');
    
    if (!modal) {
        // Create modal
        modal = document.createElement('div');
        modal.id = 'premiumUpgradeModal';
        modal.className = 'premium-modal-overlay';
        modal.innerHTML = `
            <div class="premium-modal">
                <div class="premium-modal-header">
                    <div class="premium-icon-large">...</div>
                    <h2>Premium Feature Required</h2>
                </div>
                <div class="premium-modal-body">
                    <p><strong id="premiumModuleName">${moduleName}</strong> is a premium feature that requires activation.</p>
                    <p>Contact your administrator to enable this module in your subscription.</p>
                </div>
                <div class="premium-modal-actions">
                    <button class="btn btn-secondary" onclick="closePremiumModal()">
                        <i class="bi bi-x-circle"></i>
                        Close
                    </button>
                    ${isOwner() ? `
                    <a href="/ergon/modules" class="btn btn-primary">
                        <i class="bi bi-gear-fill"></i>
                        Manage Modules
                    </a>
                    ` : ''}
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    } else {
        // Update existing modal
        document.getElementById('premiumModuleName').textContent = moduleName;
    }
    
    // Show modal
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
    
    // Auto close after 5 seconds
    setTimeout(() => {
        if (modal && modal.style.display === 'flex') {
            closePremiumModal();
        }
    }, 5000);
}

function closePremiumModal() {
    const modal = document.getElementById('premiumUpgradeModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
}

function isOwner() {
    // Check if user is owner from body data attribute
    const userRole = document.body.getAttribute('data-user-role');
    return userRole === 'owner';
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('premiumUpgradeModal');
    if (modal && e.target === modal) {
        closePremiumModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePremiumModal();
    }
});

// Make functions globally available
window.showPremiumUpgradeModal = showPremiumUpgradeModal;
window.closePremiumModal = closePremiumModal;

/* ðŸŒ™ Dark Mode Alert & Notification JavaScript Enhancements */

(function() {
    'use strict';

    // Enhanced showMessage function with Dark Mode support
    function enhancedShowMessage(message, type = 'success', title = null) {
        const modal = document.getElementById('universalModal');
        const icon = document.getElementById('universalIcon');
        const titleEl = document.getElementById('universalTitle');
        const messageEl = document.getElementById('universalMessage');
        
        if (!modal || !icon || !titleEl || !messageEl) {
            // Fallback to browser alert if modal elements not found
            alert(message);
            return;
        }
        
        const config = {
            success: { icon: 'âœ…', title: title || 'Success!' },
            error: { icon: 'âŒ', title: title || 'Error!' },
            warning: { icon: 'âš ï¸', title: title || 'Warning!' },
            info: { icon: 'â„¹ï¸', title: title || 'Information' }
        };
        
        const typeConfig = config[type] || config.success;
        icon.textContent = typeConfig.icon;
        titleEl.textContent = typeConfig.title;
        messageEl.textContent = message;
        
        // Apply Dark Mode class if needed
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark' || 
                          document.body.classList.contains('theme-dark');
        
        modal.className = `universal-modal ${type} show`;
        if (isDarkMode) {
            modal.classList.add('theme-dark');
        }
        
        modal.style.display = 'flex';
        
        // Auto close after appropriate time
        const autoCloseTime = type === 'success' ? 4000 : 6000;
        setTimeout(() => {
            if (modal.classList.contains('show')) {
                closeUniversalModal();
            }
        }, autoCloseTime);
    }

    // Enhanced toast notification function
    function showToast(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        
        // Apply Dark Mode styling if needed
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark' || 
                          document.body.classList.contains('theme-dark');
        
        if (isDarkMode) {
            toast.classList.add('theme-dark');
        }
        
        // Add icon based on type
        const icons = {
            success: 'âœ…',
            error: 'âŒ',
            warning: 'âš ï¸',
            info: 'â„¹ï¸'
        };
        
        toast.innerHTML = `
            <span style="margin-right: 8px;">${icons[type] || icons.success}</span>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Hide and remove toast
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, duration);
    }

    // Enhanced alert creation function
    function createAlert(message, type = 'info', container = null) {
        const alert = document.createElement('div');
        alert.className = `alert alert--${type}`;
        
        // Apply Dark Mode styling if needed
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark' || 
                          document.body.classList.contains('theme-dark');
        
        if (isDarkMode) {
            alert.classList.add('theme-dark');
        }
        
        // Add icon and message
        const icons = {
            success: 'âœ…',
            error: 'âŒ',
            warning: 'âš ï¸',
            info: 'â„¹ï¸'
        };
        
        alert.innerHTML = `
            <span style="margin-right: 8px;">${icons[type] || icons.info}</span>
            <span>${message}</span>
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: inherit; opacity: 0.7;">Ã—</button>
        `;
        
        // Insert alert
        if (container) {
            container.insertBefore(alert, container.firstChild);
        } else {
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.insertBefore(alert, mainContent.firstChild);
            } else {
                document.body.insertBefore(alert, document.body.firstChild);
            }
        }
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (document.body.contains(alert)) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
        
        return alert;
    }

    // Enhanced notification system
    function enhanceNotifications() {
        // Override native alert function
        const originalAlert = window.alert;
        window.alert = function(message) {
            if (typeof message === 'string' && message.startsWith('âœ…')) {
                enhancedShowMessage(message.replace('âœ… ', ''), 'success');
            } else if (typeof message === 'string' && message.startsWith('âŒ')) {
                enhancedShowMessage(message.replace('âŒ ', ''), 'error');
            } else if (typeof message === 'string' && message.startsWith('âš ï¸')) {
                enhancedShowMessage(message.replace('âš ï¸ ', ''), 'warning');
            } else {
                enhancedShowMessage(message, 'info');
            }
        };

        // Enhance existing notification functions
        if (window.showMessage) {
            window.showMessage = enhancedShowMessage;
        }
        
        // Add new utility functions
        window.showToast = showToast;
        window.createAlert = createAlert;
        window.showSuccess = (message, title) => enhancedShowMessage(message, 'success', title);
        window.showError = (message, title) => enhancedShowMessage(message, 'error', title);
        window.showWarning = (message, title) => enhancedShowMessage(message, 'warning', title);
        window.showInfo = (message, title) => enhancedShowMessage(message, 'info', title);
    }

    // Theme change observer
    function observeThemeChanges() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                    updateExistingAlerts();
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme']
        });
    }

    // Update existing alerts when theme changes
    function updateExistingAlerts() {
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
        
        // Update alerts
        document.querySelectorAll('.alert, .toast, .notification, .universal-modal').forEach(element => {
            if (isDarkMode) {
                element.classList.add('theme-dark');
            } else {
                element.classList.remove('theme-dark');
            }
        });
    }

    // Initialize when DOM is ready
    function initialize() {
        enhanceNotifications();
        observeThemeChanges();
        
        // Update existing alerts on load
        updateExistingAlerts();
        
        // Ensure all dynamically created alerts are visible
        const style = document.createElement('style');
        style.textContent = `
            .alert:not(.d-none):not([style*="display: none"]) {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        `;
        document.head.appendChild(style);
    }

    // Initialize immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

})();


