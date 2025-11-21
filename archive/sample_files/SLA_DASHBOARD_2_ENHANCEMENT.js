// SLA Dashboard 2.0 - Advanced Real-Time Intelligence
// Enhanced predictive analytics and breach prevention

// Advanced SLA Metrics Calculator
function updateAdvancedSLAMetrics(data) {
    // Velocity Index: (Progress % / Time Used %) × 100
    const velocityEl = document.querySelector('.velocity-index');
    const completionRate = data.completion_rate || 0;
    const timeUtilization = data.sla_total_seconds > 0 ? 
        (data.active_seconds / data.sla_total_seconds) * 100 : 0;
    
    const velocityIndex = timeUtilization > 0 ? 
        Math.round((completionRate / timeUtilization) * 100) : 0;
    
    if (velocityEl) {
        velocityEl.textContent = velocityIndex + '%';
        velocityEl.className = 'metric-value velocity-index';
        if (velocityIndex >= 100) velocityEl.classList.add('text-success');
        else if (velocityIndex >= 75) velocityEl.classList.add('text-warning');
        else velocityEl.classList.add('text-danger');
    }
    
    // Breach Risk Assessment
    const riskEl = document.querySelector('.breach-risk');
    const remainingSeconds = data.remaining_seconds || 0;
    
    let riskLevel = 'Low';
    let riskClass = 'text-success';
    
    if (remainingSeconds <= 0) {
        riskLevel = 'Critical';
        riskClass = 'text-danger';
    } else if (remainingSeconds < 3600 || velocityIndex < 50) {
        riskLevel = 'High';
        riskClass = 'text-danger';
    } else if (remainingSeconds < 7200 || velocityIndex < 75) {
        riskLevel = 'Medium';
        riskClass = 'text-warning';
    }
    
    if (riskEl) {
        riskEl.textContent = riskLevel;
        riskEl.className = 'metric-value breach-risk ' + riskClass;
    }
}

// Enhanced SLA Dashboard Update Function
function updateSLADashboard2(data) {
    // Update basic metrics
    const slaTotal = document.querySelector('.sla-total-time');
    const slaUsed = document.querySelector('.sla-used-time');
    const slaRemaining = document.querySelector('.sla-remaining-time');
    const slaPause = document.querySelector('.sla-pause-time');
    
    const newValues = {
        total: formatTimeHours(data.sla_total_seconds || 0),
        used: formatTimeHours(data.active_seconds || 0),
        remaining: formatTimeHours(data.remaining_seconds || 0),
        pause: formatTimeHours(data.pause_seconds || 0)
    };
    
    if (slaTotal) slaTotal.textContent = newValues.total;
    if (slaUsed) slaUsed.textContent = newValues.used;
    if (slaRemaining) {
        slaRemaining.textContent = newValues.remaining;
        // Color code remaining time
        const remainingSeconds = data.remaining_seconds || 0;
        slaRemaining.className = 'metric-value sla-remaining-time';
        if (remainingSeconds < 3600) slaRemaining.classList.add('text-danger');
        else if (remainingSeconds < 7200) slaRemaining.classList.add('text-warning');
        else slaRemaining.classList.add('text-success');
    }
    if (slaPause) slaPause.textContent = newValues.pause;
    
    // Update advanced metrics
    updateAdvancedSLAMetrics(data);
    
    // Check for alerts
    checkSLAAlerts(data);
    
    console.log('SLA Dashboard 2.0 updated:', newValues);
}

// SLA Alert System
function checkSLAAlerts(data) {
    const remainingSeconds = data.remaining_seconds || 0;
    const completionRate = data.completion_rate || 0;
    const velocityIndex = calculateVelocityIndex(data);
    
    let alertMessage = '';
    
    if (remainingSeconds <= 0) {
        alertMessage = '🔥 Critical: SLA breach detected!';
        showSLAAlert(alertMessage, 'critical');
    } else if (remainingSeconds < 1800 && completionRate < 80) {
        alertMessage = '⚠️ Warning: SLA breach imminent in 30 minutes!';
        showSLAAlert(alertMessage, 'warning');
    } else if (velocityIndex < 50 && completionRate < 50) {
        alertMessage = '⏳ Low velocity detected. Prioritize high-impact tasks.';
        showSLAAlert(alertMessage, 'info');
    }
}

function showSLAAlert(message, type) {
    // Create or update alert notification
    let alert = document.querySelector('.sla-alert');
    if (!alert) {
        alert = document.createElement('div');
        alert.className = 'sla-alert';
        document.querySelector('.sla-metrics').appendChild(alert);
    }
    
    alert.innerHTML = `
        <div class="alert-content alert-${type}">
            <span class="alert-message">${message}</span>
            <button onclick="dismissSLAAlert()" class="alert-dismiss">×</button>
        </div>
    `;
    alert.style.display = 'block';
}

function dismissSLAAlert() {
    const alert = document.querySelector('.sla-alert');
    if (alert) alert.style.display = 'none';
}

function calculateVelocityIndex(data) {
    const completionRate = data.completion_rate || 0;
    const timeUtilization = data.sla_total_seconds > 0 ? 
        (data.active_seconds / data.sla_total_seconds) * 100 : 0;
    return timeUtilization > 0 ? (completionRate / timeUtilization) * 100 : 0;
}

// Task-level SLA indicators
function updateTaskSLAChips() {
    document.querySelectorAll('.task-card').forEach(card => {
        const taskId = card.dataset.taskId;
        const status = card.dataset.status;
        
        if (status === 'in_progress' || status === 'not_started') {
            addSLAChipToTask(card);
        }
    });
}

function addSLAChipToTask(taskCard) {
    let chip = taskCard.querySelector('.sla-chip');
    if (!chip) {
        chip = document.createElement('span');
        chip.className = 'sla-chip';
        const title = taskCard.querySelector('.task-card__title');
        if (title) title.appendChild(chip);
    }
    
    // Calculate task-specific SLA status
    const slaDuration = parseInt(taskCard.dataset.slaDuration || 0);
    const activeSeconds = parseInt(taskCard.dataset.activeSeconds || 0);
    const remaining = slaDuration - activeSeconds;
    
    if (remaining <= 0) {
        chip.textContent = '🔥';
        chip.className = 'sla-chip sla-critical';
        chip.title = 'SLA Breached';
    } else if (remaining < 3600) {
        chip.textContent = '⚠️';
        chip.className = 'sla-chip sla-warning';
        chip.title = 'SLA Risk';
    } else {
        chip.textContent = '✅';
        chip.className = 'sla-chip sla-good';
        chip.title = 'SLA On Track';
    }
}

// Initialize SLA Dashboard 2.0
document.addEventListener('DOMContentLoaded', function() {
    // Override original updateSLADashboard function
    if (typeof updateSLADashboard === 'function') {
        window.originalUpdateSLADashboard = updateSLADashboard;
        window.updateSLADashboard = updateSLADashboard2;
    }
    
    // Add SLA chips to existing tasks
    updateTaskSLAChips();
    
    // Enhanced refresh interval
    setInterval(() => {
        updateTaskSLAChips();
    }, 5000);
});