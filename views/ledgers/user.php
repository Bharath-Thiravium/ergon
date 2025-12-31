<?php
$title = 'User Ledger';
$active_page = 'ledgers';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1>📒 User Ledger</h1>
        <p>Ledger entries (credits = advances paid to user, debits = expenses deducted)</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/users" class="btn btn--secondary">← Back to Users</a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Current Balance: ₹<?= number_format($balance ?? 0,2) ?></h2>
    </div>
    <div class="card__body">
        <?php if (empty($entries)): ?>
            <p>No ledger entries found for this user.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Direction</th>
                            <th>Amount</th>
                            <th>Balance After</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $e): ?>
                        <tr>
                            <td><?= date('M d, Y H:i', strtotime($e['created_at'])) ?></td>
                            <td><?= htmlspecialchars($e['entry_type']) ?></td>
                            <td><?= htmlspecialchars($e['reference_type'] . ' #' . $e['reference_id']) ?></td>
                            <td><?= htmlspecialchars($e['description'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($e['direction']) ?></td>
                            <td>₹<?= number_format($e['amount'],2) ?></td>
                            <td>₹<?= number_format($e['balance_after'],2) ?></td>
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
