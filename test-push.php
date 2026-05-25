<?php
/**
 * Push Notification Test Script
 * URL: /ergon/test-push.php
 * DELETE this file after testing
 */
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/PushService.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    die('<p style="color:red;font-family:sans-serif;">Please <a href="/ergon/login">login</a> first.</p>');
}

$userId  = (int)$_SESSION['user_id'];
$db      = Database::connect();
$message = '';
$msgType = '';

// Handle send action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetUser = intval($_POST['target_user'] ?? $userId);
    $title      = trim($_POST['title']       ?? '🔔 Ergon Test');
    $body       = trim($_POST['body']        ?? 'This is a test push notification from Ergon.');
    $url        = trim($_POST['url']         ?? '/ergon/notifications');

    // Check target has subscription
    $stmt = $db->prepare("SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ?");
    $stmt->execute([$targetUser]);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $message = "❌ User #$targetUser has no push subscription. They need to open the app and allow notifications first.";
        $msgType = 'error';
    } else {
        PushService::sendToUser($targetUser, $title, $body, $url);
        $message = "✅ Push sent to user #$targetUser — watch for the OS notification!";
        $msgType = 'success';
    }
}

// Fetch all subscribed users
$users = $db->query("
    SELECT u.id, u.name, u.role, ps.type, ps.updated_at
    FROM push_subscriptions ps
    JOIN users u ON u.id = ps.user_id
    ORDER BY ps.updated_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch current user's subscription
$stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$mySub = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Push Notification Test</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 700px; margin: 40px auto; padding: 0 20px; background: #f8fafc; color: #1f2937; }
    h1 { font-size: 1.5rem; margin-bottom: 4px; }
    .sub { color: #6b7280; font-size: .9rem; margin-bottom: 24px; }
    .card { background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
    .card h2 { font-size: 1rem; margin: 0 0 14px; color: #374151; }
    label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 4px; color: #374151; }
    input, select, textarea { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: .9rem; box-sizing: border-box; margin-bottom: 12px; }
    textarea { resize: vertical; height: 70px; }
    button { background: #3b82f6; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-size: .9rem; font-weight: 600; cursor: pointer; }
    button:hover { background: #2563eb; }
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
    .alert.success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .status { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: .75rem; font-weight: 600; }
    .status.ok  { background: #d1fae5; color: #065f46; }
    .status.bad { background: #fee2e2; color: #991b1b; }
    table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    th { text-align: left; padding: 8px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
    td { padding: 8px; border-bottom: 1px solid #f1f5f9; }
    .warn { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; padding: 10px 14px; border-radius: 8px; font-size: .85rem; margin-top: 20px; }
    a { color: #3b82f6; }
</style>
</head>
<body>

<h1>🔔 Push Notification Test</h1>
<p class="sub">Logged in as: <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></strong> (ID: <?= $userId ?>)</p>

<?php if ($message): ?>
<div class="alert <?= $msgType ?>"><?= $message ?></div>
<?php endif; ?>

<!-- System Status -->
<div class="card">
    <h2>⚙️ System Status</h2>
    <table>
        <tr>
            <td>VAPID Public Key</td>
            <td><?= !empty($_ENV['VAPID_PUBLIC_KEY']) ? '<span class="status ok">✅ Set</span>' : '<span class="status bad">❌ Missing</span>' ?></td>
        </tr>
        <tr>
            <td>VAPID Private Key</td>
            <td><?= !empty($_ENV['VAPID_PRIVATE_KEY']) ? '<span class="status ok">✅ Set</span>' : '<span class="status bad">❌ Missing</span>' ?></td>
        </tr>
        <tr>
            <td>OpenSSL Extension</td>
            <td><?= extension_loaded('openssl') ? '<span class="status ok">✅ Available</span>' : '<span class="status bad">❌ Missing</span>' ?></td>
        </tr>
        <tr>
            <td>cURL Extension</td>
            <td><?= extension_loaded('curl') ? '<span class="status ok">✅ Available</span>' : '<span class="status bad">❌ Missing</span>' ?></td>
        </tr>
        <tr>
            <td>Your Subscription</td>
            <td><?= $mySub ? '<span class="status ok">✅ Saved (updated ' . date('d M H:i', strtotime($mySub['updated_at'])) . ')</span>' : '<span class="status bad">❌ Not subscribed — reload the app and allow notifications</span>' ?></td>
        </tr>
        <tr>
            <td>Total Subscribed Users</td>
            <td><strong><?= count($users) ?></strong></td>
        </tr>
    </table>
</div>

<!-- Send Test -->
<div class="card">
    <h2>📤 Send Test Notification</h2>
    <form method="POST">
        <label>Send To</label>
        <select name="target_user">
            <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $u['id'] == $userId ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['name']) ?> (<?= $u['role'] ?>) — <?= $u['type'] ?> — last seen <?= date('d M H:i', strtotime($u['updated_at'])) ?>
            </option>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <option value="<?= $userId ?>">No subscriptions found</option>
            <?php endif; ?>
        </select>

        <label>Title</label>
        <input type="text" name="title" value="🔔 Ergon Test Notification" required>

        <label>Message</label>
        <textarea name="body">This is a test push notification sent at <?= date('H:i:s') ?>. Push notifications are working!</textarea>

        <label>Click URL (where notification takes you)</label>
        <input type="text" name="url" value="/ergon/notifications">

        <button type="submit">🚀 Send Push Now</button>
    </form>
</div>

<!-- Subscribed Users -->
<?php if (!empty($users)): ?>
<div class="card">
    <h2>👥 Subscribed Users (<?= count($users) ?>)</h2>
    <table>
        <thead>
            <tr><th>User</th><th>Role</th><th>Type</th><th>Last Updated</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['name']) ?> (#<?= $u['id'] ?>)</td>
                <td><?= $u['role'] ?></td>
                <td><span class="status ok"><?= strtoupper($u['type']) ?></span></td>
                <td><?= date('d M Y H:i', strtotime($u['updated_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<p><a href="/ergon/dashboard">← Back to Dashboard</a></p>

<div class="warn">
    ⚠️ <strong>Security Warning:</strong> Delete this file after testing.<br>
    Path: <code><?= __FILE__ ?></code>
</div>

</body>
</html>
