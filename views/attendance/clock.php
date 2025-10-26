<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h4><i class="fas fa-clock me-2"></i>Clock In/Out</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <div id="currentTime" class="h3 text-primary"></div>
                        <div id="currentDate" class="text-muted"></div>
                    </div>
                    
                    <div class="d-grid gap-3">
                        <button id="clockInBtn" class="btn btn-success btn-lg">
                            <i class="fas fa-play me-2"></i>Clock In
                        </button>
                        <button id="clockOutBtn" class="btn btn-danger btn-lg">
                            <i class="fas fa-stop me-2"></i>Clock Out
                        </button>
                    </div>
                    
                    <div id="locationStatus" class="mt-3 text-muted">
                        <small><i class="fas fa-map-marker-alt me-1"></i>Getting location...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPosition = null;

function updateTime() {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleTimeString();
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                currentPosition = position;
                document.getElementById('locationStatus').innerHTML = 
                    '<small class="text-success"><i class="fas fa-check me-1"></i>Location detected</small>';
            },
            function(error) {
                document.getElementById('locationStatus').innerHTML = 
                    '<small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Location unavailable</small>';
            }
        );
    }
}

function clockAction(type) {
    const formData = new FormData();
    formData.append('type', type);
    
    if (currentPosition) {
        formData.append('latitude', currentPosition.coords.latitude);
        formData.append('longitude', currentPosition.coords.longitude);
    }
    
    fetch('/ergon/public/attendance/clock', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            ERGON.showAlert(`Clocked ${type} successfully!`, 'success');
            setTimeout(() => window.location.href = '/ergon/public/attendance', 1000);
        } else {
            ERGON.showAlert(data.error, 'danger');
        }
    });
}

document.getElementById('clockInBtn').addEventListener('click', () => clockAction('in'));
document.getElementById('clockOutBtn').addEventListener('click', () => clockAction('out'));

// Initialize
updateTime();
setInterval(updateTime, 1000);
getLocation();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
