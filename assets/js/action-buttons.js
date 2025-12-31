// Action Buttons - Enhanced Tooltip functionality
document.addEventListener('DOMContentLoaded', function() {
    const tooltip = document.createElement('div');
    tooltip.className = 'action-tooltip';
    document.body.appendChild(tooltip);
    
    function showTooltip(element, text) {
        tooltip.textContent = text;
        tooltip.style.display = 'block';
        tooltip.style.opacity = '0';
        
        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
        let top = rect.top - tooltipRect.height - 8;
        
        if (left < 5) left = 5;
        if (left + tooltipRect.width > window.innerWidth - 5) {
            left = window.innerWidth - tooltipRect.width - 5;
        }
        if (top < 5) {
            top = rect.bottom + 8;
        }
        
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        tooltip.style.opacity = '1';
    }
    
    function hideTooltip() {
        tooltip.style.opacity = '0';
        setTimeout(() => {
            tooltip.style.display = 'none';
        }, 150);
    }
    
    function attachTooltips() {
        document.querySelectorAll('[title]').forEach(element => {
            const title = element.getAttribute('title');
            if (title && !element.hasAttribute('data-tooltip-attached')) {
                element.setAttribute('data-tooltip-attached', 'true');
                
                element.addEventListener('mouseenter', function() {
                    showTooltip(this, title);
                });
                
                element.addEventListener('mouseleave', hideTooltip);
                
                element.addEventListener('focus', function() {
                    showTooltip(this, title);
                });
                
                element.addEventListener('blur', hideTooltip);
            }
        });
    }
    
    attachTooltips();
    
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                attachTooltips();
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
