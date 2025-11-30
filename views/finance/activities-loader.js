async function loadRecentActivities(type = 'all') {
    const prefix = document.getElementById('companyPrefix')?.value;
    if (!prefix) {
        const container = document.getElementById('recentActivities');
        if (container) container.innerHTML = '<div class="activity-item"><div class="activity-loading">Select a prefix to load activities</div></div>';
        return;
    }
    
    try {
        let url = `/ergon/src/api/activities.php?prefix=${encodeURIComponent(prefix)}&limit=20`;
        if (type !== 'all') url += `&record_type=${encodeURIComponent(type)}`;
        
        const response = await fetch(url);
        const result = await response.json();
        const container = document.getElementById('recentActivities');
        
        if (result.success && result.data?.length > 0) {
            container.innerHTML = result.data.map(a => `
                <div class="activity-item">
                    <div class="activity-icon">${a.icon}</div>
                    <div class="activity-content">
                        <div class="activity-title">${a.document_number}</div>
                        <div class="activity-details">${a.customer_name || 'N/A'} • ₹${a.formatted_amount}</div>
                        <div class="activity-meta">
                            <span class="activity-type">${getActivityTypeLabel(a.record_type)}</span>
                            <span>${getTimeAgo(a.created_at)}</span>
                        </div>
                    </div>
                    <div class="activity-status activity-status--${getActivityStatusClass(a.status)}">
                        ${getStatusLabel(a.status)}
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="activity-item"><div class="activity-loading">No activities found</div></div>';
        }
        
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });
    } catch (error) {
        console.error('Activities error:', error);
        const container = document.getElementById('recentActivities');
        if (container) container.innerHTML = '<div class="activity-item"><div class="activity-loading">Error loading activities</div></div>';
    }
}
