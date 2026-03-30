<?php
$title = 'Approved Expenses';
$active_page = 'approved_expenses';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1>✅ Approved Expenses</h1>
        <p>List of approved and processed expenses</p>
    </div>
    <div class="page-actions">
        <form method="GET" style="display:flex; gap:.5rem; align-items:center;">
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="paid" <?= (($_GET['status'] ?? '') === 'paid') ? 'selected' : '' ?>>Paid</option>
                <option value="unpaid" <?= (($_GET['status'] ?? '') === 'unpaid') ? 'selected' : '' ?>>Unpaid</option>
            </select>
            <label style="display:flex; align-items:center; gap:.25rem;"><input type="checkbox" name="deducted" value="1" <?= (($_GET['deducted'] ?? '') === '1') ? 'checked' : '' ?>/> Deducted from Advance</label>
            <button class="btn btn--primary" type="submit">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body">
        <?php require_once __DIR__ . '/../../app/helpers/ProofHelper.php'; ?>
        <?php if (empty($rows)): ?>
            <p>No approved expenses found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Approved By</th>
                            <th>Paid At</th>
                            <th>Proof</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                            <td><?= htmlspecialchars($r['user_name']) ?></td>
                            <td><?= htmlspecialchars($r['category']) ?></td>
                            <td>
                                <div>Claimed: ₹<?= number_format($r['claimed_amount'] ?? ($r['amount'] ?? 0),2) ?></div>
                                <div style="margin-top:.25rem;">Approved: <?= isset($r['approved_amount']) ? '₹' . number_format($r['approved_amount'],2) : '<em>—</em>' ?></div>
                            </td>
                            <td><?= htmlspecialchars($r['approved_by_name'] ?? $r['approved_by'] ?? '') ?></td>
                            <td><?= $r['paid_at'] ? date('M d, Y', strtotime($r['paid_at'])) : 'Unpaid' ?></td>
                            <td>
                                <?php if (!empty($r['payment_proof'])): ?>
                                    <?php echo proof_preview_html('/ergon/storage/proofs/' . $r['payment_proof'], 'Proof'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
