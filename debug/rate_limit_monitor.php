<?php
/**
 * Rate Limit Monitor - Debug Tool
 * Use this to monitor and reset rate limiting data
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Access denied. Please log in first.');
}

$action = $_GET['action'] ?? 'view';

if ($action === 'reset') {
    // Reset all rate limiting data
    unset($_SESSION['timer_calls_per_task']);
    unset($_SESSION['timer_calls_global']);
    unset($_SESSION['api_calls']);
    echo "<div style='color: green; font-weight: bold;'>✅ Rate limiting data reset successfully!</div><br>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Rate Limit Monitor</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .reset-btn { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .reset-btn:hover { background: #c82333; }
        .refresh-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px; }
        .refresh-btn:hover { background: #0056b3; }
        .status { padding: 5px 10px; border-radius: 3px; }
        .status.ok { background: #d4edda; color: #155724; }
        .status.warning { background: #fff3cd; color: #856404; }
        .status.danger { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚦 Rate Limit Monitor</h1>
        <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
        
        <div class="section">
            <h2>Global API Calls</h2>
            <?php
            $apiCalls = $_SESSION['api_calls'] ?? [];
            $apiCallCount = count($apiCalls);
            $apiStatus = $apiCallCount >= 45 ? 'danger' : ($apiCallCount >= 30 ? 'warning' : 'ok');
            ?>
            <div class="status <?= $apiStatus ?>">
                API Calls in last 60 seconds: <?= $apiCallCount ?> / 50 (limit)
            </div>
        </div>

        <div class="section">
            <h2>Global Timer Calls</h2>
            <?php
            $globalTimerCalls = $_SESSION['timer_calls_global'] ?? [];
            $globalTimerCount = count($globalTimerCalls);
            $globalTimerStatus = $globalTimerCount >= 25 ? 'danger' : ($globalTimerCount >= 20 ? 'warning' : 'ok');
            ?>
            <div class="status <?= $globalTimerStatus ?>">
                Global Timer Calls in last 60 seconds: <?= $globalTimerCount ?> / 30 (limit)
            </div>
        </div>

        <div class="section">
            <h2>Per-Task Timer Calls</h2>
            <?php
            $perTaskCalls = $_SESSION['timer_calls_per_task'] ?? [];
            if (empty($perTaskCalls)): ?>
                <p>No per-task timer data available.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Task ID</th>
                            <th>Calls in last 60s</th>
                            <th>Status</th>
                            <th>Last Call Times</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($perTaskCalls as $taskId => $calls): 
                            $callCount = count($calls);
                            $taskStatus = $callCount >= 5 ? 'danger' : ($callCount >= 4 ? 'warning' : 'ok');
                            $lastCalls = array_slice($calls, -3); // Show last 3 calls
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($taskId) ?></td>
                            <td><?= $callCount ?> / 6</td>
                            <td><span class="status <?= $taskStatus ?>"><?= ucfirst($taskStatus) ?></span></td>
                            <td>
                                <?php foreach ($lastCalls as $timestamp): ?>
                                    <?= date('H:i:s', $timestamp) ?><br>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Actions</h2>
            <button class="reset-btn" onclick="if(confirm('Reset all rate limiting data?')) window.location.href='?action=reset'">
                🔄 Reset Rate Limits
            </button>
            <button class="refresh-btn" onclick="window.location.reload()">
                ↻ Refresh Data
            </button>
        </div>

        <div class="section">
            <h2>Rate Limiting Rules</h2>
            <ul>
                <li><strong>API Calls:</strong> Max 50 calls per minute (all actions except timer)</li>
                <li><strong>Global Timer Calls:</strong> Max 30 calls per minute (all timer requests combined)</li>
                <li><strong>Per-Task Timer Calls:</strong> Max 6 calls per minute per task (1 every 10 seconds)</li>
                <li><strong>Client-side Throttling:</strong> 15 seconds between server calls per task</li>
                <li><strong>Queue System:</strong> Max 2 concurrent timer requests</li>
            </ul>
        </div>

        <div class="section">
            <h2>Troubleshooting</h2>
            <p><strong>If you're getting 429 errors:</strong></p>
            <ol>
                <li>Click "Reset Rate Limits" above</li>
                <li>Refresh your Daily Planner page</li>
                <li>Avoid rapidly clicking timer-related buttons</li>
                <li>The system will automatically throttle requests</li>
            </ol>
        </div>
    </div>

    <script>
        // Auto-refresh every 10 seconds
        setTimeout(() => window.location.reload(), 10000);
    </script>
</body>
</html>
