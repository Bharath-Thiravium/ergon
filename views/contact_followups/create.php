<?php
$active_page = 'contact_followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>➕</span> Create Follow-up</h1>
        <p>Create a new follow-up for contact communication</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/contacts/followups" class="btn btn--secondary">
            <span>←</span> Back to Contacts
        </a>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Follow-up Details</h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/contacts/followups/create" id="followupForm">
            <div class="form-group">
                <label class="form-label" for="followup_type">Follow-up Type *</label>
                <select name="followup_type" id="followup_type" class="form-control" required onchange="toggleTaskSelection()">
                    <option value="standalone">Standalone Follow-up</option>
                    <option value="task">Task-linked Follow-up</option>
                </select>
                <small class="form-help">Choose whether this is a standalone follow-up or linked to a task</small>
            </div>
            
            <div id="taskSelection" class="form-group" style="display: none;">
                <label class="form-label" for="task_id">Link to Task *</label>
                <select name="task_id" id="task_id" class="form-control">
                    <option value="">Select a task</option>
                    <?php if (isset($tasks)): ?>
                        <?php foreach ($tasks as $task): ?>
                            <option value="<?= $task['id'] ?>">
                                <?= htmlspecialchars($task['title']) ?>
                                <?php if ($task['due_date']): ?>
                                    (Due: <?= date('M j, Y', strtotime($task['due_date'])) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small class="form-help">Select the task this follow-up is related to</small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="contact_id">Contact *</label>
                    <select name="contact_id" id="contact_id" class="form-control" required>
                        <option value="">Select a contact</option>
                        <?php foreach ($contacts as $contact): ?>
                            <option value="<?= $contact['id'] ?>" <?= (isset($_GET['contact_id']) && $_GET['contact_id'] == $contact['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($contact['name']) ?>
                                <?php if ($contact['company']): ?>
                                    - <?= htmlspecialchars($contact['company']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-help">Select the contact this follow-up is for</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="follow_up_date">Follow-up Date *</label>
                    <input type="date" name="follow_up_date" id="follow_up_date" class="form-control" 
                           value="<?= date('Y-m-d') ?>" required>
                    <small class="form-help">When should this follow-up be done?</small>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="title">Title *</label>
                <input type="text" name="title" id="title" class="form-control" 
                       placeholder="e.g., Follow up on proposal discussion" required>
                <small class="form-help">Brief description of what this follow-up is about</small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4" 
                          placeholder="Additional details about this follow-up..."></textarea>
                <small class="form-help">Optional: Add more context or notes about this follow-up</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <span>💾</span> Create Follow-up
                </button>
                <a href="/ergon/contacts/followups" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Quick Contact Creation -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">Don't see your contact?</h3>
    </div>
    <div class="card__body">
        <p>If the contact you need isn't in the list above, you can quickly add them:</p>
        
        <form id="quickContactForm" class="quick-contact-form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="contact_name">Name *</label>
                    <input type="text" id="contact_name" class="form-control" placeholder="Contact name" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact_phone">Phone</label>
                    <input type="tel" id="contact_phone" class="form-control" placeholder="Phone number">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="contact_email">Email</label>
                    <input type="email" id="contact_email" class="form-control" placeholder="Email address">
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact_company">Company</label>
                    <input type="text" id="contact_company" class="form-control" placeholder="Company name">
                </div>
            </div>
            
            <button type="button" class="btn btn--success" onclick="createQuickContact()">
                <span>👤</span> Add Contact & Select
            </button>
        </form>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.8rem;
    color: #6b7280;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.quick-contact-form {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function toggleTaskSelection() {
    const followupType = document.getElementById('followup_type').value;
    const taskSelection = document.getElementById('taskSelection');
    const taskSelect = document.getElementById('task_id');
    const form = document.getElementById('followupForm');
    
    if (followupType === 'task') {
        taskSelection.style.display = 'block';
        taskSelect.required = true;
        form.action = '/ergon/contacts/followups/create-task';
    } else {
        taskSelection.style.display = 'none';
        taskSelect.required = false;
        form.action = '/ergon/contacts/followups/create';
    }
}

function createQuickContact() {
    const name = document.getElementById('contact_name').value.trim();
    const phone = document.getElementById('contact_phone').value.trim();
    const email = document.getElementById('contact_email').value.trim();
    const company = document.getElementById('contact_company').value.trim();
    
    if (!name) {
        alert('Contact name is required');
        return;
    }
    
    // Create contact via API (you'll need to implement this endpoint)
    fetch('/ergon/api/contacts/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            name: name,
            phone: phone,
            email: email,
            company: company
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add to contact dropdown
            const select = document.getElementById('contact_id');
            const option = document.createElement('option');
            option.value = data.contact_id;
            option.textContent = name + (company ? ' - ' + company : '');
            option.selected = true;
            select.appendChild(option);
            
            // Clear form
            document.getElementById('quickContactForm').reset();
            
            alert('Contact created and selected successfully!');
        } else {
            alert('Error creating contact: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating the contact');
    });
}

// Auto-focus on contact selection if not pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const contactSelect = document.getElementById('contact_id');
    if (!contactSelect.value) {
        contactSelect.focus();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>