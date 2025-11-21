<?php
$title = 'Clock In/Out';
$active_page = 'attendance';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🕰️</span> Clock In/Out</h1>
        <p>Track your attendance with GPS location</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/attendance" class="btn btn--secondary">
            <span>📍</span> Back to Attendance
        </a>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: 1fr; max-width: 600px; margin: 0 auto;">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>🕰️</span> Current Time
            </h2>
        </div>
        <div class="card__body" style="text-align: center; padding: 2rem;">
            <div style="margin-bottom: 2rem;">
                <div id="currentTime" style="font-size: 2.5rem; font-weight: 700; color: #1f2937; margin-bottom: 0.5rem;"></div>
                <div id="currentDate" style="color: #6b7280; font-size: 1rem;"></div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 1rem; max-width: 300px; margin: 0 auto;">
                <?php if ($on_leave): ?>
                    <div style="padding: 1rem; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; text-align: center; color: #92400e;">
                        <span>🏖️</span> You are on approved leave today
                    </div>
                    <button class="btn btn--secondary" disabled style="padding: 1rem 2rem; font-size: 1.1rem; font-weight: 600; opacity: 0.5; cursor: not-allowed;">
                        <span>🏖️</span> On Leave
                    </button>
                <?php else: ?>
                    <button id="clockBtn" class="btn" style="padding: 1rem 2rem; font-size: 1.1rem; font-weight: 600;">
                        <span id="clockBtnIcon">▶️</span> <span id="clockBtnText">Clock In</span>
                    </button>
                <?php endif; ?>
            </div>
            
            <div id="locationStatus" style="margin-top: 1.5rem; color: #6b7280; font-size: 0.875rem;">
                <span>📍</span> Getting location...
            </div>
        </div>
    </div>
</div>

<script>
let currentPosition = null;

function updateTime() {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleTimeString();
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                currentPosition = position;
                document.getElementById('locationStatus').innerHTML = 
                    '<span>📍</span> Location detected';
            },
            function(error) {
                document.getElementById('locationStatus').innerHTML = 
                    '<span>⚠️</span> Location unavailable';
            }
        );
    }
}

// Smart button state management - shared with header
let attendanceStatus = <?= json_encode($attendance_status ?? []) ?>;

// Sync with header button status if available
if (typeof headerAttendanceStatus !== 'undefined') {
    headerAttendanceStatus = attendanceStatus;
}

function updateClockButton(status) {
    const btn = document.getElementById('clockBtn');
    const icon = document.getElementById('clockBtnIcon');
    const text = document.getElementById('clockBtnText');
    
    if (!btn || !icon || !text) return;
    
    btn.disabled = false;
    btn.onclick = null;
    
    if (status.on_leave) {
        // On Leave state
        text.textContent = 'On Leave';
        icon.textContent = '🏖️';
        btn.className = 'btn btn--secondary';
        btn.disabled = true;
    } else if (status.is_completed || (status.has_clocked_in && status.has_clocked_out)) {
        // Completed state
        text.textContent = 'Completed';
        icon.textContent = '✅';
        btn.className = 'btn btn--secondary';
        btn.disabled = true;
    } else if (!status.has_clocked_in) {
        // Clock In state
        text.textContent = 'Clock In';
        icon.textContent = '▶️';
        btn.className = 'btn btn--success';
        btn.onclick = () => clockAction('in');
    } else if (status.has_clocked_in && !status.has_clocked_out) {
        // Clock Out state
        text.textContent = 'Clock Out';
        icon.textContent = '⏹️';
        btn.className = 'btn btn--danger';
        btn.onclick = () => clockAction('out');
    }
    
    // Sync header button if available
    if (typeof updateHeaderAttendanceButton === 'function') {
        if (typeof headerAttendanceStatus !== 'undefined') {
            headerAttendanceStatus = status;
        }
        updateHeaderAttendanceButton();
    }
}

function clockAction(type) {
    const btn = document.getElementById('clockBtn');
    const text = document.getElementById('clockBtnText');
    
    // Disable button and show loading
    btn.disabled = true;
    const originalText = text.textContent;
    text.textContent = type === 'in' ? 'Clocking In...' : 'Clocking Out...';
    
    const formData = new FormData();
    formData.append('type', type);
    
    if (currentPosition) {
        formData.append('latitude', currentPosition.coords.latitude);
        formData.append('longitude', currentPosition.coords.longitude);
    }
    
    fetch('/ergon/attendance/clock', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update attendance status
            if (type === 'in') {
                attendanceStatus.has_clocked_in = true;
                attendanceStatus.clock_in_time = new Date().toISOString();
            } else {
                attendanceStatus.has_clocked_out = true;
                attendanceStatus.clock_out_time = new Date().toISOString();
            }
            
            // Update both buttons
            updateClockButton(attendanceStatus);
            
            // Sync header button status
            if (typeof headerAttendanceStatus !== 'undefined') {
                headerAttendanceStatus = attendanceStatus;
                if (typeof updateHeaderAttendanceButton === 'function') {
                    updateHeaderAttendanceButton();
                }
            }
            
            alert(`Clocked ${type} successfully!`);
            setTimeout(() => window.location.href = '/ergon/attendance', 1500);
        } else {
            alert(data.error || 'An error occurred');
            // Restore button state
            text.textContent = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Server error occurred. Please try again.');
        // Restore button state
        text.textContent = originalText;
        btn.disabled = false;
    });
}

<?php if (!$on_leave): ?>
// Initialize smart button
updateClockButton(attendanceStatus);
<?php endif; ?>

// Initialize
updateTime();
setInterval(updateTime, 1000);
getLocation();
</script>

<style>
.btn--success {
    background: #22c55e !important;
    color: white !important;
    border-color: #22c55e !important;
}

.btn--success:hover {
    background: #16a34a !important;
    border-color: #16a34a !important;
}

.btn--danger {
    background: #b91c1c !important;
    color: #ffffff !important;
    border-color: #991b1b !important;
    box-shadow: 0 4px 20px rgba(185,28,28,0.8) !important;
    font-weight: 800 !important;
    border-width: 3px !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}

.btn--danger:hover {
    background: #dc2626 !important;
    border-color: #b91c1c !important;
    box-shadow: 0 6px 16px rgba(239,68,68,0.5) !important;
}

.btn--secondary {
    background: #059669 !important;
    color: white !important;
    border-color: #047857 !important;
    box-shadow: 0 4px 12px rgba(5,150,105,0.3) !important;
}

#clockBtn {
    transition: all 0.3s ease;
    min-width: 200px;
}

#clockBtn:disabled {
    opacity: 1 !important;
    cursor: not-allowed;
    background: #059669 !important;
    border-color: #047857 !important;
    box-shadow: 0 4px 12px rgba(5,150,105,0.3) !important;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
