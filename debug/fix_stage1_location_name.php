<?php
/**
 * STAGE 1 — Fix wrong location_name
 * Re-derives location_name from GPS against configured locations and updates DB.
 * URL: /ergon/debug/fix_stage1_location_name.php
 */
require_once __DIR__ . '/../app/config/environment.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/LocationHelper.php';

$db = Database::connect();

function haversine($lat1,$lon1,$lat2,$lon2): float {
    if (!$lat1||!$lon1||!$lat2||!$lon2) return PHP_FLOAT_MAX;
    $R=6371000;
    $a=sin(deg2rad($lat2-$lat1)/2)**2+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin(deg2rad($lon2-$lon1)/2)**2;
    return $R*2*atan2(sqrt($a),sqrt(1-$a));
}

$dry = !isset($_GET['run']);
$settings = LocationHelper::getOfficeSettings($db);
$allowedLocations = LocationHelper::getAllowedLocations($db);

$records = $db->query("
    SELECT a.id, a.latitude, a.longitude, a.location_name, a.project_id, u.name as user_name
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE a.latitude IS NOT NULL AND a.longitude != 0
      AND a.manual_entry = 0
      AND a.check_in >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetchAll(PDO::FETCH_ASSOC);

$results = [];
foreach ($records as $rec) {
    $lat = (float)$rec['latitude'];
    $lng = (float)$rec['longitude'];
    $correctName = null;

    foreach ($allowedLocations as $loc) {
        $d = haversine($lat, $lng, $loc['lat'], $loc['lng']);
        if ($d <= $loc['radius']) {
            $correctName = $loc['name'];
            break;
        }
    }

    if (!$correctName) continue; // outside all zones — handled in stage 4
    if ($rec['location_name'] === $correctName) continue; // already correct

    $results[] = [
        'id'       => $rec['id'],
        'user'     => $rec['user_name'],
        'old_name' => $rec['location_name'],
        'new_name' => $correctName,
    ];

    if (!$dry) {
        $db->prepare("UPDATE attendance SET location_name = ? WHERE id = ?")
           ->execute([$correctName, $rec['id']]);
    }
}

header('Content-Type: text/html');
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Stage 1 Fix</title>
<style>body{font-family:system-ui;padding:24px;background:#f9fafb}table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:8px}th{background:#f3f4f6;padding:10px;text-align:left;font-size:.75rem;color:#6b7280;text-transform:uppercase}td{padding:10px;border-top:1px solid #f3f4f6}.btn{padding:8px 18px;border-radius:6px;border:none;cursor:pointer;font-size:.875rem}.btn-run{background:#1d4ed8;color:#fff}.warn{background:#fef3c7;border:1px solid #fcd34d;padding:12px;border-radius:8px;margin-bottom:16px}.ok{background:#d1fae5;border:1px solid #6ee7b7;padding:12px;border-radius:8px;margin-bottom:16px}</style>
</head><body>
<h2>🔧 Stage 1 — Fix Wrong Location Name</h2>
<?php if ($dry): ?>
<div class="warn">⚠️ DRY RUN — no changes made. <a href="?run=1"><button class="btn btn-run">▶ Apply <?= count($results) ?> Fix(es)</button></a></div>
<?php else: ?>
<div class="ok">✅ Applied <?= count($results) ?> fix(es) to the database.</div>
<?php endif; ?>

<?php if (empty($results)): ?>
<div class="ok">✓ No location_name discrepancies found.</div>
<?php else: ?>
<table>
<thead><tr><th>ID</th><th>User</th><th>Old Name</th><th>New Name</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($results as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['user']) ?></td>
    <td style="color:#dc2626"><?= htmlspecialchars($r['old_name']) ?></td>
    <td style="color:#059669"><?= htmlspecialchars($r['new_name']) ?></td>
    <td><?= $dry ? '⏳ Pending' : '✅ Fixed' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
<p style="margin-top:24px"><a href="fix_stage2_wrong_project.php">→ Proceed to Stage 2</a></p>
</body></html>
