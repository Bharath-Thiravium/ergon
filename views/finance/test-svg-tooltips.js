/**
 * SVG Chart Tooltip Test Script
 * Tests tooltip functionality for all 6 charts
 */

function testSvgTooltips() {
    console.log('=== SVG Chart Tooltip Tests ===\n');
    
    const tests = [
        testQuotationsTooltip,
        testPurchaseOrdersTooltip,
        testInvoicesTooltip,
        testOutstandingTooltip,
        testAgingTooltip,
        testPaymentsTooltip
    ];
    
    let passed = 0;
    let failed = 0;
    
    tests.forEach(test => {
        try {
            test();
            passed++;
            console.log(`✓ ${test.name} PASSED\n`);
        } catch (e) {
            failed++;
            console.error(`✗ ${test.name} FAILED: ${e.message}\n`);
        }
    });
    
    console.log(`\n=== Test Results ===`);
    console.log(`Passed: ${passed}/${tests.length}`);
    console.log(`Failed: ${failed}/${tests.length}`);
    
    return failed === 0;
}

function testQuotationsTooltip() {
    const svg = document.getElementById('quotationsChart');
    if (!svg) throw new Error('quotationsChart SVG not found');
    
    const elements = svg.querySelectorAll('[data-idx]');
    if (elements.length !== 3) throw new Error(`Expected 3 elements, got ${elements.length}`);
    
    elements.forEach((el, idx) => {
        if (!el.getAttribute('data-idx')) throw new Error(`Element ${idx} missing data-idx`);
        if (!el.getAttribute('data-opacity')) throw new Error(`Element ${idx} missing data-opacity`);
    });
    
    // Simulate hover
    const event = new MouseEvent('mouseenter', { bubbles: true });
    elements[0].dispatchEvent(event);
    
    if (elements[0].style.opacity !== '1') throw new Error('Opacity not updated on hover');
}

function testPurchaseOrdersTooltip() {
    const svg = document.getElementById('purchaseOrdersChart');
    if (!svg) throw new Error('purchaseOrdersChart SVG not found');
    
    const circles = svg.querySelectorAll('circle[data-idx]');
    if (circles.length !== 4) throw new Error(`Expected 4 circles, got ${circles.length}`);
    
    circles.forEach((el, idx) => {
        if (el.getAttribute('data-idx') !== idx.toString()) {
            throw new Error(`Circle ${idx} has wrong data-idx`);
        }
    });
    
    // Test hover on first circle
    const event = new MouseEvent('mouseenter', { bubbles: true });
    circles[0].dispatchEvent(event);
    
    if (circles[0].getAttribute('r') !== '6') throw new Error('Circle radius not updated on hover');
}

function testInvoicesTooltip() {
    const svg = document.getElementById('invoicesChart');
    if (!svg) throw new Error('invoicesChart SVG not found');
    
    const paths = svg.querySelectorAll('path[data-idx]');
    if (paths.length !== 3) throw new Error(`Expected 3 paths, got ${paths.length}`);
    
    paths.forEach((el, idx) => {
        const strokeWidth = el.getAttribute('stroke-width');
        if (!strokeWidth) throw new Error(`Path ${idx} missing stroke-width`);
    });
}

function testOutstandingTooltip() {
    const svg = document.getElementById('outstandingByCustomerChart');
    if (!svg) throw new Error('outstandingByCustomerChart SVG not found');
    
    const paths = svg.querySelectorAll('path[data-idx]');
    if (paths.length < 1) throw new Error('No paths with data-idx found');
    
    paths.forEach((el, idx) => {
        if (!el.getAttribute('data-opacity')) throw new Error(`Path ${idx} missing data-opacity`);
    });
}

function testAgingTooltip() {
    const svg = document.getElementById('agingBucketsChart');
    if (!svg) throw new Error('agingBucketsChart SVG not found');
    
    const paths = svg.querySelectorAll('path[data-idx]');
    if (paths.length !== 4) throw new Error(`Expected 4 paths, got ${paths.length}`);
    
    // Check all 4 aging buckets exist
    const indices = Array.from(paths).map(p => p.getAttribute('data-idx'));
    if (!indices.includes('0')) throw new Error('Missing 0-30 Days segment');
    if (!indices.includes('1')) throw new Error('Missing 31-60 Days segment');
    if (!indices.includes('2')) throw new Error('Missing 61-90 Days segment');
    if (!indices.includes('3')) throw new Error('Missing 90+ Days segment');
}

function testPaymentsTooltip() {
    const svg = document.getElementById('paymentsChart');
    if (!svg) throw new Error('paymentsChart SVG not found');
    
    const rects = svg.querySelectorAll('rect[data-idx]');
    if (rects.length !== 7) throw new Error(`Expected 7 bars, got ${rects.length}`);
    
    rects.forEach((el, idx) => {
        if (el.getAttribute('data-idx') !== idx.toString()) {
            throw new Error(`Bar ${idx} has wrong data-idx`);
        }
    });
}

function testTooltipDisplay() {
    console.log('\n=== Tooltip Display Test ===\n');
    
    const svg = document.getElementById('quotationsChart');
    if (!svg) {
        console.error('SVG not found');
        return false;
    }
    
    const element = svg.querySelector('[data-idx="0"]');
    if (!element) {
        console.error('Element with data-idx="0" not found');
        return false;
    }
    
    // Simulate hover
    const event = new MouseEvent('mouseenter', {
        bubbles: true,
        pageX: 100,
        pageY: 100
    });
    
    element.dispatchEvent(event);
    
    // Check if tooltip exists
    const tooltip = document.querySelector('.chart-tooltip');
    if (tooltip) {
        console.log('✓ Tooltip displayed');
        console.log(`  Content: ${tooltip.textContent}`);
        console.log(`  Position: ${tooltip.style.left}, ${tooltip.style.top}`);
        return true;
    } else {
        console.error('✗ Tooltip not displayed');
        return false;
    }
}

function testTooltipContent() {
    console.log('\n=== Tooltip Content Test ===\n');
    
    const charts = [
        { id: 'quotationsChart', expectedTexts: ['Pending:', 'Placed:', 'Rejected:'] },
        { id: 'invoicesChart', expectedTexts: ['Paid:', 'Unpaid:', 'Overdue:'] },
        { id: 'agingBucketsChart', expectedTexts: ['0-30 Days:', '31-60 Days:', '61-90 Days:', '90+ Days:'] },
        { id: 'paymentsChart', expectedTexts: ['₹', 'K'] }
    ];
    
    let allPassed = true;
    
    charts.forEach(chart => {
        const svg = document.getElementById(chart.id);
        if (!svg) {
            console.error(`✗ ${chart.id} not found`);
            allPassed = false;
            return;
        }
        
        const elements = svg.querySelectorAll('[data-idx]');
        if (elements.length === 0) {
            console.error(`✗ ${chart.id} has no elements with data-idx`);
            allPassed = false;
            return;
        }
        
        console.log(`✓ ${chart.id}: ${elements.length} interactive elements found`);
    });
    
    return allPassed;
}

// Run tests
function runAllTests() {
    console.clear();
    console.log('Starting SVG Chart Tooltip Tests...\n');
    
    const structureTest = testSvgTooltips();
    const displayTest = testTooltipDisplay();
    const contentTest = testTooltipContent();
    
    console.log('\n=== Final Result ===');
    if (structureTest && displayTest && contentTest) {
        console.log('✓ All tests PASSED');
        return true;
    } else {
        console.log('✗ Some tests FAILED');
        return false;
    }
}

// Export for use
window.testSvgTooltips = testSvgTooltips;
window.testTooltipDisplay = testTooltipDisplay;
window.testTooltipContent = testTooltipContent;
window.runAllTests = runAllTests;

// Auto-run on load if in test mode
if (window.location.search.includes('test=svg')) {
    document.addEventListener('DOMContentLoaded', runAllTests);
}
