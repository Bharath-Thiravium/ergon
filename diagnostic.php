<?php
/**
 * DIAGNOSTIC TOOL: LOCAL vs LIVE Comparison
 * Company Owner Expense/Advance Visibility
 * 
 * Deploy this file to LIVE server and visit it to get diagnostic output
 * File: /ergon/diagnostic.php
 * Access: http://yourserver.com/ergon/diagnostic.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/app/config/database.php';

echo "=" . str_repeat("=", 78) . "\n";
echo "DIAGNOSTIC REPORT: Company Owner Expenses/Advances Visibility\n";
echo "=" . str_repeat("=", 78) . "\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: " . (getenv('APP_ENV') ?: 'Unknown') . "\n";
echo "\n";

try {
    $db = Database::connect();
    echo "[✓] Database connection successful\n\n";
    
    // TEST 1: Users by role
    echo "-" . str_repeat("-", 77) . "\n";
    echo "TEST 1: USERS BY ROLE (Active users)\n";
    echo "-" . str_repeat("-", 77) . "\n";
    $stmt = $db->query("
        SELECT role, COUNT(*) as count 
        FROM users 
        WHERE status='active' 
        GROUP BY role 
        ORDER BY role
    ");
    $users_by_role = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users_by_role) === 0) {
        echo "[!] WARNING: No active users found!\n";
    } else {
        foreach ($users_by_role as $row) {
            $status = ($row['role'] === 'company_owner') ? '[✓]' : '[ ]';
            printf("%s %-20s : %3d users\n", $status, $row['role'], $row['count']);
        }
    }
    echo "\n";
    
    // TEST 2: Expenses by user role
    echo "-" . str_repeat("-", 77) . "\n";
    echo "TEST 2: EXPENSES BY USER ROLE\n";
    echo "-" . str_repeat("-", 77) . "\n";
    $stmt = $db->query("
        SELECT u.role, COUNT(e.id) as expense_count, SUM(e.amount) as total_amount
        FROM expenses e
        JOIN users u ON e.user_id = u.id
        GROUP BY u.role
        ORDER BY u.role
    ");
    $expenses_by_role = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($expenses_by_role) === 0) {
        echo "[!] No expenses found in database\n";
    } else {
        printf("%-20s | %10s | %15s\n", "Role", "Count", "Total Amount");
        echo str_repeat("-", 50) . "\n";
        foreach ($expenses_by_role as $row) {
            $status = ($row['role'] === 'company_owner') ? '[✓]' : '[ ]';
            printf("%s %-17s | %10d | ₹%13.2f\n", 
                $status, 
                $row['role'], 
                $row['expense_count'], 
                $row['total_amount'] ?? 0
            );
        }
        
        // Check for owner expenses
        $owner_found = false;
        foreach ($expenses_by_role as $row) {
            if ($row['role'] === 'company_owner' && $row['expense_count'] > 0) {
                $owner_found = true;
                break;
            }
        }
        
        if (!$owner_found) {
            echo "\n[✗] ISSUE FOUND: No expenses from company_owner role!\n";
        } else {
            echo "\n[✓] Company owner expenses exist in database\n";
        }
    }
    echo "\n";
    
    // TEST 3: Advances by user role
    echo "-" . str_repeat("-", 77) . "\n";
    echo "TEST 3: ADVANCES BY USER ROLE\n";
    echo "-" . str_repeat("-", 77) . "\n";
    $stmt = $db->query("
        SELECT u.role, COUNT(a.id) as advance_count, SUM(a.amount) as total_amount
        FROM advances a
        JOIN users u ON a.user_id = u.id
        GROUP BY u.role
        ORDER BY u.role
    ");
    $advances_by_role = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($advances_by_role) === 0) {
        echo "[!] No advances found in database\n";
    } else {
        printf("%-20s | %10s | %15s\n", "Role", "Count", "Total Amount");
        echo str_repeat("-", 50) . "\n";
        foreach ($advances_by_role as $row) {
            $status = ($row['role'] === 'company_owner') ? '[✓]' : '[ ]';
            printf("%s %-17s | %10d | ₹%13.2f\n", 
                $status, 
                $row['role'], 
                $row['advance_count'], 
                $row['total_amount'] ?? 0
            );
        }
        
        // Check for owner advances
        $owner_found = false;
        foreach ($advances_by_role as $row) {
            if ($row['role'] === 'company_owner' && $row['advance_count'] > 0) {
                $owner_found = true;
                break;
            }
        }
        
        if (!$owner_found) {
            echo "\n[✗] ISSUE FOUND: No advances from company_owner role!\n";
        } else {
            echo "\n[✓] Company owner advances exist in database\n";
        }
    }
    echo "\n";
    
    // TEST 4: Company owner details
    echo "-" . str_repeat("-", 77) . "\n";
    echo "TEST 4: COMPANY OWNER USER DETAILS\n";
    echo "-" . str_repeat("-", 77) . "\n";
    $stmt = $db->query("SELECT id, name, email, role, status FROM users WHERE role='company_owner' LIMIT 5");
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($owners) === 0) {
        echo "[✗] ERROR: No company_owner user found in database!\n";
        echo "    This is likely the ROOT CAUSE of the issue.\n";
    } else {
        echo "[✓] Found " . count($owners) . " company_owner user(s):\n\n";
        
        foreach ($owners as $owner) {
            echo "  ID: " . $owner['id'] . "\n";
            echo "  Name: " . $owner['name'] . "\n";
            echo "  Email: " . $owner['email'] . "\n";
            echo "  Role: " . $owner['role'] . "\n";
            echo "  Status: " . $owner['status'] . "\n";
            
            // Get expenses for this owner
            $stmt = $db->prepare("
                SELECT COUNT(*) as count, SUM(amount) as total 
                FROM expenses 
                WHERE user_id = ?
            ");
            $stmt->execute([$owner['id']]);
            $exp = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  Expenses: " . $exp['count'] . " records, ₹" . ($exp['total'] ?? 0) . "\n";
            
            // Get advances for this owner
            $stmt = $db->prepare("
                SELECT COUNT(*) as count, SUM(amount) as total 
                FROM advances 
                WHERE user_id = ?
            ");
            $stmt->execute([$owner['id']]);
            $adv = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  Advances: " . $adv['count'] . " records, ₹" . ($adv['total'] ?? 0) . "\n";
            
            if ($exp['count'] == 0 && $adv['count'] == 0) {
                echo "  [!] WARNING: This owner has no expenses or advances\n";
            }
            echo "\n";
        }
    }
    echo "\n";
    
    // TEST 5: Query filter verification
    echo "-" . str_repeat("-", 77) . "\n";
    echo "TEST 5: SQL QUERY FILTER VERIFICATION\n";
    echo "-" . str_repeat("-", 77) . "\n";
    
    // Get an admin user
    $stmt = $db->query("SELECT id FROM users WHERE role IN ('admin', 'owner') LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "[!] No admin user found, cannot test query filter\n";
    } else {
        $admin_id = $admin['id'];
        
        // Test the actual query from ExpenseController
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM expenses e
            JOIN users u ON e.user_id = u.id
            WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)
        ");
        $stmt->execute([$admin_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Admin user ID: $admin_id\n";
        echo "Current query filter result: " . $result['count'] . " expenses\n";
        echo "  Query: WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)\n";
        echo "  Bindings: [$admin_id]\n\n";
        
        // Break down the filter
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM expenses e
            JOIN users u ON e.user_id = u.id
            WHERE u.role = 'user'
        ");
        $stmt->execute();
        $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM expenses e
            JOIN users u ON e.user_id = u.id
            WHERE u.role = 'company_owner'
        ");
        $stmt->execute();
        $owner_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM expenses e
            WHERE e.user_id = ?
        ");
        $stmt->execute([$admin_id]);
        $admin_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "Filter breakdown:\n";
        printf("  - Expenses from 'user' role: %d\n", $user_count);
        printf("  - Expenses from 'company_owner' role: %d\n", $owner_count);
        printf("  - Expenses from admin (ID=$admin_id): %d\n", $admin_count);
        printf("  - Total (UNION): %d\n", $user_count + $owner_count + $admin_count);
        
        if ($owner_count == 0) {
            echo "\n[✗] ISSUE: Zero expenses from company_owner in query result!\n";
        } else {
            echo "\n[✓] Company owner expenses ARE included in admin view\n";
        }
    }
    echo "\n";
    
    // TEST 6: Role column verification
    echo "-" . str_repeat("-", 77) . "\n";
    echo "TEST 6: USERS TABLE SCHEMA - ROLE COLUMN\n";
    echo "-" . str_repeat("-", 77) . "\n";
    
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'role') {
            echo "Role Column Info:\n";
            echo "  Type: " . $col['Type'] . "\n";
            echo "  Null: " . $col['Null'] . "\n";
            echo "  Default: " . ($col['Default'] ?? 'NULL') . "\n";
            
            // Check if ENUM and extract values
            if (stripos($col['Type'], 'enum') !== false) {
                preg_match("/enum\((.*?)\)/i", $col['Type'], $matches);
                if (isset($matches[1])) {
                    $values = str_getcsv($matches[1], ",", "'");
                    echo "  Allowed values: " . implode(", ", $values) . "\n";
                    
                    if (in_array("'company_owner'", $values) || in_array("company_owner", $values)) {
                        echo "  [✓] 'company_owner' is allowed\n";
                    } else {
                        echo "  [✗] 'company_owner' is NOT in enum values!\n";
                        echo "      This could be the issue!\n";
                    }
                }
            }
            break;
        }
    }
    echo "\n";
    
    // TEST 7: Direct expense query for company_owner
    echo "-" . str_repeat("-", 77) . "\n";
    echo "TEST 7: SAMPLE COMPANY OWNER EXPENSES (Direct Query)\n";
    echo "-" . str_repeat("-", 77) . "\n";
    
    $stmt = $db->query("
        SELECT e.id, e.user_id, e.category, e.amount, e.status, e.created_at, u.name, u.role
        FROM expenses e
        JOIN users u ON e.user_id = u.id
        WHERE u.role = 'company_owner'
        ORDER BY e.created_at DESC
        LIMIT 5
    ");
    $owner_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($owner_expenses) === 0) {
        echo "[!] No company_owner expenses found\n";
    } else {
        echo "Found " . count($owner_expenses) . " company_owner expenses:\n\n";
        foreach ($owner_expenses as $exp) {
            printf("  ID: %d | User: %s (%s) | Category: %s | Amount: ₹%s | Status: %s | Date: %s\n",
                $exp['id'],
                $exp['name'],
                $exp['role'],
                $exp['category'],
                $exp['amount'],
                $exp['status'],
                $exp['created_at']
            );
        }
    }
    echo "\n";
    
    // FINAL SUMMARY
    echo "=" . str_repeat("=", 78) . "\n";
    echo "SUMMARY & RECOMMENDATIONS\n";
    echo "=" . str_repeat("=", 78) . "\n\n";
    
    $issues = [];
    
    if (count($owners) === 0) {
        $issues[] = "No company_owner user exists in database";
    }
    
    $owner_expense_found = false;
    foreach ($expenses_by_role as $row) {
        if ($row['role'] === 'company_owner' && $row['expense_count'] > 0) {
            $owner_expense_found = true;
        }
    }
    if (!$owner_expense_found) {
        $issues[] = "No expenses found for company_owner role";
    }
    
    $owner_advance_found = false;
    foreach ($advances_by_role as $row) {
        if ($row['role'] === 'company_owner' && $row['advance_count'] > 0) {
            $owner_advance_found = true;
        }
    }
    if (!$owner_advance_found) {
        $issues[] = "No advances found for company_owner role";
    }
    
    if (count($issues) === 0) {
        echo "[✓] ALL CHECKS PASSED!\n";
        echo "\nThe database has:\n";
        echo "  - Company owner user(s)\n";
        echo "  - Company owner expenses\n";
        echo "  - Company owner advances\n";
        echo "\nIf expenses/advances are not showing on admin screen:\n";
        echo "  1. Check admin user is logged in\n";
        echo "  2. Verify role is 'admin' or 'owner'\n";
        echo "  3. Check browser console for JavaScript errors\n";
        echo "  4. Clear browser cache\n";
    } else {
        echo "[✗] ISSUES FOUND (" . count($issues) . "):\n\n";
        foreach ($issues as $i => $issue) {
            echo ($i+1) . ". " . $issue . "\n";
        }
        echo "\nRECOMMENDATION: Data synchronization issue between LOCAL and LIVE\n";
        echo "  - Run database migration on LIVE\n";
        echo "  - Verify company_owner user exists\n";
        echo "  - Check data was properly migrated\n";
    }
    
    echo "\n" . "=" . str_repeat("=", 78) . "\n";
    
} catch (Exception $e) {
    echo "[✗] FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\nGenerated: " . date('Y-m-d H:i:s') . "\n";
echo "=" . str_repeat("=", 78) . "\n";

// CLEANUP: Delete this file after use
if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'yes') {
    if (unlink(__FILE__)) {
        echo "\n[✓] Diagnostic file deleted.\n";
    }
}
?>
