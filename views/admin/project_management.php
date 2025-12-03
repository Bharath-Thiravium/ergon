<?php
$title = 'Project Management';
$active_page = 'project-management';
include __DIR__ . '/../shared/modal_component.php';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📁</span> Project Management</h1>
        <p>Manage projects and departments for task organization</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--primary" onclick="showAddProjectModal()">
            <span>➕</span> Add Project
        </button>
    </div>
</div>

<?php renderModalCSS(); ?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📁</div>
        </div>
        <div class="kpi-card__value"><?= count($data['projects']) ?></div>
        <div class="kpi-card__label">Total Projects</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['projects'], function($p) { return $p['status'] === 'active'; })) ?></div>
        <div class="kpi-card__label">Active Projects</div>
        <div class="kpi-card__status">Running</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏢</div>
        </div>
        <div class="kpi-card__value"><?= count($data['departments']) ?></div>
        <div class="kpi-card__label">Departments</div>
        <div class="kpi-card__status">Available</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📁</span> Projects List
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Department</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['projects'] as $project): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($project['name']) ?></strong></td>
                        <td>
                            <?php if ($project['department_name']): ?>
                                <span class="badge badge--info"><?= htmlspecialchars($project['department_name']) ?></span>
                            <?php else: ?>
                                <span class="badge badge--secondary">General</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($project['description']) ?></td>
                        <td>
                            <span class="badge badge--<?= $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'warning') ?>">
                                <?= ucfirst($project['status']) ?>
                            </span>
                        </td>
                        <td><?= date('M j, Y', strtotime($project['created_at'])) ?></td>
                        <td>
                            <div class="ab-container">
                                <button class="ab-btn ab-btn--edit" data-id="<?= $project['id'] ?>" data-name="<?= htmlspecialchars($project['name'], ENT_QUOTES) ?>" data-description="<?= htmlspecialchars($project['description'], ENT_QUOTES) ?>" data-dept="<?= $project['department_id'] ?? '' ?>" data-status="<?= $project['status'] ?>" onclick="editProjectFromButton(this)" title="Edit Project">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        <path d="M15 5l4 4"/>
                                    </svg>
                                </button>
                                <button class="ab-btn ab-btn--danger" data-id="<?= $project['id'] ?>" data-name="<?= htmlspecialchars($project['name'], ENT_QUOTES) ?>" onclick="deleteProjectFromButton(this)" title="Delete Project">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <polyline points="3,6 5,6 21,6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$projectContent = '
<form id="projectForm">
    <input type="hidden" id="projectId" name="project_id">
    
    <div class="form-group">
        <label class="form-label">Project Name *</label>
        <input type="text" id="projectName" name="name" class="form-control" required placeholder="Enter project name">
    </div>
    
    <div class="form-group">
        <label class="form-label">Department</label>
        <select id="projectDepartment" name="department_id" class="form-control">
            <option value="">Select Department</option>';
            foreach ($data['departments'] as $dept) {
                $projectContent .= '<option value="' . $dept['id'] . '">' . htmlspecialchars($dept['name']) . '</option>';
            }
$projectContent .= '
        </select>
    </div>
    
    <div class="form-group">
        <label class="form-label">Description</label>
        <textarea id="projectDescription" name="description" class="form-control" rows="3" placeholder="Project description"></textarea>
    </div>
    
    <div class="form-group" id="statusGroup" style="display: none;">
        <label class="form-label">Status</label>
        <select id="projectStatus" name="status" class="form-control">
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="on_hold">On Hold</option>
            <option value="cancelled">Cancelled</option>
            <option value="withheld">Withheld</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>
</form>';

$projectFooter = '
<button type="button" class="btn btn--secondary" onclick="closeModal(\'projectModal\')">Cancel</button>
<button type="submit" class="btn btn--primary" form="projectForm">
    <span id="submitText">Add Project</span>
</button>';

renderModal('projectModal', '<span id="modalTitle">Add New Project</span>', $projectContent, $projectFooter, ['icon' => '📁']);
?>

<script>
let isEditing = false;

function showAddProjectModal() {
    isEditing = false;
    document.getElementById('modalTitle').textContent = 'Add New Project';
    document.getElementById('submitText').textContent = 'Add Project';
    document.getElementById('projectForm').reset();
    document.getElementById('projectId').value = '';
    document.getElementById('statusGroup').style.display = 'none';
    showModal('projectModal');
}

function editProjectFromButton(btn) {
    editProject(btn.dataset.id, btn.dataset.name, btn.dataset.description, btn.dataset.dept, btn.dataset.status);
}

function editProject(id, name, description, deptId, status) {
    isEditing = true;
    document.getElementById('modalTitle').textContent = 'Edit Project';
    document.getElementById('submitText').textContent = 'Update Project';
    document.getElementById('projectId').value = id;
    document.getElementById('projectName').value = name;
    document.getElementById('projectDescription').value = description;
    document.getElementById('projectDepartment').value = deptId || '';
    document.getElementById('projectStatus').value = status;
    document.getElementById('statusGroup').style.display = 'block';
    showModal('projectModal');
}

function deleteProjectFromButton(btn) {
    deleteProject(btn.dataset.id, btn.dataset.name);
}

function deleteProject(id, name) {
    if (confirm(`Are you sure you want to delete project "${name}"? This action cannot be undone.`)) {
        const formData = new FormData();
        formData.append('project_id', id);
        
        fetch('/ergon/project-management/delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete project: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete project');
        });
    }
}

document.getElementById('projectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = isEditing ? '/ergon/project-management/update' : '/ergon/project-management/create';
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                throw new Error('Expected JSON response but got: ' + text.substring(0, 100));
            });
        }
    })
    .then(data => {
        if (data.success) {
            closeModal('projectModal');
            location.reload();
        } else {
            alert('Failed to save project: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        alert('Failed to save project. Error: ' + error.message + '. Check console for details.');
    });
});
</script>

<?php renderModalJS(); ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
