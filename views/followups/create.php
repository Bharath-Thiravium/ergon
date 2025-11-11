<?php
$title = 'Create Follow-up';
$active_page = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📞</span> Create Follow-up</h1>
        <p>Add a new follow-up to track client interactions</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/followups" class="btn btn--secondary">
            <span>←</span> Back to Follow-ups
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📝</span> Follow-up Details
        </h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/followups/create">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="Follow-up title">
                </div>
                <div class="form-group">
                    <label class="form-label">Company</label>
                    <div class="search-input-container">
                        <input type="text" name="company_name" id="company_name" class="form-control search-input" placeholder="Type to search companies..." autocomplete="off">
                        <div class="search-suggestions" id="company_suggestions"></div>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <div class="search-input-container">
                        <input type="text" name="contact_person" id="contact_person" class="form-control search-input" placeholder="Type to search contacts..." autocomplete="off">
                        <div class="search-suggestions" id="contact_suggestions"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="contact_phone" class="form-control" placeholder="Contact phone number">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Project</label>
                    <div class="search-input-container">
                        <input type="text" name="project_name" id="project_name" class="form-control search-input" placeholder="Type to search projects..." autocomplete="off">
                        <div class="search-suggestions" id="project_suggestions"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Follow-up Date *</label>
                    <input type="date" name="follow_up_date" class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Follow-up Time</label>
                    <input type="time" name="reminder_time" class="form-control" value="09:00">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="postponed">Postponed</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Follow-up description and notes"></textarea>
            </div>
            
            <div class="form-actions">
                <a href="/ergon/followups" class="btn btn--secondary">Cancel</a>
                <button type="submit" class="btn btn--primary">
                    <span>💾</span> Save Follow-up
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let followupData = [];

// Load existing followup data
fetch('/ergon/direct_followup_test.php')
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data);
        followupData = data.followups || [];
        console.log('Followup data loaded:', followupData.length, 'items');
        setupSearchInputs();
    })
    .catch(error => console.log('Failed to load followup data:', error));

function setupSearchInputs() {
    setupSearchInput('company_name', 'company_suggestions', 'company_name');
    setupSearchInput('contact_person', 'contact_suggestions', 'contact_person');
    setupSearchInput('project_name', 'project_suggestions', 'project_name');
}

function setupSearchInput(inputId, suggestionsId, field) {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);
    
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        if (query.length < 2) {
            suggestions.style.display = 'none';
            return;
        }
        
        const matches = [...new Set(followupData
            .map(item => item[field])
            .filter(value => value && value.toLowerCase().includes(query))
            .slice(0, 5))]; // Limit to 5 suggestions
        
        if (matches.length > 0) {
            suggestions.innerHTML = matches
                .map(match => `<div class="suggestion-item" onclick="selectSuggestion('${inputId}', '${match.replace(/'/g, "\\'")}')"><strong>${match}</strong></div>`)
                .join('');
            suggestions.style.display = 'block';
        } else {
            suggestions.style.display = 'none';
        }
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.style.display = 'none';
        }
    });
}

function selectSuggestion(inputId, value) {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(inputId.replace('_name', '_suggestions').replace('_person', '_suggestions'));
    
    input.value = value;
    suggestions.style.display = 'none';
    
    // Auto-fill related fields when selecting contact
    if (inputId === 'contact_person') {
        const contactData = followupData.find(item => item.contact_person === value);
        if (contactData) {
            if (contactData.company_name && !document.getElementById('company_name').value) {
                document.getElementById('company_name').value = contactData.company_name;
            }
            if (contactData.contact_phone && !document.getElementById('contact_phone').value) {
                document.getElementById('contact_phone').value = contactData.contact_phone;
            }
            if (contactData.project_name && !document.getElementById('project_name').value) {
                document.getElementById('project_name').value = contactData.project_name;
            }
        }
    }
}
</script>

<style>
.search-input-container {
    position: relative;
}

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 1000;
    display: none;
    max-height: 200px;
    overflow-y: auto;
}

.suggestion-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.suggestion-item:hover {
    background-color: #f8f9fa;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.search-input {
    border-radius: 4px 4px 0 0;
}

.search-input:focus + .search-suggestions {
    border-color: #007bff;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>