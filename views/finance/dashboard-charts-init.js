/**
 * Chart Refresh Integration - Triggers chart updates when prefix changes
 */

// Hook into existing prefix change handlers
const originalUpdateCompanyPrefix = window.updateCompanyPrefix;
window.updateCompanyPrefix = async function() {
    await originalUpdateCompanyPrefix?.call(this);
    window.refreshCharts?.();
};

const originalUpdateConversionFunnel = window.updateConversionFunnel;
window.updateConversionFunnel = async function(data) {
    await originalUpdateConversionFunnel?.call(this, data);
    window.refreshCharts?.();
};

// Ensure charts refresh on initial load
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => window.refreshCharts?.(), 500);
});
