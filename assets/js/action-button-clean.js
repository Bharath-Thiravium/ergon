/* Clean Action Button Handler - Self-contained */

class ActionButton {
    constructor() {
        this.init();
    }

    init() {
        document.addEventListener('click', this.handleClick.bind(this));
    }

    handleClick(event) {
        const btn = event.target.closest('.ab-btn');
        if (!btn) return;

        const action = btn.dataset.action;
        const id = btn.dataset.id;
        const name = btn.dataset.name;

        switch (action) {
            case 'view':
                this.handleView(btn, id);
                break;
            case 'edit':
                this.handleEdit(btn, id);
                break;
            case 'delete':
                this.handleDelete(btn, id, name);
                break;
            case 'approve':
                this.handleApprove(btn, id, name);
                break;
            case 'reject':
                this.handleReject(btn, id, name);
                break;
            default:
                console.log('Action button clicked:', action, id);
        }
    }

    handleView(btn, id) {
        const module = btn.dataset.module || 'tasks';
        window.location.href = `/ergon/${module}/view/${id}`;
    }

    handleEdit(btn, id) {
        const module = btn.dataset.module || 'tasks';
        window.location.href = `/ergon/${module}/edit/${id}`;
    }

    handleDelete(btn, id, name) {
        if (confirm(`Delete "${name}"? This cannot be undone.`)) {
            const module = btn.dataset.module || 'tasks';
            fetch(`/ergon/${module}/delete/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Delete failed'));
                }
            })
            .catch(() => alert('Network error occurred'));
        }
    }

    handleApprove(btn, id, name) {
        if (confirm(`Approve "${name}"?`)) {
            const module = btn.dataset.module || 'tasks';
            fetch(`/ergon/${module}/approve/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Approval failed'));
                }
            })
            .catch(() => alert('Network error occurred'));
        }
    }

    handleReject(btn, id, name) {
        const reason = prompt(`Reject "${name}"?\n\nReason (optional):`);
        if (reason !== null) {
            const module = btn.dataset.module || 'tasks';
            fetch(`/ergon/${module}/reject/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Rejection failed'));
                }
            })
            .catch(() => alert('Network error occurred'));
        }
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new ActionButton());
} else {
    new ActionButton();
}