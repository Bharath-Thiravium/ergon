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
                    <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? 'ERGON Company') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <select class="form-control" name="timezone">
                        <option value="Asia/Kolkata" <?= ($settings['timezone'] ?? '') === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata</option>
                        <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Working Hours Start Time</label>
                    <input type="time" class="form-control" name="working_hours_start" value="<?= htmlspecialchars(substr($settings['working_hours_start'] ?? '09:00:00', 0, 5)) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Working Hours End Time</label>
                    <input type="time" class="form-control" name="working_hours_end" value="<?= htmlspecialchars(substr($settings['working_hours_end'] ?? '18:00:00', 0, 5)) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Attendance Radius (meters)</label>
                    <input type="number" class="form-control" name="attendance_radius" value="<?= htmlspecialchars($settings['attendance_radius'] ?? '200') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Office Address</label>
                    <input type="text" class="form-control" name="office_address" id="office_address" placeholder="Enter office address..." value="<?= htmlspecialchars($settings['office_address'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Office Coordinates</label>
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
                    <button type="button" class="btn btn--secondary" onclick="getCurrentLocation()">
                        <span>📍</span> Use Current Location
                    </button>
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

// Form will submit normally to POST /ergon/settings
</script>

<style>
.location-input-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
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
