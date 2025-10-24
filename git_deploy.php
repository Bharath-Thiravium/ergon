<?php
/**
 * Git Deployment Status Checker
 * Checks git status and provides deployment commands
 */

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Git Deployment</title>";
echo "<style>body{font-family:monospace;margin:20px;} .cmd{background:#f0f0f0;padding:10px;margin:10px 0;border-radius:5px;} .success{color:green;} .error{color:red;}</style>";
echo "</head><body>";

echo "<h1>🚀 Git Deployment Status</h1>";

// Check if git is available
if (!is_dir('.git')) {
    echo "<p class='error'>❌ Not a git repository</p>";
    exit;
}

// Get current branch
$branch = trim(shell_exec('git branch --show-current 2>/dev/null') ?: 'unknown');
echo "<p><strong>Current Branch:</strong> $branch</p>";

// Get git status
echo "<h2>📋 Git Status</h2>";
$status = shell_exec('git status --porcelain 2>/dev/null');
if (empty($status)) {
    echo "<p class='success'>✅ Working directory clean</p>";
} else {
    echo "<p class='error'>⚠️ Uncommitted changes:</p>";
    echo "<div class='cmd'>" . htmlspecialchars($status) . "</div>";
}

// Get last commit
echo "<h2>📝 Last Commit</h2>";
$lastCommit = shell_exec('git log -1 --oneline 2>/dev/null');
echo "<div class='cmd'>" . htmlspecialchars($lastCommit) . "</div>";

// Check remote status
echo "<h2>🌐 Remote Status</h2>";
$remoteStatus = shell_exec('git status -uno 2>/dev/null | grep -E "(ahead|behind|up to date)"');
echo "<div class='cmd'>" . htmlspecialchars($remoteStatus ?: 'Unable to check remote status') . "</div>";

// Deployment commands
echo "<h2>🔧 Deployment Commands</h2>";
echo "<p>Copy and run these commands in your terminal:</p>";

echo "<h3>1. Add all changes:</h3>";
echo "<div class='cmd'>git add .</div>";

echo "<h3>2. Commit changes:</h3>";
echo "<div class='cmd'>git commit -m \"Update CSS and fix deployment issues\"</div>";

echo "<h3>3. Push to main:</h3>";
echo "<div class='cmd'>git push origin main</div>";

// Check if files need to be added
$untracked = shell_exec('git ls-files --others --exclude-standard 2>/dev/null');
if (!empty($untracked)) {
    echo "<h3>📁 Untracked Files:</h3>";
    echo "<div class='cmd'>" . htmlspecialchars($untracked) . "</div>";
}

// Modified files
$modified = shell_exec('git diff --name-only 2>/dev/null');
if (!empty($modified)) {
    echo "<h3>📝 Modified Files:</h3>";
    echo "<div class='cmd'>" . htmlspecialchars($modified) . "</div>";
}

echo "<h2>🎯 Next Steps</h2>";
echo "<ol>";
echo "<li>Run the git commands above in your terminal</li>";
echo "<li>Wait for webhook deployment (usually 1-2 minutes)</li>";
echo "<li>Check production site: <a href='https://athenas.co.in/ergon/deployment_audit.php' target='_blank'>Run Audit</a></li>";
echo "<li>Clear browser cache and test</li>";
echo "</ol>";

echo "</body></html>";
?>