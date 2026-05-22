<?php
/**
 * Attendance Location Discrepancy Analyser
 * Access: https://yourdomain.com/ergon/debug/attendance_location_audit.php
 * TEMPORARY — delete after analysis.
 */

require_once __DIR__ . '/../app/config/environment.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/LocationHelper.php';

$db = Database::connect();

// ── Helpers ───────────────────────────────────────────────────────────────────

function haversine($lat1, $lon1, $lat2, $lon2): float {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return PHP_FLOAT_MAX;
    $R = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function badge(string $text, string $color): string {
    $colors = [
        'green'  => '#065f46;background:#d1fae5',
        'red'    => '#991b1b;background:#fee2e2',
        'yellow' => '#92400e;background:#fef3c7',
        'blue'   => '#1e40af;background:#dbeafe',
        'gray'   => '#374151;background:#f3f4f6',
    ];
    $style = $colors[$color] ?? $colors['gray'];
    return "<span style='color:{$style};padding:2px 8px;border-radius:12px;font-size:0.75rem;font-weight:600'>{$text}</span>";
}

function googleMapsLink($lat, $lng, $label = 'Map'): string {
    if (!$lat || !$lng) return '<span style="color:#9ca3af">No coords</span>';
    return "<a href='https://maps.google.com/?q={$lat},{$lng}' target='_blank' style='color:#3b82f6'>{$label} ↗</a>";
}

function distanceBadge(float $distance, float $radius): string {
    if ($distance === PHP_FLOAT_MAX) return badge('No GPS', 'gray');
    $pct = round(($distance / max($radius, 1)) * 100);
    if ($distance <= $radius)      return badge('✓ Inside (' . round($distance) . 'm)', 'green');
    if ($distance <= $radius * 2)  return badge('⚠ Near (' . round($distance) . 'm)', 'yellow');
    return badge('✗ Outside (' . round($distance) . 'm)', 'red');
}

// ── Data fetching ─────────────────────────────────────────────────────────────

$settings = LocationHelper::getOfficeSettings($db);
$allowedLocations = LocationHelper::getAllowedLocations($db);

// All attendance records with user + project info (last 30 days)
$records = $db->query("
    SELECT
        a.id, a.user_id, a.project_id, a.check_in, a.check_out,
        a.latitude, a.longitude, a.location_name, a.manual_entry,
        u.name as user_name, u.role as user_role,
        p.name as project_name, p.place as project_place,
        p.latitude as proj_lat, p.longitude as proj_lng,
        p.checkin_radius as proj_radius
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN projects p ON a.project_id = p.id
    WHERE a.check_in >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY a.check_in DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Projects with location config
$projects = $db->query("
    SELECT id, name, place, latitude, longitude, checkin_radius, status
    FROM projects ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Users
$users = $db->query("
    SELECT id, name, role, status FROM users WHERE status = 'active' ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Discrepancy analysis
$discrepancies = [];
$stats = [
    'total'           => count($records),
    'no_gps'          => 0,
    'outside_all'     => 0,
    'wrong_project'   => 0,
    'hardcoded_office'=> 0,
    'manual_entry'    => 0,
    'valid'           => 0,
];

foreach ($records as &$rec) {
    $lat = (float)($rec['latitude']  ?? 0);
    $lng = (float)($rec['longitude'] ?? 0);
    $issues = [];

    // 1. No GPS recorded
    if (!$lat || !$lng) {
        $stats['no_gps']++;
        $issues[] = ['type' => 'no_gps', 'msg' => 'No GPS coordinates recorded'];
    }

    // 2. Manual entry
    if ($rec['manual_entry']) {
        $stats['manual_entry']++;
        $issues[] = ['type' => 'manual', 'msg' => 'Manual entry — location not GPS-verified'];
    }

    // 3. location_name hardcoded as 'Office' regardless of actual location
    if ($rec['location_name'] === 'Office' && $lat && $lng) {
        // Check if they were actually at office
        $offLat = (float)$settings['base_location_lat'];
        $offLng = (float)$settings['base_location_lng'];
        $offRadius = (float)($settings['attendance_radius'] ?? 50);
        if ($offLat && $offLng) {
            $distToOffice = haversine($lat, $lng, $offLat, $offLng);
            if ($distToOffice > $offRadius) {
                $stats['hardcoded_office']++;
                $issues[] = ['type' => 'wrong_location_name', 'msg' => "location_name='Office' but user was " . round($distToOffice) . "m away from office"];
            }
        }
    }

    // 4. Check if GPS was outside ALL allowed zones at time of clock-in
    if ($lat && $lng && !$rec['manual_entry']) {
        $nearestDist = PHP_FLOAT_MAX;
        $nearestName = '';
        $insideAny   = false;

        foreach ($allowedLocations as $loc) {
            $d = haversine($lat, $lng, $loc['lat'], $loc['lng']);
            if ($d < $nearestDist) {
                $nearestDist = $d;
                $nearestName = $loc['name'];
            }
            if ($d <= $loc['radius']) {
                $insideAny = true;
                break;
            }
        }

        if (!$insideAny && !empty($allowedLocations)) {
            $stats['outside_all']++;
            $issues[] = ['type' => 'outside_all', 'msg' => "Outside all zones. Nearest: {$nearestName} (" . round($nearestDist) . "m away)"];
        }
    }

    // 5. Project mismatch — has project_id but GPS doesn't match that project
    if ($rec['project_id'] && $lat && $lng && $rec['proj_lat'] && $rec['proj_lng']) {
        $distToProject = haversine($lat, $lng, (float)$rec['proj_lat'], (float)$rec['proj_lng']);
        $radius = (float)($rec['proj_radius'] ?? 100);
        if ($distToProject > $radius) {
            $stats['wrong_project']++;
            $issues[] = ['type' => 'wrong_project', 'msg' => "Assigned to project '{$rec['project_name']}' but GPS is " . round($distToProject) . "m from project site (radius: {$radius}m)"];
        }
    }

    // 6. No project assigned but GPS matches a project
    if (!$rec['project_id'] && $lat && $lng) {
        foreach ($projects as $proj) {
            if (!$proj['latitude'] || !$proj['longitude']) continue;
            $d = haversine($lat, $lng, (float)$proj['latitude'], (float)$proj['longitude']);
            if ($d <= (float)($proj['checkin_radius'] ?? 100)) {
                $issues[] = ['type' => 'missing_project', 'msg' => "GPS matches project '{$proj['name']}' but no project_id recorded"];
                break;
            }
        }
    }

    $rec['_issues'] = $issues;
    $rec['_dist_to_office'] = ($lat && $lng && $settings['base_location_lat'])
        ? haversine($lat, $lng, (float)$settings['base_location_lat'], (float)$settings['base_location_lng'])
        : null;

    if (empty($issues)) $stats['valid']++;
    else $discrepancies[] = $rec;
}
unset($rec);

// Per-user summary
$userSummary = [];
foreach ($records as $rec) {
    $uid = $rec['user_id'];
    if (!isset($userSummary[$uid])) {
        $userSummary[$uid] = [
            'name'    => $rec['user_name'],
            'role'    => $rec['user_role'],
            'total'   => 0,
            'issues'  => 0,
            'types'   => [],
        ];
    }
    $userSummary[$uid]['total']++;
    if (!empty($rec['_issues'])) {
        $userSummary[$uid]['issues']++;
        foreach ($rec['_issues'] as $issue) {
            $userSummary[$uid]['types'][$issue['type']] = ($userSummary[$uid]['types'][$issue['type']] ?? 0) + 1;
        }
    }
}
usort($userSummary, fn($a, $b) => $b['issues'] - $a['issues']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Location Audit</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, sans-serif; background: #f9fafb; color: #111827; font-size: 0.875rem; }
    .wrap { max-width: 1400px; margin: 0 auto; padding: 24px; }
    h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 4px; }
    h2 { font-size: 1rem; font-weight: 600; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 2px solid #e5e7eb; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 24px; }
    .stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; text-align: center; }
    .stat-num { font-size: 2rem; font-weight: 700; }
    .stat-label { font-size: 0.75rem; color: #6b7280; margin-top: 4px; }
    .red   { color: #dc2626; }
    .green { color: #059669; }
    .yellow{ color: #d97706; }
    .blue  { color: #2563eb; }
    table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; margin-bottom: 24px; }
    th { background: #f3f4f6; padding: 10px 12px; text-align: left; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; color: #6b7280; white-space: nowrap; }
    td { padding: 10px 12px; border-top: 1px solid #f3f4f6; vertical-align: top; }
    tr:hover td { background: #f9fafb; }
    .issue-list { list-style: none; }
    .issue-list li { padding: 2px 0; }
    .section { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
    .coords { font-family: monospace; font-size: 0.75rem; color: #6b7280; }
    .filter-bar { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
    .filter-bar a { padding: 6px 14px; border-radius: 6px; border: 1px solid #d1d5db; text-decoration: none; color: #374151; font-size: 0.8rem; }
    .filter-bar a.active, .filter-bar a:hover { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }
    .warn-box { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
    .ok-box   { background: #d1fae5; border: 1px solid #6ee7b7; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="wrap">

<h1>🗺️ Attendance Location Discrepancy Audit</h1>
<p style="color:#6b7280;margin-bottom:24px">Last 30 days · Generated <?= date('d M Y, h:i A') ?> IST</p>

<!-- ── SECTION 1: Summary Stats ── -->
<h2>1. Overview</h2>
<div class="grid">
    <div class="stat"><div class="stat-num blue"><?= $stats['total'] ?></div><div class="stat-label">Total Records</div></div>
    <div class="stat"><div class="stat-num green"><?= $stats['valid'] ?></div><div class="stat-label">Clean Records</div></div>
    <div class="stat"><div class="stat-num red"><?= count($discrepancies) ?></div><div class="stat-label">With Issues</div></div>
    <div class="stat"><div class="stat-num red"><?= $stats['no_gps'] ?></div><div class="stat-label">No GPS</div></div>
    <div class="stat"><div class="stat-num red"><?= $stats['outside_all'] ?></div><div class="stat-label">Outside All Zones</div></div>
    <div class="stat"><div class="stat-num yellow"><?= $stats['wrong_project'] ?></div><div class="stat-label">Wrong Project</div></div>
    <div class="stat"><div class="stat-num yellow"><?= $stats['hardcoded_office'] ?></div><div class="stat-label">Wrong Location Name</div></div>
    <div class="stat"><div class="stat-num blue"><?= $stats['manual_entry'] ?></div><div class="stat-label">Manual Entries</div></div>
</div>

<!-- ── SECTION 2: Office & Location Config ── -->
<h2>2. Configured Locations</h2>
<?php if (empty($allowedLocations)): ?>
<div class="warn-box">⚠️ <strong>No locations configured.</strong> GPS validation is completely bypassed — anyone can clock in from anywhere. Go to Settings and set the office location.</div>
<?php else: ?>
<div class="ok-box">✓ <?= count($allowedLocations) ?> location(s) configured. GPS validation is active.</div>
<table>
    <thead><tr><th>Type</th><th>Name</th><th>Coordinates</th><th>Radius</th><th>Map</th></tr></thead>
    <tbody>
    <?php foreach ($allowedLocations as $loc): ?>
    <tr>
        <td><?= badge(ucfirst($loc['type']), $loc['type'] === 'office' ? 'blue' : 'green') ?></td>
        <td><?= htmlspecialchars($loc['name']) ?></td>
        <td class="coords"><?= $loc['lat'] ?>, <?= $loc['lng'] ?></td>
        <td><?= $loc['radius'] ?>m</td>
        <td><?= googleMapsLink($loc['lat'], $loc['lng'], 'View') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- ── SECTION 3: Per-User Summary ── -->
<h2>3. Per-User Issue Summary</h2>
<table>
    <thead><tr><th>User</th><th>Role</th><th>Total Records</th><th>Issues</th><th>Issue Breakdown</th></tr></thead>
    <tbody>
    <?php foreach ($userSummary as $u): ?>
    <tr>
        <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
        <td><?= badge($u['role'], 'gray') ?></td>
        <td><?= $u['total'] ?></td>
        <td><?= $u['issues'] > 0 ? "<span class='red'><strong>{$u['issues']}</strong></span>" : "<span class='green'>0</span>" ?></td>
        <td>
            <?php foreach ($u['types'] as $type => $count): ?>
                <?= badge(str_replace('_', ' ', $type) . " ({$count})", match($type) {
                    'no_gps', 'outside_all' => 'red',
                    'wrong_project', 'wrong_location_name' => 'yellow',
                    'manual' => 'blue',
                    default => 'gray'
                }) ?>
            <?php endforeach; ?>
            <?php if (empty($u['types'])): ?><?= badge('Clean', 'green') ?><?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- ── SECTION 4: Detailed Discrepancies ── -->
<h2>4. Detailed Discrepancy Records</h2>
<?php if (empty($discrepancies)): ?>
<div class="ok-box">✓ No discrepancies found in the last 30 days.</div>
<?php else: ?>
<div class="filter-bar">
    <a href="#" class="active" onclick="filterTable('all');return false">All (<?= count($discrepancies) ?>)</a>
    <a href="#" onclick="filterTable('outside_all');return false">Outside Zone</a>
    <a href="#" onclick="filterTable('wrong_project');return false">Wrong Project</a>
    <a href="#" onclick="filterTable('no_gps');return false">No GPS</a>
    <a href="#" onclick="filterTable('manual');return false">Manual Entry</a>
    <a href="#" onclick="filterTable('wrong_location_name');return false">Wrong Name</a>
</div>
<table id="discTable">
    <thead>
        <tr>
            <th>User</th>
            <th>Date / Time</th>
            <th>Recorded Location</th>
            <th>GPS Coordinates</th>
            <th>Distance to Office</th>
            <th>Assigned Project</th>
            <th>Issues</th>
            <th>Map</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($discrepancies as $rec):
        $issueTypes = array_column($rec['_issues'], 'type');
        $dataTypes  = implode(' ', $issueTypes);
    ?>
    <tr data-types="<?= htmlspecialchars($dataTypes) ?>">
        <td>
            <strong><?= htmlspecialchars($rec['user_name']) ?></strong><br>
            <?= badge($rec['user_role'], 'gray') ?>
        </td>
        <td>
            <?= date('d M Y', strtotime($rec['check_in'])) ?><br>
            <span style="color:#6b7280">In: <?= date('H:i', strtotime($rec['check_in'])) ?>
            <?= $rec['check_out'] ? ' · Out: ' . date('H:i', strtotime($rec['check_out'])) : ' · <span style="color:#f59e0b">No checkout</span>' ?></span>
        </td>
        <td>
            <?= htmlspecialchars($rec['location_name'] ?? '—') ?>
            <?php if ($rec['manual_entry']): ?><br><?= badge('Manual', 'blue') ?><?php endif; ?>
        </td>
        <td class="coords">
            <?php if ($rec['latitude'] && $rec['longitude']): ?>
                <?= $rec['latitude'] ?><br><?= $rec['longitude'] ?>
            <?php else: ?>
                <?= badge('Missing', 'red') ?>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($rec['_dist_to_office'] !== null): ?>
                <?= distanceBadge($rec['_dist_to_office'], (float)($settings['attendance_radius'] ?? 50)) ?>
                <br><span style="color:#9ca3af;font-size:0.7rem">Allowed: <?= $settings['attendance_radius'] ?? 50 ?>m</span>
            <?php else: ?>
                <?= badge('N/A', 'gray') ?>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($rec['project_name']): ?>
                <strong><?= htmlspecialchars($rec['project_name']) ?></strong><br>
                <span style="color:#6b7280"><?= htmlspecialchars($rec['project_place'] ?? '') ?></span>
                <?php if ($rec['proj_lat'] && $rec['proj_lng'] && $rec['latitude'] && $rec['longitude']): ?>
                    <?php $dp = haversine((float)$rec['latitude'], (float)$rec['longitude'], (float)$rec['proj_lat'], (float)$rec['proj_lng']); ?>
                    <br><?= distanceBadge($dp, (float)($rec['proj_radius'] ?? 100)) ?>
                <?php endif; ?>
            <?php else: ?>
                <span style="color:#9ca3af">None assigned</span>
            <?php endif; ?>
        </td>
        <td>
            <ul class="issue-list">
            <?php foreach ($rec['_issues'] as $issue): ?>
                <li><?= badge(match($issue['type']) {
                    'no_gps'              => '⚠ No GPS',
                    'outside_all'         => '✗ Outside Zone',
                    'wrong_project'       => '✗ Wrong Project',
                    'wrong_location_name' => '⚠ Wrong Name',
                    'missing_project'     => '⚠ Missing Project',
                    'manual'              => 'ℹ Manual',
                    default               => $issue['type']
                }, match($issue['type']) {
                    'no_gps', 'outside_all', 'wrong_project' => 'red',
                    'wrong_location_name', 'missing_project' => 'yellow',
                    default => 'blue'
                }) ?>
                <br><span style="color:#6b7280;font-size:0.7rem"><?= htmlspecialchars($issue['msg']) ?></span></li>
            <?php endforeach; ?>
            </ul>
        </td>
        <td><?= googleMapsLink($rec['latitude'], $rec['longitude'], 'User') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- ── SECTION 5: Projects Without Location Config ── -->
<h2>5. Projects Missing GPS Configuration</h2>
<?php
$noGpsProjects = array_filter($projects, fn($p) => !$p['latitude'] || !$p['longitude'] || $p['latitude'] == 0);
?>
<?php if (empty($noGpsProjects)): ?>
<div class="ok-box">✓ All active projects have GPS coordinates configured.</div>
<?php else: ?>
<div class="warn-box">⚠️ <?= count($noGpsProjects) ?> project(s) have no GPS — attendance at these sites cannot be location-validated.</div>
<table>
    <thead><tr><th>Project</th><th>Place</th><th>Status</th><th>Latitude</th><th>Longitude</th><th>Radius</th></tr></thead>
    <tbody>
    <?php foreach ($noGpsProjects as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= htmlspecialchars($p['place'] ?? '—') ?></td>
        <td><?= badge($p['status'], $p['status'] === 'active' ? 'green' : 'gray') ?></td>
        <td><?= $p['latitude'] ?: badge('Missing', 'red') ?></td>
        <td><?= $p['longitude'] ?: badge('Missing', 'red') ?></td>
        <td><?= $p['checkin_radius'] ?: badge('Missing', 'yellow') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- ── SECTION 6: Settings Check ── -->
<h2>6. System Settings Check</h2>
<table>
    <thead><tr><th>Setting</th><th>Value</th><th>Status</th></tr></thead>
    <tbody>
    <tr>
        <td>Office Location Title</td>
        <td><?= htmlspecialchars($settings['location_title'] ?? '—') ?></td>
        <td><?= $settings['location_title'] ? badge('Set', 'green') : badge('Missing', 'red') ?></td>
    </tr>
    <tr>
        <td>Base Latitude</td>
        <td class="coords"><?= $settings['base_location_lat'] ?? '—' ?></td>
        <td><?= ($settings['base_location_lat'] ?? 0) != 0 ? badge('Set', 'green') : badge('Not configured', 'red') ?></td>
    </tr>
    <tr>
        <td>Base Longitude</td>
        <td class="coords"><?= $settings['base_location_lng'] ?? '—' ?></td>
        <td><?= ($settings['base_location_lng'] ?? 0) != 0 ? badge('Set', 'green') : badge('Not configured', 'red') ?></td>
    </tr>
    <tr>
        <td>Attendance Radius</td>
        <td><?= $settings['attendance_radius'] ?? '—' ?>m</td>
        <td><?= ($settings['attendance_radius'] ?? 0) > 0 ? badge('Set', 'green') : badge('Using default 50m', 'yellow') ?></td>
    </tr>
    <tr>
        <td>Office on Map</td>
        <td><?= googleMapsLink($settings['base_location_lat'] ?? 0, $settings['base_location_lng'] ?? 0, 'View office location') ?></td>
        <td></td>
    </tr>
    </tbody>
</table>

<!-- ── SECTION 7: Raw GPS Spot-Check ── -->
<h2>7. GPS Coordinate Spot-Check (Last 10 Records)</h2>
<p style="color:#6b7280;margin-bottom:12px">Click "Map" to verify each user's actual clock-in location on Google Maps.</p>
<table>
    <thead><tr><th>User</th><th>Date</th><th>GPS</th><th>Location Recorded</th><th>Distance to Office</th><th>Verify</th></tr></thead>
    <tbody>
    <?php foreach (array_slice($records, 0, 10) as $rec): ?>
    <tr>
        <td><?= htmlspecialchars($rec['user_name']) ?></td>
        <td><?= date('d M, H:i', strtotime($rec['check_in'])) ?></td>
        <td class="coords">
            <?= $rec['latitude'] ? $rec['latitude'] . ', ' . $rec['longitude'] : badge('No GPS', 'red') ?>
        </td>
        <td><?= htmlspecialchars($rec['location_name'] ?? '—') ?></td>
        <td>
            <?php if ($rec['_dist_to_office'] !== null): ?>
                <?= distanceBadge($rec['_dist_to_office'], (float)($settings['attendance_radius'] ?? 50)) ?>
            <?php else: ?>—<?php endif; ?>
        </td>
        <td><?= googleMapsLink($rec['latitude'], $rec['longitude'], 'Open Map') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<p style="color:#9ca3af;font-size:0.75rem;margin-top:32px">⚠️ Delete this file from the server after analysis: <code>/ergon/debug/attendance_location_audit.php</code></p>
</div>

<script>
function filterTable(type) {
    document.querySelectorAll('.filter-bar a').forEach(a => a.classList.remove('active'));
    event.target.classList.add('active');
    document.querySelectorAll('#discTable tbody tr').forEach(row => {
        row.style.display = (type === 'all' || row.dataset.types.includes(type)) ? '' : 'none';
    });
}
</script>
</body>
</html>
