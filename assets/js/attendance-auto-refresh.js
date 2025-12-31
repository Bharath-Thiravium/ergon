// Auto-refresh attendance data for admin/owner panels
document.addEventListener('DOMContentLoaded', function() {
    const userRole = document.body.getAttribute('data-user-role');
    if (!userRole || !['admin', 'owner'].includes(userRole)) {
        return;
    }
    
    setInterval(function() {
        const dateFilter = document.getElementById('dateFilter');
        const today = new Date().toISOString().split('T')[0];
        
        if (dateFilter && dateFilter.value === today) {
            fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                const newTable = newDoc.querySelector('.table tbody');
                const currentTable = document.querySelector('.table tbody');
                
                if (newTable && currentTable && newTable.innerHTML !== currentTable.innerHTML) {
                    currentTable.innerHTML = newTable.innerHTML;
                    console.log('Attendance data refreshed');
                }
            })
            .catch(error => {
                console.log('Auto-refresh failed:', error);
            });
        }
    }, 30000);
});
