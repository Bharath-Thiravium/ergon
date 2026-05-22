<?php
/**
 * STAGE 4 — Flag records needing admin review
 * Marks outside_all and no_gps records with location_name = 'NEEDS_REVIEW'.
 * URL: /ergon/debug/fix_stage4_flag_review.php
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

$dry  = !isset($_GET['run']);
$del  = isset($_GET['delete']); // delete flagged record

// Handle single delete
if ($del && isset($_GET['id'])) {
    $db->prepare("DELETE FROM attendance WHERE id = ?")->execute([(int)$_GET['id']]);
    header('Location: fix_stage4_flag_review.php?run=1');
    exit;
}

$allowedLocations = LocationHelper::getAllowedLocations($db);

// No GPS records
$noGps = $db->query("
    SELECT a.id, a.check_in, a.location_name, a.manual_entry, u.name as user_name
    FROM attendance a JOIN users u ON a.user_id = u.id
    WHERE (a.latitude IS NULL OR a.latitude = 0)
      AND a.manual_entry = 0
      AND a.check_in >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetchAll(PDO::FETCH_ASSOC);

// Outside all zones
$withGps = $db->query("
    SELECT a.id, a.check_in, a.latitude, a.longitude, a.location_name, u.name as user_name
    FROM attendance a JOIN users u ON a.user_id = u.id
    WHERE a.latitude IS NOT NULL AND a.latitude != 0
      AND a.manual_entry = 0
      AND a.location_name != 'NEEDS_REVIEW'
      AND a.check_in >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetchAll(PDO::FETCH_ASSOC);

$outsideAll = [];
foreach ($withGps as $rec) {
    $lat = (float)$rec['latitude'];
    $lng = (float)$rec['longitude'];
    $inside = false;
    $nearest = PHP_FLOAT_MAX;
    $nearestName = '';
    foreach ($allowedLocations as $loc) {
        $d = haversine($lat, $lng, $loc['lat'], $loc['lng']);
        if ($d < $nearest) { $nearest = $d; $nearestName = $loc['name']; }
        if ($d <= $loc['radius']) { $inside = true; break; }
    }
    if (!$inside && !empty($allowedLocations)) {
        $rec['nearest_name'] = $nearestName;
        $rec['nearest_dist'] = round($nearest);
        $outsideAll[] = $rec;
        if (!$dry) {
            $db->prepare("UPDATE attendance SET location_name = 'NEEDS_REVIEW' WHERE id = ?")
               ->execute([$rec['id']]);
        }
    }
}

$totalFlags = count($noGps) + count($outsideAll);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Stage 4 Fix</title>
<style>body{font-family:system-ui;padding:24px;background:#f9fafb}table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px}th{background:#f3f4f6;padding:10px;text-align:left;font-size:.75rem;color:#6b7280;text-transform:uppercase}td{padding:10px;border-top:1px solid #f3f4f6}.btn{padding:6px 14px;border-radius:6px;border:none;cursor:pointer;font-size:.8rem}.btn-run{background:#1d4ed8;color:#fff}.btn-del{background:#dc2626;color:#fff}.warn{background:#fef3c7;border:1px solid #fcd34d;padding:12px;border-radius:8px;margin-bottom:16px}.ok{background:#d1fae5;border:1px solid #6ee7b7;padding:12px;border-radius:8px;margin-bottom:16px}.red{color:#dc2626}.gray{color:#6b7280}</style>
</head><body>
<h2>🔧 Stage 4 — Flag Records for Admin Review</h2>
<p class="gray" style="margin-bottom:16px">Records that cannot be auto-fixed (no GPS or outside all zones). You can flag them as NEEDS_REVIEW or delete them.</p>

<?php if ($dry): ?>
<div class="warn">⚠️ DRY RUN — no changes made. <a href="?run=1"><button class="btn btn-run">▶ Flag <?= $totalFlags ?> Record(s)</button></a></div>
<?php else: ?>
<div class="ok">✅ Flagged <?= count($outsideAll) ?> outside-zone record(s) as NEEDS_REVIEW. No-GPS records shown below for manual action.</div>
<?php endif; ?>

<h3 style="margin:16px 0 8px">Outside All Zones (<?= count($outsideAll) ?>)</h3>
<?php if (empty($outsideAll)): ?>
<div class="ok">✓ None found.</div>
<?php else: ?>
<table>
<thead><tr><th>ID</th><th>User</th><th>Date</th><th>Nearest Location</th><th>Distance</th><th>GPS</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($outsideAll as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['user_name']) ?></td>
    <td><?= date('d M Y H:i', strtotime($r['check_in'])) ?></td>
    <td><?= htmlspecialchars($r['nearest_name']) ?></td>
    <td class="red"><?= $r['nearest_dist'] ?>m away</td>
    <td><a href="https://maps.google.com/?q=<?= $r['latitude'] ?>,<?= $r['longitude'] ?>" target="_blank" style="color:#3b82f6">Map ↗</a></td>
    <td><a href="?run=1&delete=1&id=<?= $r['id'] ?>" onclick="return confirm('Delete this attendance record?')"><button class="btn btn-del">Delete</button></a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<h3 style="margin:16px 0 8px">No GPS Recorded (<?= count($noGps) ?>)</h3>
<?php if (empty($noGps)): ?>
<div class="ok">✓ None found.</div>
<?php else: ?>
<table>
<thead><tr><th>ID</th><th>User</th><th>Date</th><th>Location Name</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($noGps as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['user_name']) ?></td>
    <td><?= date('d M Y H:i', strtotime($r['check_in'])) ?></td>
    <td><?= htmlspecialchars($r['location_name'] ?? '—') ?></td>
    <td><a href="?run=1&delete=1&id=<?= $r['id'] ?>" onclick="return confirm('Delete this attendance record?')"><button class="btn btn-del">Delete</button></a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<p style="margin-top:24px">
    <a href="fix_stage3_missing_project.php">← Stage 3</a> &nbsp;|&nbsp;
    <a href="attendance_location_audit.php">↩ Back to Audit</a>
</p>
</body></html>
