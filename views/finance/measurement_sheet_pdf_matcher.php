<?php
$title = 'PDF Format Matcher - Measurement Sheet';
$active_page = 'measurement_sheet';
ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div>
        <h2 style="margin:0;font-size:22px;font-weight:700;">PDF Format Matcher</h2>
        <p style="margin:4px 0 0;color:#6b7280;font-size:14px;">Customize the measurement sheet to match your exact PDF format</p>
    </div>
</div>

<!-- Format Configuration Panel -->
<div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:20px;">
    <h3 style="margin:0 0 16px;font-size:16px;font-weight:700;">PDF Format Configuration</h3>
    
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px;">
        <!-- Header Configuration -->
        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
            <h4 style="margin:0 0 12px;font-size:14px;font-weight:600;">Header Layout</h4>
            
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Company Logo Position</label>
                <select id="logoPosition" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
                    <option value="left">Left Side</option>
                    <option value="center">Center</option>
                    <option value="right">Right Side</option>
                    <option value="top-center">Top Center</option>
                </select>
            </div>
            
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Company Info Layout</label>
                <select id="companyLayout" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
                    <option value="center">Center Aligned</option>
                    <option value="left">Left Aligned</option>
                    <option value="right">Right Aligned</option>
                    <option value="two-column">Two Column</option>
                </select>
            </div>
            
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Header Border</label>
                <select id="headerBorder" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
                    <option value="none">No Border</option>
                    <option value="bottom">Bottom Border</option>
                    <option value="full">Full Border</option>
                    <option value="double">Double Border</option>
                </select>
            </div>
        </div>
        
        <!-- Table Configuration -->
        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
            <h4 style="margin:0 0 12px;font-size:14px;font-weight:600;">Table Structure</h4>
            
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Column Order</label>
                <select id="columnOrder" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
                    <option value="standard">Standard (S.No, Desc, Unit, PO, Prev, This, Cum)</option>
                    <option value="grouped">Grouped (S.No, Desc, PO Details, Claims)</option>
                    <option value="minimal">Minimal (S.No, Desc, PO Qty, This Qty, Amount)</option>
                    <option value="custom">Custom Order</option>
                </select>
            </div>
            
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Header Style</label>
                <select id="tableHeaderStyle" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
                    <option value="single-row">Single Row Headers</option>
                    <option value="multi-row">Multi-Row Headers</option>
                    <option value="grouped">Grouped Headers</option>
                </select>
            </div>
            
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Border Style</label>
                <select id="tableBorderStyle" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
                    <option value="all">All Borders</option>
                    <option value="outer">Outer Border Only</option>
                    <option value="horizontal">Horizontal Lines</option>
                    <option value="minimal">Minimal Borders</option>
                </select>
            </div>
        </div>
        
        <!-- Content Configuration -->
        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
            <h4 style="margin:0 0 12px;font-size:14px;font-weight:600;">Content & Styling</h4>
            
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Font Family</label>
                <select id="fontFamily" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
                    <option value="Arial">Arial</option>
                    <option value="Times">Times New Roman</option>
                    <option value="Calibri">Calibri</option>
                    <option value="Helvetica">Helvetica</option>
                </select>
            </div>
            
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Base Font Size</label>
                <select id="baseFontSize" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
                    <option value="9px">9px (Very Small)</option>
                    <option value="10px">10px (Small)</option>
                    <option value="11px" selected>11px (Medium)</option>
                    <option value="12px">12px (Large)</option>
                </select>
            </div>
            
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Page Margins</label>
                <select id="pageMargins" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
                    <option value="narrow">Narrow (5mm)</option>
                    <option value="normal" selected>Normal (10mm)</option>
                    <option value="wide">Wide (15mm)</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Column Visibility Controls -->
    <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:16px;">
        <h4 style="margin:0 0 12px;font-size:14px;font-weight:600;">Column Visibility & Order</h4>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showSNo" checked> S.No
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showDescription" checked> Description of Work
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showUnit" checked> Unit
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showPOQty" checked> PO Quantity
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showPORate" checked> PO Rate
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showPOAmount" checked> PO Amount
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showPrevQty" checked> Previous Quantity
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showPrevPct" checked> Previous %
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showPrevAmount" checked> Previous Amount
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showThisQty" checked> This Quantity
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showThisPct" checked> This %
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showThisAmount" checked> This Amount
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showCumQty" checked> Cumulative Quantity
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="showCumAmount" checked> Cumulative Amount
            </label>
        </div>
    </div>
    
    <!-- Custom Text Fields -->
    <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:16px;">
        <h4 style="margin:0 0 12px;font-size:14px;font-weight:600;">Custom Text & Labels</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Document Title</label>
                <input type="text" id="docTitle" value="Measurement Sheet" 
                       style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Company Subtitle</label>
                <input type="text" id="companySubtitle" value="Civil & Construction Works" 
                       style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">RA Bill Prefix</label>
                <input type="text" id="raBillPrefix" value="RA-" 
                       style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:4px;">
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div style="display:flex;gap:12px;justify-content:flex-end;">
        <button onclick="resetToDefault()" 
                style="padding:8px 16px;border:1px solid #e5e7eb;background:#fff;border-radius:6px;cursor:pointer;">
            Reset to Default
        </button>
        <button onclick="saveTemplate()" 
                style="padding:8px 16px;background:#059669;color:#fff;border:none;border-radius:6px;cursor:pointer;">
            Save as Template
        </button>
        <button onclick="generatePreview()" 
                style="padding:8px 16px;background:#000080;color:#fff;border:none;border-radius:6px;cursor:pointer;">
            Generate Preview
        </button>
    </div>
</div>

<!-- Live Preview Area -->
<div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;font-size:16px;font-weight:700;">Live Preview</h3>
        <div style="display:flex;gap:8px;">
            <button onclick="zoomOut()" style="padding:4px 8px;border:1px solid #e5e7eb;background:#fff;border-radius:4px;">-</button>
            <span id="zoomLevel">100%</span>
            <button onclick="zoomIn()" style="padding:4px 8px;border:1px solid #e5e7eb;background:#fff;border-radius:4px;">+</button>
        </div>
    </div>
    
    <div id="previewContainer" style="border:1px solid #e5e7eb;border-radius:8px;padding:20px;background:#f9fafb;overflow:auto;transform-origin:top left;" data-zoom="1">
        <div id="previewContent" style="background:#fff;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.1);min-height:400px;">
            <p style="text-align:center;color:#6b7280;margin:100px 0;">
                Click "Generate Preview" to see your customized measurement sheet format
            </p>
        </div>
    </div>
</div>

<script>
let currentZoom = 1;

function generatePreview() {
    // Collect all configuration values
    const config = {
        logoPosition: document.getElementById('logoPosition').value,
        companyLayout: document.getElementById('companyLayout').value,
        headerBorder: document.getElementById('headerBorder').value,
        columnOrder: document.getElementById('columnOrder').value,
        tableHeaderStyle: document.getElementById('tableHeaderStyle').value,
        tableBorderStyle: document.getElementById('tableBorderStyle').value,
        fontFamily: document.getElementById('fontFamily').value,
        baseFontSize: document.getElementById('baseFontSize').value,
        pageMargins: document.getElementById('pageMargins').value,
        docTitle: document.getElementById('docTitle').value,
        companySubtitle: document.getElementById('companySubtitle').value,
        raBillPrefix: document.getElementById('raBillPrefix').value,
        
        // Column visibility
        columns: {
            sNo: document.getElementById('showSNo').checked,
            description: document.getElementById('showDescription').checked,
            unit: document.getElementById('showUnit').checked,
            poQty: document.getElementById('showPOQty').checked,
            poRate: document.getElementById('showPORate').checked,
            poAmount: document.getElementById('showPOAmount').checked,
            prevQty: document.getElementById('showPrevQty').checked,
            prevPct: document.getElementById('showPrevPct').checked,
            prevAmount: document.getElementById('showPrevAmount').checked,
            thisQty: document.getElementById('showThisQty').checked,
            thisPct: document.getElementById('showThisPct').checked,
            thisAmount: document.getElementById('showThisAmount').checked,
            cumQty: document.getElementById('showCumQty').checked,
            cumAmount: document.getElementById('showCumAmount').checked
        }
    };
    
    // Generate preview HTML
    const previewHTML = generateMeasurementSheetHTML(config);
    document.getElementById('previewContent').innerHTML = previewHTML;
}

function generateMeasurementSheetHTML(config) {
    return `
        <div style="font-family:${config.fontFamily};font-size:${config.baseFontSize};">
            ${generateHeader(config)}
            ${generateDocumentTitle(config)}
            ${generateInfoGrid(config)}
            ${generateMeasurementTable(config)}
            ${generateSummary(config)}
            ${generateSignatures(config)}
        </div>
    `;
}

function generateHeader(config) {
    const logoPosition = config.logoPosition;
    const companyLayout = config.companyLayout;
    const headerBorder = config.headerBorder;
    
    let borderStyle = '';
    if (headerBorder === 'bottom') borderStyle = 'border-bottom:2px solid #000;';
    else if (headerBorder === 'full') borderStyle = 'border:2px solid #000;';
    else if (headerBorder === 'double') borderStyle = 'border-bottom:3px double #000;';
    
    return `
        <table style="width:100%;border-collapse:collapse;margin-bottom:12px;${borderStyle}">
            <tr>
                ${logoPosition === 'left' ? '<td style="width:80px;text-align:left;vertical-align:middle;"><div style="width:60px;height:60px;border:2px dashed #aaa;display:flex;align-items:center;justify-content:center;font-size:8px;">Logo</div></td>' : ''}
                <td style="text-align:${companyLayout};vertical-align:middle;padding:8px;">
                    <div style="font-size:16px;font-weight:700;text-transform:uppercase;">COMPANY NAME</div>
                    <div style="font-size:10px;color:#555;margin-top:2px;">${config.companySubtitle}</div>
                    <div style="font-size:9px;color:#555;margin-top:2px;">GSTIN: 123456789012345</div>
                </td>
                ${logoPosition === 'right' ? '<td style="width:80px;text-align:right;vertical-align:middle;"><div style="width:60px;height:60px;border:2px dashed #aaa;display:flex;align-items:center;justify-content:center;font-size:8px;">Seal</div></td>' : ''}
            </tr>
        </table>
    `;
}

function generateDocumentTitle(config) {
    return `
        <div style="text-align:center;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:2px;border:2px solid #000;padding:8px;margin:12px 0;background:#e8eaf6;">
            ${config.docTitle} | ${config.raBillPrefix}07
        </div>
    `;
}

function generateInfoGrid(config) {
    return `
        <table style="width:100%;border-collapse:collapse;margin-bottom:12px;">
            <tr>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;font-weight:700;background:#f5f5f5;width:120px;">PROJECT</td>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;">Sample Project Name</td>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;font-weight:700;background:#f5f5f5;width:120px;">PO / WO REF</td>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;">PO-2024-001</td>
            </tr>
            <tr>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;font-weight:700;background:#f5f5f5;">CONTRACTOR</td>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;">Sample Contractor</td>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;font-weight:700;background:#f5f5f5;">RA BILL NO</td>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;font-weight:700;color:#000080;">${config.raBillPrefix}07</td>
            </tr>
            <tr>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;font-weight:700;background:#f5f5f5;">CLIENT</td>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;">Sample Client</td>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;font-weight:700;background:#f5f5f5;">DATE</td>
                <td style="padding:6px 8px;font-size:10px;border:1px solid #999;">${new Date().toLocaleDateString()}</td>
            </tr>
        </table>
    `;
}

function generateMeasurementTable(config) {
    const columns = config.columns;
    let headers = [];
    let sampleRow = [];
    
    if (columns.sNo) { headers.push('S.No'); sampleRow.push('1'); }
    if (columns.description) { headers.push('Description of Work'); sampleRow.push('Sample Work Item'); }
    if (columns.unit) { headers.push('Unit'); sampleRow.push('Nos'); }
    if (columns.poQty) { headers.push('PO Qty'); sampleRow.push('100.000'); }
    if (columns.poRate) { headers.push('PO Rate (₹)'); sampleRow.push('1,000.00'); }
    if (columns.poAmount) { headers.push('PO Amount (₹)'); sampleRow.push('1,00,000.00'); }
    if (columns.prevQty) { headers.push('Prev Qty'); sampleRow.push('50.000'); }
    if (columns.prevPct) { headers.push('Prev %'); sampleRow.push('50.00%'); }
    if (columns.prevAmount) { headers.push('Prev Amount (₹)'); sampleRow.push('50,000.00'); }
    if (columns.thisQty) { headers.push('This Qty'); sampleRow.push('25.000'); }
    if (columns.thisPct) { headers.push('This %'); sampleRow.push('25.00%'); }
    if (columns.thisAmount) { headers.push('This Amount (₹)'); sampleRow.push('25,000.00'); }
    if (columns.cumQty) { headers.push('Cum Qty'); sampleRow.push('75.000'); }
    if (columns.cumAmount) { headers.push('Cum Amount (₹)'); sampleRow.push('75,000.00'); }
    
    let headerHTML = '<tr>';
    headers.forEach(header => {
        headerHTML += `<th style="background:#000080;color:#fff;padding:8px 4px;font-size:9px;text-align:center;border:1px solid #000;">${header}</th>`;
    });
    headerHTML += '</tr>';
    
    let rowHTML = '<tr>';
    sampleRow.forEach((cell, index) => {
        const align = index === 1 ? 'left' : 'center'; // Description left-aligned
        rowHTML += `<td style="padding:6px 4px;border:1px solid #bbb;font-size:9px;text-align:${align};">${cell}</td>`;
    });
    rowHTML += '</tr>';
    
    return `
        <table style="width:100%;border-collapse:collapse;margin-top:8px;">
            <thead>${headerHTML}</thead>
            <tbody>${rowHTML}</tbody>
        </table>
    `;
}

function generateSummary(config) {
    return `
        <table style="width:100%;border-collapse:collapse;margin-top:12px;">
            <tr>
                <td style="padding:6px 8px;border:1px solid #bbb;font-size:10px;font-weight:700;background:#f5f5f5;width:200px;">PO Contract Value</td>
                <td style="padding:6px 8px;border:1px solid #bbb;font-size:10px;text-align:right;font-weight:700;">₹1,00,000.00</td>
                <td style="padding:6px 8px;border:1px solid #bbb;font-size:10px;font-weight:700;background:#f5f5f5;width:200px;">This ${config.raBillPrefix.replace('-','')} Bill Amount</td>
                <td style="padding:6px 8px;border:1px solid #bbb;font-size:10px;text-align:right;font-weight:700;color:#059669;">₹25,000.00</td>
            </tr>
        </table>
    `;
}

function generateSignatures(config) {
    return `
        <table style="width:100%;border-collapse:collapse;margin-top:20px;">
            <tr>
                <td style="padding:8px 12px;text-align:center;vertical-align:bottom;width:33%;">
                    <div style="border-top:1px solid #000;margin-top:40px;padding-top:6px;font-size:9px;font-weight:700;">
                        Prepared By<br><span style="font-weight:400;">Name & Designation</span>
                    </div>
                </td>
                <td style="padding:8px 12px;text-align:center;vertical-align:bottom;width:33%;">
                    <div style="border-top:1px solid #000;margin-top:40px;padding-top:6px;font-size:9px;font-weight:700;">
                        Checked By<br><span style="font-weight:400;">Site Engineer</span>
                    </div>
                </td>
                <td style="padding:8px 12px;text-align:center;vertical-align:bottom;width:33%;">
                    <div style="border-top:1px solid #000;margin-top:40px;padding-top:6px;font-size:9px;font-weight:700;">
                        Approved By<br><span style="font-weight:400;">Project Manager</span>
                    </div>
                </td>
            </tr>
        </table>
    `;
}

function zoomIn() {
    currentZoom = Math.min(currentZoom + 0.1, 2);
    updateZoom();
}

function zoomOut() {
    currentZoom = Math.max(currentZoom - 0.1, 0.5);
    updateZoom();
}

function updateZoom() {
    const container = document.getElementById('previewContainer');
    container.style.transform = `scale(${currentZoom})`;
    container.dataset.zoom = currentZoom;
    document.getElementById('zoomLevel').textContent = Math.round(currentZoom * 100) + '%';
}

function resetToDefault() {
    // Reset all form fields to default values
    document.getElementById('logoPosition').value = 'left';
    document.getElementById('companyLayout').value = 'center';
    document.getElementById('headerBorder').value = 'none';
    document.getElementById('columnOrder').value = 'standard';
    document.getElementById('tableHeaderStyle').value = 'multi-row';
    document.getElementById('tableBorderStyle').value = 'all';
    document.getElementById('fontFamily').value = 'Arial';
    document.getElementById('baseFontSize').value = '11px';
    document.getElementById('pageMargins').value = 'normal';
    document.getElementById('docTitle').value = 'Measurement Sheet';
    document.getElementById('companySubtitle').value = 'Civil & Construction Works';
    document.getElementById('raBillPrefix').value = 'RA-';
    
    // Reset all checkboxes to checked
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
    
    generatePreview();
}

function saveTemplate() {
    const config = {
        logoPosition: document.getElementById('logoPosition').value,
        companyLayout: document.getElementById('companyLayout').value,
        headerBorder: document.getElementById('headerBorder').value,
        // ... collect all other values
    };
    
    localStorage.setItem('measurementSheetTemplate', JSON.stringify(config));
    alert('Template saved successfully!');
}

// Initialize with default preview
document.addEventListener('DOMContentLoaded', function() {
    generatePreview();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';