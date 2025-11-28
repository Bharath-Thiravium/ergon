<?php
// Simple test to debug the JavaScript API communication issue
session_start();

// Set up session like the real application
$_SESSION['user_id'] = 16;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "<!DOCTYPE html>
<html>
<head>
    <title>API Debug Test</title>
    <meta name='csrf-token' content='" . $_SESSION['csrf_token'] . "'>
</head>
<body>
    <h1>API Debug Test</h1>
    <p>Session User ID: " . $_SESSION['user_id'] . "</p>
    <p>CSRF Token: " . $_SESSION['csrf_token'] . "</p>
    
    <button onclick='testPauseAPI()'>Test Pause API</button>
    <button onclick='testResumeAPI()'>Test Resume API</button>
    
    <div id='results'></div>
    
    <script>
    function testPauseAPI() {
        const taskId = 212; // From your data - task in_progress
        const csrfToken = document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content');
        
        console.log('Testing pause API with:', {
            taskId: taskId,
            csrfToken: csrfToken
        });
        
        fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({ 
                task_id: parseInt(taskId, 10), 
                csrf_token: csrfToken 
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            document.getElementById('results').innerHTML = '<h3>Pause API Result:</h3><pre>' + text + '</pre>';
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('results').innerHTML = '<h3>Pause API Error:</h3><pre>' + error.message + '</pre>';
        });
    }
    
    function testResumeAPI() {
        const taskId = 209; // From your data - task on_break
        const csrfToken = document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content');
        
        console.log('Testing resume API with:', {
            taskId: taskId,
            csrfToken: csrfToken
        });
        
        fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({ 
                task_id: parseInt(taskId, 10), 
                csrf_token: csrfToken 
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            document.getElementById('results').innerHTML += '<h3>Resume API Result:</h3><pre>' + text + '</pre>';
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('results').innerHTML += '<h3>Resume API Error:</h3><pre>' + error.message + '</pre>';
        });
    }
    </script>
</body>
</html>";
?>