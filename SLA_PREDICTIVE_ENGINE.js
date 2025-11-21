// SLA Dashboard 2.0 - Predictive Performance Engine
// Core Logic Modules for Real-Time Intelligence

// 1. Core Logic Module: calculateVelocityIndex()
function calculateVelocityIndex(data) {
    const completedTasks = data.completed_tasks || 0;
    const totalTasks = data.total_tasks || 1;
    const activeSeconds = data.active_seconds || 0;
    const slaTotal = data.sla_total_seconds || 1;
    
    const completionRate = (completedTasks / totalTasks) * 100;
    const timeUtilization = (activeSeconds / slaTotal) * 100;
    
    return timeUtilization > 0 ? Math.round((completionRate / timeUtilization) * 100) : 0;
}

// 2. Core Logic Module: assessBreachRisk()
function assessBreachRisk(velocityIndex, remainingSlaTime, taskPriority = 'medium') {
    const remainingHours = remainingSlaTime / 3600;
    
    // Critical conditions
    if (remainingSlaTime <= 0) return { level: 'Critical', class: 'text-danger' };
    
    // High risk conditions
    if (remainingHours < 0.5 || // < 30 mins
        (velocityIndex < 50 && taskPriority === 'high') ||
        (velocityIndex < 25)) {
        return { level: 'High', class: 'text-danger' };
    }
    
    // Medium risk conditions
    if (remainingHours < 2 || // < 2 hours
        velocityIndex < 75) {
        return { level: 'Medium', class: 'text-warning' };
    }
    
    return { level: 'Low', class: 'text-success' };
}

// 3. Core Logic Module: generateAlerts()
function generateAlerts(data) {
    const velocityIndex = calculateVelocityIndex(data);
    const remainingSeconds = data.remaining_seconds || 0;
    const completionRate = data.completion_rate || 0;
    const pauseRatio = data.active_seconds > 0 ? (data.pause_seconds / data.active_seconds) : 0;
    
    const alerts = [];
    
    // Critical: SLA breach detected
    if (remainingSeconds <= 0) {
        alerts.push({
            type: 'critical',
            icon: '🔥',
            message: 'Critical: SLA breach detected!',
            timestamp: new Date().toISOString(),
            taskIds: data.overdue_task_ids || []
        });
    }
    
    // Warning: SLA breach imminent
    else if (remainingSeconds < 1800 && completionRate < 80) {
        alerts.push({
            type: 'warning',
            icon: '⚠️',
            message: 'Warning: SLA breach imminent in 30 minutes!',
            timestamp: new Date().toISOString(),
            taskIds: data.at_risk_task_ids || []
        });
    }
    
    // Info: Low velocity detected
    if (velocityIndex < 50 && completionRate < 50) {
        alerts.push({
            type: 'info',
            icon: '⏳',
            message: 'Low velocity detected. Prioritize high-impact tasks.',
            timestamp: new Date().toISOString(),
            taskIds: []
        });
    }
    
    // Info: High break ratio
    if (pauseRatio > 0.3) {
        alerts.push({
            type: 'info',
            icon: '☕',
            message: 'High break ratio detected. Consider focused work sessions.',
            timestamp: new Date().toISOString(),
            taskIds: []
        });
    }
    
    return alerts;
}

// Enhanced SLA Dashboard Update with Predictive Engine
function updateSLADashboardPredictive(data) {
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
        const remainingSeconds = data.remaining_seconds || 0;
        slaRemaining.className = 'metric-value sla-remaining-time';
        if (remainingSeconds < 3600) slaRemaining.classList.add('text-danger');
        else if (remainingSeconds < 7200) slaRemaining.classList.add('text-warning');
        else slaRemaining.classList.add('text-success');
    }
    if (slaPause) slaPause.textContent = newValues.pause;
    
    // Calculate Velocity Index
    const velocityIndex = calculateVelocityIndex(data);
    const velocityEl = document.querySelector('.velocity-index');
    
    if (velocityEl) {
        velocityEl.textContent = velocityIndex + '%';
        velocityEl.className = 'metric-value velocity-index';
        if (velocityIndex >= 100) velocityEl.classList.add('text-success');
        else if (velocityIndex >= 75) velocityEl.classList.add('text-warning');
        else velocityEl.classList.add('text-danger');
    }
    
    // Assess Breach Risk
    const riskAssessment = assessBreachRisk(velocityIndex, data.remaining_seconds || 0);
    const riskEl = document.querySelector('.breach-risk');
    
    if (riskEl) {
        riskEl.textContent = riskAssessment.level;
        riskEl.className = 'metric-value breach-risk ' + riskAssessment.class;
    }
    
    // Generate and process alerts
    const alerts = generateAlerts(data);
    processAlerts(alerts);
    
    // Update SLA health indicator
    updateSLAHealthIndicator(riskAssessment.level);
    
    // Log SLA update event
    logSLAEvent({
        event_type: 'dashboard_update',
        velocity_index: velocityIndex,
        breach_risk: riskAssessment.level,
        remaining_seconds: data.remaining_seconds,
        timestamp: new Date().toISOString()
    });
}

// Process and display alerts
function processAlerts(alerts) {
    dismissSLAAlert();
    
    if (alerts.length > 0) {
        const primaryAlert = alerts.find(a => a.type === 'critical') || 
                           alerts.find(a => a.type === 'warning') || 
                           alerts[0];
        
        showSLAAlert(primaryAlert.icon + ' ' + primaryAlert.message, primaryAlert.type);
        
        logSLAEvent({
            event_type: 'alert_generated',
            alert_type: primaryAlert.type,
            message: primaryAlert.message,
            task_ids: primaryAlert.taskIds,
            timestamp: primaryAlert.timestamp
        });
    }
}

// SLA Health Indicator
function updateSLAHealthIndicator(riskLevel) {
    const pageTitle = document.querySelector('.page-title h1');
    if (!pageTitle) return;
    
    let indicator = pageTitle.querySelector('.sla-health-indicator');
    if (!indicator) {
        indicator = document.createElement('span');
        indicator.className = 'sla-health-indicator';
        pageTitle.appendChild(indicator);
    }
    
    const indicators = {
        'Critical': { icon: '🔥', title: 'Critical SLA Risk' },
        'High': { icon: '⚠️', title: 'High SLA Risk' },
        'Medium': { icon: '🟡', title: 'Medium SLA Risk' },
        'Low': { icon: '✅', title: 'SLA On Track' }
    };
    
    const config = indicators[riskLevel] || indicators['Low'];
    indicator.textContent = config.icon;
    indicator.title = config.title;
}

// Audit Compliance: SLA Event Logging
function logSLAEvent(eventData) {
    console.log('SLA Event:', eventData);
    
    if (window.SLA_AUDIT_ENABLED) {
        fetch('/ergon/api/sla_audit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(eventData)
        }).catch(err => console.log('SLA audit log failed:', err));
    }
}

// Sync Watchdog: Detect timer drift
let lastSyncTime = Date.now();
function syncWatchdog() {
    const now = Date.now();
    const drift = now - lastSyncTime - 1000;
    
    if (Math.abs(drift) > 2000) {
        console.warn('SLA Timer drift detected:', drift + 'ms');
        if (typeof refreshSLADashboard === 'function') {
            refreshSLADashboard();
        }
    }
    
    lastSyncTime = now;
}

// Initialize Predictive Engine
document.addEventListener('DOMContentLoaded', function() {
    // Override original function
    if (typeof updateSLADashboard === 'function') {
        window.originalUpdateSLADashboard = updateSLADashboard;
        window.updateSLADashboard = updateSLADashboardPredictive;
    }
    
    // Initialize state persistence
    window.SLA_STATE = JSON.parse(localStorage.getItem('sla_dashboard_state') || '{}');
    
    // Enhanced monitoring
    setInterval(() => {
        syncWatchdog();
    }, 1000);
    
    // Auto-recovery
    setInterval(() => {
        const slaData = {
            lastUpdate: Date.now(),
            velocityIndex: document.querySelector('.velocity-index')?.textContent,
            breachRisk: document.querySelector('.breach-risk')?.textContent
        };
        localStorage.setItem('sla_dashboard_state', JSON.stringify(slaData));
    }, 5000);
    
    // Session recovery
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && typeof refreshSLADashboard === 'function') {
            refreshSLADashboard();
        }
    });
});

// Export API
window.SLAEngine = {
    calculateVelocityIndex,
    assessBreachRisk,
    generateAlerts,
    logSLAEvent
};