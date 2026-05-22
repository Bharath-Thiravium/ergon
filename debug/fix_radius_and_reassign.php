<?php
/**
 * Attendance Location Fix — Radius Update + Record Reassignment
 * Based on audit findings from 22 May 2026.
 * URL: /ergon/debug/fix_radius_and_reassign.php
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

$dry = !isset($_GET['run']);
$log = [];

// ── Recommended radius updates based on audit ────────────────────────────────
// Project name => new radius in meters
$radiusUpdates = [
    'Koppal Site'       => 3000,   // Ameerpasha consistently 1900-2300m away
    'Enrich Navia 2'    => 5000,   // Johnkennedy consistently 3500-4800m away
    'Phoenix'           => 5000,   // Johnkennedy at Phoenix area
    'Athens Site'       => 1500,   // keep as is
];

// ── Step 1: Update radii ──────────────────────────────────────────────────────
$projects = $db->query("SELECT id, name, checkin_radius FROM projects WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

$radiusLog = [];
foreach ($projects as $proj) {
    foreach ($radiusUpdates as $nameFragment => $newRadius) {
        if (stripos($proj['name'], $nameFragment) !== false && $proj['checkin_radius'] != $newRadius) {
            $radiusLog[] = [
                'project' => $proj['name'],
                'old'     => $proj['checkin_radius'],
                'new'     => $newRadius,
                'id'      => $proj['id'],
            ];
            if (!$dry) {
                $db->prepare("UPDATE projects SET checkin_radius = ? WHERE id = ?")
                   ->execute([$newRadius, $proj['id']]);
            }
        }
    }
}

// ── Step 2: Re-fetch projects with updated radii ──────────────────────────────
$projects = $db->query("
    SELECT id, name, latitude, longitude, checkin_radius
    FROM projects WHERE latitude IS NOT NULL AND latitude != 0 AND status = 'active'
")->fetchAll(PDO::FETCH_ASSOC);

// ── Step 3: Reassign NEEDS_REVIEW records to correct project ─────────────────
$flagged = $db->query("
    SELECT a.id, a.latitude, a.longitude, u.name as user_name
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE a.location_name = 'NEEDS_REVIEW'
      AND a.latitude IS NOT NULL AND a.latitude != 0
")->fetchAll(PDO::FETCH_ASSOC);

$reassignLog = [];
foreach ($flagged as $rec) {
    $lat = (float)$rec['latitude'];
    $lng = (float)$rec['longitude'];
    $bestId   = null;
    $bestName = null;
    $bestDist = PHP_FLOAT_MAX;

    foreach ($projects as $proj) {
        $d = haversine($lat, $lng, (float)$proj['latitude'], (float)$proj['longitude']);
        // Use updated radius (already applied above in dry run preview)
        $radius = $proj['checkin_radius'];
        // For dry run, use the new radius from $radiusUpdates if applicable
        foreach ($radiusUpdates as $frag => $nr) {
            if (stripos($proj['name'], $frag) !== false) { $radius = $nr; break; }
        }
        if ($d <= $radius && $d < $bestDist) {
            $bestDist = $d;
            $bestId   = $proj['id'];
            $bestName = $proj['name'];
        }
    }

    $reassignLog[] = [
        'id'       => $rec['id'],
        'user'     => $rec['user_name'],
        'project'  => $bestName ?? '— still outside all zones',
        'proj_id'  => $bestId,
        'distance' => round($bestDist === PHP_FLOAT_MAX ? 0 : $bestDist),
        'fixable'  => $bestId !== null,
    ];

    if (!$dry && $bestId) {
        $db->prepare("UPDATE attendance SET project_id = ?, location_name = ? WHERE id = ?")
           ->execute([$bestId, $bestName . ' Site', $rec['id']]);
    }
}

// ── Step 4: Fix no-GPS records — mark as manual ───────────────────────────────
$noGps = $db->query("
    SELECT a.id, u.name as user_name, a.check_in
    FROM attendance a JOIN users u ON a.user_id = u.id
    WHERE (a.latitude IS NULL OR a.latitude = 0)
      AND a.manual_entry = 0
      AND a.location_name != 'NEEDS_REVIEW'
")->fetchAll(PDO::FETCH_ASSOC);

if (!$dry) {
    foreach ($noGps as $r) {
        $db->prepare("UPDATE attendance SET manual_entry = 1, location_name = 'Manual Entry' WHERE id = ?")
           ->execute([$r['id']]);
    }
}

?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fix Radius & Reassign</title>
<style>
body{font-family:system-ui;padding:24px;background:#f9fafb;color:#111827}
h2{font-size:1.1rem;font-weight:700;margin:20px 0 10px;padding-bottom:6px;border-bottom:2px solid #e5e7eb}
table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:20px}
th{background:#f3f4f6;padding:10px;text-align:left;font-size:.75rem;color:#6b7280;text-transform:uppercase}
td{padding:10px;border-top:1px solid #f3f4f6;font-size:.875rem}
.warn{background:#fef3c7;border:1px solid #fcd34d;padding:12px 16px;border-radius:8px;margin-bottom:16px}
.ok{background:#d1fae5;border:1px solid #6ee7b7;padding:12px 16px;border-radius:8px;margin-bottom:16px}
.red{color:#dc2626}.green{color:#059669}.gray{color:#6b7280}
.btn{padding:8px 20px;border-radius:6px;border:none;cursor:pointer;font-weight:600;font-size:.875rem;background:#1d4ed8;color:#fff}
</style>
</head><body>
<h1 style="font-size:1.3rem;font-weight:700;margin-bottom:4px">🔧 Fix Radius & Reassign Attendance</h1>
<p class="gray" style="margin-bottom:20px">Based on audit findings — 22 May 2026</p>

<?php if ($dry): ?>
<div class="warn">
    ⚠️ <strong>DRY RUN</strong> — previewing changes only. Nothing has been saved.
    <br><br>
    <a href="?run=1"><button class="btn">▶ Apply All Fixes</button></a>
</div>
<?php else: ?>
<div class="ok">✅ All fixes applied successfully.</div>
<?php endif; ?>

<!-- Radius Updates -->
<h2>Step 1 — Project Radius Updates (<?= count($radiusLog) ?>)</h2>
<?php if (empty($radiusLog)): ?>
<div class="ok">✓ All radii already correct.</div>
<?php else: ?>
<table>
<thead><tr><th>Project</th><th>Old Radius</th><th>New Radius</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($radiusLog as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['project']) ?></td>
    <td class="red"><?= $r['old'] ?>m</td>
    <td class="green"><?= $r['new'] ?>m</td>
    <td><?= $dry ? '⏳ Pending' : '✅ Updated' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- Reassignment -->
<h2>Step 2 — Reassign NEEDS_REVIEW Records (<?= count($reassignLog) ?>)</h2>
<?php if (empty($reassignLog)): ?>
<div class="ok">✓ No flagged records to reassign.</div>
<?php else: ?>
<table>
<thead><tr><th>ID</th><th>User</th><th>Assigned To</th><th>Distance</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($reassignLog as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['user']) ?></td>
    <td <?= $r['fixable'] ? 'class="green"' : 'class="red"' ?>><?= htmlspecialchars($r['project']) ?></td>
    <td><?= $r['fixable'] ? $r['distance'].'m' : '—' ?></td>
    <td><?= $dry ? '⏳ Pending' : ($r['fixable'] ? '✅ Fixed' : '⚠ Still outside') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- No GPS -->
<h2>Step 3 — No-GPS Records Marked as Manual (<?= count($noGps) ?>)</h2>
<?php if (empty($noGps)): ?>
<div class="ok">✓ No records without GPS.</div>
<?php else: ?>
<table>
<thead><tr><th>ID</th><th>User</th><th>Date</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($noGps as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['user_name']) ?></td>
    <td><?= date('d M Y H:i', strtotime($r['check_in'])) ?></td>
    <td><?= $dry ? '⏳ Pending' : '✅ Marked Manual' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<p style="margin-top:24px;font-size:.75rem;color:#9ca3af">
    ⚠️ Delete this file after use: <code>/ergon/debug/fix_radius_and_reassign.php</code><br>
    <a href="attendance_location_audit.php" style="color:#3b82f6">↩ Re-run audit to verify</a>
</p>
</body></html>
