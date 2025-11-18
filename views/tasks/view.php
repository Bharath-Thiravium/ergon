<?php
$title = 'Task Details';
$active_page = 'tasks';
ob_start();
?>

<style>
.progress-fill-mini { transition: none !important; }
.progress-fill { transition: none !important; }
</style>

<div class="page-header">
    <div class="page-title">
        <h1><span>✅</span> Task Details</h1>
        <p>View task information and progress</p>
    </div>
    <div class="page-actions">
        <?php if ($task['status'] !== 'completed'): ?>
        <button onclick="toggleProgressUpdate()" class="btn btn--primary">
            <span>📊</span> Update Progress
        </button>
        <?php endif; ?>
        <button onclick="showTaskHistory(<?= $task['id'] ?>)" class="btn btn--info">
            <span>📋</span> History
        </button>
        <a href="/ergon/tasks/edit/<?= $task['id'] ?? '' ?>" class="btn btn--secondary">
            <span>✏️</span> Edit Task
        </a>
        <a href="/ergon/tasks" class="btn btn--secondary">
            <span>←</span> Back to Tasks
        </a>
    </div>
</div>

<div class="task-compact">
    <div class="card">
        <div class="card__header">
            <div class="task-title-row">
                <h2 class="task-title">📋 <?= htmlspecialchars($task['title'] ?? 'Task') ?></h2>
                <div class="task-badges">
                    <?php 
                    $progress = $task['progress'] ?? 0;
                    $status = $task['status'] ?? 'assigned';
                    $statusClass = match($status) {
                        'completed' => 'success',
                        'in_progress' => 'info', 
                        'blocked' => 'danger',
                        default => 'warning'
                    };
                    $statusIcon = match($status) {
                        'completed' => '✅',
                        'in_progress' => '⚡',
                        'blocked' => '🚫',
                        default => '📋'
                    };
                    ?>
                    <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                    <div class="progress-mini">
                        <div class="progress-bar-mini">
                            <div class="progress-fill-mini" style="width: <?= $progress ?>% !important; transition: none !important; height: 100% !important;" data-progress="<?= match(true) { $progress == 0 => '0', $progress >= 100 => '100', $progress >= 75 => '75-99', $progress >= 50 => '50-74', $progress >= 25 => '25-49', default => '1-24' } ?>"></div>
                        </div>
                        <span class="progress-text"><?= $progress ?>%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card__body">
            <?php if ($task['description']): ?>
            <div class="description-compact">
                <strong>Description:</strong> <?= nl2br(htmlspecialchars($task['description'])) ?>
            </div>
            <?php endif; ?>
            
            <div class="details-compact">
                <div class="detail-group">
                    <h4>👥 Assignment</h4>
                    <div class="detail-items">
                        <span><strong>To:</strong> 👤 <?= htmlspecialchars($task['assigned_user'] ?? $task['assigned_to_name'] ?? 'Unassigned') ?></span>
                        <span><strong>By:</strong> 👨💼 <?= htmlspecialchars($task['assigned_by_name'] ?? 'System') ?></span>
                        <span><strong>Priority:</strong> 
                            <?php 
                            $priority = $task['priority'] ?? 'medium';
                            $priorityClass = match($priority) {
                                'high' => 'danger',
                                'medium' => 'warning',
                                default => 'info'
                            };
                            $priorityIcon = match($priority) {
                                'high' => '🔴',
                                'medium' => '🟡', 
                                default => '🟢'
                            };
                            ?>
                            <span class="badge badge--<?= $priorityClass ?>"><?= $priorityIcon ?> <?= ucfirst($priority) ?></span>
                        </span>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>📅 Timeline</h4>
                    <div class="detail-items">
                        <span><strong>Due:</strong> 
                            <?php if ($task['deadline'] ?? $task['due_date']): ?>
                                📅 <?= date('M d, Y', strtotime($task['deadline'] ?? $task['due_date'])) ?>
                            <?php else: ?>
                                <span class="text-muted">No due date</span>
                            <?php endif; ?>
                        </span>
                        <span><strong>SLA:</strong> ⏱️ <?= htmlspecialchars($task['sla_hours'] ?? '24') ?>h</span>
                        <span><strong>Assigned for:</strong> 📅 <?= ($task['assigned_at'] ?? $task['created_at']) ? date('M d, Y', strtotime($task['assigned_at'] ?? $task['created_at'])) : 'N/A' ?></span>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>🏷️ Details</h4>
                    <div class="detail-items">
                        <span><strong>Type:</strong> 
                            <?php 
                            $taskType = $task['task_type'] ?? 'ad-hoc';
                            $typeIcon = match($taskType) {
                                'checklist' => '✅',
                                'milestone' => '🎯',
                                'timed' => '⏰',
                                default => '📋'
                            };
                            ?>
                            <span class="badge badge--info"><?= $typeIcon ?> <?= ucfirst(str_replace('-', ' ', $taskType)) ?></span>
                        </span>
                        <span><strong>Dept:</strong> 
                            <?php if ($task['department_name']): ?>
                                <span class="badge badge--secondary">🏢 <?= htmlspecialchars($task['department_name']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">None</span>
                            <?php endif; ?>
                        </span>
                        <span><strong>Category:</strong> 
                            <?php if ($task['task_category'] ?? null): ?>
                                <span class="badge badge--info">🏷️ <?= htmlspecialchars($task['task_category']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">General</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inline Progress Update -->
    <div id="progressUpdate" class="progress-update progress-update--hidden">
        <div class="progress-update__header">
            <h4 id="progress">📊 Update Progress</h4>
            <button onclick="toggleProgressUpdate()" class="close-btn">&times;</button>
        </div>
        <div class="progress-update__body">
            <div class="progress-control">
                <label>Progress: <span id="progressValue"><?= $task['progress'] ?? 0 ?>%</span></label>
                <input type="range" id="taskProgress" min="0" max="100" value="<?= $task['progress'] ?? 0 ?>" oninput="updateProgress(this.value)" class="progress-slider">
            </div>
            <div class="status-display">
                <span>Status: <span id="currentStatus" class="status-badge status-<?= $task['status'] ?? 'assigned' ?>"><?= ucfirst(str_replace('_', ' ', $task['status'] ?? 'assigned')) ?></span></span>
                <?php if (($task['status'] ?? 'assigned') === 'blocked'): ?>
                <button onclick="unblockTask()" class="unblock-btn">✅ Unblock</button>
                <?php else: ?>
                <button onclick="blockTask()" class="block-btn">🚫 Block</button>
                <?php endif; ?>
            </div>
            <button onclick="saveProgress()" class="save-btn">💾 Save</button>
        </div>
    </div>
</div>

<!-- Removed Modal -->


<script>
document.addEventListener('DOMContentLoaded', function() {
    var currentStatus = '<?= addslashes($task['status'] ?? 'assigned') ?>';
    var taskId = <?= intval($task['id'] ?? 0) ?>;

    window.toggleProgressUpdate = function() {
        document.getElementById('progressUpdate').classList.toggle('progress-update--hidden');
    };

    window.updateProgress = function(value) {
        document.getElementById('progressValue').textContent = value + '%';
        
        // Update mini progress bar
        var progressFill = document.querySelector('.progress-fill-mini');
        var progressText = document.querySelector('.progress-text');
        if (progressFill) progressFill.style.width = value + '%';
        if (progressText) progressText.textContent = value + '%';
        
        if (currentStatus !== 'blocked') {
            var newStatus = value >= 100 ? 'completed' : value > 0 ? 'in_progress' : 'assigned';
            updateStatusDisplay(newStatus);
        }
    };

    function updateStatusDisplay(status) {
        currentStatus = status;
        var statusEl = document.getElementById('currentStatus');
        var statusText = {
            'assigned': 'Assigned',
            'in_progress': 'In Progress',
            'completed': 'Completed',
            'blocked': 'Blocked'
        };
        statusEl.textContent = statusText[status];
        statusEl.className = 'status-badge status-' + status;
    }

    window.blockTask = function() {
        updateStatusDisplay('blocked');
        var btn = document.querySelector('.block-btn');
        if (btn) btn.outerHTML = '<button onclick="unblockTask()" class="unblock-btn">✅ Unblock</button>';
    };

    window.unblockTask = function() {
        var progress = document.getElementById('taskProgress').value;
        var newStatus = progress >= 100 ? 'completed' : progress > 0 ? 'in_progress' : 'assigned';
        updateStatusDisplay(newStatus);
        var btn = document.querySelector('.unblock-btn');
        if (btn) btn.outerHTML = '<button onclick="blockTask()" class="block-btn">🚫 Block</button>';
    };

    window.saveProgress = function() {
        var progress = document.getElementById('taskProgress').value;
        
        fetch('/ergon/tasks/update-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                task_id: taskId,
                progress: progress,
                status: currentStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                localStorage.setItem('taskUpdated', JSON.stringify({
                    id: taskId,
                    progress: progress,
                    status: currentStatus
                }));
                location.reload();
            } else alert('Error: ' + (data.message || 'Unknown error'));
        })
        .catch(() => alert('Error updating task'));
    };

    // Auto-open progress form if URL has #progress hash
    if (window.location.hash === '#progress') {
        var progressEl = document.getElementById('progressUpdate');
        if (progressEl) progressEl.classList.remove('progress-update--hidden');
    }
    
    window.showTaskHistory = function(taskId) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('historyModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'historyModal';
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>📋 Task History</h3>
                        <button class="modal-close" onclick="closeModal('historyModal')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="historyContent">Loading...</div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        // Show modal
        modal.style.display = 'block';
        document.getElementById('historyContent').innerHTML = 'Loading...';
        
        // Load history
        fetch(`/ergon/tasks/history/${taskId}`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('historyContent').innerHTML = data.html || 'No history available';
            } else {
                document.getElementById('historyContent').innerHTML = 'Error: ' + (data.error || 'Failed to load history');
            }
        })
        .catch(error => {
            console.error('Error loading history:', error);
            document.getElementById('historyContent').innerHTML = 'Error loading history';
        });
    };
    
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    };
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('historyModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
});
</script>

<style>
.task-compact {
    max-width: 1000px;
    margin: 0 auto;
}

.task-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    gap: 1.5rem;
    min-height: 2rem;
}

.task-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1 1 auto;
    min-width: 200px;
    max-width: calc(100% - 200px);
    overflow-wrap: break-word;
    word-break: break-word;
    line-height: 1.3;
}

.task-badges {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 0 0 auto;
    min-width: 180px;
    justify-content: flex-end;
}

.progress-mini {
    
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.progress-bar-mini {
    width: 80px;
    height: 6px;
    background: linear-gradient(90deg, #f1f5f9, #e2e8f0);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    position: relative;
}

[data-theme="dark"] .progress-bar-mini {
    background: linear-gradient(90deg, #1e293b, #334155);
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.3);
}

.progress-fill-mini {
    height: 100%;
    border-radius: 10px;
    position: relative;
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Progress colors based on percentage */
.progress-fill-mini[data-progress="0"] { background: linear-gradient(90deg, #e2e8f0, #cbd5e1) !important; }
.progress-fill-mini[data-progress="1-24"] { background: linear-gradient(90deg, #fbbf24, #f59e0b) !important; }
.progress-fill-mini[data-progress="25-49"] { background: linear-gradient(90deg, #fb923c, #ea580c) !important; }
.progress-fill-mini[data-progress="50-74"] { background: linear-gradient(90deg, #3b82f6, #2563eb) !important; }
.progress-fill-mini[data-progress="75-99"] { background: linear-gradient(90deg, #8b5cf6, #7c3aed) !important; }
.progress-fill-mini[data-progress="100"] { background: linear-gradient(90deg, #10b981, #059669) !important; }

/* Dark theme colors */
[data-theme="dark"] .progress-fill-mini[data-progress="0"] { background: linear-gradient(90deg, #475569, #64748b) !important; }
[data-theme="dark"] .progress-fill-mini[data-progress="1-24"] { background: linear-gradient(90deg, #fcd34d, #f59e0b) !important; }
[data-theme="dark"] .progress-fill-mini[data-progress="25-49"] { background: linear-gradient(90deg, #fb7185, #e11d48) !important; }
[data-theme="dark"] .progress-fill-mini[data-progress="50-74"] { background: linear-gradient(90deg, #60a5fa, #3b82f6) !important; }
[data-theme="dark"] .progress-fill-mini[data-progress="75-99"] { background: linear-gradient(90deg, #a78bfa, #8b5cf6) !important; }
[data-theme="dark"] .progress-fill-mini[data-progress="100"] { background: linear-gradient(90deg, #34d399, #10b981) !important; }

.progress-fill-mini::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, rgba(255,255,255,0.3) 0%, transparent 50%, rgba(255,255,255,0.3) 100%);
    border-radius: 10px;
    animation: progressGlow 2s ease-in-out infinite alternate;
}

[data-theme="dark"] .progress-fill-mini {
    box-shadow: 0 2px 6px rgba(0,0,0,0.4);
}

@keyframes progressGlow {
    0% { opacity: 0.4; }
    100% { opacity: 0.8; }
}

.progress-text {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-primary);
    min-width: 30px;
}

.description-compact {
    background: var(--bg-secondary);
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 3px solid var(--primary);
    margin-bottom: 1rem;
    font-size: 0.9rem;
    line-height: 1.4;
}

.details-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.detail-group {
    background: var(--bg-secondary);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.detail-group h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    color: var(--primary);
    font-weight: 600;
}

.detail-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-items span {
    font-size: 0.85rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-items strong {
    color: var(--text-primary);
    min-width: 50px;
    font-size: 0.8rem;
}

.text-muted {
    color: var(--text-tertiary) !important;
    font-style: italic;
}



.progress-update {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    margin: 1rem 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.progress-update--hidden {
    display: none;
}

.progress-update__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.progress-update__header h4 {
    margin: 0;
    color: var(--primary);
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.progress-update__body {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.progress-control label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.progress-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: var(--bg-secondary);
    outline: none;
}

.progress-slider::-webkit-slider-thumb {
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--primary);
    cursor: pointer;
}

.status-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 500;
    font-size: 0.85rem;
}

.status-assigned { background: #fff3cd; color: #856404; }
.status-in_progress { background: #d1ecf1; color: #0c5460; }
.status-completed { background: #d4edda; color: #155724; }
.status-blocked { background: #f8d7da; color: #721c24; }

.block-btn, .unblock-btn {
    padding: 6px 12px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background: var(--bg-primary);
    cursor: pointer;
    font-size: 0.8rem;
}

.save-btn {
    padding: 8px 16px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    align-self: flex-start;
}

@media (max-width: 768px) {
    .task-title-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        min-height: auto;
    }
    
    .task-title {
        max-width: 100%;
        min-width: auto;
    }
    
    .task-badges {
        width: 100%;
        min-width: auto;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .details-compact {
        grid-template-columns: 1fr;
    }
    
    .detail-items span {
        flex-wrap: wrap;
    }
}

@media (max-width: 1024px) and (min-width: 769px) {
    .task-title {
        max-width: calc(100% - 220px);
        min-width: 180px;
    }
    
    .task-badges {
        min-width: 200px;
    }
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 99999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(2px);
}

.modal-content {
    background-color: var(--bg-primary);
    margin: 5% auto;
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 600px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-4);
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.modal-header h3 {
    margin: 0;
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--text-primary);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-muted);
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: var(--transition);
}

.modal-close:hover {
    color: var(--text-primary);
    background: var(--bg-hover);
}

.modal-body {
    padding: var(--space-4);
    max-height: 400px;
    overflow-y: auto;
}

.history-timeline {
    position: relative;
    padding-left: 2rem;
}

.history-timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--primary-light), var(--border-color));
}

.history-entry {
    position: relative;
    margin-bottom: 1.5rem;
    background: var(--bg-secondary);
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid var(--primary);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.history-entry:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.history-icon {
    position: absolute;
    left: -2.25rem;
    top: 1rem;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    color: white;
    border: 3px solid var(--bg-primary);
    z-index: 1;
}

.history-content {
    margin-left: 0.5rem;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.history-action {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.history-time {
    font-size: 0.75rem;
    color: var(--text-muted);
    background: var(--bg-tertiary);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
}

.history-change {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.5rem 0;
    padding: 0.5rem;
    background: var(--bg-tertiary);
    border-radius: 6px;
    font-size: 0.85rem;
}

.change-from {
    color: var(--text-secondary);
    text-decoration: line-through;
    opacity: 0.7;
}

.change-arrow {
    color: var(--primary);
    font-weight: bold;
}

.change-to {
    color: var(--success);
    font-weight: 500;
}

.history-notes {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 0.75rem;
    margin: 0.5rem 0;
    font-style: italic;
    color: var(--text-secondary);
    font-size: 0.85rem;
    line-height: 1.4;
}

.history-user {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--border-color);
}

.no-history {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

.no-history p {
    margin: 0;
    font-size: 0.9rem;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>