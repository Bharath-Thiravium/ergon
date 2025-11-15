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
        .then(response => response.json())
        .then(data => {
            if (data.display_name) {
                document.getElementById('office_address').value = data.display_name;
            }
        })
        .catch(error => {
            document.getElementById('office_address').value = `${lat}, ${lng}`;
        });
}

let previewMap, previewMarker;

// Initialize preview map
function initPreviewMap() {
    const lat = parseFloat(document.getElementById('office_latitude').value) || 28.6139;
    const lng = parseFloat(document.getElementById('office_longitude').value) || 77.2090;
    
    previewMap = L.map('preview-map').setView([lat, lng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(previewMap);
    
    previewMarker = L.marker([lat, lng], { draggable: true }).addTo(previewMap);
    
    previewMarker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        document.getElementById('office_latitude').value = pos.lat.toFixed(6);
        document.getElementById('office_longitude').value = pos.lng.toFixed(6);
    });
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
    // Load Leaflet CSS and JS
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);
    
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = initPreviewMap;
    document.head.appendChild(script);
});

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
