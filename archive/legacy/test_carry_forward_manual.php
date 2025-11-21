<!DOCTYPE html>
<html>
<head>
    <title>Manual Carry Forward Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .btn { padding: 10px 20px; margin: 10px; background: #007cba; color: white; border: none; cursor: pointer; }
        .result { margin: 20px 0; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; }
        .error { background: #ffe6e6; border-color: #ff9999; }
        .success { background: #e6ffe6; border-color: #99ff99; }
    </style>
</head>
<body>
    <h1>Manual Carry Forward Test</h1>
    
    <button class="btn" onclick="testCarryForward()">Test Carry Forward</button>
    <button class="btn" onclick="checkTasks()">Check Today's Tasks</button>
    <button class="btn" onclick="createTestTask()">Create Test Task (Yesterday)</button>
    
    <div id="result" class="result" style="display: none;"></div>
    
    <script>
    function showResult(message, isError = false) {
        const result = document.getElementById('result');
        result.style.display = 'block';
        result.className = 'result ' + (isError ? 'error' : 'success');
        result.innerHTML = message;
    }
    
    function testCarryForward() {
        fetch('/ergon/workflow/manual-carry-forward', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResult(`✓ ${data.message}`);
            } else {
                showResult(`✗ ${data.message}`, true);
            }
        })
        .catch(error => {
            showResult(`✗ Network error: ${error.message}`, true);
        });
    }
    
    function checkTasks() {
        const today = new Date().toISOString().split('T')[0];
        fetch(`/ergon/workflow/daily-planner/${today}`)
        .then(response => {
            if (response.ok) {
                showResult(`✓ Redirecting to today's planner...`);
                setTimeout(() => {
                    window.location.href = `/ergon/workflow/daily-planner/${today}`;
                }, 1000);
            } else {
                showResult(`✗ Failed to access daily planner`, true);
            }
        })
        .catch(error => {
            showResult(`✗ Error: ${error.message}`, true);
        });
    }
    
    function createTestTask() {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterdayStr = yesterday.toISOString().split('T')[0];
        
        const taskData = {
            title: `Test Task - ${new Date().toLocaleTimeString()}`,
            description: 'This task should be carried forward to today',
            assigned_to: 1, // Replace with actual user ID
            planned_date: yesterdayStr,
            status: 'assigned',
            priority: 'medium'
        };
        
        // This would need to be implemented as an API endpoint
        showResult(`Test task data prepared for ${yesterdayStr}. Create manually in database or via task creation form.`);
    }
    </script>
</body>
</html>