/**
 * Navigation Clickable Fix - Critical JavaScript
 * Ensures all navigation elements are properly clickable
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        initNavigationFixes();
    });
    
    function initNavigationFixes() {
        // Remove conflicting hover event handlers
        const navDropdowns = document.querySelectorAll('.nav-dropdown');
        navDropdowns.forEach(dropdown => {
            dropdown.removeAttribute('onmouseenter');
            dropdown.removeAttribute('onmouseleave');
            dropdown.onmouseenter = null;
            dropdown.onmouseleave = null;
        });
        
        // Fix dropdown button clicks
        const dropdownButtons = document.querySelectorAll('.nav-dropdown-btn');
        dropdownButtons.forEach(button => {
            // Remove any existing event listeners
            button.removeEventListener('click', handleDropdownClick);
            
            // Add proper click handler
            button.addEventListener('click', handleDropdownClick, { passive: false });
            
            // Ensure button is focusable and clickable
            button.setAttribute('tabindex', '0');
            button.style.pointerEvents = 'auto';
            button.style.cursor = 'pointer';
        });
        
        // Fix dropdown item clicks
        const dropdownItems = document.querySelectorAll('.nav-dropdown-item');
        dropdownItems.forEach(item => {
            item.style.pointerEvents = 'auto';
            item.style.cursor = 'pointer';
            
            // Prevent menu from closing on hover
            item.addEventListener('mouseenter', function(e) {
                e.stopPropagation();
            });
            
            item.addEventListener('mouseleave', function(e) {
                e.stopPropagation();
            });
            
            // Ensure links work properly
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close all dropdowns
                closeAllDropdowns();
                
                // Navigate to the link
                if (this.href && this.href !== '#') {
                    window.location.href = this.href;
                }
            });
        });
        
        // Fix profile button
        const profileButton = document.getElementById('profileButton');
        if (profileButton) {
            profileButton.style.pointerEvents = 'auto';
            profileButton.style.cursor = 'pointer';
            
            profileButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleProfile();
            });
        }
        
        // Fix control buttons
        const controlButtons = document.querySelectorAll('.control-btn');
        controlButtons.forEach(button => {
            button.style.pointerEvents = 'auto';
            button.style.cursor = 'pointer';
        });
        
        // Fix specific dropdown IDs that might not work
        const specificDropdowns = ['overview', 'management', 'operations', 'hrfinance', 'analytics', 'team', 'tasks', 'approvals', 'work', 'personal'];
        specificDropdowns.forEach(id => {
            const dropdown = document.getElementById(id);
            if (dropdown) {
                dropdown.style.pointerEvents = 'auto';
                dropdown.style.display = 'none';
            }
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-dropdown') && !e.target.closest('.header__controls')) {
                closeAllDropdowns();
            }
        });
        
        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllDropdowns();
            }
        });
    }
    
    function handleDropdownClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = e.currentTarget;
        const dropdown = button.parentElement;
        const menu = dropdown.querySelector('.nav-dropdown-menu');
        
        if (!menu) return;
        
        const isOpen = menu.classList.contains('show');
        
        // Close all other dropdowns first
        closeAllDropdowns();
        
        if (!isOpen) {
            // Open this dropdown
            showDropdown(menu.id);
        }
    }
    
    function showDropdown(id) {
        const dropdown = document.getElementById(id);
        if (!dropdown) {
            console.warn('Dropdown not found:', id);
            return;
        }
        
        const button = dropdown.previousElementSibling;
        if (!button) {
            console.warn('Button not found for dropdown:', id);
            return;
        }
        
        // Close other dropdowns first
        closeAllDropdowns();
        
        // Position the dropdown
        const rect = button.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.top = (rect.bottom + 8) + 'px';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.zIndex = '99999';
        dropdown.style.pointerEvents = 'auto';
        dropdown.style.display = 'block';
        
        // Show the dropdown
        dropdown.classList.add('show');
        button.classList.add('active');
        
        // Prevent menu from closing when hovering over it
        dropdown.addEventListener('mouseenter', function(e) {
            e.stopPropagation();
        });
        
        dropdown.addEventListener('mouseleave', function(e) {
            e.stopPropagation();
        });
    }
    
    function closeAllDropdowns() {
        const dropdowns = document.querySelectorAll('.nav-dropdown-menu');
        const buttons = document.querySelectorAll('.nav-dropdown-btn');
        const profileMenu = document.getElementById('profileMenu');
        
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
        });
        
        buttons.forEach(button => {
            button.classList.remove('active');
        });
        
        if (profileMenu) {
            profileMenu.classList.remove('show');
        }
    }
    
    function toggleProfile() {
        const menu = document.getElementById('profileMenu');
        if (!menu) return;
        
        const isOpen = menu.classList.contains('show');
        
        // Close all dropdowns first
        closeAllDropdowns();
        
        if (!isOpen) {
            menu.classList.add('show');
            menu.style.pointerEvents = 'auto';
        }
    }
    
    // Make functions globally available
    window.showDropdown = showDropdown;
    window.hideDropdown = function(id) {
        const dropdown = document.getElementById(id);
        if (dropdown) {
            dropdown.classList.remove('show');
            const button = dropdown.previousElementSibling;
            if (button) button.classList.remove('active');
        }
    };
    window.toggleDropdown = function(id) {
        const dropdown = document.getElementById(id);
        if (dropdown && dropdown.classList.contains('show')) {
            window.hideDropdown(id);
        } else {
            showDropdown(id);
        }
    };
    window.toggleProfile = toggleProfile;
    
})();