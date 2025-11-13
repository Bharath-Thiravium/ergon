<?php
$title = 'Task Details';
$active_page = 'tasks';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>✅</span> Task Details</h1>
        <p>View task information and progress</p>
    </div>
    <div class="page-actions">
        <?php if ($task['status'] !== 'completed'): ?>
        <button onclick="updateTaskStatus(<?= $task['id'] ?>, '<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>', <?= $task['progress'] ?? 0 ?>)" class="btn btn--primary">
            <span>📊</span> Update Progress
        </button>
        <?php endif; ?>
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
                        <div class="progress-bar-mini progress--<?= match(true) { $progress >= 100 => 'completed', $progress >= 75 => 'high', $progress >= 50 => 'medium', $progress >= 25 => 'low', default => 'start' } ?>">
                            <div class="progress-fill-mini" style="width: <?= $progress ?>%"></div>
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
                        <span><strong>Created:</strong> 📅 <?= ($task['created_at']) ? date('M d, Y', strtotime($task['created_at'])) : 'N/A' ?></span>
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
                            <?php if ($task['task_category'] ?? $task['category']): ?>
                                <span class="badge badge--info">🏷️ <?= htmlspecialchars($task['task_category'] ?? $task['category']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">General</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Status Update Modal -->
<div id="statusModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📊 Update Task Progress</h3>
            <button class="modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Task: <strong id="taskTitle"></strong></label>
            </div>
            <div class="form-group">
                <label for="taskProgress">Progress: <span id="progressValue">0%</span></label>
                <input type="range" id="taskProgress" min="0" max="100" value="0" oninput="updateProgressValue(this.value)" class="progress-slider">
            </div>
            <div class="form-group">
                <label for="taskStatus">Status</label>
                <select id="taskStatus" onchange="alert('Status changed to: ' + this.value); if(this.value==='completed'){document.getElementById('taskProgress').value=100;document.getElementById('progressValue').textContent='100%';alert('Progress set to 100%');}">
                    <option value="assigned">📋 Assigned</option>
                    <option value="in_progress">⚡ In Progress</option>
                    <option value="completed">✅ Completed</option>
                    <option value="blocked">🚫 Blocked</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="saveTaskStatus()" class="btn btn--primary">💾 Update</button>
            <button onclick="closeStatusModal()" class="btn btn--secondary">❌ Cancel</button>
        </div>
    </div>
</div>

<script>
let currentTaskId = <?= $task['id'] ?? 0 ?>;

function updateTaskStatus(taskId, taskTitle, currentProgress) {
    currentTaskId = taskId;
    document.getElementById('taskTitle').textContent = taskTitle;
    document.getElementById('taskProgress').value = currentProgress || 0;
    document.getElementById('progressValue').textContent = (currentProgress || 0) + '%';
    
    const statusSelect = document.getElementById('taskStatus');
    if (currentProgress >= 100) {
        statusSelect.value = 'completed';
    } else if (currentProgress > 0) {
        statusSelect.value = 'in_progress';
    } else {
        statusSelect.value = 'assigned';
    }
    
    // Test script to diagnose the issue
    setTimeout(() => {
        console.log('=== DIAGNOSTIC TEST ===');
        console.log('Modal visible:', document.getElementById('statusModal').style.display);
        console.log('Status select exists:', !!document.getElementById('taskStatus'));
        console.log('Progress slider exists:', !!document.getElementById('taskProgress'));
        console.log('Progress value exists:', !!document.getElementById('progressValue'));
        console.log('Status select onchange:', document.getElementById('taskStatus').onchange);
        
        // Test direct manipulation
        const testSlider = document.getElementById('taskProgress');
        const testValue = document.getElementById('progressValue');
        console.log('Current slider value:', testSlider.value);
        console.log('Current progress text:', testValue.textContent);
        
        // Try direct set
        testSlider.value = 50;
        testValue.textContent = '50%';
        console.log('After setting 50% - Slider:', testSlider.value, 'Text:', testValue.textContent);
        
        // Test status change programmatically
        const testStatus = document.getElementById('taskStatus');
        testStatus.value = 'completed';
        console.log('Status set to completed:', testStatus.value);
        
        // Trigger change event manually
        testStatus.dispatchEvent(new Event('change'));
        console.log('Change event dispatched');
        
        console.log('=== END DIAGNOSTIC ===');
    }, 100);
    
    document.getElementById('statusModal').style.display = 'flex';
}

function handleStatusChange() {
    const statusSelect = document.getElementById('taskStatus');
    const progressSlider = document.getElementById('taskProgress');
    const progressValue = document.getElementById('progressValue');
    
    console.log('Status changed to:', statusSelect.value);
    
    if (statusSelect.value === 'completed') {
        progressSlider.value = 100;
        progressValue.textContent = '100%';
        console.log('Set progress to 100%');
    } else if (statusSelect.value === 'assigned' && progressSlider.value == 100) {
        progressSlider.value = 0;
        progressValue.textContent = '0%';
        console.log('Reset progress to 0%');
    }
}

function updateProgressValue(value) {
    document.getElementById('progressValue').textContent = value + '%';
    
    const statusSelect = document.getElementById('taskStatus');
    if (value >= 100) {
        statusSelect.value = 'completed';
    } else if (value > 0) {
        statusSelect.value = 'in_progress';
    } else {
        statusSelect.value = 'assigned';
    }
}



function saveTaskStatus() {
    const progress = document.getElementById('taskProgress').value;
    const status = document.getElementById('taskStatus').value;
    
    fetch('/ergon/tasks/update-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: currentTaskId,
            progress: progress,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating task: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating task status');
    });
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
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
    width: 60px;
    height: 4px;
    background: darkorange;
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill-mini {
    height: 100%;
    transition: width 0.3s ease;
    border-radius: 2px;
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

.progress--start .progress-fill-mini { background: linear-gradient(90deg, #e2e8f0, #cbd5e1); }
.progress--low .progress-fill-mini { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.progress--medium .progress-fill-mini { background: linear-gradient(90deg, #3b82f6, #2563eb); }
.progress--high .progress-fill-mini { background: linear-gradient(90deg, #8b5cf6, #7c3aed); }
.progress--completed .progress-fill-mini { background: linear-gradient(90deg, #10b981, #059669); }

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: var(--bg-primary);
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    margin: 0;
    color: var(--primary);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
    justify-content: flex-end;
}

.progress-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: var(--bg-secondary);
    outline: none;
    margin-top: 0.5rem;
}

.progress-slider::-webkit-slider-thumb {
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary);
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.progress-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary);
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
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
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>