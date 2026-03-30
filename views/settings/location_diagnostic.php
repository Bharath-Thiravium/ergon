<?php
$title = 'Location Diagnostic';
$active_page = 'settings';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🔍</span> Location Diagnostic</h1>
        <p>Test and debug attendance location validation</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/settings" class="btn btn--secondary">
            <span>⚙️</span> Back to Settings
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📍</span> Current Location Test
            </h2>
        </div>
        <div class="card__body">
            <div id="locationStatus" class="alert alert--info">
                <span>📍</span> Click "Get My Location" to test your current position
            </div>
            
            <button id="getLocationBtn" class="btn btn--primary" onclick="testCurrentLocation()">
                <span>📍</span> Get My Location & Test
            </button>
            
            <div id="locationResults" style="margin-top: 1rem; display: none;">
                <h4>Your Location:</h4>
                <div id="userCoords"></div>
                
                <h4>Validation Results:</h4>
                <div id="validationResults"></div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>⚙️</span> Configured Locations
            </h2>
        </div>
        <div class="card__body">
            <div id="configuredLocations">Loading...</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>🧪</span> Manual Location Test
            </h2>
        </div>
        <div class="card__body">
            <form id="manualTestForm">
                <div class="location-input-grid">
                    <div class="form-group">
                        <label class="form-label">Test Latitude</label>
                        <input type="number" class="form-control" id="testLat" step="0.000001" placeholder="28.6139">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Test Longitude</label>
                        <input type="number" class="form-control" id="testLng" step="0.000001" placeholder="77.2090">
                    </div>
                </div>
                <button type="button" class="btn btn--secondary" onclick="testManualLocation()">
                    <span>🧪</span> Test These Coordinates
                </button>
            </form>
            
            <div id="manualResults" style="margin-top: 1rem; display: none;">
                <h4>Manual Test Results:</h4>
                <div id="manualValidation"></div>
            </div>
        </div>
    </div>
</div>

<script>
let currentUserLocation = null;

function testCurrentLocation() {
    const btn = document.getElementById('getLocationBtn');
    const status = document.getElementById('locationStatus');
    
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Getting Location...';
    status.className = 'alert alert--info';
    status.innerHTML = '<span>📍</span> Requesting location access...';
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                currentUserLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                
                displayUserLocation();
                validateUserLocation();
                
                btn.disabled = false;
                btn.innerHTML = '<span>📍</span> Get My Location & Test';
                status.className = 'alert alert--success';
                status.innerHTML = '<span>✅</span> Location obtained successfully!';
            },
            function(error) {
                btn.disabled = false;
                btn.innerHTML = '<span>📍</span> Get My Location & Test';
                status.className = 'alert alert--error';
                
                let errorMsg = 'Location access failed: ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMsg += 'Permission denied. Please allow location access.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMsg += 'Location information unavailable.';
                        break;
                    case error.TIMEOUT:
                        errorMsg += 'Location request timed out.';
                        break;
                    default:
                        errorMsg += 'Unknown error occurred.';
                        break;
                }
                status.innerHTML = '<span>❌</span> ' + errorMsg;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000
            }
        );
    } else {
        btn.disabled = false;
        btn.innerHTML = '<span>📍</span> Get My Location & Test';
        status.className = 'alert alert--error';
        status.innerHTML = '<span>❌</span> Geolocation is not supported by this browser.';
    }
}

function displayUserLocation() {
    const coordsDiv = document.getElementById('userCoords');
    const resultsDiv = document.getElementById('locationResults');
    
    coordsDiv.innerHTML = `
        <div class="location-info">
            <strong>Latitude:</strong> ${currentUserLocation.lat.toFixed(6)}<br>
            <strong>Longitude:</strong> ${currentUserLocation.lng.toFixed(6)}<br>
            <strong>Accuracy:</strong> ±${currentUserLocation.accuracy.toFixed(0)} meters
        </div>
    `;
    
    resultsDiv.style.display = 'block';
}

function validateUserLocation() {
    fetch('/ergon/api/validate-location', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            latitude: currentUserLocation.lat,
            longitude: currentUserLocation.lng
        })
    })
    .then(response => response.json())
    .then(data => {
        displayValidationResults(data);
    })
    .catch(error => {
        console.error('Validation error:', error);
        document.getElementById('validationResults').innerHTML = 
            '<div class="alert alert--error">❌ Failed to validate location</div>';
    });
}

function testManualLocation() {
    const lat = parseFloat(document.getElementById('testLat').value);
    const lng = parseFloat(document.getElementById('testLng').value);
    
    if (!lat || !lng) {
        alert('Please enter valid latitude and longitude values');
        return;
    }
    
    fetch('/ergon/api/validate-location', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            latitude: lat,
            longitude: lng
        })
    })
    .then(response => response.json())
    .then(data => {
        displayManualResults(data);
    })
    .catch(error => {
        console.error('Manual validation error:', error);
        document.getElementById('manualValidation').innerHTML = 
            '<div class="alert alert--error">❌ Failed to validate location</div>';
    });
}

function displayValidationResults(data) {
    const resultsDiv = document.getElementById('validationResults');
    
    if (data.allowed) {
        resultsDiv.innerHTML = `
            <div class="alert alert--success">
                ✅ <strong>Location Allowed</strong><br>
                You are within the allowed area for: <strong>${data.location_info.title}</strong><br>
                Distance: ${data.distance}m (max ${data.location_info.radius}m)
            </div>
        `;
    } else {
        resultsDiv.innerHTML = `
            <div class="alert alert--error">
                ❌ <strong>Location Not Allowed</strong><br>
                ${data.error.replace(/\n/g, '<br>')}
            </div>
        `;
    }
}

function displayManualResults(data) {
    const resultsDiv = document.getElementById('manualResults');
    const validationDiv = document.getElementById('manualValidation');
    
    if (data.allowed) {
        validationDiv.innerHTML = `
            <div class="alert alert--success">
                ✅ <strong>Test Location Allowed</strong><br>
                Location would be allowed for: <strong>${data.location_info.title}</strong><br>
                Distance: ${data.distance}m (max ${data.location_info.radius}m)
            </div>
        `;
    } else {
        validationDiv.innerHTML = `
            <div class="alert alert--error">
                ❌ <strong>Test Location Not Allowed</strong><br>
                ${data.error.replace(/\n/g, '<br>')}
            </div>
        `;
    }
    
    resultsDiv.style.display = 'block';
}

// Load configured locations on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('/ergon/api/configured-locations')
        .then(response => response.json())
        .then(data => {
            displayConfiguredLocations(data);
        })
        .catch(error => {
            console.error('Error loading locations:', error);
            document.getElementById('configuredLocations').innerHTML = 
                '<div class="alert alert--error">❌ Failed to load configured locations</div>';
        });
});

function displayConfiguredLocations(locations) {
    const div = document.getElementById('configuredLocations');
    
    if (locations.length === 0) {
        div.innerHTML = '<div class="alert alert--warning">⚠️ No attendance locations configured</div>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Type</th><th>Coordinates</th><th>Radius</th></tr></thead><tbody>';
    
    locations.forEach(location => {
        html += `
            <tr>
                <td><strong>${location.name}</strong></td>
                <td><span class="badge badge--${location.type === 'office' ? 'primary' : 'secondary'}">${location.type}</span></td>
                <td>${location.lat.toFixed(6)}, ${location.lng.toFixed(6)}</td>
                <td>${location.radius}m</td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    div.innerHTML = html;
}
</script>

<style>
.location-input-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.location-info {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

@media (max-width: 768px) {
    .location-input-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
