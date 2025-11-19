// Simple working reschedule function
function rescheduleFollowup(id) {
    const newDate = prompt('Enter new date (YYYY-MM-DD):');
    if (!newDate) return;
    
    const reason = prompt('Reason for rescheduling (optional):') || 'No reason provided';
    
    const formData = new FormData();
    formData.append('new_date', newDate);
    formData.append('reason', reason);
    
    fetch(`/ergon/contacts/followups/reschedule/${id}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Follow-up rescheduled successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to reschedule'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred');
    });
}