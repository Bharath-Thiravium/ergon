<?php
require_once __DIR__ . '/../../app/config/constants.php';
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
            <?php if (USE_GOOGLE_MAPS && !empty(GOOGLE_MAPS_API_KEY)): ?>
                <span class="badge">Google Maps</span>
            <?php else: ?>
                <span class="badge">OpenStreetMap</span>
            <?php endif; ?>
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
                <input type="number" class="form-control" name="attendance_radius" value="5" min="5" max="1000">
                <small class="form-text">Minimum 5 meters required for attendance validation</small>
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

<?php if (USE_GOOGLE_MAPS && !empty(GOOGLE_MAPS_API_KEY)): ?>
<!-- Google Maps Implementation -->
<script>
let map, marker;

function initMap() {
    const defaultLat = 28.6139;
    const defaultLng = 77.2090;
    
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: defaultLat, lng: defaultLng },
        zoom: 13,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true
    });
    
    marker = new google.maps.Marker({
        position: { lat: defaultLat, lng: defaultLng },
        map: map,
        draggable: true,
        title: 'Office Location'
    });
    
    // Update coordinates when marker is dragged
    marker.addListener('dragend', function() {
        const position = marker.getPosition();
        updateLocationInputs(position.lat(), position.lng());
        reverseGeocode(position.lat(), position.lng());
    });
    
    // Update coordinates when map is clicked
    map.addListener('click', function(e) {
        const lat = e.latLng.lat();
        const lng = e.latLng.lng();
        marker.setPosition({ lat: lat, lng: lng });
        updateLocationInputs(lat, lng);
        reverseGeocode(lat, lng);
    });
    
    updateLocationInputs(defaultLat, defaultLng);
    reverseGeocode(defaultLat, defaultLng);
}

function reverseGeocode(lat, lng) {
    const geocoder = new google.maps.Geocoder();
    const latlng = { lat: lat, lng: lng };
    
    geocoder.geocode({ location: latlng }, (results, status) => {
        if (status === 'OK' && results[0]) {
            document.getElementById('address').value = results[0].formatted_address;
        } else {
            document.getElementById('address').value = `${lat}, ${lng}`;
        }
    });
}

function searchLocation(query) {
    const service = new google.maps.places.PlacesService(map);
    const request = {
        query: query,
        fields: ['name', 'geometry']
    };
    
    service.findPlaceFromQuery(request, (results, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK && results[0]) {
            const place = results[0];
            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            
            map.setCenter({ lat: lat, lng: lng });
            map.setZoom(15);
            marker.setPosition({ lat: lat, lng: lng });
            updateLocationInputs(lat, lng);
            reverseGeocode(lat, lng);
        } else {
            alert('Location not found. Please try a different search term.');
        }
    });
}
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places&callback=initMap"></script>

<?php else: ?>
<!-- OpenStreetMap Implementation -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map, marker;

function initMap() {
    const defaultLat = 28.6139;
    const defaultLng = 77.2090;
    
    map = L.map('map').setView([defaultLat, defaultLng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    marker = L.marker([defaultLat, defaultLng], {
        draggable: true
    }).addTo(map);
    
    marker.on('dragend', function(e) {
        const position = e.target.getLatLng();
        updateLocationInputs(position.lat, position.lng);
        reverseGeocode(position.lat, position.lng);
    });
    
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        marker.setLatLng([lat, lng]);
        updateLocationInputs(lat, lng);
        reverseGeocode(lat, lng);
    });
    
    updateLocationInputs(defaultLat, defaultLng);
    reverseGeocode(defaultLat, defaultLng);
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
            document.getElementById('address').value = `${lat}, ${lng}`;
        });
}

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
            alert('Search failed. Please try again.');
        });
}

document.addEventListener('DOMContentLoaded', function() {
    initMap();
});
</script>
<?php endif; ?>

<script>
function updateLocationInputs(lat, lng) {
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
}

function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                <?php if (USE_GOOGLE_MAPS && !empty(GOOGLE_MAPS_API_KEY)): ?>
                map.setCenter({ lat: lat, lng: lng });
                map.setZoom(15);
                marker.setPosition({ lat: lat, lng: lng });
                <?php else: ?>
                map.setView([lat, lng], 15);
                marker.setLatLng([lat, lng]);
                <?php endif; ?>
                
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

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchLocation(this.value);
    }
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

.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    background: var(--primary);
    color: white;
    border-radius: 4px;
    margin-left: 0.5rem;
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
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>