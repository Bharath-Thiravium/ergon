// Override loadRecentActivities - only load when prefix exists
window.loadRecentActivities = async function(type = 'all') {
    const prefix = document.getElementById('companyPrefix')?.value?.trim();
    if (!prefix) return;
    
    try {
        const url = `/ergon/src/api/activities.php?prefix=${encodeURIComponent(prefix)}&limit=20${type !== 'all' ? `&record_type=${encodeURIComponent(type)}` : ''}`;
        const response = await fetch(url);
        const result = await response.json();
        const container = document.getElementById('recentActivities');
        
        if (!container) return;
        
        if (result.success && result.data?.length) {
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
            container.innerHTML = '<div class="activity-item"><div class="activity-loading">No activities</div></div>';
        }
        
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });
    } catch (e) {
        console.error('Activities:', e);
    }
};
