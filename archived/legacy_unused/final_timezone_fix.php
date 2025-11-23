<?php
// Final fix - directly modify the controller to force conversion
require_once __DIR__ . '/app/config/database.php';

echo "<h2>Final Controller Fix</h2>";

// Read the current controller
$controllerPath = __DIR__ . '/app/controllers/AttendanceController.php';
$controllerContent = file_get_contents($controllerPath);

// Find and replace the problematic section
$oldCode = '                // Convert UTC times to IST in PHP
                foreach ($employeeAttendance as &$employee) {
                    if ($employee[\'check_in\']) {
                        $employee[\'check_in\'] = TimezoneHelper::utcToIst($employee[\'check_in\']);
                    }
                    if ($employee[\'check_out\']) {
                        $employee[\'check_out\'] = TimezoneHelper::utcToIst($employee[\'check_out\']);
                    }
                }';

$newCode = '                // Force IST conversion with direct math
                foreach ($employeeAttendance as &$employee) {
                    if ($employee[\'check_in\'] && $employee[\'check_in\'] !== \'0000-00-00 00:00:00\') {
                        // Add 5.5 hours directly
                        $timestamp = strtotime($employee[\'check_in\']);
                        $istTimestamp = $timestamp + 19800; // 5.5 hours in seconds
                        $employee[\'check_in\'] = date(\'Y-m-d H:i:s\', $istTimestamp);
                    }
                    if ($employee[\'check_out\'] && $employee[\'check_out\'] !== \'0000-00-00 00:00:00\') {
                        // Add 5.5 hours directly
                        $timestamp = strtotime($employee[\'check_out\']);
                        $istTimestamp = $timestamp + 19800; // 5.5 hours in seconds
                        $employee[\'check_out\'] = date(\'Y-m-d H:i:s\', $istTimestamp);
                    }
                }';

// Replace in controller
$newControllerContent = str_replace($oldCode, $newCode, $controllerContent);

if ($newControllerContent !== $controllerContent) {
    file_put_contents($controllerPath, $newControllerContent);
    echo "✓ Updated controller with direct timestamp conversion<br>";
} else {
    echo "✗ Could not find the conversion code in controller<br>";
    
    // Alternative: Add conversion right before view call
    $viewCallPattern = '/\$this->view\(\$viewName, \[\s*\'employees\' => \$employeeAttendance,/';
    
    $replacement = '// Force timezone conversion before view
                foreach ($employeeAttendance as &$emp) {
                    if ($emp[\'check_in\']) {
                        $ts = strtotime($emp[\'check_in\']);
                        $emp[\'check_in\'] = date(\'Y-m-d H:i:s\', $ts + 19800);
                    }
                    if ($emp[\'check_out\']) {
                        $ts = strtotime($emp[\'check_out\']);
                        $emp[\'check_out\'] = date(\'Y-m-d H:i:s\', $ts + 19800);
                    }
                }
                
                $this->view($viewName, [
                \'employees\' => $employeeAttendance,';
    
    $newControllerContent = preg_replace($viewCallPattern, $replacement, $controllerContent);
    
    if ($newControllerContent !== $controllerContent) {
        file_put_contents($controllerPath, $newControllerContent);
        echo "✓ Added conversion before view call<br>";
    } else {
        echo "✗ Could not modify controller automatically<br>";
    }
}

// Also update TimezoneHelper to handle already converted times
$helperCode = '<?php
class TimezoneHelper {
    
    public static function displayTime($dbTime) {
        if (!$dbTime || $dbTime === "0000-00-00 00:00:00") return null;
        
        // Extract time part only
        if (strpos($dbTime, " ") !== false) {
            $parts = explode(" ", $dbTime);
            $timePart = $parts[1];
        } else {
            $timePart = $dbTime;
        }
        
        // Return just H:i format
        return date("H:i", strtotime($timePart));
    }
    
    public static function utcToIst($utcTime) {
        if (!$utcTime) return null;
        $timestamp = strtotime($utcTime);
        return date("Y-m-d H:i:s", $timestamp + 19800);
    }
    
    public static function nowUtc() {
        return gmdate("Y-m-d H:i:s");
    }
    
    public static function getCurrentDate() {
        return date("Y-m-d");
    }
}
?>';

file_put_contents(__DIR__ . '/app/helpers/TimezoneHelper.php', $helperCode);
echo "✓ Updated TimezoneHelper to handle converted times<br>";

echo "<h3>✓ Final fix applied!</h3>";
echo "The controller now converts UTC to IST before sending to views.<br>";
echo "TimezoneHelper now just formats the already-converted times.<br>";
?>