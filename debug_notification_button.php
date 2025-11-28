<!DOCTYPE html>
<html>
<head>
    <title>Debug Notification Button</title>
    <style>
        .notification-btn { background: #007bff; color: white; padding: 10px; border: none; cursor: pointer; }
        .notification-dropdown { display: none; position: absolute; background: white; border: 1px solid #ccc; padding: 10px; }
    </style>
</head>
<body>
    <h2>🔍 Notification Button Debug</h2>
    
    <button class="notification-btn" onclick="testNotificationClick()" id="testBtn">
        🔔 Test Notification Button
    </button>
    
    <div id="testDropdown" class="notification-dropdown">
        <p>Dropdown is working!</p>
    </div>
    
    <div id="debugOutput"></div>
    
    <script>
        function testNotificationClick() {
            console.log('Button clicked!');
            document.getElementById('debugOutput').innerHTML += '<p>✅ Button click detected</p>';
            
            const dropdown = document.getElementById('testDropdown');
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
                document.getElementById('debugOutput').innerHTML += '<p>📤 Dropdown closed</p>';
            } else {
                dropdown.style.display = 'block';
                document.getElementById('debugOutput').innerHTML += '<p>📥 Dropdown opened</p>';
            }
        }
        
        // Test if the function exists in dashboard
        function checkDashboardFunction() {
            if (typeof toggleNotifications === 'function') {
                document.getElementById('debugOutput').innerHTML += '<p>✅ toggleNotifications function exists</p>';
            } else {
                document.getElementById('debugOutput').innerHTML += '<p>❌ toggleNotifications function NOT found</p>';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('debugOutput').innerHTML += '<p>🚀 Page loaded</p>';
            checkDashboardFunction();
        });
    </script>
</body>
</html>