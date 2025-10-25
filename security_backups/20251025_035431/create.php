<?php
$title = 'Create Employee';
$active_page = 'users';
ob_start();
?>

<div class="header-actions" style="margin-bottom: var(--space-6);">
    <a href="/ergon/users" class="btn btn--secondary">Back to Users</a>
</div>

<?php if (isset($data['error'])): ?>
<div class="alert alert--error" style="margin-bottom: var(--space-4);">
    <?= htmlspecialchars($data['error']) ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="user-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
    <div class="form-sections">
        <!-- Personal Information -->
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Personal Information</h2>
            </div>
            <div class="card__body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($data['old_data']['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employee ID *</label>
                        <input type="text" name="employee_id" class="form-control" id="employeeId" readonly required>
                        <button type="button" class="btn btn--sm btn--secondary" onclick="generateEmployeeId()">Generate ID</button>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($data['old_data']['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" required pattern="[0-9]{10}" maxlength="10" placeholder="10-digit mobile number" value="<?= htmlspecialchars($data['old_data']['phone'] ?? '') ?>">
                        <small class="form-text">Enter 10-digit mobile number</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Emergency Contact</label>
                    <input type="tel" name="emergency_contact" class="form-control">
                </div>
            </div>
        </div>

        <!-- Employment Details -->
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Employment Details</h2>
            </div>
            <div class="card__body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Designation *</label>
                        <input type="text" name="designation" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Joining Date *</label>
                        <input type="date" name="joining_date" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Monthly Salary (₹)</label>
                        <input type="number" name="salary" class="form-control" step="1" min="0" placeholder="Enter amount without decimals">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="user">Employee</option>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
                            <option value="admin">Admin</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Departments *</label>
                    <div class="checkbox-group">
                        <?php foreach ($data['departments'] as $dept): ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="departments[]" value="<?= $dept['id'] ?>">
                            <span class="checkbox-label"><?= htmlspecialchars($dept['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Uploads -->
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Document Uploads</h2>
            </div>
            <div class="card__body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Profile Photo</label>
                        <input type="file" name="profile_photo" class="form-control" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PAN Card</label>
                        <input type="file" name="pan_card" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Aadhar Card</label>
                        <input type="file" name="aadhar_card" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Resume</label>
                        <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Passport</label>
                        <input type="file" name="passport" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Driving License</label>
                        <input type="file" name="driving_license" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Create Employee</button>
        <button type="button" class="btn btn--success" onclick="createWithCredentials()" style="display:none;" id="credentialBtn">Create & Download Credentials</button>
        <a href="/ergon/users" class="btn btn--secondary">Cancel</a>
    </div>
</form>

<script>
function generateEmployeeId() {
    fetch('/ergon/api/generate-employee-id')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('employeeId').value = data.employee_id;
                document.getElementById('credentialBtn').style.display = 'inline-block';
            } else {
                console.error('Employee ID generation failed:', data.message);
                alert('Failed to generate Employee ID: ' + data.message);
            }
        })
        .catch(error => {
            console.error('API Error:', error);
            alert('Error connecting to server');
        });
}

function createWithCredentials() {
    const form = document.querySelector('.user-form');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'generate_credentials';
    input.value = '1';
    form.appendChild(input);
    form.submit();
}

// Auto-generate on page load
document.addEventListener('DOMContentLoaded', function() {
    generateEmployeeId();
    
    // Phone validation
    const phoneInput = document.querySelector('input[name="phone"]');
    phoneInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
        if (this.value.length === 10) {
            this.setCustomValidity('');
        } else {
            this.setCustomValidity('Please enter exactly 10 digits');
        }
    });
    
    // Email validation
    const emailInput = document.querySelector('input[name="email"]');
    emailInput.addEventListener('blur', function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(this.value)) {
            this.setCustomValidity('Please enter a valid email address');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php if (isset($_SESSION['new_user_credentials'])): ?>
<div class="card" style="margin-top: 20px; border: 2px solid #28a745;">
    <div class="card__header">
        <h2 class="card__title" style="color: #28a745;">✅ User Created Successfully!</h2>
    </div>
    <div class="card__body">
        <div class="credential-display">
            <h3>Login Credentials</h3>
            <div class="credential-item">
                <strong>Employee ID:</strong> <?= htmlspecialchars($_SESSION['new_user_credentials']['employee_id']) ?>
            </div>
            <div class="credential-item">
                <strong>Email:</strong> <?= htmlspecialchars($_SESSION['new_user_credentials']['email']) ?>
            </div>
            <div class="credential-item">
                <strong>Temporary Password:</strong> <code><?= htmlspecialchars($_SESSION['new_user_credentials']['temp_password']) ?></code>
            </div>
            <div class="credential-actions" style="margin-top: 15px;">
                <button onclick="downloadCredentials()" class="btn btn--success">📥 Download Credentials</button>
                <button onclick="clearCredentials()" class="btn btn--secondary">Clear</button>
            </div>
        </div>
    </div>
</div>

<script>
function downloadCredentials() {
    const credentials = <?= json_encode($_SESSION['new_user_credentials']) ?>;
    const content = `ERGON Employee Login Credentials\n================================\n\nEmployee ID: ${credentials.employee_id}\nEmail: ${credentials.email}\nTemporary Password: ${credentials.temp_password}\n\nInstructions:\n1. Login at: ${window.location.origin}/ergon/login\n2. You will be required to reset your password on first login\n3. Choose a strong password (minimum 6 characters)\n\nGenerated on: ${new Date().toLocaleString()}`;
    
    const element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
    element.setAttribute('download', `credentials_${credentials.employee_id}.txt`);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

function clearCredentials() {
    if (confirm('Clear credentials display? This cannot be undone.')) {
        window.location.href = '/ergon/users/create';
    }
}
</script>

<style>
.credential-display {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #28a745;
}
.credential-item {
    margin: 10px 0;
    font-size: 16px;
}
.credential-item code {
    background: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 14px;
}
</style>
<?php unset($_SESSION['new_user_credentials']); endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>