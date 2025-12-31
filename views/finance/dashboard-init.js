// Ensure charts render after DOM is fully loaded
window.addEventListener('load', function() {
    console.log('Window load event fired - DOM fully ready');
    setTimeout(() => {
        if (document.getElementById('quotationsChart')) {
            console.log('Canvas elements found - updating analytics widgets');
            updateAnalyticsWidgets();
        } else {
            console.warn('Canvas elements not found yet');
        }
    }, 100);
});
