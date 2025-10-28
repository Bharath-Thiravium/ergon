<?php
$title = 'System Settings';
$active_page = 'settings';
ob_start();
?>

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
                    <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($data['settings']['company_name'] ?? 'ERGON Company') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <select class="form-control" name="timezone">
                        <option value="Asia/Kolkata" <?= ($data['settings']['timezone'] ?? '') === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata</option>
                        <option value="UTC" <?= ($data['settings']['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Working Hours per Day</label>
                    <input type="number" class="form-control" name="working_hours_start" value="<?= htmlspecialchars($data['settings']['working_hours_start'] ?? '09:00') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Attendance Radius (meters)</label>
                    <input type="number" class="form-control" name="attendance_radius" value="<?= htmlspecialchars($data['settings']['attendance_radius'] ?? '200') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Office Location</label>
                    <input type="text" class="form-control" id="locationSearch" placeholder="Enter office address...">
                    <div class="location-picker">
                        <div class="location-input-grid">
                            <div class="form-group">
                                <label class="form-label">Latitude</label>
                                <input type="number" class="form-control" name="office_latitude" id="office_latitude" step="0.000001" placeholder="28.6139" value="<?= htmlspecialchars($data['settings']['office_latitude'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Longitude</label>
                                <input type="number" class="form-control" name="office_longitude" id="office_longitude" step="0.000001" placeholder="77.2090" value="<?= htmlspecialchars($data['settings']['office_longitude'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="button" class="btn btn--secondary" onclick="getCurrentLocation()">
                            <span>📍</span> Use Current Location
                        </button>
                        <a href="/ergon/settings/location" class="btn btn--primary">
                            <span>🗺️</span> Advanced Map Picker
                        </a>
                        <input type="hidden" name="office_address" id="office_address" value="<?= htmlspecialchars($data['settings']['office_address'] ?? '') ?>">
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
                
                document.getElementById('office_latitude').value = lat;
                document.getElementById('office_longitude').value = lng;
                
                // Reverse geocoding using a free service
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

function reverseGeocode(lat, lng) {
    // Using OpenStreetMap Nominatim for reverse geocoding (free alternative)
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(response => response.json())
        .then(data => {
            if (data.display_name) {
                document.getElementById('office_address').value = data.display_name;
                document.getElementById('locationSearch').value = data.display_name;
            }
        })
        .catch(error => {
            console.log('Reverse geocoding failed:', error);
            document.getElementById('office_address').value = `${lat}, ${lng}`;
        });
}

// Address search using OpenStreetMap Nominatim
document.getElementById('locationSearch').addEventListener('input', function() {
    const query = this.value;
    if (query.length > 3) {
        searchLocation(query);
    }
});

function searchLocation(query) {
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                const result = data[0];
                document.getElementById('office_latitude').value = result.lat;
                document.getElementById('office_longitude').value = result.lon;
                document.getElementById('office_address').value = result.display_name;
            }
        })
        .catch(error => {
            console.log('Location search failed:', error);
        });
}

// Form will submit normally to POST /ergon/settings
</script>

<style>
.location-picker {
    margin-top: 10px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9;
}

.location-input-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.location-picker .form-group {
    margin-bottom: 0.5rem;
}

.location-picker .form-label {
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
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
