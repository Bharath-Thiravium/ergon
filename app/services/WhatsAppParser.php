<?php
/**
 * WhatsAppParser — Shared parsing service
 * Used by both Admin and Employee panels. Do NOT duplicate this logic.
 *
 * Input  : Raw pasted WhatsApp message (multi-line string)
 * Output : Structured array { work_done, materials_used, issues_faced, raw_cleaned }
 */
class WhatsAppParser {

    // Patterns that identify WhatsApp noise
    private static $noisePatterns = [
        '/^\d{1,2}:\d{2}\s*(AM|PM)?\s*[-–]\s*/im',   // timestamps: "10:45 AM -"
        '/^\[?\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4},?\s*\d{1,2}:\d{2}.*?\]\s*/im', // date+time headers
        '/^(Forwarded|This message was forwarded)\b.*/im', // forwarded labels
        '/[\x{1F300}-\x{1FAFF}]/u',                   // emoji block
        '/[\x{2600}-\x{27BF}]/u',                     // misc symbols
        '/^~.+~$/m',                                   // strikethrough text
        '/\u200e|\u200f|\u202a-\u202e/u',              // RTL/LTR marks
    ];

    // Section keyword map → normalized key
    private static $sectionKeywords = [
        'work_done'      => ['work done', 'work completed', 'completed', 'done', 'task done',
                             'tasks done', 'work', 'activity', 'activities', 'today\'s work'],
        'materials_used' => ['materials used', 'materials', 'material used', 'items used',
                             'resources used', 'resources', 'items', 'tools used', 'tools'],
        'issues_faced'   => ['issues', 'issue', 'problems', 'problem', 'challenges',
                             'challenge', 'blockers', 'blocker', 'pending', 'remarks'],
    ];

    /**
     * Main entry point.
     *
     * @param  string $raw  Raw pasted text
     * @return array        { work_done, materials_used, issues_faced, raw_cleaned, is_whatsapp }
     */
    public static function parse(string $raw): array {
        $result = [
            'work_done'      => '',
            'materials_used' => '',
            'issues_faced'   => '',
            'raw_cleaned'    => '',
            'is_whatsapp'    => false,
        ];

        if (empty(trim($raw))) {
            return $result;
        }

        $result['is_whatsapp'] = self::isWhatsAppContent($raw);
        $cleaned               = self::clean($raw);
        $result['raw_cleaned'] = $cleaned;

        if ($result['is_whatsapp']) {
            $extracted = self::extractSections($cleaned);
            $result    = array_merge($result, $extracted);
        }

        return $result;
    }

    /**
     * Heuristic: does this look like a WhatsApp message?
     */
    public static function isWhatsAppContent(string $text): bool {
        // Timestamp pattern
        if (preg_match('/\d{1,2}:\d{2}\s*(AM|PM)?\s*[-–]/i', $text)) return true;
        // Forwarded label
        if (preg_match('/^(Forwarded|This message was forwarded)/im', $text)) return true;
        // Multiple emoji
        if (preg_match_all('/[\x{1F300}-\x{1FAFF}]/u', $text) >= 2) return true;
        // Section keywords present
        foreach (array_merge(...array_values(self::$sectionKeywords)) as $kw) {
            if (stripos($text, $kw . ':') !== false || stripos($text, $kw . ' :') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Strip noise from raw text.
     */
    public static function clean(string $text): string {
        foreach (self::$noisePatterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }
        // Collapse 3+ blank lines into 2
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    /**
     * Extract structured sections from cleaned text.
     * Handles both "Section:" header style and plain multi-line text.
     */
    public static function extractSections(string $text): array {
        $sections = [
            'work_done'      => '',
            'materials_used' => '',
            'issues_faced'   => '',
        ];

        // Build a flat keyword → section-key map
        $kwMap = [];
        foreach (self::$sectionKeywords as $sectionKey => $keywords) {
            foreach ($keywords as $kw) {
                $kwMap[strtolower($kw)] = $sectionKey;
            }
        }

        // Split into lines and walk through them
        $lines          = preg_split('/\r?\n/', $text);
        $currentSection = null;
        $buffer         = [];
        $sectionBuffers = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($currentSection) $buffer[] = '';
                continue;
            }

            // Check if this line is a section header: "Work Done:" or "Work Done :"
            $matched = false;
            foreach ($kwMap as $kw => $sectionKey) {
                $pattern = '/^' . preg_quote($kw, '/') . '\s*:?\s*$/i';
                if (preg_match($pattern, $trimmed)) {
                    // Save previous buffer
                    if ($currentSection !== null) {
                        $sectionBuffers[$currentSection][] = implode("\n", $buffer);
                    }
                    $currentSection = $sectionKey;
                    $buffer         = [];
                    $matched        = true;
                    break;
                }
                // "Work Done: some text on same line"
                $inlinePattern = '/^' . preg_quote($kw, '/') . '\s*:\s*(.+)$/i';
                if (preg_match($inlinePattern, $trimmed, $m)) {
                    if ($currentSection !== null) {
                        $sectionBuffers[$currentSection][] = implode("\n", $buffer);
                    }
                    $currentSection = $sectionKey;
                    $buffer         = [trim($m[1])];
                    $matched        = true;
                    break;
                }
            }

            if (!$matched) {
                $buffer[] = $trimmed;
            }
        }

        // Flush last buffer
        if ($currentSection !== null && !empty($buffer)) {
            $sectionBuffers[$currentSection][] = implode("\n", $buffer);
        }

        // If no sections were detected, treat entire text as work_done
        if (empty($sectionBuffers)) {
            $sections['work_done'] = trim($text);
            return $sections;
        }

        // Merge buffers per section
        foreach ($sectionBuffers as $key => $parts) {
            $sections[$key] = trim(implode("\n", array_filter($parts)));
        }

        return $sections;
    }

    /**
     * Validate parsed output — returns array of error strings (empty = valid).
     */
    public static function validate(array $parsed): array {
        $errors = [];
        if (empty(trim($parsed['work_done'] ?? ''))) {
            $errors[] = 'Work done section is empty or could not be extracted.';
        }
        return $errors;
    }

    /**
     * Parse a site daily report WhatsApp message.
     * Extracts: date, site, total_manpower, manpower categories, machinery, tasks.
     * Used by SiteReportController — same engine for all roles.
     *
     * @param  string $raw  Raw pasted WhatsApp text
     * @return array        Structured site report data
     */
    public static function parseSiteReport(string $raw): array {
        $cleaned = self::clean($raw);
        $text    = $cleaned;
        $lines   = preg_split('/\r?\n/', $text);

        $result = [
            'date'            => '',
            'site'            => '',
            'total_manpower'  => 0,
            'manpower_counts' => [],
            'manpower_names'  => [],
            'machinery'       => [],
            'tasks'           => [],
            'remarks'         => '',
            'raw_cleaned'     => $cleaned,
            'is_whatsapp'     => self::isWhatsAppContent($raw),
        ];

        // ── Date ────────────────────────────────────────────────────────────────
        // Use $raw (before clean()) — clean() can strip bare date lines.
        // Priority 1: numeric DD/MM/YYYY — Indian format, locale-safe. Never use strtotime().
        if (preg_match('/date\s*[:\-]?\s*(\d{1,2})\s*[\/\-\.]\s*(\d{1,2})\s*[\/\-\.]\s*(\d{2,4})/i', $raw, $m)) {
            $d  = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $y  = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            $dateObj = DateTime::createFromFormat('d/m/Y', "{$d}/{$mo}/{$y}");
            $errs    = DateTime::getLastErrors();
            // PHP 8.2+: getLastErrors() returns false when no errors (not an array)
            $hasErrors = is_array($errs) && ($errs['warning_count'] + $errs['error_count']) > 0;
            if ($dateObj && !$hasErrors) {
                $result['date'] = $dateObj->format('Y-m-d');
                error_log('WhatsAppParser parsed date: ' . $result['date']);
            }
        }
        // Priority 2: text month — "Date: 20 Mar 2026" (unambiguous)
        if (empty($result['date']) &&
            preg_match('/date\s*[:\-]?\s*(\d{1,2})\s+([A-Za-z]{3,9})\s+(\d{2,4})/i', $raw, $m)) {
            $y = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            $dateObj = DateTime::createFromFormat('d M Y', $m[1] . ' ' . $m[2] . ' ' . $y)
                    ?: DateTime::createFromFormat('d F Y', $m[1] . ' ' . $m[2] . ' ' . $y);
            $errs    = DateTime::getLastErrors();
            $hasErrors = is_array($errs) && ($errs['warning_count'] + $errs['error_count']) > 0;
            if ($dateObj && !$hasErrors) {
                $result['date'] = $dateObj->format('Y-m-d');
                error_log('WhatsAppParser parsed date: ' . $result['date']);
            }
        }
        // Fallback: today
        if (empty($result['date'])) {
            $result['date'] = date('Y-m-d');
        }

        // ── Site / Project ────────────────────────────────────────────────────
        if (preg_match('/site\s*[\/&]\s*project\s*[:\-]?\s*(.+)/i', $text, $m)) {
            $result['site'] = trim(preg_replace('/[*_]/', '', $m[1]));
        }

        // ── Total manpower ────────────────────────────────────────────────────
        if (preg_match('/total\s*manpower\s*[:\-]?\s*[\(]?\s*(\d+)/i', $text, $m)) {
            $result['total_manpower'] = (int)$m[1];
        }

        // ── Section map ───────────────────────────────────────────────────────
        $sectionMap = [
            '/today.?s?\s*task|work\s*progress|progress|task\s*done|work\s*done|activities/i' => 'tasks',
            '/total\s*manpower/i'           => '__total_mp',
            '/ac\s*[&+]\s*dc/i'            => 'ac_dc_team',
            '/local\s*labour/i'             => 'local_labour',
            '/driver|operator/i'            => 'driver_operator',
            '/engineer/i'                   => 'engineer',
            '/supervisor/i'                 => 'supervisor',
            '/mms/i'                        => 'mms_team',
            '/civil|mason|weld|housekeep/i' => 'civil_mason',
            '/machinery|machine/i'          => 'machinery',
        ];

        $machineKeys = ['tractor', 'jcb', 'hydra', 'tata_ace', 'dg', 'crane'];

        $currentSection  = null;
        $pendingMachCount = null;

        foreach ($lines as $line) {
            $clean = trim(preg_replace('/[*_📋👷👥👤🚜🧑🔧⚡🔩🧱🚗]/u', '', $line));
            if ($clean === '') continue;

            // Pending machine count (count on one line, machine name on next)
            if ($pendingMachCount !== null) {
                $mk = self::matchMachineKey($clean);
                if ($mk) {
                    $result['machinery'][$mk] = ($result['machinery'][$mk] ?? 0) + $pendingMachCount;
                    $pendingMachCount = null;
                    continue;
                }
                $pendingMachCount = null;
            }

            // Detect section heading
            $sec = null;
            foreach ($sectionMap as $rx => $key) {
                if (preg_match($rx, $clean)) { $sec = $key; break; }
            }

            if ($sec !== null) {
                if ($sec === '__total_mp') {
                    if (preg_match('/[\(:]?\s*(\d+)/', $clean, $m)) {
                        $result['total_manpower'] = (int)$m[1];
                    }
                    $currentSection = null;
                    continue;
                }
                $currentSection = $sec;
                // Inline count: "Engineers (4)", "AC & DC Team: 07"
                if ($sec !== 'tasks' && $sec !== 'machinery') {
                    if (preg_match('/[\(]\s*(\d+)\s*[\)]|[:\-]\s*(\d+)\s*$|\s(\d+)\s*$/', $clean, $m)) {
                        $cnt = (int)($m[1] ?: $m[2] ?: $m[3]);
                        if ($cnt > 0) $result['manpower_counts'][$sec] = $cnt;
                    }
                }
                continue;
            }

            // Tasks section
            if ($currentSection === 'tasks') {
                if (preg_match('/^\d+[.)\s]+(.+)/', $clean, $m)) {
                    $result['tasks'][] = trim($m[1]);
                }
                continue;
            }

            // Machinery inline: "JCB-1 | DG-2", "JCB: 1"
            if ($currentSection === 'machinery' || preg_match('/\b(dg|jcb|tractor|hydra|tata\s*ace|crane)\b/i', $clean)) {
                preg_match_all('/(tractor|jcb|hydra|tata\s*ace|dg|crane)\s*[-:\s]\s*(\d+)|(\d+)\s*[-:\s]?\s*(tractor|jcb|hydra|tata\s*ace|dg|crane)/i', $clean, $matches, PREG_SET_ORDER);
                $found = false;
                foreach ($matches as $match) {
                    $key   = strtolower(trim($match[1] ?: $match[4]));
                    $key   = str_replace(' ', '_', $key);
                    $count = (int)($match[2] ?: $match[3]);
                    if ($count > 0) {
                        $result['machinery'][$key] = ($result['machinery'][$key] ?? 0) + $count;
                        $found = true;
                    }
                }
                if ($found) continue;
            }

            // Standalone number in machinery section
            if ($currentSection === 'machinery' && preg_match('/^\d+$/', $clean)) {
                $pendingMachCount = (int)$clean;
                continue;
            }

            // Manpower names
            $nameOnlySections = ['engineer', 'supervisor', 'ac_dc_team', 'civil_mason', 'local_labour', 'driver_operator'];
            if ($currentSection && in_array($currentSection, $nameOnlySections)) {
                $names = self::extractNames($clean);
                if ($names) {
                    $result['manpower_names'][$currentSection] = array_merge(
                        $result['manpower_names'][$currentSection] ?? [],
                        $names
                    );
                }
            }
        }

        // Fallback count patterns across full text
        $countPatterns = [
            '/engineer[s]?\s*[\(\-:\s]\s*(\d+)/i'              => 'engineer',
            '/supervisor[s]?\s*[\(\-:\s]\s*(\d+)/i'             => 'supervisor',
            '/ac\s*[&+]\s*dc\s*team\s*[\(\-:\s]\s*(\d+)/i'     => 'ac_dc_team',
            '/mms\s*team\s*[\(\-:\s]\s*(\d+)/i'                => 'mms_team',
            '/civil[\s\/]*(?:mason)?\s*[\(\-:\s]\s*(\d+)/i'    => 'civil_mason',
            '/local\s*labour\s*[\(\-:\s]\s*(\d+)/i'            => 'local_labour',
            '/driver[s]?\/operator[s]?\s*[:\-\s]\s*(\d+)/i'    => 'driver_operator',
        ];
        foreach ($countPatterns as $rx => $key) {
            if (empty($result['manpower_counts'][$key])) {
                if (preg_match($rx, $text, $m)) {
                    $result['manpower_counts'][$key] = (int)$m[1];
                }
            }
        }

        // Prefer name-list length over stated count when names are present
        foreach ($result['manpower_names'] as $key => $names) {
            $result['manpower_counts'][$key] = max(
                count($names),
                $result['manpower_counts'][$key] ?? 0
            );
        }

        return $result;
    }

    private static function matchMachineKey(string $str): ?string {
        $map = ['tractor' => 'tractor', 'jcb' => 'jcb', 'hydra' => 'hydra',
                'tata ace' => 'tata_ace', 'tata_ace' => 'tata_ace', 'dg' => 'dg', 'crane' => 'crane'];
        $s = strtolower($str);
        foreach ($map as $k => $v) {
            if (strpos($s, $k) !== false) return $v;
        }
        return null;
    }

    private static function extractNames(string $clean): array {
        if (preg_match('/[·•]/', $clean)) {
            return array_filter(array_map('trim', preg_split('/[·•]/', preg_replace('/[*_\d.]/', '', $clean))));
        }
        if (substr_count($clean, ',') >= 1) {
            return array_filter(array_map('trim', explode(',', $clean)));
        }
        if (preg_match('/^\d+[.)\s]+(.+)/', $clean, $m)) {
            return [trim(preg_replace('/[*_]/', '', $m[1]))];
        }
        return [];
    }
}
