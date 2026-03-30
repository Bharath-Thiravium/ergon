<?php
require_once __DIR__ . '/../../app/config/constants.php';
$title = 'Project Management';
$active_page = 'project-management';
ob_start();
?>



<div class="page-header">
    <div class="page-title">
        <h1><span>📁</span> Project Management</h1>
        <p>Manage projects and departments for task organization</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--primary" onclick="showAddProjectModal()">
            <span>➕</span> Add Project
        </button>
    </div>
</div>



<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📁</div>
        </div>
        <div class="kpi-card__value"><?= count($data['projects']) ?></div>
        <div class="kpi-card__label">Total Projects</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['projects'], function($p) { return $p['status'] === 'active'; })) ?></div>
        <div class="kpi-card__label">Active Projects</div>
        <div class="kpi-card__status">Running</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏢</div>
        </div>
        <div class="kpi-card__value"><?= count($data['departments']) ?></div>
        <div class="kpi-card__label">Departments</div>
        <div class="kpi-card__status">Available</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📁</span> Projects List
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Department</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['projects'] as $project): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($project['name']) ?></strong></td>
                        <td>
                            <?php if (!empty($project['departments'])): ?>
                                <?php foreach ($project['departments'] as $dept): ?>
                                    <span class="badge badge--info"><?= htmlspecialchars($dept['name']) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="badge badge--secondary">General</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($project['latitude']) && !empty($project['longitude'])): ?>
                                <div>
                                    <?php if (!empty($project['place'])): ?>
                                        <strong><?= htmlspecialchars($project['place']) ?></strong><br>
                                    <?php endif; ?>
                                    <small>📍 <?= round($project['latitude'], 4) ?>, <?= round($project['longitude'], 4) ?></small><br>
                                    <span class="badge badge--info"><?= $project['checkin_radius'] ?>m radius</span>
                                </div>
                            <?php else: ?>
                                <span class="badge badge--secondary">No location</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge--<?= $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'warning') ?>">
                                <?= ucfirst($project['status']) ?>
                            </span>
                        </td>
                        <td><?= date('M j, Y', strtotime($project['created_at'])) ?></td>
                        <td>
                            <div class="ab-container">
                                <a href="/ergon/project-subcategories/<?= $project['id'] ?>" class="btn btn--sm" style="background:#8b5cf6;color:#fff;border:none;padding:.3rem .6rem;border-radius:4px;font-size:.85rem;text-decoration:none;" title="Work Categories">📂 Works</a>
                                <button class="ab-btn ab-btn--edit"
                                    data-id="<?= $project['id'] ?>"
                                    data-name="<?= htmlspecialchars($project['name'], ENT_QUOTES) ?>"
                                    data-description="<?= htmlspecialchars($project['description'] ?? '', ENT_QUOTES) ?>"
                                    data-place="<?= htmlspecialchars($project['place'] ?? '', ENT_QUOTES) ?>"
                                    data-lat="<?= $project['latitude'] ?? '' ?>"
                                    data-lng="<?= $project['longitude'] ?? '' ?>"
                                    data-radius="<?= $project['checkin_radius'] ?? 100 ?>"
                                    data-depts="<?= htmlspecialchars(json_encode(array_column($project['departments'], 'id')), ENT_QUOTES) ?>"
                                    data-status="<?= htmlspecialchars($project['status'], ENT_QUOTES) ?>"
                                    data-budget="<?= $project['budget'] ?? '' ?>"
                                    onclick="editProjectFromBtn(this)" title="Edit Project">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        <path d="M15 5l4 4"/>
                                    </svg>
                                </button>
                                <button class="ab-btn ab-btn--danger" onclick="deleteProject(<?= $project['id'] ?>, '<?= htmlspecialchars($project['name']) ?>')" title="Delete Project">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <polyline points="3,6 5,6 21,6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- Leaflet CSS -->
<link rel="stylesheet" href="/ergon/assets/css/leaflet.css">
<!-- Project Map CSS -->
<link rel="stylesheet" href="/ergon/assets/css/project-map.css">

<script>
let isEditing = false;
let projectMap = null;
let projectMarker = null;
let searchTimeout = null;

function showAddProjectModal() {
    isEditing = false;
    let modal = document.getElementById('projectModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'projectModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="margin: 2% auto; width: 95%; max-width: 700px; max-height: 95vh; overflow-y: auto;">
                <div class="modal-header">
                    <h3 id="modalTitle">📁 Add New Project</h3>
                    <button class="modal-close" onclick="hideProjectModal()">&times;</button>
                </div>
            <div class="modal-body">
                <form id="projectForm">
                    
                    <label>Project Name *</label>
                    <input type="text" id="projectName" name="name" class="form-control" required placeholder="Enter project name">
                    
                    <label>Departments</label>
                    <select id="projectDepartments" name="department_ids[]" class="form-control" multiple style="height: 120px;">
                        <?php foreach ($data['departments'] as $dept): ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:#666;">Hold Ctrl / Cmd to select multiple departments</small>
                    
                    <label>Description</label>
                    <textarea id="projectDescription" name="description" class="form-control" rows="3" placeholder="Project description"></textarea>
                    
                    <label>Budget (₹)</label>
                    <input type="number" id="projectBudget" name="budget" class="form-control" step="0.01" min="0" placeholder="Enter project budget">
                    
                    <label>📍 Place Name</label>
                    <input type="text" id="projectPlace" name="place" class="form-control" placeholder="Enter place name">
                    
                    <label>🔍 Search Location</label>
                    <div style="position: relative;">
                        <input type="text" id="locationSearch" class="form-control" placeholder="Search for location..." autocomplete="off">
                        <div id="searchResults" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 4px 4px; max-height: 200px; overflow-y: auto; z-index: 1000; display: none;"></div>
                    </div>
                    
                    <div id="locationMap" style="height: 300px; border-radius: 8px; margin: 1rem 0; border: 2px solid #ddd; position: relative;"></div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 1rem;">
                        <button type="button" class="btn btn--secondary" onclick="window.toggleCoordinateEdit(false)">📝 Edit Coordinates</button>
                        <button type="button" class="btn btn--secondary" onclick="window.toggleCoordinateEdit(true)">🔒 Lock Coordinates</button>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div>
                            <label>Latitude</label>
                            <input type="number" id="projectLatitude" name="latitude" class="form-control" step="0.000001" readonly>
                        </div>
                        <div>
                            <label>Longitude</label>
                            <input type="number" id="projectLongitude" name="longitude" class="form-control" step="0.000001" readonly>
                        </div>
                    </div>
                    
                    <label>Check-in Radius (meters)</label>
                    <input type="number" id="projectRadius" name="checkin_radius" class="form-control" value="100" min="10" max="1000">
                    
                    <button type="button" class="btn btn--secondary" onclick="getCurrentLocationForProject()" style="margin-top: 0.5rem;">
                        <span>📍</span> Use Current Location
                    </button>
                    
                    <div id="statusGroup" style="display: none;">
                        <label>Status</label>
                        <select id="projectStatus" name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="on_hold">On Hold</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="withheld">Withheld</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn--secondary" onclick="hideProjectModal()">Cancel</button>
                <button class="btn btn--primary" onclick="submitProjectForm()"><span id="submitText">Add Project</span></button>
            </div>
        </div>
        `;
        document.body.appendChild(modal);
    }
    
    document.getElementById('modalTitle').textContent = '📁 Add New Project';
    document.getElementById('submitText').textContent = 'Add Project';
    document.getElementById('projectForm').reset();
    const existingId = document.getElementById('projectId');
    if (existingId) existingId.remove();
    document.getElementById('statusGroup').style.display = 'none';
    
    modal.style.display = 'flex';
    
    // Initialize map after modal is shown
    setTimeout(() => {
        initProjectMap();
        setupLocationSearch();
    }, 300);
}

function editProjectFromBtn(btn) {
    const d = btn.dataset;
    editProject(
        d.id, d.name, d.description, d.place,
        d.lat || null, d.lng || null,
        d.radius || 100,
        JSON.parse(d.depts || '[]'),
        d.status, d.budget || null
    );
}

function editProject(id, name, description, place, latitude, longitude, radius, deptIds, status, budget) {
    isEditing = true;
    showAddProjectModal();
    
    setTimeout(() => {
        const form = document.getElementById('projectForm');
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.id = 'projectId';
        hiddenInput.name = 'project_id';
        hiddenInput.value = id;
        form.insertBefore(hiddenInput, form.firstChild);
        
        document.getElementById('modalTitle').textContent = '📁 Edit Project';
        document.getElementById('submitText').textContent = 'Update Project';
        document.getElementById('projectName').value = name;
        document.getElementById('projectDescription').value = description;
        document.getElementById('projectBudget').value = budget || '';
        document.getElementById('projectStatus').value = status;
        document.getElementById('statusGroup').style.display = 'block';

        // Set multiple departments
        const select = document.getElementById('projectDepartments');
        const ids = Array.isArray(deptIds) ? deptIds.map(String) : [];
        Array.from(select.options).forEach(opt => {
            opt.selected = ids.includes(opt.value);
        });
        
        // Set location data
        document.getElementById('projectPlace').value = place || '';
        document.getElementById('projectLatitude').value = latitude || '';
        document.getElementById('projectLongitude').value = longitude || '';
        document.getElementById('projectRadius').value = radius || 100;
        
        // Update map with existing location
        if (latitude && longitude && projectMap) {
            setTimeout(() => {
                setProjectMapLocation(parseFloat(latitude), parseFloat(longitude), place);
                projectMap.setView([latitude, longitude], 15);
            }, 500);
        }
    }, 100);
}

function submitProjectForm() {
    const form = document.getElementById('projectForm');
    const formData = new FormData(form);
    const projectId = document.getElementById('projectId');
    const base = APP_BASE;
    const url = projectId ? base + '/project-management/update' : base + '/project-management/create';
    
    const btn = document.querySelector('#projectModal .btn--primary');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Saving...';

    fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) throw new Error('Server returned ' + response.status);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            hideProjectModal();
            location.reload();
        } else {
            alert('Failed to save project: ' + (data.error || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save project: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function hideProjectModal() {
    const modal = document.getElementById('projectModal');
    if (modal) {
        modal.style.display = 'none';
        
        // Clean up map
        if (projectMap) {
            projectMap.remove();
            projectMap = null;
            projectMarker = null;
        }
    }
}

function deleteProject(id, name) {
    if (confirm(`Are you sure you want to delete project "${name}"? This action cannot be undone.`)) {
        const formData = new FormData();
        formData.append('project_id', id);
        const base = APP_BASE;
        
        fetch(base + '/project-management/delete', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) throw new Error('Server returned ' + response.status);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete project: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete project: ' + error.message);
        });
    }
}
</script>

<!-- Leaflet JS -->
<script src="/ergon/assets/js/leaflet.js"></script>
<!-- Offline Map -->
<script src="/ergon/assets/js/offline-map.js"></script>
<!-- Coordinate Picker -->
<script src="/ergon/assets/js/coordinate-picker.js"></script>
<!-- Location Utils -->
<script src="/ergon/assets/js/location-utils.js"></script>

<script>
function initProjectMap() {
    const mapElement = document.getElementById('locationMap');
    if (!mapElement || projectMap) return;
    
    // Default location (India center)
    const defaultLat = 20.5937;
    const defaultLng = 78.9629;
    
    try {
        // Check if Leaflet is available
        if (typeof L === 'undefined') {
            throw new Error('Leaflet library not loaded');
        }
        
        // Show loading state
        mapElement.innerHTML = '<div class="map-loading"><div style="text-align: center;"><div style="font-size: 24px; margin-bottom: 8px;">🗺️</div><div>Loading map...</div></div></div>';
        
        // Initialize map
        projectMap = L.map('locationMap').setView([defaultLat, defaultLng], 5);
        
        // Add tile layer with error handling
        const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
            errorTileUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNHB4IiBmaWxsPSIjNjY2Ij5NYXAgVGlsZSBOb3QgQXZhaWxhYmxlPC90ZXh0Pjwvc3ZnPg=='
        });
        
        tileLayer.on('tileerror', function(e) {
            console.warn('Tile loading error:', e);
        });
        
        tileLayer.addTo(projectMap);
        
        // Add click event to map
        projectMap.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            setProjectMapLocation(lat, lng);
            reverseGeocode(lat, lng);
        });
        
        // Load existing location if editing
        const existingLat = document.getElementById('projectLatitude').value;
        const existingLng = document.getElementById('projectLongitude').value;
        
        if (existingLat && existingLng) {
            setProjectMapLocation(parseFloat(existingLat), parseFloat(existingLng));
            projectMap.setView([existingLat, existingLng], 15);
        }
        
    } catch (error) {
        console.error('Error initializing map:', error);
        // Fallback to coordinate picker interface
        if (window.CoordinatePicker) {
            window.CoordinatePicker.createSimpleInterface('locationMap');
        } else {
            mapElement.innerHTML = `
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; padding: 20px; text-align: center; color: white;">
                    <div style="font-size: 48px; margin-bottom: 16px;">🗺️</div>
                    <div style="font-weight: 600; margin-bottom: 8px;">Interactive Map Unavailable</div>
                    <div style="font-size: 14px; margin-bottom: 12px; opacity: 0.9;">Use search or enter coordinates manually</div>
                    <div style="font-size: 12px; opacity: 0.7;">Search works with offline city database</div>
                </div>
            `;
        }
    }
}

function setupLocationSearch() {
    const searchInput = document.getElementById('locationSearch');
    const searchResults = document.getElementById('searchResults');
    
    if (!searchInput || !searchResults) return;
    
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        if (query.length < 3) {
            searchResults.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchLocation(query);
        }, 500);
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
}

async function searchLocation(query) {
    const searchResults = document.getElementById('searchResults');
    
    // Show loading
    searchResults.innerHTML = '<div style="padding: 10px; text-align: center; color: #666;">🔍 Searching...</div>';
    searchResults.style.display = 'block';
    
    try {
        // First try offline search
        if (window.OfflineMap) {
            const offlineResults = window.OfflineMap.searchCities(query);
            if (offlineResults && offlineResults.length > 0) {
                displayEnhancedSearchResults(offlineResults);
                return;
            }
        }
        
        // Try online search if offline fails
        if (window.LocationUtils) {
            const results = await window.LocationUtils.searchLocations(query, 5);
            if (results && results.length > 0) {
                displayEnhancedSearchResults(results);
                return;
            }
        }
        
        // Fallback to direct API call
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1&countrycodes=in`);
        const data = await response.json();
        
        if (data && data.length > 0) {
            displaySearchResults(data);
        } else {
            searchResults.innerHTML = '<div style="padding: 10px; text-align: center; color: #666;">No results found</div>';
        }
    } catch (error) {
        console.error('Search error:', error);
        // Final fallback to offline search
        if (window.OfflineMap) {
            const offlineResults = window.OfflineMap.searchCities(query);
            if (offlineResults && offlineResults.length > 0) {
                displayEnhancedSearchResults(offlineResults);
            } else {
                searchResults.innerHTML = '<div style="padding: 10px; text-align: center; color: #666;">No results found in offline database</div>';
            }
        } else {
            searchResults.innerHTML = '<div style="padding: 10px; text-align: center; color: #e74c3c;">Search unavailable</div>';
        }
    }
}

function displaySearchResults(results) {
    const searchResults = document.getElementById('searchResults');
    
    const html = results.map(result => {
        const displayName = result.display_name;
        const lat = parseFloat(result.lat);
        const lng = parseFloat(result.lon);
        const name = displayName.split(',')[0];
        
        return `
            <div style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;" 
                 onclick="selectSearchResult(${lat}, ${lng}, '${name.replace(/'/g, "\\'")}')"
                 onmouseover="this.style.backgroundColor='#f5f5f5'"
                 onmouseout="this.style.backgroundColor='white'">
                <div style="font-weight: 500; margin-bottom: 2px; color: #333;">${name}</div>
                <div style="font-size: 12px; color: #666;">${displayName}</div>
                <div style="font-size: 11px; color: #999; margin-top: 2px;">📍 ${lat.toFixed(4)}, ${lng.toFixed(4)}</div>
            </div>
        `;
    }).join('');
    
    searchResults.innerHTML = html;
    searchResults.style.display = 'block';
}

function displayEnhancedSearchResults(results) {
    const searchResults = document.getElementById('searchResults');
    
    const html = results.map(result => {
        const { lat, lng, name, displayName } = result;
        
        return `
            <div style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;" 
                 onclick="selectSearchResult(${lat}, ${lng}, '${name.replace(/'/g, "\\'")}')"
                 onmouseover="this.style.backgroundColor='#f5f5f5'"
                 onmouseout="this.style.backgroundColor='white'">
                <div style="font-weight: 500; margin-bottom: 2px; color: #333;">${name}</div>
                <div style="font-size: 12px; color: #666;">${displayName}</div>
                <div style="font-size: 11px; color: #999; margin-top: 2px;">📍 ${lat.toFixed(4)}, ${lng.toFixed(4)}</div>
            </div>
        `;
    }).join('');
    
    searchResults.innerHTML = html;
    searchResults.style.display = 'block';
}

function selectSearchResult(lat, lng, displayName) {
    const searchResults = document.getElementById('searchResults');
    const searchInput = document.getElementById('locationSearch');
    
    // Hide search results
    searchResults.style.display = 'none';
    searchInput.value = displayName.split(',')[0];
    
    // Set location on map
    setProjectMapLocation(lat, lng, displayName.split(',')[0]);
    
    // Center map on selected location
    if (projectMap) {
        projectMap.setView([lat, lng], 15);
    }
}

async function reverseGeocode(lat, lng) {
    try {
        // First try offline reverse geocoding
        if (window.OfflineMap) {
            const nearest = window.OfflineMap.findNearestCity(lat, lng);
            if (nearest && nearest.distance < 50) { // Within 50km
                document.getElementById('projectPlace').value = `Near ${nearest.name}`;
                return;
            }
        }
        
        // Try online reverse geocoding
        if (window.LocationUtils) {
            const result = await window.LocationUtils.reverseGeocode(lat, lng);
            if (result && result.name) {
                document.getElementById('projectPlace').value = result.name;
                return;
            }
        }
        
        // Fallback to direct API call
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`);
        const data = await response.json();
        if (data && data.display_name) {
            const placeName = data.display_name.split(',')[0];
            document.getElementById('projectPlace').value = placeName;
            return;
        }
    } catch (error) {
        console.error('Reverse geocoding error:', error);
    }
    
    // Final fallback to coordinates
    document.getElementById('projectPlace').value = `Location (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
}

function setProjectMapLocation(lat, lng, place) {
    updateProjectLocationInputs(lat, lng);
    
    if (place) {
        document.getElementById('projectPlace').value = place;
    }
    
    // Update map marker
    if (projectMap) {
        if (projectMarker) {
            projectMap.removeLayer(projectMarker);
        }
        
        // Create custom marker with better styling
        const markerIcon = L.divIcon({
            className: 'custom-marker',
            html: '<div style="background-color: #dc3545; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });
        
        projectMarker = L.marker([lat, lng], { icon: markerIcon }).addTo(projectMap)
            .bindPopup(`
                <div style="text-align: center; min-width: 200px;">
                    <div style="font-weight: bold; margin-bottom: 8px;">📍 ${place || 'Selected Location'}</div>
                    <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Latitude: ${lat.toFixed(6)}</div>
                    <div style="font-size: 12px; color: #666;">Longitude: ${lng.toFixed(6)}</div>
                </div>
            `)
            .openPopup();
    }
}

window.toggleCoordinateEdit = function(lock) {
    const latInput = document.getElementById('projectLatitude');
    const lngInput = document.getElementById('projectLongitude');
    
    if (latInput && lngInput) {
        latInput.readOnly = lock;
        lngInput.readOnly = lock;
        
        if (lock) {
            latInput.style.backgroundColor = '#f8f9fa';
            lngInput.style.backgroundColor = '#f8f9fa';
        } else {
            latInput.style.backgroundColor = 'white';
            lngInput.style.backgroundColor = 'white';
        }
    }
};

async function getCurrentLocationForProject() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    try {
        // Show loading state
        button.innerHTML = '<span>🔄</span> Getting Location...';
        button.disabled = true;
        
        let position;
        if (window.LocationUtils) {
            position = await window.LocationUtils.getCurrentPosition();
        } else {
            // Fallback geolocation
            position = await new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation not supported'));
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    (pos) => resolve({
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        accuracy: pos.coords.accuracy
                    }),
                    (error) => reject(new Error(error.message)),
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 300000 }
                );
            });
        }
        
        const { lat, lng, accuracy } = position;
        
        setProjectMapLocation(lat, lng, 'Current Location');
        
        if (projectMap) {
            projectMap.setView([lat, lng], 15);
            
            // Add accuracy circle if available
            if (accuracy && accuracy < 1000) {
                L.circle([lat, lng], {
                    radius: accuracy,
                    color: '#007bff',
                    fillColor: '#007bff',
                    fillOpacity: 0.1,
                    weight: 1
                }).addTo(projectMap);
            }
        }
        
        // Get place name
        await reverseGeocode(lat, lng);
        
        // Show success message
        button.innerHTML = '<span>✓</span> Location Set';
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
        
    } catch (error) {
        console.error('Location error:', error);
        alert('Error getting location: ' + error.message);
        
        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
    }
}
</script>

<script>
function updateProjectLocationInputs(lat, lng) {
    document.getElementById('projectLatitude').value = lat.toFixed(6);
    document.getElementById('projectLongitude').value = lng.toFixed(6);
}

// Manual coordinate validation and map update
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('input', function(e) {
        if (e.target.id === 'projectLatitude' || e.target.id === 'projectLongitude') {
            const latInput = document.getElementById('projectLatitude');
            const lngInput = document.getElementById('projectLongitude');
            const lat = latInput.value;
            const lng = lngInput.value;
            
            // Validate coordinates
            let validation = { valid: false };
            if (window.LocationUtils) {
                validation = window.LocationUtils.validateCoordinates(lat, lng);
            } else if (window.OfflineMap) {
                validation = window.OfflineMap.validateCoordinates(lat, lng);
            } else {
                // Fallback validation
                const latNum = parseFloat(lat);
                const lngNum = parseFloat(lng);
                if (!isNaN(latNum) && !isNaN(lngNum) && latNum >= -90 && latNum <= 90 && lngNum >= -180 && lngNum <= 180) {
                    validation = { valid: true, lat: latNum, lng: lngNum };
                }
            }
            
            // Update input styling
            if (lat) {
                latInput.classList.toggle('coordinate-valid', validation.valid && lat);
                latInput.classList.toggle('coordinate-invalid', !validation.valid && lat);
            }
            
            if (lng) {
                lngInput.classList.toggle('coordinate-valid', validation.valid && lng);
                lngInput.classList.toggle('coordinate-invalid', !validation.valid && lng);
            }
            
            if (validation.valid && projectMap) {
                setProjectMapLocation(validation.lat, validation.lng);
                projectMap.setView([validation.lat, validation.lng], 15);
                reverseGeocode(validation.lat, validation.lng);
            }
        }
    });
});
</script>



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
