// Action Button Clean - Tooltip System
document.addEventListener('DOMContentLoaded', function() {
    // Convert all title attributes to data-tooltip to prevent native tooltips
    function convertTitleToDataTooltip() {
        document.querySelectorAll('[title]').forEach(element => {
            const title = element.getAttribute('title');
            if (title) {
                element.setAttribute('data-tooltip', title);
                element.removeAttribute('title');
            }
        });
    }
    
    // Initial conversion
    convertTitleToDataTooltip();
    
    // Watch for dynamically added elements
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                convertTitleToDataTooltip();
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
