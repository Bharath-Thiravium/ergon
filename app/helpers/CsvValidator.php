<?php
/**
 * CsvValidator — validates bulk upload CSV files for advances and expenses.
 *
 * Usage:
 *   $v = new CsvValidator($db);
 *   $result = $v->validate($tmpFilePath, 'advance');  // or 'expense'
 *   // $result['valid']   bool
 *   // $result['rows']    array of per-row results
 *   // $result['summary'] ['total','ok','errors','warnings']
 */
class CsvValidator {

    private PDO $db;
    private array $userMap    = [];   // lower(name) → id
    private array $projectMap = [];   // lower(name) → id

    private const ADVANCE_REQUIRED = ['employee_name', 'advance_type', 'amount', 'reason', 'advance_date'];
    private const EXPENSE_REQUIRED = ['employee_name', 'category', 'amount', 'description', 'expense_date'];

    private const ADVANCE_TYPES = [
        'salary advance', 'travel advance', 'emergency advance',
        'project advance', 'general advance'
    ];
    private const EXPENSE_CATEGORIES = [
        'travel', 'food', 'accommodation', 'office_supplies', 'utilities', 'training_development', 'medical_expenses', 'material_costs', 'equipment_machinery', 'contractor_subcontractor', 'transportation_logistics', 'work_advance', 'maintenance_repairs', 'insurance', 'legal_professional', 'marketing_advertising', 'others', 'salary',
        // Enhanced categories
        'software_licenses', 'fuel_vehicle', 'communication', 'entertainment_client', 'books_publications', 'conference_events', 'parking_tolls', 'subscriptions_memberships', 'bank_charges', 'postage_shipping', 'security_services', 'cleaning_janitorial', 'rent_lease', 'taxes_fees', 'research_development', 'gifts_awards', 'charitable_donations', 'website_domain', 'photography_videography', 'translation_services', 'recruitment_hiring', 'quality_assurance', 'waste_disposal'
    ];

    private const MAX_AMOUNT   = 10_000_000;
    private const MAX_ROWS     = 500;
    private const MAX_TEXT_LEN = 1000;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->loadMaps();
    }

    private function loadMaps(): void {
        foreach ($this->db->query("SELECT id, name FROM users WHERE status='active'")->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $this->userMap[strtolower(trim($u['name']))] = (int)$u['id'];
        }
        foreach ($this->db->query("SELECT id, name FROM projects WHERE status='active'")->fetchAll(PDO::FETCH_ASSOC) as $p) {
            $this->projectMap[strtolower(trim($p['name']))] = (int)$p['id'];
        }
    }

    // ── Public entry point ────────────────────────────────────────────────

    public function validate(string $filePath, string $type): array {
        if (!in_array($type, ['advance', 'expense'], true)) {
            return $this->fatal('Invalid entry type.');
        }

        $handle = @fopen($filePath, 'r');
        if (!$handle) {
            return $this->fatal('Cannot open uploaded file.');
        }

        // Read header row
        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            fclose($handle);
            return $this->fatal('File is empty or unreadable.');
        }

        $headers = array_map(fn($h) => strtolower(trim($h)), $rawHeaders);

        // Strip BOM if present
        $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");

        // Check required columns
        $required = $type === 'advance' ? self::ADVANCE_REQUIRED : self::EXPENSE_REQUIRED;
        $missing  = array_diff($required, $headers);
        if ($missing) {
            fclose($handle);
            return $this->fatal('Missing required columns: ' . implode(', ', $missing));
        }

        $rows    = [];
        $rowNum  = 1;
        $okCount = 0;
        $errCount = 0;
        $warnCount = 0;

        while (($raw = fgetcsv($handle)) !== false) {
            $rowNum++;

            // Skip comment lines (lines starting with #)
            if (isset($raw[0]) && str_starts_with(trim($raw[0]), '#')) continue;

            // Skip blank rows
            if (count(array_filter(array_map('trim', $raw))) === 0) continue;

            if ($rowNum > self::MAX_ROWS + 1) {
                $rows[] = $this->rowResult($rowNum, 'error', [], ['File exceeds ' . self::MAX_ROWS . ' data rows. Remaining rows ignored.']);
                $errCount++;
                break;
            }

            $data   = array_combine($headers, array_pad($raw, count($headers), ''));
            $errors = [];
            $warnings = [];

            if ($type === 'advance') {
                $this->validateAdvanceRow($data, $errors, $warnings);
            } else {
                $this->validateExpenseRow($data, $errors, $warnings);
            }

            $status = count($errors) > 0 ? 'error' : (count($warnings) > 0 ? 'warning' : 'ok');
            if ($status === 'ok')      $okCount++;
            elseif ($status === 'error')   $errCount++;
            else                           $warnCount++;

            $rows[] = $this->rowResult($rowNum, $status, $data, $errors, $warnings);
        }

        fclose($handle);

        $total = $okCount + $errCount + $warnCount;
        return [
            'valid'   => $errCount === 0 && $total > 0,
            'summary' => [
                'total'    => $total,
                'ok'       => $okCount,
                'warnings' => $warnCount,
                'errors'   => $errCount,
            ],
            'rows' => $rows,
        ];
    }

    // ── Row validators ────────────────────────────────────────────────────

    private function validateAdvanceRow(array $data, array &$errors, array &$warnings): void {
        // employee_name
        $emp = strtolower(trim($data['employee_name'] ?? ''));
        if ($emp === '') {
            $errors[] = 'employee_name is required';
        } elseif (!isset($this->userMap[$emp])) {
            $errors[] = "Employee \"{$data['employee_name']}\" not found in system";
        }

        // advance_type
        $advType = strtolower(trim($data['advance_type'] ?? ''));
        if ($advType === '') {
            $errors[] = 'advance_type is required';
        } elseif (!in_array($advType, self::ADVANCE_TYPES, true)) {
            $errors[] = "Invalid advance_type \"{$data['advance_type']}\". Allowed: " . implode(', ', self::ADVANCE_TYPES);
        }

        // amount
        $this->validateAmount($data['amount'] ?? '', $errors);

        // reason
        $reason = trim($data['reason'] ?? '');
        if ($reason === '') {
            $errors[] = 'reason is required';
        } elseif (strlen($reason) > self::MAX_TEXT_LEN) {
            $errors[] = 'reason exceeds ' . self::MAX_TEXT_LEN . ' characters';
        }

        // advance_date
        $this->validateDate($data['advance_date'] ?? '', 'advance_date', true, $errors, $warnings);

        // repayment_date (optional)
        $repDate = trim($data['repayment_date'] ?? '');
        if ($repDate !== '') {
            $this->validateDate($repDate, 'repayment_date', false, $errors, $warnings);
            // repayment must be after advance_date
            $advDate = trim($data['advance_date'] ?? '');
            $tsRep = $this->parseDMY($repDate);
            $tsAdv = $this->parseDMY($advDate);
            if ($tsRep !== false && $tsAdv !== false && $tsRep <= $tsAdv) {
                $warnings[] = 'repayment_date should be after advance_date';
            }
        }

        // project_name (optional)
        $this->validateProject($data['project_name'] ?? '', $warnings);
    }

    private function validateExpenseRow(array $data, array &$errors, array &$warnings): void {
        // employee_name
        $emp = strtolower(trim($data['employee_name'] ?? ''));
        if ($emp === '') {
            $errors[] = 'employee_name is required';
        } elseif (!isset($this->userMap[$emp])) {
            $errors[] = "Employee \"{$data['employee_name']}\" not found in system";
        }

        // category
        $cat = strtolower(trim($data['category'] ?? ''));
        if ($cat === '') {
            $errors[] = 'category is required';
        } elseif (!in_array($cat, self::EXPENSE_CATEGORIES, true)) {
            $errors[] = "Invalid category \"{$data['category']}\". Allowed: " . implode(', ', self::EXPENSE_CATEGORIES);
        }

        // amount
        $this->validateAmount($data['amount'] ?? '', $errors);

        // description
        $desc = trim($data['description'] ?? '');
        if ($desc === '') {
            $errors[] = 'description is required';
        } elseif (strlen($desc) > self::MAX_TEXT_LEN) {
            $errors[] = 'description exceeds ' . self::MAX_TEXT_LEN . ' characters';
        }

        // expense_date
        $this->validateDate($data['expense_date'] ?? '', 'expense_date', true, $errors, $warnings);

        // project_name (optional)
        $this->validateProject($data['project_name'] ?? '', $warnings);
    }

    // ── Field-level validators ────────────────────────────────────────────

    private function validateAmount(string $raw, array &$errors): void {
        $raw = trim($raw);
        if ($raw === '') {
            $errors[] = 'amount is required';
            return;
        }
        if (!is_numeric($raw)) {
            $errors[] = "amount \"{$raw}\" is not a valid number";
            return;
        }
        $val = (float)$raw;
        if ($val <= 0) {
            $errors[] = 'amount must be greater than 0';
        } elseif ($val > self::MAX_AMOUNT) {
            $errors[] = 'amount exceeds maximum allowed value of ₹' . number_format(self::MAX_AMOUNT);
        }
    }

    /**
     * Parse a DD-MM-YYYY string to a Unix timestamp, or return false.
     */
    private function parseDMY(string $raw): int|false {
        if (!preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw, $m)) return false;
        [$_, $d, $mo, $y] = $m;
        if (!checkdate((int)$mo, (int)$d, (int)$y)) return false;
        return mktime(0, 0, 0, (int)$mo, (int)$d, (int)$y);
    }

    private function validateDate(string $raw, string $field, bool $required, array &$errors, array &$warnings): void {
        $raw = trim($raw);
        if ($raw === '') {
            if ($required) $errors[] = "{$field} is required";
            return;
        }
        $ts = $this->parseDMY($raw);
        if ($ts === false) {
            $errors[] = "{$field} \"{$raw}\" must be a valid date in DD-MM-YYYY format";
            return;
        }
        // Warn if date is more than 2 years in the past or future
        if (abs(time() - $ts) > 2 * 365 * 86400) {
            $warnings[] = "{$field} \"{$raw}\" is more than 2 years from today — please verify";
        }
    }

    private function validateProject(string $raw, array &$warnings): void {
        $raw = trim($raw);
        if ($raw === '') return;
        if (!isset($this->projectMap[strtolower($raw)])) {
            $warnings[] = "Project \"{$raw}\" not found — row will be saved without a project";
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function rowResult(int $rowNum, string $status, array $data, array $errors, array $warnings = []): array {
        return [
            'row'      => $rowNum,
            'status'   => $status,           // 'ok' | 'warning' | 'error'
            'employee' => $data['employee_name'] ?? '',
            'amount'   => $data['amount'] ?? '',
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    private function fatal(string $message): array {
        return [
            'valid'   => false,
            'fatal'   => $message,
            'summary' => ['total' => 0, 'ok' => 0, 'warnings' => 0, 'errors' => 0],
            'rows'    => [],
        ];
    }

    // ── Utility: resolve employee/project IDs (used by controller) ────────

    public function getUserId(string $name): ?int {
        return $this->userMap[strtolower(trim($name))] ?? null;
    }

    public function getProjectId(string $name): ?int {
        $name = trim($name);
        return $name !== '' ? ($this->projectMap[strtolower($name)] ?? null) : null;
    }
}
