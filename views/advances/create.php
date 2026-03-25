<?php
$title = 'Request Advance';
$active_page = 'advances';
ob_start();
?>

<div class="header-actions" style="margin-bottom: var(--space-6);">
    <a href="/ergon/advances" class="btn btn--secondary">Back to Advances</a>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Request Advance</h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/advances/create" class="form">
            <div class="form-group">
                <label class="form-label">Advance Type</label>
                <select name="type" class="form-control" required>
                    <option value="">Select advance type</option>
                    <option value="Salary Advance">Salary Advance</option>
                    <option value="Travel Advance">Travel Advance</option>
                    <option value="Emergency Advance">Emergency Advance</option>
                    <option value="Project Advance">Project Advance</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Project *</label>
                <select name="project_id" id="project_id" class="form-control" onchange="loadSubcategories(this.value, 'subcategory_id')" required>
                    <option value="">Select Project</option>
                </select>
            </div>

            <div class="form-group" id="subcategory_group" style="display:none;">
                <label class="form-label">Work Category (Subcategory)</label>
                <select name="subcategory_id" id="subcategory_id" class="form-control">
                    <option value="">-- Select work category --</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Amount (₹) *</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="1" placeholder="Enter amount" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Reason *</label>
                <textarea name="reason" class="form-control" rows="4" placeholder="Please provide reason for advance..." required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Expected Repayment Date (Optional)</label>
                <input type="date" name="repayment_date" class="form-control">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Submit Advance Request</button>
                <a href="/ergon/advances" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
fetch('/ergon/api/projects.php')
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response:', text);
            throw new Error('Invalid JSON response');
        }
    })
    .then(data => {
        const projectSelect = document.getElementById('project_id');
        if (data.success && data.projects) {
            data.projects.forEach(project => {
                const option = document.createElement('option');
                option.value = project.id;
                let text = project.name;
                if (project.department_name) text += ' - ' + project.department_name;
                if (project.description) text += ' (' + project.description + ')';
                option.textContent = text;
                projectSelect.appendChild(option);
            });
        }
    })
    .catch(error => console.error('Error loading projects:', error));

function loadSubcategories(projectId, selectId) {
    const group = document.getElementById('subcategory_group');
    const sel   = document.getElementById(selectId);
    sel.innerHTML = '<option value="">-- Select work category --</option>';
    if (!projectId) { group.style.display = 'none'; return; }
    fetch('/ergon/api/project-subcategories/' + projectId)
        .then(r => r.json())
        .then(data => {
            if (data.length) {
                data.forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.id;
                    o.textContent = s.name + (s.budget > 0 ? ' (Budget: ₹' + parseFloat(s.budget).toLocaleString() + ')' : '');
                    sel.appendChild(o);
                });
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        })
        .catch(() => { group.style.display = 'none'; });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
