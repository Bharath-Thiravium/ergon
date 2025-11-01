<?php
$title = 'Clock In/Out';
$active_page = 'attendance';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🕰️</span> Clock In/Out</h1>
        <p>Track your attendance with GPS location</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/attendance" class="btn btn--secondary">
            <span>📍</span> Back to Attendance
        </a>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: 1fr; max-width: 600px; margin: 0 auto;">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>🕰️</span> Current Time
            </h2>
        </div>
        <div class="card__body" style="text-align: center; padding: 2rem;">
            <div style="margin-bottom: 2rem;">
                <div id="currentTime" style="font-size: 2.5rem; font-weight: 700; color: #1f2937; margin-bottom: 0.5rem;"></div>
                <div id="currentDate" style="color: #6b7280; font-size: 1rem;"></div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 1rem; max-width: 300px; margin: 0 auto;">
                <button id="clockInBtn" class="btn btn--primary" style="padding: 1rem 2rem; font-size: 1.1rem; font-weight: 600;">
                    <span>▶️</span> Clock In
                </button>
                <button id="clockOutBtn" class="btn btn--secondary" style="padding: 1rem 2rem; font-size: 1.1rem; font-weight: 600; background: #dc2626 !important; color: white !important; border-color: #dc2626 !important;">
                    <span>⏹️</span> Clock Out
                </button>
            </div>
            
            <div id="locationStatus" style="margin-top: 1.5rem; color: #6b7280; font-size: 0.875rem;">
                <span>📍</span> Getting location...
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
                    '<span>📍</span> Location detected';
            },
            function(error) {
                document.getElementById('locationStatus').innerHTML = 
                    '<span>⚠️</span> Location unavailable';
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
    
    fetch('/ergon/attendance/clock', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(`Clocked ${type} successfully!`);
            setTimeout(() => window.location.href = '/ergon/attendance', 1000);
        } else {
            alert(data.error || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Server error occurred');
    });
}

document.getElementById('clockInBtn').addEventListener('click', () => clockAction('in'));
document.getElementById('clockOutBtn').addEventListener('click', () => clockAction('out'));

// Initialize
updateTime();
setInterval(updateTime, 1000);
getLocation();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
