<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Request Leave</h4>
                </div>
                <div class="card-body">
                    <form id="leaveForm">
                        <div class="mb-3">
                            <label for="type" class="form-label">Leave Type</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="casual">Casual Leave</option>
                                <option value="sick">Sick Leave</option>
                                <option value="annual">Annual Leave</option>
                                <option value="emergency">Emergency Leave</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                        <a href="/ergon_clean/public/leaves" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('leaveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/ergon_clean/public/leaves/create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            ERGON.showAlert('Leave request submitted successfully!', 'success');
            setTimeout(() => window.location.href = '/ergon_clean/public/leaves', 1000);
        } else {
            ERGON.showAlert(data.error, 'danger');
        }
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>