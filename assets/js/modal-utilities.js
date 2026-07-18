/**
 * Global Modal Utilities for ERGON
 * Provides standardized modal control functions across all pages
 * Supports both approval/rejection forms and generic modals
 */

(function() {
    'use strict';

    // Initialize on DOM ready or immediately if already loaded
    const initializeModalUtilities = function() {
        // Define global modal control functions
        window.showModal = showModal;
        window.hideModal = hideModal;
        window.toggleModal = toggleModal;
        window.showSuccess = showSuccess;
        window.showError = showError;
        window.showWarning = showWarning;
        window.showInfo = showInfo;
        
        // Legacy compatibility
        window.showSuccessMessage = showSuccess;
        window.showErrorMessage = showError;
    };

    /**
     * Show a modal by ID
     * @param {string} modalId - The ID of the modal element
     */
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.warn(`Modal with ID "${modalId}" not found`);
            return false;
        }
        modal.setAttribute('data-visible', 'true');
        modal.style.display = 'flex';
        return true;
    }

    /**
     * Hide a modal by ID
     * @param {string} modalId - The ID of the modal element
     */
    function hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.warn(`Modal with ID "${modalId}" not found`);
            return false;
        }
        modal.setAttribute('data-visible', 'false');
        modal.style.display = 'none';
        return true;
    }

    /**
     * Toggle modal visibility
     * @param {string} modalId - The ID of the modal element
     */
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.warn(`Modal with ID "${modalId}" not found`);
            return false;
        }
        const isVisible = modal.getAttribute('data-visible') === 'true';
        if (isVisible) {
            hideModal(modalId);
        } else {
            showModal(modalId);
        }
        return !isVisible;
    }

    /**
     * Show success message using universal modal
     * @param {string} message - The message to display
     * @param {string} title - Optional custom title
     */
    function showSuccess(message, title) {
        const modal = document.getElementById('universalModal');
        if (!modal) {
            // Fallback: use browser alert
            alert('✅ ' + message);
            return false;
        }
        showUniversalModal(message, 'success', title || 'Success!');
        return true;
    }

    /**
     * Show error message using universal modal
     * @param {string} message - The message to display
     * @param {string} title - Optional custom title
     */
    function showError(message, title) {
        const modal = document.getElementById('universalModal');
        if (!modal) {
            // Fallback: use browser alert
            alert('❌ ' + message);
            return false;
        }
        showUniversalModal(message, 'error', title || 'Error!');
        return true;
    }

    /**
     * Show warning message using universal modal
     * @param {string} message - The message to display
     * @param {string} title - Optional custom title
     */
    function showWarning(message, title) {
        const modal = document.getElementById('universalModal');
        if (!modal) {
            alert('⚠️ ' + message);
            return false;
        }
        showUniversalModal(message, 'warning', title || 'Warning!');
        return true;
    }

    /**
     * Show info message using universal modal
     * @param {string} message - The message to display
     * @param {string} title - Optional custom title
     */
    function showInfo(message, title) {
        const modal = document.getElementById('universalModal');
        if (!modal) {
            alert('ℹ️ ' + message);
            return false;
        }
        showUniversalModal(message, 'info', title || 'Information');
        return true;
    }

    /**
     * Show universal modal with styled message
     * @param {string} message - The message to display
     * @param {string} type - Type: success, error, warning, info
     * @param {string} title - The title to display
     */
    function showUniversalModal(message, type, title) {
        const modal = document.getElementById('universalModal');
        const icon = document.getElementById('universalIcon');
        const titleEl = document.getElementById('universalTitle');
        const messageEl = document.getElementById('universalMessage');
        
        if (!modal || !icon || !titleEl || !messageEl) {
            console.error('Universal modal elements not found in DOM');
            return false;
        }

        const config = {
            success: { icon: '✅', title: title || 'Success!' },
            error: { icon: '❌', title: title || 'Error!' },
            warning: { icon: '⚠️', title: title || 'Warning!' },
            info: { icon: 'ℹ️', title: title || 'Information' }
        };

        const typeConfig = config[type] || config.success;
        
        icon.textContent = typeConfig.icon;
        titleEl.textContent = typeConfig.title;
        messageEl.textContent = message;
        
        // Set modal class
        modal.className = `universal-modal ${type} show`;
        
        // Auto close after timeout
        const autoCloseTime = type === 'success' ? 4000 : 6000;
        setTimeout(() => {
            if (modal.classList.contains('show')) {
                closeUniversalModal();
            }
        }, autoCloseTime);
        
        return true;
    }

    /**
     * Close the universal modal
     */
    function closeUniversalModal() {
        const modal = document.getElementById('universalModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 200);
        }
    }

    // Make globally available
    window.showUniversalModal = showUniversalModal;
    window.closeUniversalModal = closeUniversalModal;

    /**
     * Close modal when clicking on closest backdrop or via button
     * @param {HTMLElement} element - The element that triggered close
     */
    window.hideClosestModal = function(element) {
        const modal = element.closest('[id$="Modal"]') || element.closest('[class*="modal"]');
        if (modal) {
            modal.style.display = 'none';
            modal.setAttribute('data-visible', 'false');
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeModalUtilities);
    } else {
        initializeModalUtilities();
    }

    // Handle escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeUniversalModal();
            // Close other modals
            document.querySelectorAll('[class*="modal-overlay"][data-visible="true"]').forEach(modal => {
                modal.setAttribute('data-visible', 'false');
                modal.style.display = 'none';
            });
        }
    });

    // Close modal when clicking backdrop
    document.addEventListener('click', function(e) {
        if (e.target.classList && e.target.classList.contains('modal-overlay')) {
            e.target.setAttribute('data-visible', 'false');
            e.target.style.display = 'none';
        }
        if (e.target.id === 'universalModal') {
            closeUniversalModal();
        }
    });

})();
