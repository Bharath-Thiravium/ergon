<?php
/**
 * Fast Dialog Component
 * Instant display without animations for better performance
 * 
 * Usage:
 * include __DIR__ . '/../shared/modal_component.php';
 * renderModal('modalId', 'Modal Title', $content, $footer, $options);
 */

function renderModal($modalId, $title, $content = '', $footer = '', $options = []) {
    $defaults = [
        'size' => 'medium',
        'closable' => true,
        'icon' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    $sizeClass = match($options['size']) {
        'small' => 'dialog-content--small',
        'large' => 'dialog-content--large', 
        'xlarge' => 'dialog-content--xlarge',
        default => ''
    };
    
    $closeBtn = $options['closable'] ? 
        "<button class='dialog-close' onclick='closeModal(\"{$modalId}\")' title='Close'>&times;</button>" : '';
    
    echo <<<HTML
<div id="{$modalId}" class="dialog" style="display: none;">
    <div class="dialog-content {$sizeClass}">
        <div class="dialog-header">
            <h4>{$options['icon']} {$title}</h4>
            {$closeBtn}
        </div>
        <div class="dialog-body">
            {$content}
        </div>
HTML;

    if ($footer) {
        echo <<<HTML
        <div class="dialog-footer">
            {$footer}
        </div>
HTML;
    }

    echo <<<HTML
    </div>
</div>
HTML;
}

function renderModalCSS() {
    echo <<<CSS
<style>
/* Fast Dialog Styles - No Animations */
.dialog {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-top: 100px;
}

.dialog-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    min-width: 300px;
    max-width: 480px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.dialog-content--small {
    max-width: 450px;
}

.dialog-content--large {
    max-width: 700px;
}

.dialog-content--xlarge {
    max-width: 900px;
    width: 95%;
}

.dialog-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
}

.dialog-header h4 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.dialog-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.dialog-close:hover {
    color: #1f2937;
    background: #f3f4f6;
}

.dialog-body {
    padding: 1.5rem;
    background: white;
}

.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
}

/* Form Styles */
.dialog .form-group {
    margin-bottom: 1rem;
}

.dialog .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
    font-size: 0.875rem;
}

.dialog .form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    background: white;
    color: #1f2937;
    box-sizing: border-box;
}

.dialog .form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Button Styles */
.dialog .btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.dialog .btn--primary {
    background: #3b82f6;
    color: white;
}

.dialog .btn--primary:hover {
    background: #2563eb;
}

.dialog .btn--secondary {
    background: #f8fafc;
    color: #374151;
    border: 1px solid #d1d5db;
}

.dialog .btn--secondary:hover {
    background: #f3f4f6;
}

.dialog .btn--success {
    background: #10b981;
    color: white;
}

.dialog .btn--success:hover {
    background: #059669;
}

.dialog .btn--warning {
    background: #f59e0b;
    color: white;
}

.dialog .btn--warning:hover {
    background: #d97706;
}

.dialog .btn--danger {
    background: #ef4444;
    color: white;
}

.dialog .btn--danger:hover {
    background: #dc2626;
}

/* Dialog buttons (simple style) */
.dialog-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.dialog-buttons button {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
}

.dialog-buttons button:first-child {
    background: #f3f4f6;
    color: #374151;
}

.dialog-buttons button:last-child {
    background: #3b82f6;
    color: white;
}

.dialog-buttons button:hover {
    opacity: 0.9;
}

/* Responsive */
@media (max-width: 768px) {
    .dialog {
        padding: 1rem;
        padding-top: 120px;
    }
    
    .dialog-content {
        width: 100%;
        max-width: none;
    }
    
    .dialog-header,
    .dialog-body,
    .dialog-footer {
        padding: 1rem;
    }
    
    .dialog-footer {
        flex-direction: column;
    }
    
    .dialog-footer .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
CSS;
}

function renderModalJS() {
    echo <<<JS
<script>
// Fast Dialog JavaScript - No Animations
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Focus first input
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 10);
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        if (modal.style.display === 'none' || modal.style.display === '') {
            showModal(modalId);
        } else {
            closeModal(modalId);
        }
    }
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openDialogs = document.querySelectorAll('.dialog[style*="display: flex"]');
        openDialogs.forEach(dialog => {
            closeModal(dialog.id);
        });
    }
});

// Close when clicking backdrop
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('dialog')) {
        closeModal(e.target.id);
    }
});
</script>
JS;
}

// Helper function to create common modal buttons
function createModalButton($text, $type = 'primary', $onclick = '', $attributes = '') {
    return "<button type=\"button\" class=\"btn btn--{$type}\" onclick=\"{$onclick}\" {$attributes}>{$text}</button>";
}

// Helper function to create form modal footer
function createFormModalFooter($cancelText = 'Cancel', $submitText = 'Save', $modalId = '', $submitType = 'primary') {
    $cancelBtn = createModalButton($cancelText, 'secondary', "closeModal('{$modalId}')");
    $submitBtn = "<button type=\"submit\" class=\"btn btn--{$submitType}\">{$submitText}</button>";
    return $cancelBtn . $submitBtn;
}
?>