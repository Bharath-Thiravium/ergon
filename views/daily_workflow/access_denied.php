<?php
$title = 'Access Restricted';
$active_page = 'daily-planner';

ob_start();
?>

<div class="access-denied-container">
    <div class="access-denied-card">
        <div class="access-denied-icon">⚠️</div>
        <h2>Access Restricted</h2>
        <p><?= htmlspecialchars($message ?? 'This feature is not available for your role.') ?></p>
        <div class="access-denied-actions">
            <a href="<?= htmlspecialchars($redirect_url ?? '/ergon/dashboard') ?>" class="btn btn-primary">
                Go to Tasks
            </a>
            <a href="/ergon/dashboard" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>

<style>
.access-denied-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 60vh;
    padding: 20px;
}

.access-denied-card {
    text-align: center;
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 100%;
}

.access-denied-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.access-denied-card h2 {
    color: #dc3545;
    margin-bottom: 15px;
    font-size: 1.8rem;
}

.access-denied-card p {
    color: #6c757d;
    margin-bottom: 30px;
    font-size: 1.1rem;
    line-height: 1.5;
}

.access-denied-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>