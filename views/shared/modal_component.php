<?php
/**
 * Standardized Modal Component
 * Based on the follow-ups modal pattern for consistency across the Ergon project
 * 
 * Usage:
 * include __DIR__ . '/../shared/modal_component.php';
 * renderModal('modalId', 'Modal Title', $content, $footer, $options);
 */

function renderModal($modalId, $title, $content = '', $footer = '', $options = []) {
    $defaults = [
        'size' => 'medium', // small, medium, large, xlarge
        'closable' => true,
        'backdrop' => true,
        'icon' => '',
        'zIndex' => 99999
    ];
    
    $options = array_merge($defaults, $options);
    
    $sizeClass = match($options['size']) {
        'small' => 'modal-content--small',
        'large' => 'modal-content--large', 
        'xlarge' => 'modal-content--xlarge',
        default => ''
    };
    
    $backdropClose = $options['backdrop'] ? 'onclick="closeModal(\'' . $modalId . '\')"' : '';
    
    echo <<<HTML
<div id="{$modalId}" class="ergon-modal" style="display: none; position: fixed; z-index: {$options['zIndex']} !important; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);" {$backdropClose}>
    <div class="ergon-modal-content {$sizeClass}" style="background: var(--bg-primary, white); margin: 5% auto; border-radius: var(--border-radius, 8px); width: 90%; max-width: 500px; box-shadow: var(--shadow-lg, 0 4px 20px rgba(0,0,0,0.15)); position: relative; z-index: {$options['zIndex']} !important; border: 1px solid var(--border-color, #e5e7eb);" onclick="event.stopPropagation();">
        <div class="ergon-modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid var(--border-color, #e5e7eb); background: var(--bg-secondary, #f8fafc);">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: var(--text-primary, #1f2937); display: flex; align-items: center; gap: 0.5rem;">
                {$options['icon']} {$title}
            </h3>
HTML;

    if ($options['closable']) {
        echo <<<HTML
            <button class="ergon-modal-close" onclick="closeModal('{$modalId}')" title="Close" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted, #6b7280); padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: var(--transition, all 0.2s ease);">&times;</button>
HTML;
    }

    echo <<<HTML
        </div>
        <div class="ergon-modal-body" style="padding: 1.5rem; background: var(--bg-primary, white); max-height: 70vh; overflow-y: auto;">
            {$content}
        </div>
HTML;

    if ($footer) {
        echo <<<HTML
        <div class="ergon-modal-footer" style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1.5rem; border-top: 1px solid var(--border-color, #e5e7eb); background: var(--bg-secondary, #f8fafc);">
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
/* Standardized Modal Styles - Based on Follow-ups Pattern */
.ergon-modal {
    position: fixed;
    z-index: 99999 !important;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(2px);
}

.ergon-modal-content {
    background-color: var(--bg-primary, white);
    margin: 5% auto;
    border-radius: var(--border-radius, 8px);
    width: 90%;
    max-width: 500px;
    box-shadow: var(--shadow-lg, 0 4px 20px rgba(0,0,0,0.15));
    position: relative;
    z-index: 100000 !important;
    border: 1px solid var(--border-color, #e5e7eb);
    animation: modalSlideIn 0.3s ease-out;
}

.ergon-modal-content--small {
    max-width: 400px;
}

.ergon-modal-content--large {
    max-width: 700px;
}

.ergon-modal-content--xlarge {
    max-width: 900px;
    width: 95%;
}

.ergon-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    background: var(--bg-secondary, #f8fafc);
}

.ergon-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary, #1f2937);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ergon-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-muted, #6b7280);
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: var(--transition, all 0.2s ease);
}

.ergon-modal-close:hover {
    color: var(--text-primary, #1f2937);
    background: var(--bg-hover, rgba(0,0,0,0.05));
}

.ergon-modal-body {
    padding: 1.5rem;
    background: var(--bg-primary, white);
    max-height: 70vh;
    overflow-y: auto;
}

.ergon-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
    background: var(--bg-secondary, #f8fafc);
}

/* Modal Form Styles */
.ergon-modal .form-group {
    margin-bottom: 1.25rem;
}

.ergon-modal .form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-primary, #374151);
    font-size: 0.875rem;
}

.ergon-modal .form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: var(--border-radius, 6px);
    font-size: 0.875rem;
    background: var(--bg-primary, white);
    color: var(--text-primary, #1f2937);
    transition: var(--transition, all 0.2s ease);
}

.ergon-modal .form-control:focus {
    outline: none;
    border-color: var(--primary, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.ergon-modal .form-control::placeholder {
    color: var(--text-muted, #9ca3af);
}

/* Button Styles */
.ergon-modal .btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition, all 0.2s ease);
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.ergon-modal .btn--primary {
    background: var(--primary, #3b82f6);
    color: white;
}

.ergon-modal .btn--primary:hover {
    background: var(--primary-dark, #2563eb);
}

.ergon-modal .btn--secondary {
    background: var(--bg-secondary, #f8fafc);
    color: var(--text-secondary, #374151);
    border: 1px solid var(--border-color, #d1d5db);
}

.ergon-modal .btn--secondary:hover {
    background: var(--bg-hover, #f3f4f6);
}

.ergon-modal .btn--success {
    background: var(--success, #10b981);
    color: white;
}

.ergon-modal .btn--success:hover {
    background: var(--success-dark, #059669);
}

.ergon-modal .btn--warning {
    background: var(--warning, #f59e0b);
    color: white;
}

.ergon-modal .btn--warning:hover {
    background: var(--warning-dark, #d97706);
}

.ergon-modal .btn--danger {
    background: var(--danger, #ef4444);
    color: white;
}

.ergon-modal .btn--danger:hover {
    background: var(--danger-dark, #dc2626);
}

/* Animation */
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .ergon-modal-content {
        margin: 2% auto;
        width: 95%;
        max-width: none;
    }
    
    .ergon-modal-header,
    .ergon-modal-body,
    .ergon-modal-footer {
        padding: 1rem;
    }
    
    .ergon-modal-footer {
        flex-direction: column;
    }
    
    .ergon-modal-footer .btn {
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
// Standardized Modal JavaScript - Based on Follow-ups Pattern
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Focus trap
        const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusableElements.length > 0) {
            focusableElements[0].focus();
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

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.ergon-modal[style*="display: block"]');
        openModals.forEach(modal => {
            closeModal(modal.id);
        });
    }
});

// Close modal when clicking outside (handled by backdrop onclick in renderModal)
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('ergon-modal')) {
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