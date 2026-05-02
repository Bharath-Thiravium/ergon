<?php
$title = 'Manage RA Bills';
$active_page = 'measurement_sheet';
ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
        <h2 style="margin:0;font-size:24px;font-weight:700;">Manage RA Bills</h2>
        <p style="margin:4px 0 0;color:#6b7280;font-size:14px;">View, edit, and manage all measurement sheet RA bills</p>
    </div>
    <a href="/ergon/finance/measurement-sheet" style="padding:10px 20px;background:#000080;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">
        ← Back to Measurement Sheets
    </a>
</div>

<?php if (isset($_GET['updated'])): ?>
<div style="background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;padding:12px;border-radius:8px;margin-bottom:20px;">
    ✅ RA Bill status updated successfully
</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
<div style="background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;padding:12px;border-radius:8px;margin-bottom:20px;">
    🗑️ RA Bill deleted successfully
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px;border-radius:8px;margin-bottom:20px;">
    ❌ <?= $_GET['error'] === 'delete' ? 'Cannot delete this RA Bill' : 'An error occurred' ?>
</div>
<?php endif; ?>

<!-- Filters -->
<div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:20px;">
    <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;align-items:end;">
        <div>
            <label style="display:block;margin-bottom:5px;font-weight:600;font-size:12px;color:#6b7280;">STATUS</label>
            <select name="status" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">
                <option value="">All Statuses</option>
                <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="submitted" <?= $filters['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
            </select>
        </div>
        
        <div>
            <label style="display:block;margin-bottom:5px;font-weight:600;font-size:12px;color:#6b7280;">PO NUMBER</label>
            <input type="text" name="po_number" value="<?= htmlspecialchars($filters['po_number']) ?>" 
                   placeholder="Search PO number" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">
        </div>
        
        <div>
            <label style="display:block;margin-bottom:5px;font-weight:600;font-size:12px;color:#6b7280;">DATE FROM</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" 
                   style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">
        </div>
        
        <div>
            <label style="display:block;margin-bottom:5px;font-weight:600;font-size:12px;color:#6b7280;">DATE TO</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" 
                   style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">
        </div>
        
        <div>
            <label style="display:block;margin-bottom:5px;font-weight:600;font-size:12px;color:#6b7280;">SEARCH</label>
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                   placeholder="RA number, project, contractor" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">
        </div>
        
        <div style="display:flex;gap:8px;">
            <button type="submit" style="padding:8px 16px;background:#000080;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;">
                Filter
            </button>
            <a href="/ergon/finance/measurement-sheet/manage" style="padding:8px 16px;background:#6b7280;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">
                Clear
            </a>
        </div>
    </form>
</div>

<!-- RA Bills Table -->
<div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;font-weight:700;font-size:16px;">
        RA Bills Management (<?= count($raBills) ?> records)
    </div>
    
    <?php if ($error): ?>
        <div style="padding:40px;text-align:center;color:#dc2626;">
            Error: <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif (empty($raBills)): ?>
        <div style="padding:40px;text-align:center;color:#9ca3af;">
            No RA Bills found matching your criteria
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                        <th style="padding:12px;text-align:left;font-weight:600;">RA Bill</th>
                        <th style="padding:12px;text-align:left;font-weight:600;">PO Number</th>
                        <th style="padding:12px;text-align:left;font-weight:600;">Project</th>
                        <th style="padding:12px;text-align:left;font-weight:600;">Contractor</th>
                        <th style="padding:12px;text-align:center;font-weight:600;">Status</th>
                        <th style="padding:12px;text-align:right;font-weight:600;">Amount</th>
                        <th style="padding:12px;text-align:center;font-weight:600;">Items</th>
                        <th style="padding:12px;text-align:center;font-weight:600;">Date</th>
                        <th style="padding:12px;text-align:center;font-weight:600;">Created By</th>
                        <th style="padding:12px;text-align:center;font-weight:600;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($raBills as $ra): ?>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:12px;">
                            <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($ra['ra_bill_number']) ?></div>
                            <div style="font-size:11px;color:#6b7280;">Seq: <?= $ra['ra_sequence'] ?></div>
                        </td>
                        <td style="padding:12px;">
                            <span style="font-weight:600;"><?= htmlspecialchars($ra['po_number']) ?></span>
                        </td>
                        <td style="padding:12px;">
                            <div style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($ra['project']) ?>">
                                <?= htmlspecialchars($ra['project'] ?: '—') ?>
                            </div>
                        </td>
                        <td style="padding:12px;">
                            <div style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($ra['contractor']) ?>">
                                <?= htmlspecialchars($ra['contractor'] ?: '—') ?>
                            </div>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <?php
                            $statusColors = [
                                'draft' => 'background:#fef3c7;color:#92400e;',
                                'submitted' => 'background:#dbeafe;color:#1e40af;',
                                'approved' => 'background:#d1fae5;color:#065f46;',
                                'rejected' => 'background:#fecaca;color:#dc2626;',
                                'paid' => 'background:#e0e7ff;color:#3730a3;'
                            ];
                            $statusStyle = $statusColors[$ra['status']] ?? 'background:#f3f4f6;color:#374151;';
                            ?>
                            <span style="<?= $statusStyle ?>padding:4px 8px;border-radius:12px;font-size:11px;font-weight:600;text-transform:uppercase;">
                                <?= htmlspecialchars($ra['status']) ?>
                            </span>
                        </td>
                        <td style="padding:12px;text-align:right;font-weight:600;">
                            ₹<?= number_format(floatval($ra['total_claimed']), 2) ?>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <span style="background:#f3f4f6;color:#374151;padding:2px 6px;border-radius:8px;font-size:11px;">
                                <?= $ra['item_count'] ?>
                            </span>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <?= date('d-M-Y', strtotime($ra['bill_date'])) ?>
                        </td>
                        <td style="padding:12px;text-align:center;font-size:11px;color:#6b7280;">
                            <?= htmlspecialchars($ra['created_by_name']) ?>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <div style="display:flex;gap:4px;justify-content:center;">
                                <!-- View Button -->
                                <a href="/ergon/finance/measurement-sheet/<?= $ra['id'] ?>/view" 
                                   style="padding:4px 8px;background:#f3f4f6;color:#374151;text-decoration:none;border-radius:4px;font-size:11px;" 
                                   title="View Details">👁️</a>
                                
                                <!-- Print Button -->
                                <a href="/ergon/finance/measurement-sheet/<?= $ra['id'] ?>/print" 
                                   style="padding:4px 8px;background:#dbeafe;color:#1e40af;text-decoration:none;border-radius:4px;font-size:11px;" 
                                   title="Print" target="_blank">🖨️</a>
                                
                                <!-- Status Update Button -->
                                <?php if (!in_array($ra['status'], ['paid'])): ?>
                                <button onclick="showStatusModal(<?= $ra['id'] ?>, '<?= $ra['ra_bill_number'] ?>', '<?= $ra['status'] ?>')" 
                                        style="padding:4px 8px;background:#fef3c7;color:#92400e;border:none;border-radius:4px;font-size:11px;cursor:pointer;" 
                                        title="Update Status">📝</button>
                                <?php endif; ?>
                                
                                <!-- Delete Button -->
                                <?php if (in_array($ra['status'], ['draft', 'rejected'])): ?>
                                <button onclick="confirmDelete(<?= $ra['id'] ?>, '<?= $ra['ra_bill_number'] ?>')" 
                                        style="padding:4px 8px;background:#fecaca;color:#dc2626;border:none;border-radius:4px;font-size:11px;cursor:pointer;" 
                                        title="Delete">🗑️</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Status Update Modal -->
<div id="statusModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:white;border-radius:12px;width:90%;max-width:500px;box-shadow:0 20px 40px rgba(0,0,0,0.3);">
        <div style="padding:20px;border-bottom:1px solid #e5e7eb;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">Update RA Bill Status</h3>
            <p style="margin:8px 0 0;color:#6b7280;font-size:14px;" id="statusModalSubtitle"></p>
        </div>
        
        <form id="statusForm" method="POST" style="padding:20px;">
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;">New Status:</label>
                <select name="status" id="statusSelect" required style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;">Notes (Optional):</label>
                <textarea name="notes" rows="3" placeholder="Add notes about this status change..." 
                          style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;resize:vertical;"></textarea>
            </div>
            
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" onclick="closeStatusModal()" 
                        style="padding:8px 16px;border:1px solid #e5e7eb;background:white;border-radius:6px;color:#374151;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" 
                        style="padding:8px 16px;background:#000080;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;">
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showStatusModal(id, raBillNumber, currentStatus) {
    document.getElementById('statusModalSubtitle').textContent = `Update status for ${raBillNumber}`;
    document.getElementById('statusSelect').value = currentStatus;
    document.getElementById('statusForm').action = `/ergon/finance/measurement-sheet/${id}/update-status`;
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

function confirmDelete(id, raBillNumber) {
    if (confirm(`⚠️ Are you sure you want to delete RA Bill "${raBillNumber}"?\n\nThis action cannot be undone and will remove all associated line items.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/ergon/finance/measurement-sheet/${id}/delete`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>