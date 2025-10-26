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
                            <div class="btn-group">
                                <button class="btn btn--sm btn--secondary" onclick="editProject(<?= $project['id'] ?>, '<?= htmlspecialchars($project['name']) ?>', '<?= htmlspecialchars($project['description']) ?>', <?= $project['department_id'] ?? 'null' ?>, '<?= $project['status'] ?>')">
                                    ✏️ Edit
                                </button>
                                <button class="btn btn--sm btn--danger" onclick="deleteProject(<?= $project['id'] ?>, '<?= htmlspecialchars($project['name']) ?>')">
                                    🗑️ Delete
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

<!-- Add/Edit Project Modal -->
<div class="modal" id="projectModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span>📁</span> <span id="modalTitle">Add New Project</span></h3>
            <button class="modal-close" onclick="closeProjectModal()">&times;</button>
        </div>
        <form id="projectForm">
            <div class="modal-body">
                <input type="hidden" id="projectId" name="project_id">
                
                <div class="form-group">
                    <label class="form-label">Project Name *</label>
                    <input type="text" id="projectName" name="name" class="form-control" required placeholder="Enter project name">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select id="projectDepartment" name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        <?php foreach ($data['departments'] as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeProjectModal()">Cancel</button>
                <button type="submit" class="btn btn--primary">
                    <span id="submitText">Add Project</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let isEditing = false;

function showAddProjectModal() {
    isEditing = false;
    document.getElementById('modalTitle').textContent = 'Add New Project';
    document.getElementById('submitText').textContent = 'Add Project';
    document.getElementById('projectForm').reset();
    document.getElementById('projectId').value = '';
    document.getElementById('statusGroup').style.display = 'none';
    document.getElementById('projectModal').style.display = 'block';
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
    document.getElementById('projectModal').style.display = 'block';
}

function closeProjectModal() {
    document.getElementById('projectModal').style.display = 'none';
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
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeProjectModal();
            location.reload();
        } else {
            alert('Failed to save project: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save project');
    });
});
</script>