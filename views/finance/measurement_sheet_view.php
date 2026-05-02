<?php
$title = 'RA Bill Details';
$active_page = 'measurement_sheet';
ob_start();
?>

<?php if ($error || !$ra): ?>
<div style="min-height:400px;display:flex;align-items:center;justify-content:center;">
    <div style="text-align:center;">
        <div style="font-size:48px;color:#dc2626;margin-bottom:16px;">⚠️</div>
        <h3 style="margin:0;color:#dc2626;font-size:18px;"><?= htmlspecialchars($error ?? 'RA Bill not found') ?></h3>
    </div>
</div>
<?php else: ?>

<!-- Header -->
<div style="background:linear-gradient(135deg, #000080 0%, #0066cc 100%);color:white;padding:24px;border-radius:12px;margin-bottom:24px;">
    <div style="display:flex;align-items:center;justify-content:between;">
        <div style="flex:1;">
            <h1 style="margin:0;font-size:28px;font-weight:800;"><?= htmlspecialchars($ra['ra_bill_number']) ?></h1>
            <p style="margin:8px 0 0;opacity:0.9;font-size:16px;"><?= htmlspecialchars($ra['po_number']) ?></p>
            <p style="margin:4px 0 0;opacity:0.7;font-size:14px;"><?= date('d M Y', strtotime($ra['bill_date'])) ?></p>
        </div>
        
        <div style="text-align:right;">
            <div style="font-size:32px;font-weight:800;margin-bottom:8px;">₹<?= number_format(floatval($ra['total_claimed']), 0) ?></div>
            <?php
            $statusColors = [
                'draft' => 'background:rgba(251,191,36,0.2);color:#f59e0b;border:1px solid rgba(251,191,36,0.3);',
                'submitted' => 'background:rgba(59,130,246,0.2);color:#3b82f6;border:1px solid rgba(59,130,246,0.3);',
                'approved' => 'background:rgba(34,197,94,0.2);color:#22c55e;border:1px solid rgba(34,197,94,0.3);',
                'rejected' => 'background:rgba(239,68,68,0.2);color:#ef4444;border:1px solid rgba(239,68,68,0.3);',
                'paid' => 'background:rgba(168,85,247,0.2);color:#a855f7;border:1px solid rgba(168,85,247,0.3);'
            ];
            $statusStyle = $statusColors[$ra['status']] ?? 'background:rgba(156,163,175,0.2);color:#9ca3af;border:1px solid rgba(156,163,175,0.3);';
            ?>
            <span style="<?= $statusStyle ?>padding:6px 12px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;">
                <?= htmlspecialchars($ra['status']) ?>
            </span>
        </div>
    </div>
</div>

<!-- Action Bar -->
<div style="display:flex;gap:12px;margin-bottom:24px;">
    <a href="/ergon/finance/measurement-sheet/manage" 
       style="display:inline-flex;align-items:center;gap:8px;padding:12px 20px;background:#f3f4f6;color:#374151;text-decoration:none;border-radius:8px;font-weight:600;transition:all 0.2s;"
       onmouseover="this.style.backgroundColor='#e5e7eb'"
       onmouseout="this.style.backgroundColor='#f3f4f6'">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
        Back to List
    </a>
    
    <a href="/ergon/finance/measurement-sheet/<?= $ra['id'] ?>/print" target="_blank"
       style="display:inline-flex;align-items:center;gap:8px;padding:12px 20px;background:#000080;color:white;text-decoration:none;border-radius:8px;font-weight:600;transition:all 0.2s;"
       onmouseover="this.style.backgroundColor='#000066'"
       onmouseout="this.style.backgroundColor='#000080'">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5z"/>
        </svg>
        Print RA Bill
    </a>
    
    <?php if ($ra['status'] === 'draft'): ?>
    <button onclick="updateStatus('submitted')" 
            style="display:inline-flex;align-items:center;gap:8px;padding:12px 20px;background:#059669;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.2s;"
            onmouseover="this.style.backgroundColor='#047857'"
            onmouseout="this.style.backgroundColor='#059669'">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
        </svg>
        Submit for Approval
    </button>
    <?php endif; ?>
</div>

<!-- Main Content Grid -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
    
    <!-- Left Column: Items -->
    <div>
        <div style="background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);overflow:hidden;">
            <div style="padding:20px;border-bottom:1px solid #e5e7eb;">
                <h3 style="margin:0;font-size:18px;font-weight:700;color:#111827;">Line Items</h3>
                <p style="margin:4px 0 0;color:#6b7280;font-size:14px;"><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?> in this RA bill</p>
            </div>
            
            <?php if (empty($items)): ?>
                <div style="padding:60px 20px;text-align:center;">
                    <div style="font-size:48px;color:#d1d5db;margin-bottom:16px;">📋</div>
                    <p style="margin:0;color:#9ca3af;font-size:16px;">No items found</p>
                </div>
            <?php else: ?>
                <div style="max-height:500px;overflow-y:auto;">
                    <?php foreach ($items as $index => $item): ?>
                    <div style="padding:20px;border-bottom:1px solid #f3f4f6;<?= $index === count($items) - 1 ? 'border-bottom:none;' : '' ?>">
                        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
                            <div style="flex:1;">
                                <h4 style="margin:0;font-size:16px;font-weight:600;color:#111827;line-height:1.4;">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </h4>
                                <?php if (!empty($item['description'])): ?>
                                <p style="margin:4px 0 0;color:#6b7280;font-size:13px;line-height:1.4;">
                                    <?= htmlspecialchars(substr($item['description'], 0, 120)) ?><?= strlen($item['description']) > 120 ? '...' : '' ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div style="text-align:right;margin-left:16px;">
                                <div style="font-size:18px;font-weight:700;color:#059669;">₹<?= number_format(floatval($item['this_amount']), 0) ?></div>
                                <div style="font-size:12px;color:#6b7280;margin-top:2px;"><?= number_format(floatval($item['this_qty']), 2) ?> <?= htmlspecialchars($item['unit'] ?? '') ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Column: Details -->
    <div>
        <!-- Project Info -->
        <div style="background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);padding:20px;margin-bottom:20px;">
            <h3 style="margin:0 0 16px;font-size:16px;font-weight:700;color:#111827;">Project Details</h3>
            
            <div style="space-y:12px;">
                <div style="margin-bottom:12px;">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Project</label>
                    <div style="font-size:14px;color:#111827;margin-top:2px;"><?= htmlspecialchars($ra['project'] ?: 'Not specified') ?></div>
                </div>
                
                <div style="margin-bottom:12px;">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Contractor</label>
                    <div style="font-size:14px;color:#111827;margin-top:2px;"><?= htmlspecialchars($ra['contractor'] ?: 'Not specified') ?></div>
                </div>
                
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Created</label>
                    <div style="font-size:14px;color:#111827;margin-top:2px;"><?= date('d M Y, H:i', strtotime($ra['created_at'])) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Notes -->
        <?php if (!empty($ra['notes'])): ?>
        <div style="background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);padding:20px;">
            <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Notes</h3>
            <div style="background:#f9fafb;padding:16px;border-radius:8px;border-left:4px solid #3b82f6;">
                <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;white-space:pre-line;">
                    <?= htmlspecialchars($ra['notes']) ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Status Update Modal (Hidden by default) -->
<div id="statusModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:white;border-radius:12px;width:90%;max-width:400px;box-shadow:0 20px 40px rgba(0,0,0,0.3);">
        <div style="padding:24px;">
            <h3 style="margin:0 0 16px;font-size:18px;font-weight:700;">Update Status</h3>
            <p style="margin:0 0 20px;color:#6b7280;">Are you sure you want to submit this RA bill for approval?</p>
            
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button onclick="closeStatusModal()" style="padding:10px 20px;border:1px solid #e5e7eb;background:white;border-radius:6px;color:#374151;cursor:pointer;">
                    Cancel
                </button>
                <button onclick="confirmStatusUpdate()" style="padding:10px 20px;background:#059669;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;">
                    Submit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let pendingStatus = null;

function updateStatus(status) {
    pendingStatus = status;
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
    pendingStatus = null;
}

function confirmStatusUpdate() {
    if (pendingStatus) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/ergon/finance/measurement-sheet/<?= $ra['id'] ?>/update-status';
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = pendingStatus;
        
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>