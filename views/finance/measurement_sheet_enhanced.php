<?php
$title = 'Enhanced Measurement Sheet';
$active_page = 'measurement_sheet';
ob_start();
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div>
        <h2 style="margin:0;font-size:22px;font-weight:700;">Enhanced Measurement Sheet</h2>
        <p style="margin:4px 0 0;color:#6b7280;font-size:14px;">Select a PO to raise an RA Bill (Enhanced Format)</p>
    </div>
    <div style="display:flex;gap:12px;">
        <a href="/ergon/finance/measurement-sheet" 
           style="padding:8px 16px;background:#6b7280;color:#fff;border-radius:6px;text-decoration:none;font-size:13px;">
            ← Standard View
        </a>
        <button onclick="showFormatCustomizer()" 
                style="padding:8px 16px;background:#059669;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;">
            🎨 Customize Format
        </button>
    </div>
</div>

<?php if (!empty($error)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['imported'])): ?>
<div style="background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
    ✅ Opening balance imported successfully. Future RA bills will now show correct previous claimed amounts.
</div>
<?php endif; ?>

<!-- Format Customizer Modal -->
<div id="formatCustomizer" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:12px;padding:24px;width:90%;max-width:600px;max-height:80vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">Customize Measurement Sheet Format</h3>
            <button onclick="hideFormatCustomizer()" style="background:none;border:none;font-size:20px;cursor:pointer;">×</button>
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Company Logo Position</label>
                <select id="logoPosition" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Header Style</label>
                <select id="headerStyle" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">
                    <option value="standard">Standard</option>
                    <option value="compact">Compact</option>
                    <option value="detailed">Detailed</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Table Border Style</label>
                <select id="borderStyle" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">
                    <option value="standard">Standard</option>
                    <option value="thick">Thick</option>
                    <option value="minimal">Minimal</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Font Size</label>
                <select id="fontSize" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">
                    <option value="small">Small (10px)</option>
                    <option value="medium" selected>Medium (11px)</option>
                    <option value="large">Large (12px)</option>
                </select>
            </div>
        </div>
        
        <div style="margin-bottom:20px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;">
                <input type="checkbox" id="showPrevClaimed" checked> Show Previous Claimed Columns
            </label>
            <label style="display:block;font-weight:600;margin-bottom:4px;">
                <input type="checkbox" id="showCumulative" checked> Show Cumulative Columns
            </label>
            <label style="display:block;font-weight:600;margin-bottom:4px;">
                <input type="checkbox" id="showClearanceSheet" checked> Include Clearance Sheet
            </label>
        </div>
        
        <div style="display:flex;gap:12px;justify-content:flex-end;">
            <button onclick="hideFormatCustomizer()" style="padding:8px 16px;border:1px solid #e5e7eb;background:#fff;border-radius:6px;cursor:pointer;">Cancel</button>
            <button onclick="applyCustomFormat()" style="padding:8px 16px;background:#000080;color:#fff;border:none;border-radius:6px;cursor:pointer;">Apply Format</button>
        </div>
    </div>
</div>

<div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);overflow:hidden;">
    <div style="padding:14px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;gap:12px;">
        <input type="text" id="poSearch" placeholder="Search PO, customer, company..."
               style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;width:300px;"
               oninput="filterTable()">
        <span style="color:#6b7280;font-size:13px;" id="poCount"><?= count($purchase_orders) ?> POs</span>
        
        <!-- Enhanced Filters -->
        <div style="margin-left:auto;display:flex;gap:8px;">
            <select id="statusFilter" onchange="filterTable()" style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="partially_completed">Partially Completed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select id="companyFilter" onchange="filterTable()" style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;">
                <option value="">All Companies</option>
                <?php 
                $companies = array_unique(array_column($purchase_orders, 'company_name'));
                foreach ($companies as $company): 
                ?>
                <option value="<?= htmlspecialchars($company) ?>"><?= htmlspecialchars($company) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="table-responsive">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb;">
                    <th style="padding:11px 16px;text-align:left;font-weight:600;color:#374151;">#</th>
                    <th style="padding:11px 16px;text-align:left;font-weight:600;color:#374151;">PO Number</th>
                    <th style="padding:11px 16px;text-align:left;font-weight:600;color:#374151;">Company</th>
                    <th style="padding:11px 16px;text-align:left;font-weight:600;color:#374151;">Customer</th>
                    <th style="padding:11px 16px;text-align:left;font-weight:600;color:#374151;">PO Date</th>
                    <th style="padding:11px 16px;text-align:right;font-weight:600;color:#374151;">PO Value</th>
                    <th style="padding:11px 16px;text-align:right;font-weight:600;color:#374151;">Claimed</th>
                    <th style="padding:11px 16px;text-align:right;font-weight:600;color:#374151;">Balance</th>
                    <th style="padding:11px 16px;text-align:center;font-weight:600;color:#374151;">Status</th>
                    <th style="padding:11px 16px;text-align:center;font-weight:600;color:#374151;">RA Bills</th>
                    <th style="padding:11px 16px;text-align:center;font-weight:600;color:#374151;">Action</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
            <?php if (empty($purchase_orders)): ?>
                <tr><td colspan="11" style="padding:40px;text-align:center;color:#9ca3af;">No purchase orders found.</td></tr>
            <?php else: ?>
                <?php foreach ($purchase_orders as $i => $po):
                    $status = strtolower($po['status'] ?? 'active');
                    $sc = ['active'=>['#ecfdf5','#059669'],'partially_completed'=>['#fffbeb','#d97706'],
                           'completed'=>['#eff6ff','#2563eb'],'cancelled'=>['#fef2f2','#dc2626']][$status]
                          ?? ['#f3f4f6','#6b7280'];
                    
                    $poValue = floatval($po['total_amount'] ?? 0);
                    $claimedAmount = floatval($po['invoice_claimed_amount'] ?? 0);
                    $balance = $poValue - $claimedAmount;
                ?>
                <tr style="border-bottom:1px solid #f3f4f6;" class="po-row" 
                    data-status="<?= $status ?>" 
                    data-company="<?= htmlspecialchars($po['company_name'] ?? '') ?>">
                    <td style="padding:11px 16px;color:#9ca3af;"><?= $i+1 ?></td>
                    <td style="padding:11px 16px;font-weight:600;color:#111827;"><?= htmlspecialchars($po['po_number'] ?? $po['internal_po_number'] ?? '—') ?></td>
                    <td style="padding:11px 16px;color:#374151;"><?= htmlspecialchars($po['company_name'] ?? '—') ?></td>
                    <td style="padding:11px 16px;color:#374151;"><?= htmlspecialchars($po['customer_name'] ?? '—') ?></td>
                    <td style="padding:11px 16px;color:#6b7280;"><?= htmlspecialchars($po['po_date'] ?? '—') ?></td>
                    <td style="padding:11px 16px;text-align:right;font-weight:600;">₹<?= number_format($poValue, 2) ?></td>
                    <td style="padding:11px 16px;text-align:right;font-weight:600;color:#059669;">₹<?= number_format($claimedAmount, 2) ?></td>
                    <td style="padding:11px 16px;text-align:right;font-weight:600;color:<?= $balance < 0 ? '#dc2626' : '#374151' ?>;">₹<?= number_format($balance, 2) ?></td>
                    <td style="padding:11px 16px;text-align:center;">
                        <span style="background:<?= $sc[0] ?>;color:<?= $sc[1] ?>;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;text-transform:capitalize;">
                            <?= str_replace('_',' ', $status) ?>
                        </span>
                    </td>
                    <td style="padding:11px 16px;text-align:center;">
                        <?php if (($po['ra_count'] ?? 0) > 0): ?>
                            <span style="background:#eff6ff;color:#2563eb;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                                <?= $po['ra_count'] ?> bill<?= $po['ra_count'] > 1 ? 's' : '' ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#9ca3af;font-size:12px;">None</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:11px 16px;text-align:center;">
                        <?php
                            $hasClaimed  = floatval($po['invoice_claimed_amount'] ?? 0) > 0;
                            $hasOpening  = ($po['has_opening'] ?? false);
                        ?>
                        <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                            <?php if ($hasClaimed && !$hasOpening): ?>
                            <a href="/ergon/finance/measurement-sheet/<?= (int)$po['id'] ?>/opening-balance"
                               style="display:inline-flex;align-items:center;gap:4px;background:#d97706;color:#fff;padding:5px 11px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">
                                <i class="bi bi-upload"></i> Import History
                            </a>
                            <?php endif; ?>
                            <a href="/ergon/finance/measurement-sheet/<?= (int)$po['id'] ?>/create-enhanced"
                               style="display:inline-flex;align-items:center;gap:4px;background:#000080;color:#fff;padding:5px 11px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">
                                <i class="bi bi-plus-lg"></i> New RA Bill
                            </a>
                            <a href="/ergon/finance/measurement-sheet/<?= (int)$po['id'] ?>/preview"
                               style="display:inline-flex;align-items:center;gap:4px;background:#059669;color:#fff;padding:5px 11px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">
                                <i class="bi bi-eye"></i> Preview
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:20px;">
    <?php
    $totalPOs = count($purchase_orders);
    $totalValue = array_sum(array_column($purchase_orders, 'total_amount'));
    $totalClaimed = array_sum(array_column($purchase_orders, 'invoice_claimed_amount'));
    $totalBalance = $totalValue - $totalClaimed;
    ?>
    <div style="background:#fff;border-radius:8px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
        <div style="font-size:13px;color:#6b7280;margin-bottom:4px;">Total POs</div>
        <div style="font-size:20px;font-weight:700;color:#111827;"><?= $totalPOs ?></div>
    </div>
    <div style="background:#fff;border-radius:8px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
        <div style="font-size:13px;color:#6b7280;margin-bottom:4px;">Total PO Value</div>
        <div style="font-size:20px;font-weight:700;color:#111827;">₹<?= number_format($totalValue, 2) ?></div>
    </div>
    <div style="background:#fff;border-radius:8px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
        <div style="font-size:13px;color:#6b7280;margin-bottom:4px;">Total Claimed</div>
        <div style="font-size:20px;font-weight:700;color:#059669;">₹<?= number_format($totalClaimed, 2) ?></div>
    </div>
    <div style="background:#fff;border-radius:8px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
        <div style="font-size:13px;color:#6b7280;margin-bottom:4px;">Total Balance</div>
        <div style="font-size:20px;font-weight:700;color:<?= $totalBalance < 0 ? '#dc2626' : '#374151' ?>;">₹<?= number_format($totalBalance, 2) ?></div>
    </div>
</div>

<script>
function filterTable() {
    const searchQuery = document.getElementById('poSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const companyFilter = document.getElementById('companyFilter').value.toLowerCase();
    const rows = document.querySelectorAll('.po-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const status = row.dataset.status;
        const company = row.dataset.company.toLowerCase();
        
        const matchesSearch = text.includes(searchQuery);
        const matchesStatus = !statusFilter || status === statusFilter;
        const matchesCompany = !companyFilter || company.includes(companyFilter);
        
        const isVisible = matchesSearch && matchesStatus && matchesCompany;
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });
    
    document.getElementById('poCount').textContent = visibleCount + ' POs';
}

function showFormatCustomizer() {
    document.getElementById('formatCustomizer').style.display = 'block';
}

function hideFormatCustomizer() {
    document.getElementById('formatCustomizer').style.display = 'none';
}

function applyCustomFormat() {
    const settings = {
        logoPosition: document.getElementById('logoPosition').value,
        headerStyle: document.getElementById('headerStyle').value,
        borderStyle: document.getElementById('borderStyle').value,
        fontSize: document.getElementById('fontSize').value,
        showPrevClaimed: document.getElementById('showPrevClaimed').checked,
        showCumulative: document.getElementById('showCumulative').checked,
        showClearanceSheet: document.getElementById('showClearanceSheet').checked
    };
    
    // Store settings in localStorage
    localStorage.setItem('measurementSheetFormat', JSON.stringify(settings));
    
    // Show success message
    alert('Format settings saved! These will be applied to new RA bills.');
    hideFormatCustomizer();
}

// Load saved format settings
document.addEventListener('DOMContentLoaded', function() {
    const savedSettings = localStorage.getItem('measurementSheetFormat');
    if (savedSettings) {
        const settings = JSON.parse(savedSettings);
        document.getElementById('logoPosition').value = settings.logoPosition || 'left';
        document.getElementById('headerStyle').value = settings.headerStyle || 'standard';
        document.getElementById('borderStyle').value = settings.borderStyle || 'standard';
        document.getElementById('fontSize').value = settings.fontSize || 'medium';
        document.getElementById('showPrevClaimed').checked = settings.showPrevClaimed !== false;
        document.getElementById('showCumulative').checked = settings.showCumulative !== false;
        document.getElementById('showClearanceSheet').checked = settings.showClearanceSheet !== false;
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';