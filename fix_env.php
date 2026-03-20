<?php
// One-time script to fix .env credentials on server
// DELETE THIS FILE after running

$envPath = __DIR__ . '/.env';
$current = file_get_contents($envPath);
echo "BEFORE:\n$current\n\n";

$updated = preg_replace('/^DB_NAME=.*$/m', 'DB_NAME=u494785662_ergon', $current);
$updated = preg_replace('/^DB_USER=.*$/m', 'DB_USER=u494785662_ergon', $updated);
$updated = preg_replace('/^DB_PASS=.*$/m', 'DB_PASS=', $updated); // <-- put real password after DB_PASS=

if (file_put_contents($envPath, $updated)) {
    echo "AFTER:\n$updated\n\n";
    echo "✓ .env updated successfully. DELETE this file now.\n";
} else {
    echo "❌ Failed to write .env — check file permissions.\n";
}
?>
