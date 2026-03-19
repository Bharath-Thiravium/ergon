<?php
$title = 'Admin Direct Entry';
$active_page = 'admin-entry';
ob_start();
?>

<style>
/* ── Enhanced Entry Form Styles ───────────────────────────────────────────── */
.entry-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 var(--space-4);
}

/* ── Modern Tab System ───────────────────────────────────────────────────── */
.entry-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--border-color);
    margin-bottom: var(--space-6);
    background: var(--bg-primary);
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    overflow: hidden;
}

.entry-tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-3);
    padding: var(--space-5) var(--space-6);
    border: none;
    background: none;
    cursor: pointer;
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--text-secondary);
    border-bottom: 3px solid transparent;
    transition: var(--transition);
    position: relative;
}

.entry-tab-btn:hover:not(.active) {
    color: var(--text-primary);
    background: var(--bg-hover);
}

.entry-tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: rgba(59, 130, 246, 0.05);
}

.entry-tab-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
    opacity: 0;
    transition: opacity var(--transition-fast);
}

.entry-tab-btn.active::before {
    opacity: 1;
}

/* ── Enhanced Card Design ────────────────────────────────────────────────── */
.entry-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.entry-card__header {
    background: linear-gradient(135deg, var(--bg-secondary) 0%, rgba(59, 130, 246, 0.02) 100%);
    border-bottom: 1px solid var(--border-color);
    padding: var(--space-6);
}

.entry-card__title {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.entry-card__body {
    padding: var(--space-8);
}

/* ── Form Section Improvements ───────────────────────────────────────────── */
.form-section {
    margin-bottom: var(--space-8);
}

.form-section:last-child {
    margin-bottom: 0;
}

.form-section-title {
    font-size: var(--font-size-sm);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--primary);
    margin-bottom: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.form-section-title::before {
    content: '';
    width: 3px;
    height: 12px;
    background: var(--primary);
    border-radius: 2px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-6);
    margin-bottom: var(--space-6);
}

.form-row--single {
    grid-template-columns: 1fr;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.form-label {
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0;
}

.form-control {
    padding: var(--space-4);
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: var(--font-size-base);
    background: var(--bg-primary);
    color: var(--text-primary);
    transition: var(--transition);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control:invalid {
    border-color: var(--error);
}

.form-hint {
    font-size: var(--font-size-xs);
    color: var(--text-muted);
    margin-top: var(--space-1);
}

/* ── Enhanced Notice Banner ──────────────────────────────────────────────── */
.entry-notice {
    display: flex;
    align-items: flex-start;
    gap: var(--space-4);
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #f59e0b;
    border-left: 4px solid var(--warning);
    border-radius: var(--border-radius);
    padding: var(--space-5);
    margin: var(--space-6) 0;
    font-size: var(--font-size-sm);
    color: #92400e;
    line-height: 1.6;
    position: relative;
}

.entry-notice::before {
    content: '⚡';
    font-size: var(--font-size-2xl);
    flex-shrink: 0;
    margin-top: var(--space-1);
}

[data-theme="dark"] .entry-notice {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0.1) 100%);
    border-color: rgba(245, 158, 11, 0.4);
    color: #fcd34d;
}

/* ── Action Buttons ──────────────────────────────────────────────────────── */
.form-actions {
    display: flex;
    gap: var(--space-4);
    align-items: center;
    justify-content: flex-end;
    padding-top: var(--space-6);
    border-top: 1px solid var(--border-color);
    margin-top: var(--space-8);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-4) var(--space-6);
    border: 2px solid transparent;
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    min-height: 44px;
    justify-content: center;
}

.btn--primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border-color: var(--primary);
}

.btn--primary:hover:not(:disabled) {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
    transform: translateY(-1px);
    box-shadow: var(--shadow-lg);
}

.btn--secondary {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border-color: var(--border-color);
}

.btn--secondary:hover:not(:disabled) {
    background: var(--bg-tertiary);
    border-color: var(--gray-400);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* ── Bulk Upload Section ─────────────────────────────────────────────────── */
.bulk-section {
    margin-top: var(--space-10);
}

.bulk-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-6);
    flex-wrap: wrap;
    gap: var(--space-4);
}

.bulk-title {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

/* ── Sample Download Strip ──────────────────────────────────────────────── */
.sample-strip {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    background: linear-gradient(135deg, var(--bg-secondary) 0%, rgba(59, 130, 246, 0.02) 100%);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--space-5);
    margin-bottom: var(--space-6);
    flex-wrap: wrap;
}

.sample-strip-label {
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--text-secondary);
    flex: 1;
    min-width: 200px;
}

.sample-strip .btn {
    padding: var(--space-3) var(--space-5);
    font-size: var(--font-size-xs);
    font-weight: 600;
    border-radius: 6px;
    text-decoration: none;
    transition: var(--transition);
}

.sample-strip .btn:hover {
    transform: translateY(-1px);
}

/* ── Enhanced Drop Zone ─────────────────────────────────────────────────── */
.drop-zone {
    border: 2px dashed var(--border-color);
    border-radius: var(--border-radius-lg);
    padding: var(--space-12) var(--space-8);
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    background: linear-gradient(135deg, var(--bg-secondary) 0%, rgba(59, 130, 246, 0.01) 100%);
    position: relative;
    margin-bottom: var(--space-6);
}

.drop-zone:hover:not(.dragover) {
    border-color: var(--gray-400);
    background: linear-gradient(135deg, var(--bg-tertiary) 0%, rgba(59, 130, 246, 0.02) 100%);
}

.drop-zone.dragover {
    border-color: var(--primary);
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
    transform: scale(1.02);
}

.drop-zone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.drop-zone-icon {
    font-size: 3rem;
    margin-bottom: var(--space-4);
    color: var(--text-muted);
    transition: var(--transition);
}

.drop-zone.dragover .drop-zone-icon {
    color: var(--primary);
    transform: scale(1.1);
}

.drop-zone-text {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--space-2);
}

.drop-zone-sub {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    margin-bottom: var(--space-4);
}

.drop-zone-file {
    font-size: var(--font-size-sm);
    color: var(--primary);
    font-weight: 600;
    margin-top: var(--space-4);
    display: none;
    padding: var(--space-2) var(--space-4);
    background: rgba(59, 130, 246, 0.1);
    border-radius: 20px;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

/* ── Column Reference ───────────────────────────────────────────────────── */
.column-ref {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--space-5);
    margin-top: var(--space-4);
}

.column-ref-title {
    font-size: var(--font-size-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-secondary);
    margin-bottom: var(--space-2);
}

.column-code {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 11px;
    background: var(--bg-primary);
    padding: var(--space-3) var(--space-4);
    border-radius: 6px;
    border: 1px solid var(--border-color);
    line-height: 1.6;
    color: var(--text-primary);
}

/* ── Validation Report ──────────────────────────────────────────────────── */
.validation-report {
    margin-top: var(--space-6);
    display: none;
}
.val-banner {
    display: flex; align-items: center; gap: var(--space-3);
    padding: var(--space-4) var(--space-5);
    border-radius: var(--border-radius);
    margin-bottom: var(--space-4);
    font-size: var(--font-size-sm); font-weight: 600;
    border-left: 4px solid;
}
.val-banner.pass { background:#d1fae5; border-color:#10b981; color:#065f46; }
.val-banner.fail { background:#fee2e2; border-color:#ef4444; color:#991b1b; }
.val-banner.warn { background:#fef3c7; border-color:#f59e0b; color:#92400e; }
[data-theme="dark"] .val-banner.pass { background:#064e3b; color:#6ee7b7; }
[data-theme="dark"] .val-banner.fail { background:#7f1d1d; color:#fca5a5; }
[data-theme="dark"] .val-banner.warn { background:#2d2000; color:#fcd34d; }
.val-summary {
    display: flex; gap: var(--space-3); flex-wrap: wrap; margin-bottom: var(--space-4);
}
.val-stat {
    display: flex; align-items: center; gap: var(--space-2);
    padding: var(--space-2) var(--space-4);
    border-radius: 20px; font-size: var(--font-size-xs); font-weight: 700;
    border: 1px solid transparent;
}
.val-stat.total { background:var(--bg-secondary); color:var(--text-primary); border-color:var(--border-color); }
.val-stat.ok    { background:#d1fae5; color:#065f46; border-color:#6ee7b7; }
.val-stat.warn  { background:#fef3c7; color:#92400e; border-color:#fcd34d; }
.val-stat.err   { background:#fee2e2; color:#991b1b; border-color:#fca5a5; }
[data-theme="dark"] .val-stat.ok   { background:#064e3b; color:#6ee7b7; }
[data-theme="dark"] .val-stat.warn { background:#2d2000; color:#fcd34d; }
[data-theme="dark"] .val-stat.err  { background:#7f1d1d; color:#fca5a5; }
.val-row-ok   td:first-child { color:#059669; font-weight:700; }
.val-row-warn td:first-child { color:#d97706; font-weight:700; }
.val-row-err  td:first-child { color:#dc2626; font-weight:700; }
.val-issues { font-size:11px; line-height:1.6; }
.val-issues .err-item  { color:#dc2626; }
.val-issues .warn-item { color:#d97706; }

/* ── Upload Results ─────────────────────────────────────────────────────── */
.upload-results {
    margin-top: var(--space-8);
    display: none;
}

.results-header {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.upload-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--space-4);
    margin-bottom: var(--space-6);
}

.upload-stat {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-4);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    font-weight: 600;
    text-align: center;
    justify-content: center;
}

.upload-stat.success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.upload-stat.failed {
    background: rgba(239, 68, 68, 0.1);
    color: var(--error);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.result-row-success td:first-child {
    color: var(--success);
    font-weight: 700;
}

.result-row-failed td:first-child {
    color: var(--error);
    font-weight: 700;
}

/* ── Responsive Design ──────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .entry-container {
        padding: 0 var(--space-3);
    }

    .entry-tab-btn {
        padding: var(--space-4) var(--space-3);
        font-size: var(--font-size-base);
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: var(--space-4);
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
    }

    .sample-strip {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-3);
    }

    .sample-strip-label {
        min-width: auto;
    }

    .bulk-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .drop-zone {
        padding: var(--space-8) var(--space-4);
    }

    .upload-summary {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .entry-card__body {
        padding: var(--space-6);
    }

    .form-section {
        margin-bottom: var(--space-6);
    }

    .entry-notice {
        padding: var(--space-4);
        flex-direction: column;
        gap: var(--space-3);
        text-align: center;
    }

    .entry-notice::before {
        align-self: center;
        margin-top: 0;
    }
}

/* ── Dark Theme Enhancements ────────────────────────────────────────────── */
[data-theme="dark"] .entry-card__header {
    background: linear-gradient(135deg, var(--bg-tertiary) 0%, rgba(59, 130, 246, 0.05) 100%);
}

[data-theme="dark"] .sample-strip {
    background: linear-gradient(135deg, var(--bg-tertiary) 0%, rgba(59, 130, 246, 0.02) 100%);
}

[data-theme="dark"] .drop-zone {
    background: linear-gradient(135deg, var(--bg-tertiary) 0%, rgba(59, 130, 246, 0.01) 100%);
}

[data-theme="dark"] .drop-zone:hover:not(.dragover) {
    background: linear-gradient(135deg, var(--bg-secondary) 0%, rgba(59, 130, 246, 0.02) 100%);
}

[data-theme="dark"] .column-ref {
    background: var(--bg-tertiary);
}

/* ── Loading States ─────────────────────────────────────────────────────── */
.btn--loading {
    position: relative;
    color: transparent !important;
}

.btn--loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ── Focus and Accessibility ────────────────────────────────────────────── */
.entry-tab-btn:focus-visible,
.btn:focus-visible,
.form-control:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

/* ── Print Styles ───────────────────────────────────────────────────────── */
@media print {
    .entry-tabs,
    .form-actions,
    .bulk-section,
    .sample-strip {
        display: none !important;
    }

    .entry-card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<div class="entry-container">
    <!-- ════════════════════════════════════════════════════════════════════════
         PAGE HEADER
    ═════════════════════════════════════════════════════════════════════════ -->
    <div class="page-header">
        <div class="page-title">
            <h1><span>📝</span> Admin Direct Entry</h1>
            <p>Record advances or expenses on behalf of employees — saved as paid instantly</p>
        </div>
        <div class="page-actions">
            <a href="/ergon/advances" class="btn btn--secondary"><span>💳</span> View Advances</a>
            <a href="/ergon/expenses" class="btn btn--secondary"><span>💰</span> View Expenses</a>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════
         SINGLE ENTRY CARD
    ═════════════════════════════════════════════════════════════════════════ -->
    <div class="entry-card">
        <div class="entry-card__header">
            <div class="entry-tabs">
                <button id="tab-btn-advance" class="entry-tab-btn active" onclick="switchTab('advance')">
                    <span>💳</span> Advance Entry
                </button>
                <button id="tab-btn-expense" class="entry-tab-btn" onclick="switchTab('expense')">
                    <span>💰</span> Expense Entry
                </button>
            </div>
        </div>

        <div class="entry-card__body">
            <!-- ── ADVANCE FORM ── -->
            <form id="advanceForm" novalidate>
                <input type="hidden" name="entry_type" value="advance">

                <div class="form-section">
                    <h3 class="form-section-title">👤 Employee & Type</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="adv_user_id">Employee <span style="color: var(--error);">*</span></label>
                            <select id="adv_user_id" name="user_id" class="form-control" required>
                                <option value="">— Select Employee —</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= ucfirst($u['role']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="adv_type">Advance Type <span style="color: var(--error);">*</span></label>
                            <select id="adv_type" name="advance_type" class="form-control" required>
                                <option value="Salary Advance">💰 Salary Advance</option>
                                <option value="Travel Advance">✈️ Travel Advance</option>
                                <option value="Emergency Advance">🚨 Emergency Advance</option>
                                <option value="Project Advance">📋 Project Advance</option>
                                <option value="General Advance">📦 General Advance</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">💵 Amount & Project</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="adv_amount">Amount (₹) <span style="color: var(--error);">*</span></label>
                            <input type="number" id="adv_amount" name="amount" class="form-control" step="0.01" min="1" required placeholder="0.00">
                            <div class="form-hint">Enter amount in rupees (minimum ₹1.00)</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="adv_project">Project <span class="form-hint" style="display: inline; margin-left: var(--space-2);">(optional)</span></label>
                            <select id="adv_project" name="project_id" class="form-control">
                                <option value="">— No Project —</option>
                                <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">📅 Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="adv_date">Advance Date <span style="color: var(--error);">*</span></label>
                            <input type="date" id="adv_date" name="advance_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="adv_repayment">Repayment Date <span class="form-hint" style="display: inline; margin-left: var(--space-2);">(optional)</span></label>
                            <input type="date" id="adv_repayment" name="repayment_date" class="form-control">
                            <div class="form-hint">When the advance should be repaid</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="adv_reason">Reason <span style="color: var(--error);">*</span></label>
                        <textarea id="adv_reason" name="reason" class="form-control" rows="4" required placeholder="Describe the purpose of this advance..."></textarea>
                        <div class="form-hint">Provide a clear reason for the advance request</div>
                    </div>
                </div>

                <div class="entry-notice">
                    <div>
                        <strong>Instant Payment:</strong> This entry will be saved directly as <strong>Paid</strong> — bypassing the approval workflow. The ledger will be updated immediately.
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--primary" id="adv-submit-btn">
                        <span>💳</span> Save Advance Entry
                    </button>
                    <button type="button" class="btn btn--secondary" onclick="resetSingleForm('advanceForm', 'adv_date')">
                        <span>↺</span> Clear Form
                    </button>
                </div>
            </form>

            <!-- ── EXPENSE FORM ── -->
            <form id="expenseForm" novalidate style="display:none;">
                <input type="hidden" name="entry_type" value="expense">

                <div class="form-section">
                    <h3 class="form-section-title">👤 Employee & Category</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="exp_user_id">Employee <span style="color: var(--error);">*</span></label>
                            <select id="exp_user_id" name="user_id" class="form-control" required>
                                <option value="">— Select Employee —</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= ucfirst($u['role']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="exp_category">Category <span style="color: var(--error);">*</span></label>
                            <select id="exp_category" name="category" class="form-control" required>
                                <option value="travel">🚗 Travel & Transportation</option>
                                <option value="food">🍽️ Food & Meals</option>
                                <option value="accommodation">🏨 Accommodation</option>
                                <option value="office_supplies">📋 Office Supplies</option>
                                <option value="communication">📱 Communication</option>
                                <option value="training">📚 Training & Development</option>
                                <option value="medical">🏥 Medical Expenses</option>
                                <option value="other">📦 Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">💵 Amount, Date & Project</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="exp_amount">Amount (₹) <span style="color: var(--error);">*</span></label>
                            <input type="number" id="exp_amount" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                            <div class="form-hint">Enter amount in rupees (minimum ₹0.01)</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="exp_date">Expense Date <span style="color: var(--error);">*</span></label>
                            <input type="date" id="exp_date" name="expense_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="exp_project">Project <span class="form-hint" style="display: inline; margin-left: var(--space-2);">(optional)</span></label>
                        <select id="exp_project" name="project_id" class="form-control">
                            <option value="">— No Project —</option>
                            <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">📝 Description</h3>
                    <div class="form-group">
                        <label class="form-label" for="exp_description">Description <span style="color: var(--error);">*</span></label>
                        <textarea id="exp_description" name="description" class="form-control" rows="4" required placeholder="Describe the expense in detail..."></textarea>
                        <div class="form-hint">Provide a detailed description of the expense</div>
                    </div>
                </div>

                <div class="entry-notice">
                    <div>
                        <strong>Instant Payment:</strong> This entry will be saved directly as <strong>Paid</strong> — bypassing the approval workflow. The ledger will be updated immediately.
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--primary" id="exp-submit-btn">
                        <span>💰</span> Save Expense Entry
                    </button>
                    <button type="button" class="btn btn--secondary" onclick="resetSingleForm('expenseForm', 'exp_date')">
                        <span>↺</span> Clear Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════
         BULK UPLOAD CARD
    ═════════════════════════════════════════════════════════════════════════ -->
    <div class="entry-card bulk-section">
        <div class="entry-card__header">
            <div class="bulk-header">
                <h2 class="bulk-title"><span>📤</span> Bulk Upload via CSV</h2>
                <div style="display: flex; gap: var(--space-3); align-items: center;">
                    <label class="form-label" style="margin: 0;">Entry Type:</label>
                    <select id="bulk_type" class="form-control" style="width: auto; min-width: 140px;">
                        <option value="advance">💳 Advances</option>
                        <option value="expense">💰 Expenses</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="entry-card__body">
            <!-- Sample download strip -->
            <div class="sample-strip">
                <span class="sample-strip-label">📄 Download sample files to fill and upload:</span>
                <div style="display: flex; gap: var(--space-3); flex-wrap: wrap;">
                    <a href="/ergon/admin/sample-csv/advances" class="btn btn--primary" download>
                        <span>⬇️</span> Advances Sample CSV
                    </a>
                    <a href="/ergon/admin/sample-csv/expenses" class="btn btn--primary" download>
                        <span>⬇️</span> Expenses Sample CSV
                    </a>
                </div>
            </div>

            <!-- Drop zone -->
            <div class="drop-zone" id="dropZone">
                <input type="file" id="csvFileInput" accept=".csv,.txt">
                <div class="drop-zone-icon">📂</div>
                <div class="drop-zone-text">Drag & drop your CSV file here</div>
                <div class="drop-zone-sub">or click to browse — .csv files only (max 10MB)</div>
                <div class="drop-zone-file" id="selectedFileName"></div>
            </div>

            <!-- Column reference -->
            <div id="colRef-advance" class="column-ref">
                <div class="column-ref-title">Required columns for Advances:</div>
                <div class="column-code">
                    employee_name &nbsp;|&nbsp; advance_type &nbsp;|&nbsp; amount &nbsp;|&nbsp; reason &nbsp;|&nbsp; advance_date <em>(DD-MM-YYYY)</em> &nbsp;|&nbsp; repayment_date <em>(DD-MM-YYYY, optional)</em> &nbsp;|&nbsp; project_name <em>(optional)</em>
                </div>
            </div>
            <div id="colRef-expense" class="column-ref" style="display:none;">
                <div class="column-ref-title">Required columns for Expenses:</div>
                <div class="column-code">
                    employee_name &nbsp;|&nbsp; category &nbsp;|&nbsp; amount &nbsp;|&nbsp; description &nbsp;|&nbsp; expense_date <em>(DD-MM-YYYY)</em> &nbsp;|&nbsp; project_name <em>(optional)</em>
                </div>
            </div>

            <div class="entry-notice">
                <div>
                    <strong>Bulk Processing:</strong> All rows will be saved directly as <strong>Paid</strong>. Employee names must exactly match the names in the system. Unmatched rows will be skipped and reported.
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn--secondary" id="validateBtn" onclick="validateCsv()">
                    <span>🔍</span> Validate File
                </button>
                <button type="button" class="btn btn--primary" id="bulkUploadBtn" onclick="submitBulkUpload()" disabled>
                    <span>📤</span> Upload & Process
                </button>
                <button type="button" class="btn btn--secondary" onclick="clearBulkUpload()">
                    <span>↺</span> Clear
                </button>
            </div>

            <!-- Validation Report -->
            <div class="validation-report" id="validationReport">
                <div id="valBanner"></div>
                <div class="val-summary" id="valSummary"></div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Row #</th>
                                <th>Employee</th>
                                <th>Amount</th>
                                <th>Issues</th>
                            </tr>
                        </thead>
                        <tbody id="valBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Results -->
            <div class="upload-results" id="uploadResults">
                <h3 class="results-header"><span>📊</span> Upload Results</h3>
                <div class="upload-summary" id="uploadSummary"></div>
                <div class="table-responsive">
                    <table class="table" id="resultsTable">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Row #</th>
                                <th>Employee</th>
                                <th>Amount</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody id="resultsBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* ── Enhanced Tab Switching ──────────────────────────────────────────────── */
function switchTab(tab) {
    const isAdv = tab === 'advance';
    document.getElementById('advanceForm').style.display = isAdv ? 'block' : 'none';
    document.getElementById('expenseForm').style.display  = isAdv ? 'none'  : 'block';
    document.getElementById('tab-btn-advance').classList.toggle('active', isAdv);
    document.getElementById('tab-btn-expense').classList.toggle('active', !isAdv);

    // Smooth scroll to top of form
    document.querySelector('.entry-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetSingleForm(formId, ...dateFieldIds) {
    const form = document.getElementById(formId);
    form.reset();

    // Reset date fields to today
    dateFieldIds.forEach(id => {
        const field = document.getElementById(id);
        if (field) field.value = new Date().toISOString().split('T')[0];
    });

    // Clear any validation states
    form.querySelectorAll('.form-control').forEach(el => {
        el.classList.remove('is-invalid');
    });
}

/* ── Enhanced Form Submission ────────────────────────────────────────────── */
function submitEntry(form, btnId) {
    const btn = document.getElementById(btnId);
    const originalHTML = btn.innerHTML;

    // Prevent double submission
    if (btn.disabled) return;

    btn.disabled = true;
    btn.classList.add('btn--loading');
    btn.innerHTML = '<span>⏳</span> Saving…';

    fetch('/ergon/admin/entry', {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message || 'Entry saved successfully!');
            form.reset();
            form.querySelectorAll('input[type="date"]').forEach(dateInput => {
                if (dateInput.name === 'expense_date' || dateInput.name === 'advance_date') {
                    dateInput.value = new Date().toISOString().split('T')[0];
                }
            });
        } else {
            showError(data.error || 'Failed to save entry');
        }
    })
    .catch(() => {
        showError('Network error — please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.classList.remove('btn--loading');
        btn.innerHTML = originalHTML;
    });
}

document.getElementById('advanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!this.checkValidity()) {
        this.reportValidity();
        return;
    }
    submitEntry(this, 'adv-submit-btn');
});

document.getElementById('expenseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!this.checkValidity()) {
        this.reportValidity();
        return;
    }
    submitEntry(this, 'exp-submit-btn');
});

/* ── Enhanced Bulk Upload ───────────────────────────────────────────────── */
const dropZone   = document.getElementById('dropZone');
const fileInput  = document.getElementById('csvFileInput');
const fileLabel  = document.getElementById('selectedFileName');

dropZone.addEventListener('dragover',  e => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files[0]) {
        fileInput.files = files;
        showFileName(files[0].name);
    }
});

fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) {
        showFileName(fileInput.files[0].name);
        // Reset validation when a new file is picked
        document.getElementById('validationReport').style.display = 'none';
        document.getElementById('bulkUploadBtn').disabled = true;
    }
});

function showFileName(name) {
    fileLabel.textContent = `📎 ${name}`;
    fileLabel.style.display = 'block';
    dropZone.querySelector('.drop-zone-text').textContent = 'File selected successfully';
    dropZone.querySelector('.drop-zone-sub').style.display = 'none';
}

/* ── Column Reference Toggle ────────────────────────────────────────────── */
document.getElementById('bulk_type').addEventListener('change', function() {
    const isAdv = this.value === 'advance';
    document.getElementById('colRef-advance').style.display = isAdv ? 'block' : 'none';
    document.getElementById('colRef-expense').style.display = isAdv ? 'none'  : 'block';
    // Reset validation when type changes
    document.getElementById('validationReport').style.display = 'none';
    document.getElementById('bulkUploadBtn').disabled = true;
});

/* ── CSV Validation ─────────────────────────────────────────────────────── */
function validateCsv() {
    if (!fileInput.files[0]) { showError('Please select a CSV file first.'); return; }

    const btn = document.getElementById('validateBtn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Validating…';

    // Reset upload button until re-validated
    document.getElementById('bulkUploadBtn').disabled = true;
    document.getElementById('uploadResults').style.display = 'none';

    const fd = new FormData();
    fd.append('csv_file', fileInput.files[0]);
    fd.append('bulk_type', document.getElementById('bulk_type').value);

    fetch('/ergon/admin/validate-csv', { method:'POST', body: fd, credentials:'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showError(data.error || 'Validation failed'); return; }
            renderValidation(data);
        })
        .catch(() => showError('Network error — please try again.'))
        .finally(() => { btn.disabled = false; btn.innerHTML = orig; });
}

function renderValidation(data) {
    const report  = document.getElementById('validationReport');
    const banner  = document.getElementById('valBanner');
    const summary = document.getElementById('valSummary');
    const tbody   = document.getElementById('valBody');
    const s       = data.summary;

    if (data.fatal) {
        banner.innerHTML = `<div class="val-banner fail">❌ ${data.fatal}</div>`;
        report.style.display = 'block';
        return;
    }

    // Banner
    if (s.errors === 0 && s.total > 0) {
        const cls = s.warnings > 0 ? 'warn' : 'pass';
        const icon = s.warnings > 0 ? '⚠️' : '✅';
        const msg  = s.warnings > 0
            ? `${s.ok} rows ready, ${s.warnings} with warnings — review before uploading`
            : `All ${s.total} rows passed validation — ready to upload`;
        banner.innerHTML = `<div class="val-banner ${cls}">${icon} ${msg}</div>`;
        document.getElementById('bulkUploadBtn').disabled = false;
    } else if (s.total === 0) {
        banner.innerHTML = `<div class="val-banner fail">❌ No data rows found in the file</div>`;
    } else {
        banner.innerHTML = `<div class="val-banner fail">❌ ${s.errors} error(s) found — fix them before uploading</div>`;
        document.getElementById('bulkUploadBtn').disabled = true;
    }

    // Summary pills
    summary.innerHTML = `
        <span class="val-stat total">📋 ${s.total} Total</span>
        <span class="val-stat ok">✅ ${s.ok} OK</span>
        ${s.warnings ? `<span class="val-stat warn">⚠️ ${s.warnings} Warnings</span>` : ''}
        ${s.errors   ? `<span class="val-stat err">❌ ${s.errors} Errors</span>` : ''}
    `;

    // Row table — only show rows that have issues (warnings or errors)
    const issueRows = (data.rows || []).filter(r => r.status !== 'ok');
    if (issueRows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--text-secondary);padding:16px;">✅ All rows are valid — no issues found</td></tr>`;
    } else {
        tbody.innerHTML = issueRows.map(r => {
            const cls = r.status === 'error' ? 'val-row-err' : 'val-row-warn';
            const icon = r.status === 'error' ? '❌' : '⚠️';
            const errHtml  = (r.errors  || []).map(e => `<div class="err-item">❌ ${e}</div>`).join('');
            const warnHtml = (r.warnings|| []).map(w => `<div class="warn-item">⚠️ ${w}</div>`).join('');
            return `<tr class="${cls}">
                <td>${icon} ${r.status === 'error' ? 'Error' : 'Warning'}</td>
                <td>${r.row}</td>
                <td>${r.employee || '—'}</td>
                <td>${r.amount ? '₹' + parseFloat(r.amount).toLocaleString('en-IN',{minimumFractionDigits:2}) : '—'}</td>
                <td><div class="val-issues">${errHtml}${warnHtml}</div></td>
            </tr>`;
        }).join('');
    }

    report.style.display = 'block';
    report.scrollIntoView({ behavior:'smooth', block:'start' });
}

/* ── Enhanced Bulk Upload Submission ────────────────────────────────────── */
function submitBulkUpload() {
    if (!fileInput.files[0]) {
        showError('Please select a CSV file first.');
        return;
    }

    // Check file size (10MB limit)
    if (fileInput.files[0].size > 10 * 1024 * 1024) {
        showError('File size must be less than 10MB.');
        return;
    }

    const btn = document.getElementById('bulkUploadBtn');
    const originalHTML = btn.innerHTML;

    btn.disabled = true;
    btn.classList.add('btn--loading');
    btn.innerHTML = '<span>⏳</span> Processing…';

    const fd = new FormData();
    fd.append('csv_file', fileInput.files[0]);
    fd.append('bulk_type', document.getElementById('bulk_type').value);

    fetch('/ergon/admin/bulk-upload', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success && !data.rows) {
            showError(data.error || 'Upload failed');
            return;
        }
        renderResults(data);
    })
    .catch(() => {
        showError('Network error — please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.classList.remove('btn--loading');
        btn.innerHTML = originalHTML;
    });
}

function renderResults(data) {
    const wrap = document.getElementById('uploadResults');
    const summary = document.getElementById('uploadSummary');
    const tbody = document.getElementById('resultsBody');

    summary.innerHTML = `
        <div class="upload-stat success">
            <span>✅</span> ${data.inserted ?? 0} Inserted
        </div>
        <div class="upload-stat failed">
            <span>❌</span> ${data.failed ?? 0} Failed
        </div>
    `;

    tbody.innerHTML = (data.rows || []).map(r => {
        const ok = r.status === 'success';
        return `<tr class="${ok ? 'result-row-success' : 'result-row-failed'}">
            <td>${ok ? '✅ Success' : '❌ Failed'}</td>
            <td>${r.row}</td>
            <td>${r.employee ?? '—'}</td>
            <td>${r.amount ? '₹' + parseFloat(r.amount).toLocaleString('en-IN', {minimumFractionDigits:2}) : '—'}</td>
            <td>${r.reason ?? ''}</td>
        </tr>`;
    }).join('');

    wrap.style.display = 'block';
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });

    if ((data.inserted ?? 0) > 0) {
        showSuccess(`${data.inserted} record(s) uploaded successfully!`);
    }
}

function clearBulkUpload() {
    fileInput.value = '';
    fileLabel.style.display = 'none';
    dropZone.querySelector('.drop-zone-text').textContent = 'Drag & drop your CSV file here';
    dropZone.querySelector('.drop-zone-sub').style.display = 'block';
    document.getElementById('uploadResults').style.display = 'none';
    document.getElementById('resultsBody').innerHTML = '';
    document.getElementById('validationReport').style.display = 'none';
    document.getElementById('valBody').innerHTML = '';
    document.getElementById('bulkUploadBtn').disabled = true;
}

/* ── Utility Functions ──────────────────────────────────────────────────── */
function showSuccess(message) {
    // Assuming there's a global showSuccess function or use a simple alert
    if (typeof showSuccess === 'function') {
        showSuccess(message);
    } else {
        alert('Success: ' + message);
    }
}

function showError(message) {
    // Assuming there's a global showError function or use a simple alert
    if (typeof showError === 'function') {
        showError(message);
    } else {
        alert('Error: ' + message);
    }
}

/* ── Initialize ─────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    // Set initial date values
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('adv_date').value = today;
    document.getElementById('exp_date').value = today;
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
