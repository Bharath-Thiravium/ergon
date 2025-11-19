<?php
$title = 'System Settings';
$active_page = 'settings';
ob_start();
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">
    ✅ <?= htmlspecialchars($_GET['success']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert--error">
    ❌ <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>⚙️</span> General Settings
            </h2>
        </div>
        <div class="card__body">
            <form id="settingsForm" method="POST" action="/ergon/settings">
                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? 'ERGON Company') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Attendance Radius (meters)</label>
                    <input type="number" class="form-control" name="attendance_radius" value="<?= htmlspecialchars($settings['attendance_radius'] ?? '5') ?>" min="5" step="1">
                    <small class="form-text">Minimum 5 meters required for attendance validation</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Office Location</label>
                    <div class="location-controls">
                        <button type="button" class="btn-location-small" onclick="getCurrentLocation()" title="Use Current Location">
                            📍
                        </button>
                        <a href="/ergon/settings/map-picker" class="btn btn--secondary">
                            <span>🗺️</span> Open Map Picker
                        </a>
                    </div>
                    <div id="preview-map" style="height: 300px; margin: 1rem 0; border-radius: 8px;"></div>
                    <div class="location-input-grid">
                        <div class="form-group">
                            <label class="form-label">Latitude</label>
                            <input type="number" class="form-control" name="office_latitude" id="office_latitude" step="0.000001" placeholder="28.6139" value="<?= htmlspecialchars($settings['base_location_lat'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Longitude</label>
                            <input type="number" class="form-control" name="office_longitude" id="office_longitude" step="0.000001" placeholder="77.2090" value="<?= htmlspecialchars($settings['base_location_lng'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn--primary">Save Settings</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📊</span> System Information
            </h2>
        </div>
        <div class="card__body">
            <div class="table-responsive">
                <table class="table">
                    <tr>
                        <td>Version</td>
                        <td>2.0.0</td>
                    </tr>
                    <tr>
                        <td>Environment</td>
                        <td>Development</td>
                    </tr>
                    <tr>
                        <td>PHP Version</td>
                        <td><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <td>Database</td>
                        <td>MySQL</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                document.getElementById('office_latitude').value = lat.toFixed(6);
                document.getElementById('office_longitude').value = lng.toFixed(6);
                
                updatePreviewMap(lat, lng);
            },
            function(error) {
                alert('Error getting location: ' + error.message);
            }
        );
    } else {
        alert('Geolocation is not supported by this browser.');
    }
}

function reverseGeocode(lat, lng) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.display_name) {
                const addressField = document.getElementById('office_address');
                if (addressField) {
                    addressField.value = data.display_name;
                }
            }
        })
        .catch(error => {
            console.warn('Reverse geocoding failed:', error);
            const addressField = document.getElementById('office_address');
            if (addressField) {
                addressField.value = `${lat}, ${lng}`;
            }
        });
}

let previewMap, previewMarker;

// Initialize preview map
function initPreviewMap() {
    try {
        const lat = parseFloat(document.getElementById('office_latitude').value) || 28.6139;
        const lng = parseFloat(document.getElementById('office_longitude').value) || 77.2090;
        
        const mapElement = document.getElementById('preview-map');
        if (!mapElement) {
            console.error('Map element not found');
            return;
        }
        
        previewMap = L.map('preview-map').setView([lat, lng], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(previewMap);
        
        previewMarker = L.marker([lat, lng], { draggable: true }).addTo(previewMap);
        
        previewMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            document.getElementById('office_latitude').value = pos.lat.toFixed(6);
            document.getElementById('office_longitude').value = pos.lng.toFixed(6);
        });
        
        console.log('Preview map initialized successfully');
    } catch (error) {
        console.error('Error in initPreviewMap:', error);
        showMapError();
    }
}

// Update preview map when coordinates change
function updatePreviewMap(lat, lng) {
    if (previewMap && previewMarker) {
        previewMap.setView([lat, lng], 15);
        previewMarker.setLatLng([lat, lng]);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check if Leaflet is already loaded
    if (typeof L !== 'undefined') {
        initPreviewMap();
        return;
    }
    
    // Load Leaflet CSS
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    link.onerror = function() {
        console.warn('Failed to load Leaflet CSS');
        showMapError();
    };
    document.head.appendChild(link);
    
    // Load Leaflet JS with timeout
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    
    let loadTimeout = setTimeout(function() {
        console.error('Leaflet JS loading timeout');
        showMapError();
    }, 10000); // 10 second timeout
    
    script.onload = function() {
        clearTimeout(loadTimeout);
        // Wait a bit for L to be available
        setTimeout(function() {
            if (typeof L !== 'undefined') {
                try {
                    initPreviewMap();
                } catch (error) {
                    console.error('Error initializing map:', error);
                    showMapError();
                }
            } else {
                console.error('Leaflet L object not available');
                showMapError();
            }
        }, 100);
    };
    
    script.onerror = function() {
        clearTimeout(loadTimeout);
        console.error('Failed to load Leaflet JS');
        showMapError();
    };
    
    document.head.appendChild(script);
});

function showMapError() {
    const mapDiv = document.getElementById('preview-map');
    if (mapDiv) {
        mapDiv.innerHTML = `
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;background:#f8f9fa;color:#6c757d;border-radius:8px;padding:20px;text-align:center;">
                <div style="font-size:48px;margin-bottom:16px;">🗺️</div>
                <div style="font-weight:600;margin-bottom:8px;">Map Unavailable</div>
                <div style="font-size:14px;">Please check your internet connection or enter coordinates manually</div>
            </div>
        `;
    }
}

// Form will submit normally to POST /ergon/settings
</script>

<style>
.location-input-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.location-controls {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.btn-location-small {
    padding: 0.5rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    cursor: pointer;
    font-size: 1rem;
    transition: var(--transition);
    min-width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-location-small:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-1px);
}

#preview-map {
    border: 2px solid var(--border-color);
    box-shadow: var(--shadow);
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
