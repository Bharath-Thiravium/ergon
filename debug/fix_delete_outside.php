<?php
/**
 * Delete records still outside all zones after radius fix.
 * URL: /ergon/debug/fix_delete_outside.php
 * TEMPORARY — delete after use.
 */
require_once __DIR__ . '/../app/config/environment.php';
require_once __DIR__ . '/../app/config/database.php';

$db = Database::connect();

function haversine($lat1,$lon1,$lat2,$lon2): float {
    if (!$lat1||!$lon1||!$lat2||!$lon2) return PHP_FLOAT_MAX;
    $R=6371000;
    $a=sin(deg2rad($lat2-$lat1)/2)**2+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin(deg2rad($lon2-$lon1)/2)**2;
    return $R*2*atan2(sqrt($a),sqrt(1-$a));
}

// Handle single delete confirmation
if (isset($_GET['delete_id'])) {
    $db->prepare("DELETE FROM attendance WHERE id = ?")->execute([(int)$_GET['delete_id']]);
    header('Location: fix_delete_outside.php?run=1');
    exit;
}

// Handle delete all
if (isset($_POST['delete_all'])) {
    $ids = array_map('intval', explode(',', $_POST['ids'] ?? ''));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM attendance WHERE id IN ($placeholders)")->execute($ids);
    }
    header('Location: fix_delete_outside.php?done=1');
    exit;
}

$projects = $db->query("
    SELECT id, name, latitude, longitude, checkin_radius
    FROM projects WHERE latitude IS NOT NULL AND latitude != 0 AND status = 'active'
")->fetchAll(PDO::FETCH_ASSOC);

$settings = $db->query("SELECT base_location_lat, base_location_lng, attendance_radius, location_title FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Build full location list (office + projects)
$locations = [];
if (!empty($settings['base_location_lat'])) {
    $locations[] = [
        'name'   => $settings['location_title'] ?? 'Office',
        'lat'    => (float)$settings['base_location_lat'],
        'lng'    => (float)$settings['base_location_lng'],
        'radius' => (float)$settings['attendance_radius'],
    ];
}
foreach ($projects as $p) {
    $locations[] = [
        'name'   => $p['name'],
        'lat'    => (float)$p['latitude'],
        'lng'    => (float)$p['longitude'],
        'radius' => (float)$p['checkin_radius'],
    ];
}

// Get all records with GPS
$records = $db->query("
    SELECT a.id, a.latitude, a.longitude, a.check_in, a.check_out, a.location_name,
           u.name as user_name
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE a.latitude IS NOT NULL AND a.latitude != 0
    ORDER BY u.name, a.check_in DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Find records outside ALL zones
$outside = [];
foreach ($records as $rec) {
    $lat = (float)$rec['latitude'];
    $lng = (float)$rec['longitude'];
    $nearestDist = PHP_FLOAT_MAX;
    $nearestName = '';
    $inside = false;

    foreach ($locations as $loc) {
        $d = haversine($lat, $lng, $loc['lat'], $loc['lng']);
        if ($d < $nearestDist) { $nearestDist = $d; $nearestName = $loc['name']; }
        if ($d <= $loc['radius']) { $inside = true; break; }
    }

    if (!$inside) {
        $rec['nearest_name'] = $nearestName;
        $rec['nearest_dist'] = round($nearestDist);
        $outside[] = $rec;
    }
}

$allIds = implode(',', array_column($outside, 'id'));
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Delete Outside Zone Records</title>
<style>
body{font-family:system-ui;padding:24px;background:#f9fafb;color:#111827}
table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:20px}
th{background:#f3f4f6;padding:10px;text-align:left;font-size:.75rem;color:#6b7280;text-transform:uppercase}
td{padding:10px;border-top:1px solid #f3f4f6;font-size:.875rem}
.warn{background:#fee2e2;border:1px solid #fca5a5;padding:12px 16px;border-radius:8px;margin-bottom:16px}
.ok{background:#d1fae5;border:1px solid #6ee7b7;padding:12px 16px;border-radius:8px;margin-bottom:16px}
.btn-del{padding:6px 14px;border-radius:6px;border:none;cursor:pointer;background:#dc2626;color:#fff;font-size:.8rem}
.btn-all{padding:10px 24px;border-radius:6px;border:none;cursor:pointer;background:#dc2626;color:#fff;font-weight:700;font-size:.9rem}
.gray{color:#6b7280}
</style>
</head><body>
<h1 style="font-size:1.3rem;font-weight:700;margin-bottom:4px">🗑️ Delete Outside Zone Records</h1>
<p class="gray" style="margin-bottom:20px">Records with GPS coordinates that fall outside all configured locations even after radius updates.</p>

<?php if (isset($_GET['done'])): ?>
<div class="ok">✅ All selected records deleted successfully. <a href="attendance_location_audit.php">↩ Re-run audit</a></div>
<?php elseif (empty($outside)): ?>
<div class="ok">✓ No records found outside all zones. Everything is clean!</div>
<?php else: ?>

<div class="warn">
    ⚠️ <strong><?= count($outside) ?> record(s)</strong> are outside all configured zones.
    These cannot be matched to any valid location.
</div>

<table>
<thead>
    <tr>
        <th>ID</th>
        <th>User</th>
        <th>Date / Time</th>
        <th>GPS</th>
        <th>Nearest Location</th>
        <th>Distance</th>
        <th>Map</th>
        <th>Action</th>
    </tr>
</thead>
<tbody>
<?php foreach ($outside as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><strong><?= htmlspecialchars($r['user_name']) ?></strong></td>
    <td>
        <?= date('d M Y', strtotime($r['check_in'])) ?><br>
        <span class="gray">
            In: <?= date('H:i', strtotime($r['check_in'])) ?>
            <?= $r['check_out'] ? ' · Out: '.date('H:i', strtotime($r['check_out'])) : '' ?>
        </span>
    </td>
    <td style="font-family:monospace;font-size:.75rem"><?= $r['latitude'] ?>, <?= $r['longitude'] ?></td>
    <td><?= htmlspecialchars($r['nearest_name']) ?></td>
    <td style="color:#dc2626"><?= number_format($r['nearest_dist']) ?>m away</td>
    <td><a href="https://maps.google.com/?q=<?= $r['latitude'] ?>,<?= $r['longitude'] ?>" target="_blank" style="color:#3b82f6">Map ↗</a></td>
    <td>
        <a href="?delete_id=<?= $r['id'] ?>" onclick="return confirm('Delete attendance record #<?= $r['id'] ?> for <?= htmlspecialchars($r['user_name']) ?>?')">
            <button class="btn-del">Delete</button>
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<form method="POST" onsubmit="return confirm('Delete ALL <?= count($outside) ?> outside-zone records? This cannot be undone.')">
    <input type="hidden" name="ids" value="<?= htmlspecialchars($allIds) ?>">
    <input type="hidden" name="delete_all" value="1">
    <button type="submit" class="btn-all">🗑️ Delete All <?= count($outside) ?> Records</button>
</form>

<?php endif; ?>

<p style="margin-top:24px;font-size:.75rem;color:#9ca3af">
    ⚠️ Delete this file after use: <code>/ergon/debug/fix_delete_outside.php</code>
</p>
</body></html>
