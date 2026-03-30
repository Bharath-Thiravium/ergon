<?php
// Location Picker Component
// Usage: include this file and call renderLocationPicker($config)

function renderLocationPicker($config = []) {
    $defaults = [
        'place_id' => 'place',
        'latitude_id' => 'latitude',
        'longitude_id' => 'longitude',
        'map_id' => 'locationMap',
        'search_id' => 'locationSearch',
        'height' => '300px',
        'show_radius' => false,
        'radius_id' => 'radius',
        'default_radius' => 100
    ];
    
    $config = array_merge($defaults, $config);
    
    require_once __DIR__ . '/../../app/config/constants.php';
?>

<div class="location-picker-container">
    <div class="form-group">
        <label class="form-label">📍 Place Name</label>
        <input type="text" class="form-control" id="<?= $config['place_id'] ?>" name="place" placeholder="Enter place name">
    </div>
    
    <div class="form-group">
        <label class="form-label">🔍 Search Location</label>
        <input type="text" class="form-control" id="<?= $config['search_id'] ?>" placeholder="Search for location...">
    </div>
    
    <div id="<?= $config['map_id'] ?>" style="height: <?= $config['height'] ?>; border-radius: 8px; margin: 1rem 0; border: 2px solid #ddd;"></div>
    
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">Latitude</label>
            <input type="number" class="form-control" id="<?= $config['latitude_id'] ?>" name="latitude" step="0.000001" readonly>
        </div>
        <div class="form-group">
            <label class="form-label">Longitude</label>
            <input type="number" class="form-control" id="<?= $config['longitude_id'] ?>" name="longitude" step="0.000001" readonly>
        </div>
    </div>
    
    <?php if ($config['show_radius']): ?>
    <div class="form-group">
        <label class="form-label">Check-in Radius (meters)</label>
        <input type="number" class="form-control" id="<?= $config['radius_id'] ?>" name="checkin_radius" value="<?= $config['default_radius'] ?>" min="10" max="1000">
    </div>
    <?php endif; ?>
    
    <div class="form-actions">
        <button type="button" class="btn btn--secondary" onclick="getCurrentLocationFor<?= ucfirst($config['map_id']) ?>()">
            <span>📍</span> Use Current Location
        </button>
    </div>
</div>

<?php if (USE_GOOGLE_MAPS && !empty(GOOGLE_MAPS_API_KEY)): ?>
<script>
let <?= $config['map_id'] ?>Map, <?= $config['map_id'] ?>Marker;

function init<?= ucfirst($config['map_id']) ?>() {
    const defaultLat = 28.6139;
    const defaultLng = 77.2090;
    
    <?= $config['map_id'] ?>Map = new google.maps.Map(document.getElementById('<?= $config['map_id'] ?>'), {
        center: { lat: defaultLat, lng: defaultLng },
        zoom: 13,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true
    });
    
    <?= $config['map_id'] ?>Marker = new google.maps.Marker({
        position: { lat: defaultLat, lng: defaultLng },
        map: <?= $config['map_id'] ?>Map,
        draggable: true,
        title: 'Selected Location'
    });
    
    <?= $config['map_id'] ?>Marker.addListener('dragend', function() {
        const position = <?= $config['map_id'] ?>Marker.getPosition();
        update<?= ucfirst($config['map_id']) ?>Inputs(position.lat(), position.lng());
        reverseGeocode<?= ucfirst($config['map_id']) ?>(position.lat(), position.lng());
    });
    
    <?= $config['map_id'] ?>Map.addListener('click', function(e) {
        const lat = e.latLng.lat();
        const lng = e.latLng.lng();
        <?= $config['map_id'] ?>Marker.setPosition({ lat: lat, lng: lng });
        update<?= ucfirst($config['map_id']) ?>Inputs(lat, lng);
        reverseGeocode<?= ucfirst($config['map_id']) ?>(lat, lng);
    });
}

function reverseGeocode<?= ucfirst($config['map_id']) ?>(lat, lng) {
    const geocoder = new google.maps.Geocoder();
    const latlng = { lat: lat, lng: lng };
    
    geocoder.geocode({ location: latlng }, (results, status) => {
        if (status === 'OK' && results[0]) {
            document.getElementById('<?= $config['place_id'] ?>').value = results[0].formatted_address;
        }
    });
}

function search<?= ucfirst($config['map_id']) ?>Location(query) {
    const service = new google.maps.places.PlacesService(<?= $config['map_id'] ?>Map);
    const request = {
        query: query,
        fields: ['name', 'geometry']
    };
    
    service.findPlaceFromQuery(request, (results, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK && results[0]) {
            const place = results[0];
            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            
            <?= $config['map_id'] ?>Map.setCenter({ lat: lat, lng: lng });
            <?= $config['map_id'] ?>Map.setZoom(15);
            <?= $config['map_id'] ?>Marker.setPosition({ lat: lat, lng: lng });
            update<?= ucfirst($config['map_id']) ?>Inputs(lat, lng);
            reverseGeocode<?= ucfirst($config['map_id']) ?>(lat, lng);
        }
    });
}

<?php else: ?>
<script>
let <?= $config['map_id'] ?>Map, <?= $config['map_id'] ?>Marker;

function init<?= ucfirst($config['map_id']) ?>() {
    const defaultLat = 28.6139;
    const defaultLng = 77.2090;
    
    <?= $config['map_id'] ?>Map = L.map('<?= $config['map_id'] ?>').setView([defaultLat, defaultLng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(<?= $config['map_id'] ?>Map);
    
    <?= $config['map_id'] ?>Marker = L.marker([defaultLat, defaultLng], {
        draggable: true
    }).addTo(<?= $config['map_id'] ?>Map);
    
    <?= $config['map_id'] ?>Marker.on('dragend', function(e) {
        const position = e.target.getLatLng();
        update<?= ucfirst($config['map_id']) ?>Inputs(position.lat, position.lng);
        reverseGeocode<?= ucfirst($config['map_id']) ?>(position.lat, position.lng);
    });
    
    <?= $config['map_id'] ?>Map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        <?= $config['map_id'] ?>Marker.setLatLng([lat, lng]);
        update<?= ucfirst($config['map_id']) ?>Inputs(lat, lng);
        reverseGeocode<?= ucfirst($config['map_id']) ?>(lat, lng);
    });
}

function reverseGeocode<?= ucfirst($config['map_id']) ?>(lat, lng) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(response => response.json())
        .then(data => {
            if (data.display_name) {
                document.getElementById('<?= $config['place_id'] ?>').value = data.display_name;
            }
        })
        .catch(error => {
            console.error('Reverse geocoding failed:', error);
        });
}

function search<?= ucfirst($config['map_id']) ?>Location(query) {
    if (query.length < 3) return;
    
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                const result = data[0];
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                
                <?= $config['map_id'] ?>Map.setView([lat, lng], 15);
                <?= $config['map_id'] ?>Marker.setLatLng([lat, lng]);
                update<?= ucfirst($config['map_id']) ?>Inputs(lat, lng);
                document.getElementById('<?= $config['place_id'] ?>').value = result.display_name;
            }
        })
        .catch(error => {
            console.error('Search failed:', error);
        });
}

<?php endif; ?>

function update<?= ucfirst($config['map_id']) ?>Inputs(lat, lng) {
    document.getElementById('<?= $config['latitude_id'] ?>').value = lat.toFixed(6);
    document.getElementById('<?= $config['longitude_id'] ?>').value = lng.toFixed(6);
}

function getCurrentLocationFor<?= ucfirst($config['map_id']) ?>() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                <?php if (USE_GOOGLE_MAPS && !empty(GOOGLE_MAPS_API_KEY)): ?>
                <?= $config['map_id'] ?>Map.setCenter({ lat: lat, lng: lng });
                <?= $config['map_id'] ?>Map.setZoom(15);
                <?= $config['map_id'] ?>Marker.setPosition({ lat: lat, lng: lng });
                <?php else: ?>
                <?= $config['map_id'] ?>Map.setView([lat, lng], 15);
                <?= $config['map_id'] ?>Marker.setLatLng([lat, lng]);
                <?php endif; ?>
                
                update<?= ucfirst($config['map_id']) ?>Inputs(lat, lng);
                reverseGeocode<?= ucfirst($config['map_id']) ?>(lat, lng);
            },
            function(error) {
                alert('Error getting location: ' + error.message);
            }
        );
    } else {
        alert('Geolocation is not supported by this browser.');
    }
}

function set<?= ucfirst($config['map_id']) ?>Location(lat, lng, place) {
    if (lat && lng) {
        <?php if (USE_GOOGLE_MAPS && !empty(GOOGLE_MAPS_API_KEY)): ?>
        <?= $config['map_id'] ?>Map.setCenter({ lat: lat, lng: lng });
        <?= $config['map_id'] ?>Map.setZoom(15);
        <?= $config['map_id'] ?>Marker.setPosition({ lat: lat, lng: lng });
        <?php else: ?>
        <?= $config['map_id'] ?>Map.setView([lat, lng], 15);
        <?= $config['map_id'] ?>Marker.setLatLng([lat, lng]);
        <?php endif; ?>
        
        update<?= ucfirst($config['map_id']) ?>Inputs(lat, lng);
        if (place) {
            document.getElementById('<?= $config['place_id'] ?>').value = place;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('<?= $config['search_id'] ?>');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                search<?= ucfirst($config['map_id']) ?>Location(this.value);
            }
        });
    }
});
</script>

<?php
}
?>

<style>
.location-picker-container .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.location-picker-container .form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .location-picker-container .form-grid {
        grid-template-columns: 1fr;
    }
    
    .location-picker-container .form-actions {
        flex-direction: column;
    }
}
</style>
