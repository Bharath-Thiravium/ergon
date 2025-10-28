<?php
$title = 'Office Location Settings';
$active_page = 'settings';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📍</span> Office Location Settings</h1>
        <p>Set your office location for attendance tracking</p>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>🗺️</span> Interactive Map
        </h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/settings">
            <div class="form-group">
                <label class="form-label">Search Location</label>
                <input type="text" class="form-control" id="searchInput" placeholder="Search for your office location...">
            </div>
            
            <div id="map" style="height: 400px; border-radius: 8px; margin: 1rem 0;"></div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Latitude</label>
                    <input type="number" class="form-control" name="office_latitude" id="latitude" step="0.000001" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude</label>
                    <input type="number" class="form-control" name="office_longitude" id="longitude" step="0.000001" readonly>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="office_address" id="address" rows="2" readonly></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Attendance Radius (meters)</label>
                <input type="number" class="form-control" name="attendance_radius" value="200" min="50" max="1000">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <span>💾</span> Save Location
                </button>
                <button type="button" class="btn btn--secondary" onclick="getCurrentLocation()">
                    <span>📍</span> Use Current Location
                </button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map, marker;

// Initialize map
function initMap() {
    // Default to Delhi, India
    const defaultLat = 28.6139;
    const defaultLng = 77.2090;
    
    map = L.map('map').setView([defaultLat, defaultLng], 13);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add marker
    marker = L.marker([defaultLat, defaultLng], {
        draggable: true
    }).addTo(map);
    
    // Update coordinates when marker is dragged
    marker.on('dragend', function(e) {
        const position = e.target.getLatLng();
        updateLocationInputs(position.lat, position.lng);
        reverseGeocode(position.lat, position.lng);
    });
    
    // Update coordinates when map is clicked
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        marker.setLatLng([lat, lng]);
        updateLocationInputs(lat, lng);
        reverseGeocode(lat, lng);
    });
    
    // Set initial values
    updateLocationInputs(defaultLat, defaultLng);
    reverseGeocode(defaultLat, defaultLng);
}

function updateLocationInputs(lat, lng) {
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
}

function reverseGeocode(lat, lng) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(response => response.json())
        .then(data => {
            if (data.display_name) {
                document.getElementById('address').value = data.display_name;
            }
        })
        .catch(error => {
            console.log('Reverse geocoding failed:', error);
            document.getElementById('address').value = `${lat}, ${lng}`;
        });
}

function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                map.setView([lat, lng], 15);
                marker.setLatLng([lat, lng]);
                updateLocationInputs(lat, lng);
                reverseGeocode(lat, lng);
            },
            function(error) {
                alert('Error getting location: ' + error.message);
            }
        );
    } else {
        alert('Geolocation is not supported by this browser.');
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchLocation(this.value);
    }
});

function searchLocation(query) {
    if (query.length < 3) return;
    
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                const result = data[0];
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                
                map.setView([lat, lng], 15);
                marker.setLatLng([lat, lng]);
                updateLocationInputs(lat, lng);
                document.getElementById('address').value = result.display_name;
            } else {
                alert('Location not found. Please try a different search term.');
            }
        })
        .catch(error => {
            console.log('Location search failed:', error);
            alert('Search failed. Please try again.');
        });
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    initMap();
});
</script>

<style>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

#map {
    border: 2px solid #ddd;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.leaflet-popup-content {
    font-family: inherit;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>