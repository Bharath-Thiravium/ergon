function editFollowup(id) {
    // Strip F_ prefix if present
    id = id.replace('F_', '');
    showModal('editModal');
    document.getElementById('editFollowupId').value = id;
    
    const form = document.getElementById('editForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = document.querySelector('button[form="editForm"]');
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }
        
        fetch(`/ergon/contacts/followups/edit/${id}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('editModal');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to update follow-up'));
            }
        })
        .catch(error => {
            console.error('Edit error:', error);
            alert('Network error occurred');
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Update';
            }
        });
    };
}

function deleteFollowup(id) {
    // Strip F_ prefix if present
    id = id.replace('F_', '');
    if (confirm('Are you sure you want to delete this follow-up? This action cannot be undone.')) {
        const formData = new FormData();
        
        fetch(`/ergon/contacts/followups/delete/${id}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Follow-up deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to delete follow-up'));
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            alert('Network error occurred');
        });
    }
}
