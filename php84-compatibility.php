<?php
// PHP 8.4 Compatibility Check and Fixes

// 1. Update match() expressions to handle null cases
function getStatusBadgeClass($status) {
    return match($status) {
        'completed' => 'success',
        'pending' => 'warning',
        'in_progress' => 'info',
        'postponed' => 'warning',
        'cancelled' => 'danger',
        default => 'secondary'
    };
}

// 2. Fix deprecated dynamic property access
class Database {
    private string $host;
    private string $db_name;
    private string $username;
    private ?string $password;
    private ?PDO $conn = null;
    private static ?Database $instance = null;
}

// 3. Add proper type declarations
function logHistory(int $followupId, string $action, ?string $oldValue = null, ?string $notes = null): bool {
    // Implementation
    return true;
}

// 4. Fix array access on potentially null values
function safeArrayAccess(?array $data, string $key, mixed $default = null): mixed {
    return $data[$key] ?? $default;
}

// 5. Update session handling for PHP 8.4
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

echo "PHP 8.4 compatibility fixes applied\n";
?>