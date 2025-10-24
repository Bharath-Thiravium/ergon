<?php
$title = 'System Settings';
$active_page = 'settings';

ob_start();
?>



<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">Settings updated successfully!</div>
<?php endif; ?>

<form method="POST" action="/ergon/settings" class="settings-form">
    <div class="settings-grid">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Company Information</h2>
            </div>
            <div class="card__body">
                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" 
                           value="<?= htmlspecialchars($data['settings']['company_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Backup Email</label>
                    <input type="email" name="backup_email" class="form-control" 
                           value="<?= htmlspecialchars($data['settings']['backup_email'] ?? '') ?>">
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Attendance Settings</h2>
            </div>
            <div class="card__body">
                <div class="form-group">
                    <label class="form-label">Attendance Radius (meters)</label>
                    <input type="number" name="attendance_radius" class="form-control" 
                           value="<?= $data['settings']['attendance_radius'] ?? 200 ?>" min="10" max="1000">
                    <small class="form-help">Employees must be within this radius to clock in</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Base Location</label>
                    <div class="location-picker">
                        <div id="map" class="map-container"></div>
                        <div class="location-controls">
                            <button type="button" class="btn btn--secondary" onclick="getCurrentLocation()">Use Current Location</button>
                            <button type="button" class="btn btn--secondary" onclick="searchLocation()">Search Address</button>
                        </div>
                        <input type="search" id="addressSearch" class="form-control address-search" placeholder="Search for an address...">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Latitude</label>
                    <input type="number" name="base_location_lat" id="latitude" class="form-control" 
                           value="<?= $data['settings']['base_location_lat'] ?? 0 ?>" step="0.000001" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Longitude</label>
                    <input type="number" name="base_location_lng" id="longitude" class="form-control" 
                           value="<?= $data['settings']['base_location_lng'] ?? 0 ?>" step="0.000001" readonly>
                </div>
            </div>
        </div>
    </div>
    
    <div class="settings-actions">
        <button type="submit" class="btn btn--primary">Save Settings</button>
        <button type="button" class="btn btn--secondary" onclick="resetForm()">Reset</button>
    </div>
</form>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">System Information</h2>
    </div>
    <div class="card__body">
        <div class="system-info">
            <div class="info-item">
                <span class="info-label">Version</span>
                <span class="info-value">ERGON v1.0.0</span>
            </div>
            <div class="info-item">
                <span class="info-label">PHP Version</span>
                <span class="info-value"><?= PHP_VERSION ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Database</span>
                <span class="info-value">MySQL</span>
            </div>
            <div class="info-item">
                <span class="info-label">Last Backup</span>
                <span class="info-value">Never</span>
            </div>
        </div>
    </div>
</div>

<!-- Replace YOUR_API_KEY with your actual Google Maps API key -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBs_eUlf47Hry0q_hemamm4nge4lxx6iBc&libraries=places"></script>
<script>
let map, marker, geocoder;

function initMap() {
    const lat = parseFloat(document.getElementById('latitude').value) || 0;
    const lng = parseFloat(document.getElementById('longitude').value) || 0;
    
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: lat, lng: lng },
        zoom: lat === 0 && lng === 0 ? 2 : 15
    });
    
    marker = new google.maps.Marker({
        position: { lat: lat, lng: lng },
        map: map,
        draggable: true
    });
    
    geocoder = new google.maps.Geocoder();
    
    // Update coordinates when marker is dragged
    marker.addListener('dragend', function() {
        const position = marker.getPosition();
        updateCoordinates(position.lat(), position.lng());
    });
    
    // Add click listener to map
    map.addListener('click', function(event) {
        marker.setPosition(event.latLng);
        updateCoordinates(event.latLng.lat(), event.latLng.lng());
    });
    
    // Setup address search
    const searchBox = new google.maps.places.SearchBox(document.getElementById('addressSearch'));
    
    searchBox.addListener('places_changed', function() {
        const places = searchBox.getPlaces();
        if (places.length === 0) return;
        
        const place = places[0];
        if (!place.geometry) return;
        
        const location = place.geometry.location;
        map.setCenter(location);
        map.setZoom(15);
        marker.setPosition(location);
        updateCoordinates(location.lat(), location.lng());
    });
}

function updateCoordinates(lat, lng) {
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
}

function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            map.setCenter({ lat: lat, lng: lng });
            map.setZoom(15);
            marker.setPosition({ lat: lat, lng: lng });
            updateCoordinates(lat, lng);
        }, function() {
            alert('Error: The Geolocation service failed.');
        });
    } else {
        alert('Error: Your browser doesn\'t support geolocation.');
    }
}

function searchLocation() {
    document.getElementById('addressSearch').focus();
}

function resetForm() {
    if (confirm('Are you sure you want to reset all settings?')) {
        document.querySelector('.settings-form').reset();
        initMap();
    }
}

// Initialize map when page loads
window.onload = initMap;
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>