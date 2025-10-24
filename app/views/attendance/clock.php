<?php
$title = 'Clock In/Out';
$active_page = 'attendance';
ob_start();
?>

<div class="page-header">
    <h1>📍 Clock In/Out</h1>
</div>

<div class="card">
    <div class="card__body">
        <div id="clock-interface">
            <div class="clock-status">
                <h3>Current Status: <span id="status">Not Clocked In</span></h3>
                <p id="location-info">Getting location...</p>
            </div>
            
            <div class="clock-actions">
                <button id="clock-in-btn" class="btn btn--success btn--lg">Clock In</button>
                <button id="clock-out-btn" class="btn btn--danger btn--lg" style="display:none;">Clock Out</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize location tracking
let userLocation = null;

// Get user location on page load
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
        userLocation = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
        };
        document.getElementById('location-info').textContent = 
            `Location: ${userLocation.latitude.toFixed(6)}, ${userLocation.longitude.toFixed(6)}`;
    }, function(error) {
        document.getElementById('location-info').textContent = 'Location access denied';
    });
} else {
    document.getElementById('location-info').textContent = 'Geolocation not supported';
}

// Use global functions from ERGON core
document.addEventListener('DOMContentLoaded', function() {
    const clockInBtn = document.getElementById('clock-in-btn');
    const clockOutBtn = document.getElementById('clock-out-btn');
    
    if (clockInBtn) {
        clockInBtn.addEventListener('click', ERGON.pages.attendance.clockIn);
    }
    
    if (clockOutBtn) {
        clockOutBtn.addEventListener('click', ERGON.pages.attendance.clockOut);
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>