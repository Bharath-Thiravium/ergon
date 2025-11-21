// SLA Dashboard 2.0 - Predictive Performance Engine
function calculateVelocityIndex(data) {
    const completedTasks = data.completed_tasks || 0;
    const totalTasks = data.total_tasks || 1;
    const activeSeconds = data.active_seconds || 0;
    const slaTotal = data.sla_total_seconds || 1;
    
    const completionRate = (completedTasks / totalTasks) * 100;
    const timeUtilization = (activeSeconds / slaTotal) * 100;
    
    return timeUtilization > 0 ? Math.round((completionRate / timeUtilization) * 100) : 0;
}

function assessBreachRisk(velocityIndex, remainingSlaTime, taskPriority = 'medium') {
    const remainingHours = remainingSlaTime / 3600;
    
    if (remainingSlaTime <= 0) return { level: 'Critical', class: 'text-danger' };
    
    if (remainingHours < 0.5 || (velocityIndex < 50 && taskPriority === 'high') || (velocityIndex < 25)) {
        return { level: 'High', class: 'text-danger' };
    }
    
    if (remainingHours < 2 || velocityIndex < 75) {
        return { level: 'Medium', class: 'text-warning' };
    }
    
    return { level: 'Low', class: 'text-success' };
}

function generateAlerts(data) {
    const velocityIndex = calculateVelocityIndex(data);
    const remainingSeconds = data.remaining_seconds || 0;
    const completionRate = data.completion_rate || 0;
    const alerts = [];
    
    if (remainingSeconds <= 0) {
        alerts.push({
            type: 'critical',
            icon: '🔥',
            message: 'Critical: SLA breach detected!',
            timestamp: new Date().toISOString()
        });
    } else if (remainingSeconds < 1800 && completionRate < 80) {
        alerts.push({
            type: 'warning',
            icon: '⚠️',
            message: 'Warning: SLA breach imminent in 30 minutes!',
            timestamp: new Date().toISOString()
        });
    }
    
    return alerts;
}

window.SLAEngine = { calculateVelocityIndex, assessBreachRisk, generateAlerts };