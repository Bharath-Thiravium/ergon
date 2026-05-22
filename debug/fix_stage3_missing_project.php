<?php
/**
 * STAGE 3 — Assign missing project_id
 * Finds records with no project_id but GPS matches an active project.
 * URL: /ergon/debug/fix_stage3_missing_project.php
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

$dry = !isset($_GET['run']);

$records = $db->query("
    SELECT a.id, a.latitude, a.longitude, u.name as user_name
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE a.project_id IS NULL
      AND a.latitude IS NOT NULL AND a.latitude != 0
      AND a.manual_entry = 0
      AND a.check_in >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetchAll(PDO::FETCH_ASSOC);

$projects = $db->query("
    SELECT id, name, latitude, longitude, checkin_radius
    FROM projects WHERE latitude IS NOT NULL AND latitude != 0 AND status = 'active'
")->fetchAll(PDO::FETCH_ASSOC);

$results = [];
foreach ($records as $rec) {
    $lat = (float)$rec['latitude'];
    $lng = (float)$rec['longitude'];

    foreach ($projects as $proj) {
        $d = haversine($lat, $lng, (float)$proj['latitude'], (float)$proj['longitude']);
        if ($d <= (float)($proj['checkin_radius'] ?? 100)) {
            $results[] = [
                'id'       => $rec['id'],
                'user'     => $rec['user_name'],
                'project'  => $proj['name'],
                'proj_id'  => $proj['id'],
                'distance' => round($d),
            ];
            if (!$dry) {
                $db->prepare("UPDATE attendance SET project_id = ? WHERE id = ?")
                   ->execute([$proj['id'], $rec['id']]);
            }
            break;
        }
    }
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Stage 3 Fix</title>
<style>body{font-family:system-ui;padding:24px;background:#f9fafb}table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:8px}th{background:#f3f4f6;padding:10px;text-align:left;font-size:.75rem;color:#6b7280;text-transform:uppercase}td{padding:10px;border-top:1px solid #f3f4f6}.btn{padding:8px 18px;border-radius:6px;border:none;cursor:pointer;font-size:.875rem}.btn-run{background:#1d4ed8;color:#fff}.warn{background:#fef3c7;border:1px solid #fcd34d;padding:12px;border-radius:8px;margin-bottom:16px}.ok{background:#d1fae5;border:1px solid #6ee7b7;padding:12px;border-radius:8px;margin-bottom:16px}</style>
</head><body>
<h2>🔧 Stage 3 — Assign Missing Project ID</h2>
<?php if ($dry): ?>
<div class="warn">⚠️ DRY RUN — no changes made. <a href="?run=1"><button class="btn btn-run">▶ Apply <?= count($results) ?> Fix(es)</button></a></div>
<?php else: ?>
<div class="ok">✅ Applied <?= count($results) ?> fix(es) to the database.</div>
<?php endif; ?>

<?php if (empty($results)): ?>
<div class="ok">✓ No missing project assignments found.</div>
<?php else: ?>
<table>
<thead><tr><th>ID</th><th>User</th><th>Matched Project</th><th>GPS Distance</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($results as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['user']) ?></td>
    <td style="color:#059669"><?= htmlspecialchars($r['project']) ?></td>
    <td><?= $r['distance'] ?>m</td>
    <td><?= $dry ? '⏳ Pending' : '✅ Fixed' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
<p style="margin-top:24px">
    <a href="fix_stage2_wrong_project.php">← Stage 2</a> &nbsp;|&nbsp;
    <a href="fix_stage4_flag_review.php">→ Stage 4</a>
</p>
</body></html>
