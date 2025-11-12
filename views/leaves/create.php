<?php
$title = 'Request Leave';
$active_page = 'leaves';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🏖️</span> Request Leave</h1>
        <p>Submit your leave application for approval</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/leaves" class="btn btn--secondary">
            <span>←</span> Back to Leaves
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📝</span> Leave Application Form
        </h2>
    </div>
    <div class="card__body">
        <form id="leaveForm" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label for="type" class="form-label">Leave Type *</label>
                    <select class="form-control" id="type" name="type" required>
                        <option value="">Select Leave Type</option>
                        <option value="casual">Casual Leave</option>
                        <option value="sick">Sick Leave</option>
                        <option value="annual">Annual Leave</option>
                        <option value="emergency">Emergency Leave</option>
                        <option value="maternity">Maternity Leave</option>
                        <option value="paternity">Paternity Leave</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date *</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label for="end_date" class="form-label">End Date *</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="leave-days-display" id="leaveDaysDisplay" style="display: none;">
                    <div class="alert alert--info">
                        <div class="alert__icon">📅</div>
                        <div class="alert__content">
                            <strong>Total Leave Days: <span id="totalDays">0</span></strong>
                            <small>Including weekends and holidays</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reason" class="form-label">Reason for Leave *</label>
                <textarea class="form-control" id="reason" name="reason" rows="4" 
                         placeholder="Please provide a detailed reason for your leave request..." required></textarea>
                <small class="form-text">Minimum 10 characters required</small>
            </div>
            
            <div class="form-group">
                <label for="contact_during_leave" class="form-label">Emergency Contact During Leave</label>
                <input type="tel" class="form-control" id="contact_during_leave" name="contact_during_leave" 
                       placeholder="Phone number for emergency contact">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary" id="submitBtn">
                    📤 Submit Leave Request
                </button>
                <a href="/ergon/leaves" class="btn btn--secondary">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.alert {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.alert--info {
    background-color: #e3f2fd;
    border: 1px solid #90caf9;
    color: #1565c0;
}

.alert__icon {
    font-size: 1.25rem;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.alert__content {
    flex: 1;
}

.alert__content strong {
    display: block;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.alert__content small {
    opacity: 0.8;
    font-size: 0.875rem;
}

.form-text {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.leave-days-display {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
function calculateLeaveDays() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (end < start) {
            document.getElementById('leaveDaysDisplay').style.display = 'none';
            document.getElementById('end_date').setCustomValidity('End date must be after start date');
            return;
        }
        
        document.getElementById('end_date').setCustomValidity('');
        
        const timeDiff = end.getTime() - start.getTime();
        const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
        
        if (daysDiff > 0) {
            document.getElementById('totalDays').textContent = daysDiff;
            document.getElementById('leaveDaysDisplay').style.display = 'block';
        }
    } else {
        document.getElementById('leaveDaysDisplay').style.display = 'none';
    }
}

// Set minimum date to today
document.getElementById('start_date').min = new Date().toISOString().split('T')[0];
document.getElementById('end_date').min = new Date().toISOString().split('T')[0];

document.getElementById('start_date').addEventListener('change', function() {
    const startDate = this.value;
    document.getElementById('end_date').min = startDate;
    calculateLeaveDays();
});

document.getElementById('end_date').addEventListener('change', calculateLeaveDays);

document.getElementById('leaveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Validate reason length
    const reason = document.getElementById('reason').value.trim();
    if (reason.length < 10) {
        alert('Please provide a detailed reason (minimum 10 characters)');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Submitting...';
    
    const formData = new FormData(this);
    
    fetch('/ergon/leaves/create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Leave request submitted successfully for ' + data.days + ' days!');
            window.location.href = '/ergon/leaves';
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting the request.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>