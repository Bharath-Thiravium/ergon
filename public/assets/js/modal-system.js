/**
 * Optimized Modal System for ERGON
 */

class ModalSystem {
    constructor() {
        this.overlay = null;
        this.init();
    }

    init() {
        // Create reusable overlay
        this.overlay = document.createElement('div');
        this.overlay.className = 'modal-overlay';
        this.overlay.onclick = (e) => e.target === this.overlay && this.close();
        document.addEventListener('keydown', (e) => e.key === 'Escape' && this.close());
    }

    // Create and show modal
    show(options = {}) {
        const { title = 'Modal', body = '', size = 'md', buttons = [], closable = true } = options;
        
        this.close();
        
        this.overlay.innerHTML = `
            <div class="modal modal-${size}">
                <div class="modal-header">
                    <h3 class="modal-title">${title}</h3>
                    ${closable ? '<button class="modal-close" onclick="modalSystem.close()">&times;</button>' : ''}
                </div>
                <div class="modal-body">${body}</div>
                ${buttons.length ? `<div class="modal-footer">${buttons.map(btn => 
                    `<button class="btn ${btn.class || 'btn-secondary'}" onclick="${btn.onclick || 'modalSystem.close()'}">${btn.text}</button>`
                ).join('')}</div>` : ''}
            </div>
        `;
        
        this.overlay.className = 'modal-overlay active';
        document.body.appendChild(this.overlay);
        document.body.style.overflow = 'hidden';
        
        return this.overlay;
    }

    // Show confirmation dialog
    confirm(options = {}) {
        const { title = 'Confirm', message = 'Are you sure?', confirmText = 'Confirm', cancelText = 'Cancel', confirmClass = 'btn-danger', onConfirm, onCancel } = options;
        
        return this.show({
            title, size: 'sm',
            body: `<p style="margin:0;font-size:16px">${message}</p>`,
            buttons: [
                { text: cancelText, class: 'btn-outline', onclick: `modalSystem.close();${onCancel?`(${onCancel})()`:''}`},
                { text: confirmText, class: confirmClass, onclick: `modalSystem.close();${onConfirm?`(${onConfirm})()`:''}`}
            ]
        });
    }

    // Show alert dialog
    alert(options = {}) {
        const { title = 'Alert', message = '', type = 'info', buttonText = 'OK', onClose } = options;
        const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
        
        return this.show({
            title: `${icons[type] || icons.info} ${title}`, size: 'sm',
            body: `<div class="alert alert-${type === 'error' ? 'danger' : type}"><p style="margin:0">${message}</p></div>`,
            buttons: [{ text: buttonText, class: 'btn-primary', onclick: `modalSystem.close();${onClose?`(${onClose})()`:''}`}]
        });
    }

    // Show form modal
    form(options = {}) {
        const { title = 'Form', fields = [], submitText = 'Submit', cancelText = 'Cancel', onSubmit, onCancel, size = 'md' } = options;
        
        return this.show({
            title, size,
            body: `<form id="modalForm" onsubmit="return false">${fields.map(f => this.renderField(f)).join('')}</form>`,
            buttons: [
                { text: cancelText, class: 'btn-outline', onclick: `modalSystem.close();${onCancel?`(${onCancel})()`:''}`},
                { text: submitText, class: 'btn-primary', onclick: `modalSystem.submitForm(${onSubmit||'null'})`}
            ]
        });
    }

    // Render form field
    renderField(f) {
        const req = f.required ? 'required' : '';
        const val = f.value || '';
        let field;
        
        if (f.type === 'select') {
            field = `<select name="${f.name}" class="form-control" ${req}><option value="">Select ${f.label}</option>${f.options.map(o => `<option value="${o.value}" ${o.value === val ? 'selected' : ''}>${o.text}</option>`).join('')}</select>`;
        } else if (f.type === 'textarea') {
            field = `<textarea name="${f.name}" class="form-control" rows="${f.rows||3}" placeholder="${f.placeholder||''}" ${req}>${val}</textarea>`;
        } else if (f.type === 'range') {
            field = `<div class="range-container"><input type="range" name="${f.name}" class="range-input" min="0" max="100" value="${val}" oninput="this.nextElementSibling.textContent=this.value+'%'"><span class="range-value">${val}%</span></div>`;
        } else {
            field = `<input type="${f.type||'text'}" name="${f.name}" class="form-control" placeholder="${f.placeholder||''}" value="${val}" ${req}>`;
        }
        
        return `<div class="form-group"><label>${f.label}${f.required?' *':''}</label>${field}</div>`;
    }

    // Submit form
    submitForm(callback) {
        const form = document.getElementById('modalForm');
        if (!form || !form.checkValidity()) { form?.reportValidity(); return; }
        
        const data = Object.fromEntries(new FormData(form));
        this.close();
        if (callback) callback(data);
    }

    // Show loading modal
    loading(message = 'Loading...') {
        return this.show({
            title: 'Please Wait', size: 'sm', closable: false, buttons: [],
            body: `<div style="text-align:center;padding:20px"><div style="border:4px solid #f3f3f3;border-top:4px solid #007bff;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite;margin:0 auto 20px"></div><p>${message}</p></div><style>@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}</style>`
        });
    }

    // Close modal
    close() {
        if (this.overlay.parentNode) {
            this.overlay.remove();
            document.body.style.overflow = '';
        }
    }

    // Update modal content
    updateBody(content) {
        const body = this.overlay.querySelector('.modal-body');
        if (body) body.innerHTML = content;
    }

    // Show progress modal
    progress(options = {}) {
        const { title = 'Progress', message = 'Processing...', progress = 0 } = options;
        return this.show({
            title, size: 'sm', closable: false, buttons: [],
            body: `<div style="text-align:center"><p>${message}</p><div class="progress"><div class="progress-bar" style="width:${progress}%">${progress}%</div></div></div>`
        });
    }

    // Update progress
    updateProgress(progress, message) {
        const bar = this.overlay.querySelector('.progress-bar');
        if (bar) { bar.style.width = progress + '%'; bar.textContent = progress + '%'; }
        if (message) {
            const msg = this.overlay.querySelector('.modal-body p');
            if (msg) msg.textContent = message;
        }
    }
}

// Initialize global modal system
const modalSystem = new ModalSystem();

// Preload modal overlay on page load
document.addEventListener('DOMContentLoaded', () => modalSystem.init());

// Convenience functions
const showModal = (options) => modalSystem.show(options);
const confirmAction = (message, onConfirm, title = 'Confirm') => modalSystem.confirm({ title, message, onConfirm: onConfirm ? `() => (${onConfirm})()` : null });
const showAlert = (message, type = 'info', title = 'Alert') => modalSystem.alert({ title, message, type });
const showForm = (title, fields, onSubmit) => modalSystem.form({ title, fields, onSubmit });
const showLoading = (message = 'Loading...') => modalSystem.loading(message);
const closeModal = () => modalSystem.close();