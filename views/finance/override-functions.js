window.loadOutstandingInvoices = async function() {
    try {
        const prefix = document.getElementById('companyPrefix').value;
        if (!prefix) return;
        
        const response = await fetch(`/ergon/src/api/outstanding.php?prefix=${encodeURIComponent(prefix)}&limit=20`, {
            signal: AbortSignal.timeout(5000)
        }).catch(e => null);
        if (!response || !response.ok) throw new Error('Outstanding API unavailable');
        const result = await response.json();
        
        const tbody = document.querySelector('#outstandingTable tbody');
        
        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(invoice => {
                const addr = (invoice.shipping_address && invoice.shipping_address.trim()) ? invoice.shipping_address : 'N/A';
                return `<tr class="${invoice.status === 'Overdue' ? 'table-row--danger' : ''}">
                    <td>${invoice.invoice_number}</td>
                    <td>${invoice.customer_name}</td>
                    <td><small>📍 ${addr}</small></td>
                    <td>${invoice.invoice_date}</td>
                    <td>₹${parseFloat(invoice.total_amount).toLocaleString()}</td>
                    <td>₹${parseFloat(invoice.outstanding_amount).toLocaleString()}</td>
                    <td>${invoice.days_overdue > 0 ? invoice.days_overdue + ' days' : '-'}</td>
                    <td><span class="list-status">${invoice.status}</span></td>
                </tr>`;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No outstanding invoices found</td></tr>';
        }
    } catch (error) {
        console.warn('Outstanding invoices load failed:', error.message);
        const tbody = document.querySelector('#outstandingTable tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center">Unable to load data</td></tr>';
    }
};

window.loadRecentActivities = async function(type = 'all') {
    try {
        const prefix = document.getElementById('companyPrefix').value;
        const container = document.getElementById('recentActivities');
        
        if (!prefix) {
            if (container) container.innerHTML = '<div class="activity-item"><div class="activity-loading">Select a company prefix to view activities</div></div>';
            return;
        }
        
        let url = `/ergon/src/api/activities.php?prefix=${encodeURIComponent(prefix)}&limit=20`;
        if (type !== 'all') url += `&record_type=${encodeURIComponent(type)}`;
        
        const response = await fetch(url, {signal: AbortSignal.timeout(5000)}).catch(e => null);
        if (!response || !response.ok) throw new Error('Activities API unavailable');
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error || 'API returned error');
        
        if (result.data && result.data.length > 0) {
            container.innerHTML = result.data.map(activity => {
                const addr = (activity.shipping_address && activity.shipping_address.trim()) ? activity.shipping_address : 'N/A';
                return `<div class="activity-item">
                    <div class="activity-icon">${activity.icon}</div>
                    <div class="activity-content">
                        <div class="activity-title">${activity.document_number}</div>
                        <div class="activity-details">${activity.customer_name || 'N/A'} • ₹${activity.formatted_amount}</div>
                        <div class="activity-address">📍 ${addr}</div>
                        <div class="activity-meta">
                            <span class="activity-type">${getActivityTypeLabel(activity.record_type)}</span>
                            <span>${getTimeAgo(activity.created_at)}</span>
                        </div>
                    </div>
                    <div class="activity-status activity-status--${getActivityStatusClass(activity.status)}">
                        ${getStatusLabel(activity.status)}
                    </div>
                </div>`;
            }).join('');
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
