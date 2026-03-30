<?php
// Diagnostic - DELETE AFTER RUNNING

echo "=== .env Investigation ===\n\n";

// Check .env modification time
$envPath = __DIR__ . '/.env';
echo ".env last modified: " . date('Y-m-d H:i:s', filemtime($envPath)) . "\n";
echo ".env current content (first 3 lines):\n";
$lines = file($envPath);
echo implode('', array_slice($lines, 0, 5)) . "\n";

// Check git status
echo "=== Git Info ===\n";
echo shell_exec('cd ' . __DIR__ . ' && git log --oneline -3 2>&1') . "\n";
echo "Git tracked .env: " . shell_exec('cd ' . __DIR__ . ' && git ls-files .env 2>&1') . "\n";
echo "Git ignored .env: " . shell_exec('cd ' . __DIR__ . ' && git check-ignore -v .env 2>&1') . "\n";

// Check for cron jobs
echo "=== Cron Jobs ===\n";
echo shell_exec('crontab -l 2>&1') . "\n";

// Check .env.production file
echo "=== .env.production exists: " . (file_exists(__DIR__ . '/.env.production') ? 'YES' : 'NO') . "\n";
$prodEnv = __DIR__ . '/.env.production';
if (file_exists($prodEnv)) {
    echo "Content:\n" . file_get_contents($prodEnv) . "\n";
}
?>
