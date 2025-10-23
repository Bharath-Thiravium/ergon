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
let userLocation = null;

// Get user location
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

// Clock in/out functionality
document.getElementById('clock-in-btn').addEventListener('click', function() {
    if (!userLocation) {
        alert('Please allow location access to clock in');
        return;
    }
    
    fetch('/ergon/attendance/clock', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'clock_in',
            latitude: userLocation.latitude,
            longitude: userLocation.longitude,
            location_name: 'Office Location'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('status').textContent = 'Clocked In';
            document.getElementById('clock-in-btn').style.display = 'none';
            document.getElementById('clock-out-btn').style.display = 'inline-block';
        } else {
            alert('Clock in failed: ' + data.message);
        }
    });
});

document.getElementById('clock-out-btn').addEventListener('click', function() {
    fetch('/ergon/attendance/clock', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'clock_out'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('status').textContent = 'Clocked Out';
            document.getElementById('clock-in-btn').style.display = 'inline-block';
            document.getElementById('clock-out-btn').style.display = 'none';
        } else {
            alert('Clock out failed: ' + data.message);
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>