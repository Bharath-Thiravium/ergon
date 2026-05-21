<?php
$title = 'Database Backup & Restore';
$active_page = 'settings';
ob_start();
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <div>
        <h1 style="margin:0; font-size:1.4rem;">🗄️ Database Backup & Restore</h1>
        <p style="margin:0.25rem 0 0; color:var(--text-secondary); font-size:0.875rem;">
            Auto-backup runs daily at <strong>3:00 AM</strong>. Backups are kept for <?= $retention_days ?> days — day 46 deletes day 1 automatically.
        </p>
    </div>
    <div style="display:flex; gap:0.75rem; align-items:center;">
        <a href="/ergon/settings" class="btn btn--secondary">← Back to Settings</a>
        <button class="btn btn--secondary" onclick="runNow()" title="Trigger backup now (same as cron)">⚡ Run Now</button>
        <button class="btn btn--primary" onclick="openCreateModal()">➕ Create Backup</button>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert--error" style="margin-bottom:1rem;">❌ <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<!-- Schedule info banner -->
<div style="background:linear-gradient(135deg,#1e40af,#3b82f6); color:#fff; border-radius:8px; padding:1rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
    <div style="font-size:2rem;">⏰</div>
    <div style="flex:1;">
        <strong>Auto-Schedule Active</strong> &mdash; Backup runs every day at <strong>3:00 AM</strong><br>
        <span style="font-size:0.8rem; opacity:0.85;">Rolling 45-day window &bull; Day 46 automatically removes Day 1 &bull; Filename: <code style="background:rgba(255,255,255,0.2); padding:1px 6px; border-radius:3px;">backup_YYYY-MM-DD_auto.sql</code></span>
    </div>
    <div style="font-size:0.8rem; opacity:0.85; text-align:right;">
        Next run: <strong><?= date('d M Y', strtotime('tomorrow')) ?> 03:00 AM</strong>
    </div>
</div>

<!-- Stats row -->
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; margin-bottom:1.5rem;">
    <div class="card" style="padding:1rem; text-align:center;">
        <div style="font-size:1.75rem; font-weight:700; color:var(--primary);"><?= count($backups) ?></div>
        <div style="font-size:0.8rem; color:var(--text-secondary);">Total Backups</div>
    </div>
    <div class="card" style="padding:1rem; text-align:center;">
        <div style="font-size:1.75rem; font-weight:700; color:#10b981;"><?= $retention_days ?></div>
        <div style="font-size:0.8rem; color:var(--text-secondary);">Days Retention</div>
    </div>
    <div class="card" style="padding:1rem; text-align:center;">
        <div style="font-size:1.75rem; font-weight:700; color:#f59e0b;">
            <?= count(array_filter($backups, fn($b) => $b['age_days'] <= 1)) ?>
        </div>
        <div style="font-size:0.8rem; color:var(--text-secondary);">Created Today</div>
    </div>
    <div class="card" style="padding:1rem; text-align:center;">
        <div style="font-size:1.75rem; font-weight:700; color:#6366f1;">
            <?= $backups ? $backups[0]['size'] : '—' ?>
        </div>
        <div style="font-size:0.8rem; color:var(--text-secondary);">Latest Size</div>
    </div>
</div>

<!-- Backup list -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">📋 Backup History</h2>
    </div>
    <div class="card__body" style="padding:0;">
        <?php if (empty($backups)): ?>
        <div style="padding:3rem; text-align:center; color:var(--text-secondary);">
            <div style="font-size:3rem; margin-bottom:1rem;">🗄️</div>
            <p>No backups yet. Create your first backup now.</p>
            <button class="btn btn--primary" onclick="openCreateModal()">➕ Create Backup</button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Created</th>
                        <th>Size</th>
                        <th>Age</th>
                        <th>Expires In</th>
                        <th>Type</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $b): ?>
                    <tr>
                        <td style="font-family:monospace; font-size:0.8rem; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($b['filename']) ?>">
                            <?= htmlspecialchars($b['filename']) ?>
                        </td>
                        <td style="white-space:nowrap;"><?= $b['created'] ?></td>
                        <td><?= $b['size'] ?></td>
                        <td>
                            <span style="color:<?= $b['age_days'] <= 7 ? '#10b981' : ($b['age_days'] <= 30 ? '#f59e0b' : '#ef4444') ?>;">
                                <?= $b['age_days'] ?> day<?= $b['age_days'] != 1 ? 's' : '' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($b['expires_in'] <= 3): ?>
                                <span style="color:#ef4444; font-weight:600;">⚠️ <?= $b['expires_in'] ?> day<?= $b['expires_in'] != 1 ? 's' : '' ?></span>
                            <?php else: ?>
                                <?= $b['expires_in'] ?> days
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($b['is_auto']): ?>
                                <span style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:12px; font-size:0.75rem;">Pre-restore</span>
                            <?php elseif (strpos($b['filename'], '_auto.sql') !== false): ?>
                                <span style="background:#d1fae5; color:#065f46; padding:2px 8px; border-radius:12px; font-size:0.75rem;">⏰ Auto (Cron)</span>
                            <?php else: ?>
                                <span style="background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:12px; font-size:0.75rem;">Manual</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="ab-container">
                                <a href="/ergon/settings/backup/download/<?= urlencode($b['filename']) ?>" class="ab-btn ab-btn--view" title="Download">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </a>
                                <button class="ab-btn ab-btn--edit" onclick="confirmRestore('<?= htmlspecialchars($b['filename'], ENT_QUOTES) ?>')" title="Restore">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="1,4 1,10 7,10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                                </button>
                                <button class="ab-btn ab-btn--delete" onclick="confirmDelete('<?= htmlspecialchars($b['filename'], ENT_QUOTES) ?>')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Backup Modal -->
<div id="createBackupModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10001; align-items:center; justify-content:center;">
    <div style="background:var(--bg-primary); border-radius:10px; padding:2rem; width:90%; max-width:440px; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
        <h3 style="margin:0 0 1rem;">➕ Create New Backup</h3>
        <div class="form-group">
            <label class="form-label">Label (optional)</label>
            <input type="text" id="backupLabel" class="form-control" placeholder="e.g. before-update, monthly" maxlength="40">
            <small class="form-text">Alphanumeric and hyphens only. Appended to filename.</small>
        </div>
        <div id="createProgress" style="display:none; margin:1rem 0; text-align:center; color:var(--text-secondary);">
            <div style="font-size:2rem; margin-bottom:0.5rem;">⏳</div>
            Creating backup, please wait...
        </div>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end; margin-top:1.5rem;">
            <button class="btn btn--secondary" onclick="closeCreateModal()">Cancel</button>
            <button class="btn btn--primary" id="createBtn" onclick="createBackup()">🗄️ Create Backup</button>
        </div>
    </div>
</div>

<script>
function runNow() {
    if (!confirm('Run the automated backup now?\nThis creates the same backup the 3AM cron would create.')) return;
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '⏳ Running...';
    const fd = new FormData();
    fd.append('label', 'manual-run');
    fetch('/ergon/settings/backup/create', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ Backup created: ' + data.filename + ' (' + data.size + ')');
                location.reload();
            } else {
                alert('❌ ' + (data.error || 'Backup failed'));
                btn.disabled = false;
                btn.textContent = '⚡ Run Now';
            }
        })
        .catch(() => {
            alert('Network error.');
            btn.disabled = false;
            btn.textContent = '⚡ Run Now';
        });
}

function openCreateModal() {
    document.getElementById('createBackupModal').style.display = 'flex';
    document.getElementById('backupLabel').value = '';
    document.getElementById('createProgress').style.display = 'none';
    document.getElementById('createBtn').disabled = false;
}
function closeCreateModal() {
    document.getElementById('createBackupModal').style.display = 'none';
}

function createBackup() {
    const label = document.getElementById('backupLabel').value.replace(/[^a-zA-Z0-9_-]/g, '');
    document.getElementById('createProgress').style.display = 'block';
    document.getElementById('createBtn').disabled = true;

    const fd = new FormData();
    fd.append('label', label);

    fetch('/ergon/settings/backup/create', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeCreateModal();
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Backup failed'));
                document.getElementById('createProgress').style.display = 'none';
                document.getElementById('createBtn').disabled = false;
            }
        })
        .catch(() => {
            alert('Network error. Please try again.');
            document.getElementById('createProgress').style.display = 'none';
            document.getElementById('createBtn').disabled = false;
        });
}

function confirmRestore(filename) {
    if (!confirm('⚠️ RESTORE DATABASE?\n\nThis will overwrite ALL current data with the backup:\n"' + filename + '"\n\nA safety backup will be created first.\n\nAre you absolutely sure?')) return;
    if (!confirm('Second confirmation: This action cannot be undone. Proceed?')) return;

    fetch('/ergon/settings/backup/restore/' + encodeURIComponent(filename), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ Error: ' + (data.error || 'Restore failed'));
        }
    })
    .catch(() => alert('Network error during restore.'));
}

function confirmDelete(filename) {
    if (!confirm('Delete backup "' + filename + '"?\nThis cannot be undone.')) return;
    fetch('/ergon/settings/backup/delete/' + encodeURIComponent(filename), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert('Error: ' + (data.error || 'Delete failed'));
    });
}

// Close modal on backdrop click
document.getElementById('createBackupModal').addEventListener('click', function(e) {
    if (e.target === this) closeCreateModal();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
