// Fix for loadRecentActivities - remove AbortSignal timeout
window.loadRecentActivities = async function(type = 'all') {
    try {
        const prefix = document.getElementById('companyPrefix').value;
        if (!prefix) return;
        
        let url = `/ergon/src/api/activities.php?prefix=${encodeURIComponent(prefix)}&limit=20`;
        if (type !== 'all') {
            url += `&record_type=${encodeURIComponent(type)}`;
        }
        
        const response = await fetch(url);
        if (!response.ok) throw new Error('Activities API unavailable');
        const result = await response.json();
        
        const container = document.getElementById('recentActivities');
        
        if (result.success && result.data.length > 0) {
            container.innerHTML = result.data.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon">${activity.icon}</div>
                    <div class="activity-content">
                        <div class="activity-title">${activity.document_number}</div>
                        <div class="activity-details">${activity.customer_name || 'N/A'} • ₹${activity.formatted_amount}</div>
                        <div class="activity-meta">
                            <span class="activity-type">${getActivityTypeLabel(activity.record_type)}</span>
                            <span>${getTimeAgo(activity.created_at)}</span>
                        </div>
                    </div>
                    <div class="activity-status activity-status--${getActivityStatusClass(activity.status)}">
                        ${getStatusLabel(activity.status)}
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="activity-item"><div class="activity-loading">No recent activities found</div></div>';
        }
        
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });
        
    } catch (error) {
        console.warn('Activities load failed:', error.message);
        const container = document.getElementById('recentActivities');
        if (container) container.innerHTML = '<div class="activity-item"><div class="activity-loading">Unable to load activities</div></div>';
    }
};
