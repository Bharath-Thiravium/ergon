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
                    <div class="premium-icon-large">🔒</div>
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
