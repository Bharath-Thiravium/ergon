<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Modal System Demo - ERGON</title>
    <link href="/ergon/public/assets/css/ergon.css" rel="stylesheet">
    <link href="/ergon/public/assets/css/modals.css" rel="stylesheet">
    <style>
        .demo-container { max-width: 1200px; margin: 50px auto; padding: 20px; }
        .demo-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .demo-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .demo-card h3 { margin-top: 0; color: #2c3e50; }
        .demo-buttons { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="demo-container">
        <h1>🎭 ERGON Modal System Demo</h1>
        <p>Standardized modals and dialogs for consistent user experience across the application.</p>
        
        <div class="demo-grid">
            <!-- Basic Modals -->
            <div class="demo-card">
                <h3>📋 Basic Modals</h3>
                <p>Standard modal windows with customizable content and buttons.</p>
                <div class="demo-buttons">
                    <button class="btn btn-primary" onclick="showBasicModal()">Basic Modal</button>
                    <button class="btn btn-secondary" onclick="showLargeModal()">Large Modal</button>
                    <button class="btn btn-success" onclick="showSmallModal()">Small Modal</button>
                </div>
            </div>

            <!-- Alert Modals -->
            <div class="demo-card">
                <h3>🚨 Alert Modals</h3>
                <p>Informational alerts with different severity levels.</p>
                <div class="demo-buttons">
                    <button class="btn btn-success" onclick="showSuccessAlert()">Success</button>
                    <button class="btn btn-danger" onclick="showErrorAlert()">Error</button>
                    <button class="btn btn-warning" onclick="showWarningAlert()">Warning</button>
                    <button class="btn btn-primary" onclick="showInfoAlert()">Info</button>
                </div>
            </div>

            <!-- Confirmation Modals -->
            <div class="demo-card">
                <h3>❓ Confirmation Modals</h3>
                <p>User confirmation dialogs for critical actions.</p>
                <div class="demo-buttons">
                    <button class="btn btn-danger" onclick="showDeleteConfirm()">Delete Confirm</button>
                    <button class="btn btn-warning" onclick="showSaveConfirm()">Save Confirm</button>
                    <button class="btn btn-primary" onclick="showCustomConfirm()">Custom Confirm</button>
                </div>
            </div>

            <!-- Form Modals -->
            <div class="demo-card">
                <h3>📝 Form Modals</h3>
                <p>Dynamic form generation with validation.</p>
                <div class="demo-buttons">
                    <button class="btn btn-primary" onclick="showUserForm()">User Form</button>
                    <button class="btn btn-success" onclick="showTaskForm()">Task Form</button>
                    <button class="btn btn-warning" onclick="showFeedbackForm()">Feedback Form</button>
                </div>
            </div>

            <!-- Progress Modals -->
            <div class="demo-card">
                <h3>⏳ Progress Modals</h3>
                <p>Loading and progress indicators.</p>
                <div class="demo-buttons">
                    <button class="btn btn-primary" onclick="showLoadingModal()">Loading</button>
                    <button class="btn btn-success" onclick="showProgressModal()">Progress Bar</button>
                    <button class="btn btn-warning" onclick="simulateUpload()">File Upload</button>
                </div>
            </div>

            <!-- Advanced Modals -->
            <div class="demo-card">
                <h3>🚀 Advanced Features</h3>
                <p>Complex modal interactions and features.</p>
                <div class="demo-buttons">
                    <button class="btn btn-primary" onclick="showNestedModal()">Nested Modal</button>
                    <button class="btn btn-success" onclick="showDynamicContent()">Dynamic Content</button>
                    <button class="btn btn-warning" onclick="showAutoClose()">Auto Close</button>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="/ergon/dashboard" class="btn btn-outline">← Back to Dashboard</a>
        </div>
    </div>

    <script src="/ergon/public/assets/js/modal-system.js"></script>
    <script>
        // Basic Modals
        function showBasicModal() {
            showModal({
                title: '📋 Basic Modal',
                body: '<p>This is a basic modal with standard content and buttons.</p><p>You can include any HTML content here.</p>',
                buttons: [
                    { text: 'Cancel', class: 'btn-outline' },
                    { text: 'OK', class: 'btn-primary' }
                ]
            });
        }

        function showLargeModal() {
            showModal({
                title: '📊 Large Modal',
                body: '<p>This is a large modal that takes up more screen space.</p>'.repeat(10),
                size: 'lg',
                buttons: [{ text: 'Close', class: 'btn-primary' }]
            });
        }

        function showSmallModal() {
            showModal({
                title: '💬 Small Modal',
                body: '<p>Compact modal for simple messages.</p>',
                size: 'sm',
                buttons: [{ text: 'Got it', class: 'btn-primary' }]
            });
        }

        // Alert Modals
        function showSuccessAlert() {
            showAlert('Operation completed successfully!', 'success', 'Success');
        }

        function showErrorAlert() {
            showAlert('An error occurred while processing your request.', 'error', 'Error');
        }

        function showWarningAlert() {
            showAlert('Please review your input before proceeding.', 'warning', 'Warning');
        }

        function showInfoAlert() {
            showAlert('Here is some important information for you.', 'info', 'Information');
        }

        // Confirmation Modals
        function showDeleteConfirm() {
            confirmAction('Are you sure you want to delete this item? This action cannot be undone.', 
                () => showAlert('Item deleted successfully!', 'success'), 'Delete Item');
        }

        function showSaveConfirm() {
            modalSystem.confirm({
                title: 'Save Changes',
                message: 'Do you want to save your changes before leaving?',
                confirmText: 'Save',
                cancelText: 'Discard',
                confirmClass: 'btn-success',
                onConfirm: () => showAlert('Changes saved!', 'success')
            });
        }

        function showCustomConfirm() {
            modalSystem.confirm({
                title: '🎯 Custom Confirmation',
                message: 'This is a custom confirmation dialog with different styling.',
                confirmText: 'Yes, Continue',
                cancelText: 'No, Cancel',
                confirmClass: 'btn-warning',
                onConfirm: () => showAlert('You chose to continue!', 'info')
            });
        }

        // Form Modals
        function showUserForm() {
            showForm('👤 Add User', [
                { name: 'name', label: 'Full Name', type: 'text', required: true },
                { name: 'email', label: 'Email', type: 'email', required: true },
                { name: 'role', label: 'Role', type: 'select', required: true, 
                  options: [
                      { value: 'user', text: 'User' },
                      { value: 'admin', text: 'Admin' },
                      { value: 'owner', text: 'Owner' }
                  ]
                },
                { name: 'department', label: 'Department', type: 'text' }
            ], (data) => {
                showAlert(`User ${data.name} created successfully!`, 'success');
            });
        }

        function showTaskForm() {
            showForm('✅ Create Task', [
                { name: 'title', label: 'Task Title', type: 'text', required: true },
                { name: 'description', label: 'Description', type: 'textarea', rows: 4 },
                { name: 'priority', label: 'Priority', type: 'select', required: true,
                  options: [
                      { value: 'low', text: 'Low' },
                      { value: 'medium', text: 'Medium' },
                      { value: 'high', text: 'High' },
                      { value: 'urgent', text: 'Urgent' }
                  ]
                },
                { name: 'deadline', label: 'Deadline', type: 'datetime-local' },
                { name: 'progress', label: 'Initial Progress', type: 'range', value: 0 }
            ], (data) => {
                showAlert(`Task "${data.title}" created with ${data.priority} priority!`, 'success');
            });
        }

        function showFeedbackForm() {
            showForm('💬 Feedback Form', [
                { name: 'rating', label: 'Rating', type: 'select', required: true,
                  options: [
                      { value: '5', text: '⭐⭐⭐⭐⭐ Excellent' },
                      { value: '4', text: '⭐⭐⭐⭐ Good' },
                      { value: '3', text: '⭐⭐⭐ Average' },
                      { value: '2', text: '⭐⭐ Poor' },
                      { value: '1', text: '⭐ Very Poor' }
                  ]
                },
                { name: 'feedback', label: 'Your Feedback', type: 'textarea', required: true, rows: 5 },
                { name: 'contact', label: 'Contact for Follow-up', type: 'email' }
            ], (data) => {
                showAlert('Thank you for your feedback!', 'success');
            });
        }

        // Progress Modals
        function showLoadingModal() {
            showLoading('Processing your request...');
            setTimeout(() => {
                closeModal();
                showAlert('Process completed!', 'success');
            }, 3000);
        }

        function showProgressModal() {
            modalSystem.progress({
                title: 'Processing Data',
                message: 'Please wait while we process your data...',
                progress: 0
            });

            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                modalSystem.updateProgress(progress, `Step ${progress/10} of 10 completed...`);
                
                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        closeModal();
                        showAlert('Processing completed successfully!', 'success');
                    }, 1000);
                }
            }, 500);
        }

        function simulateUpload() {
            modalSystem.progress({
                title: '📤 Uploading File',
                message: 'Uploading document.pdf...',
                progress: 0
            });

            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 100) progress = 100;
                
                modalSystem.updateProgress(Math.floor(progress), 
                    progress < 100 ? `Uploading... ${Math.floor(progress)}%` : 'Upload complete!');
                
                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        closeModal();
                        showAlert('File uploaded successfully!', 'success');
                    }, 1000);
                }
            }, 300);
        }

        // Advanced Features
        function showNestedModal() {
            showModal({
                title: '🔗 First Modal',
                body: '<p>This is the first modal. Click the button below to open a nested modal.</p>',
                buttons: [
                    { text: 'Open Nested', class: 'btn-primary', 
                      onclick: `showAlert('This is a nested modal!', 'info', 'Nested Modal')` },
                    { text: 'Close', class: 'btn-outline' }
                ]
            });
        }

        function showDynamicContent() {
            const modal = showModal({
                title: '🔄 Dynamic Content',
                body: '<p>Loading content...</p>',
                buttons: [
                    { text: 'Refresh', class: 'btn-primary', onclick: 'updateDynamicContent()' },
                    { text: 'Close', class: 'btn-outline' }
                ]
            });

            setTimeout(() => {
                modalSystem.updateBody(`
                    <div style="text-align: center;">
                        <h4>📊 Dynamic Data</h4>
                        <p>Current time: ${new Date().toLocaleTimeString()}</p>
                        <p>Random number: ${Math.floor(Math.random() * 1000)}</p>
                        <div class="alert alert-info">Content updated dynamically!</div>
                    </div>
                `);
            }, 2000);
        }

        function updateDynamicContent() {
            modalSystem.updateBody(`
                <div style="text-align: center;">
                    <h4>🔄 Refreshed Content</h4>
                    <p>Updated time: ${new Date().toLocaleTimeString()}</p>
                    <p>New random number: ${Math.floor(Math.random() * 1000)}</p>
                    <div class="alert alert-success">Content refreshed!</div>
                </div>
            `);
        }

        function showAutoClose() {
            showModal({
                title: '⏰ Auto-Close Modal',
                body: '<p>This modal will automatically close in <span id="countdown">5</span> seconds.</p>',
                size: 'sm',
                buttons: [{ text: 'Close Now', class: 'btn-primary' }]
            });

            let countdown = 5;
            const interval = setInterval(() => {
                countdown--;
                const countdownEl = document.getElementById('countdown');
                if (countdownEl) countdownEl.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(interval);
                    closeModal();
                    showAlert('Modal closed automatically!', 'info');
                }
            }, 1000);
        }
    </script>
</body>
</html>