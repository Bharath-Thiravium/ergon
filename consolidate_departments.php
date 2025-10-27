<?php
require_once 'app/config/database.php';

echo "<h2>🔄 DEPARTMENT CONSOLIDATION</h2>";
echo "<pre>";

try {
    $db = Database::connect();
    $db->beginTransaction();
    
    echo "=== CONSOLIDATING DUPLICATE DEPARTMENTS ===\n\n";
    
    // Define consolidation mapping
    $consolidations = [
        'Finance & Accounts' => ['Accounting', 'Finance & Accounts'],
        'Information Technology' => ['IT', 'Information Technology'],
        'Marketing & Sales' => ['Marketing', 'Marketing & Sales']
    ];
    
    foreach ($consolidations as $targetDept => $sourceDepts) {
        echo "CONSOLIDATING: " . implode(' + ', $sourceDepts) . " → $targetDept\n";
        echo str_repeat("-", 60) . "\n";
        
        // Get all categories from source departments
        $allCategories = [];
        foreach ($sourceDepts as $dept) {
            $stmt = $db->prepare("SELECT category_name, description FROM task_categories WHERE department_name = ? AND is_active = 1");
            $stmt->execute([$dept]);
            $categories = $stmt->fetchAll();
            
            echo "From $dept: " . count($categories) . " categories\n";
            foreach ($categories as $cat) {
                if (!in_array($cat['category_name'], array_column($allCategories, 'category_name'))) {
                    $allCategories[] = $cat;
                }
            }
        }
        
        echo "Total unique categories: " . count($allCategories) . "\n";
        
        // Delete existing categories for target department
        $stmt = $db->prepare("DELETE FROM task_categories WHERE department_name = ?");
        $stmt->execute([$targetDept]);
        
        // Insert consolidated categories
        $stmt = $db->prepare("INSERT INTO task_categories (category_name, department_name, description, is_active) VALUES (?, ?, ?, 1)");
        foreach ($allCategories as $cat) {
            $stmt->execute([$cat['category_name'], $targetDept, $cat['description']]);
        }
        
        // Delete old department categories
        foreach ($sourceDepts as $dept) {
            if ($dept !== $targetDept) {
                $stmt = $db->prepare("DELETE FROM task_categories WHERE department_name = ?");
                $stmt->execute([$dept]);
                echo "Removed categories from: $dept\n";
            }
        }
        
        // Update department names in departments table
        foreach ($sourceDepts as $dept) {
            if ($dept !== $targetDept) {
                $stmt = $db->prepare("UPDATE departments SET status = 'inactive' WHERE name = ?");
                $stmt->execute([$dept]);
                echo "Deactivated department: $dept\n";
            }
        }
        
        // Ensure target department exists and is active
        $stmt = $db->prepare("INSERT INTO departments (name, description, status) VALUES (?, ?, 'active') ON DUPLICATE KEY UPDATE status = 'active'");
        $descriptions = [
            'Finance & Accounts' => 'Consolidated Finance, Accounting and Financial Operations',
            'Information Technology' => 'Consolidated IT Development, Infrastructure and Support',
            'Marketing & Sales' => 'Consolidated Marketing, Sales and Business Development'
        ];
        $stmt->execute([$targetDept, $descriptions[$targetDept]]);
        
        echo "✅ Consolidated into: $targetDept\n\n";
    }
    
    // Add comprehensive categories for consolidated departments
    echo "=== ADDING COMPREHENSIVE CATEGORIES ===\n\n";
    
    $comprehensiveCategories = [
        'Finance & Accounts' => [
            'Financial Reporting', 'GST Follow-up', 'Invoice Creation', 'Ledger Follow-up', 
            'Ledger Update', 'Mail Checking', 'Payment Follow-up', 'PO Creation', 
            'PO Follow-up', 'Quotation Creation', 'Bank Reconciliation', 'Expense Tracking',
            'Petty Cash Management', 'Vendor Payment', 'Customer Payment Processing',
            'Accounting', 'Audit', 'Budgeting', 'Financial Analysis', 'Invoice Processing', 
            'Tax Planning', 'Cash Flow Management', 'Investment Analysis',
            'Cost Analysis', 'Profit & Loss Review', 'Balance Sheet Preparation', 'GST Filing',
            'TDS Processing', 'Loan Management', 'Asset Management'
        ],
        
        'Information Technology' => [
            'Bug Fixing', 'Code Review', 'Development', 'Documentation', 'Hosting', 
            'Maintenance', 'Planning', 'Testing', 'System Analysis', 'Database Design',
            'API Development', 'Frontend Development', 'Backend Development', 'DevOps',
            'Cloud Management', 'Security Implementation', 'Deployment', 
            'System Administration', 'Database Management', 'Security Updates', 
            'Backup Management', 'Network Management', 'User Support',
            'Software Installation', 'Hardware Maintenance', 'Performance Monitoring'
        ],
        
        'Marketing & Sales' => [
            'Campaign Planning', 'Client Presentation', 'Content Creation', 'Email Marketing', 
            'Event Planning', 'Lead Generation', 'Market Research', 'Social Media Management',
            'Brand Management', 'Digital Marketing', 'SEO/SEM', 'Public Relations',
            'Customer Surveys', 'Competitor Analysis', 'Product Promotion',
            'Client Meeting', 'Customer Support', 'Proposal Writing', 'Sales Presentation', 
            'Deal Negotiation', 'Customer Onboarding', 'Account Management', 'Sales Reporting', 
            'CRM Management', 'Territory Management', 'Product Demo', 'Contract Management'
        ]
    ];
    
    foreach ($comprehensiveCategories as $dept => $categories) {
        echo "Adding comprehensive categories to $dept:\n";
        
        // Remove duplicates and add missing categories
        $stmt = $db->prepare("SELECT category_name FROM task_categories WHERE department_name = ?");
        $stmt->execute([$dept]);
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $missing = array_diff($categories, $existing);
        
        if (!empty($missing)) {
            $stmt = $db->prepare("INSERT IGNORE INTO task_categories (category_name, department_name, description, is_active) VALUES (?, ?, ?, 1)");
            foreach ($missing as $category) {
                $stmt->execute([$category, $dept, "Comprehensive task category for $dept department"]);
            }
            echo "Added " . count($missing) . " new categories\n";
        } else {
            echo "All categories already present\n";
        }
        echo "\n";
    }
    
    $db->commit();
    
    // Show final results
    echo "=== CONSOLIDATION RESULTS ===\n\n";
    
    $stmt = $db->query("SELECT department_name, COUNT(*) as count FROM task_categories WHERE is_active = 1 GROUP BY department_name ORDER BY department_name");
    $results = $stmt->fetchAll();
    
    foreach ($results as $result) {
        echo "✅ " . $result['department_name'] . ": " . $result['count'] . " categories\n";
    }
    
    $totalCategories = array_sum(array_column($results, 'count'));
    echo "\nTotal Categories: $totalCategories\n";
    echo "Active Departments: " . count($results) . "\n";
    
    echo "\n🎉 DEPARTMENT CONSOLIDATION COMPLETE! 🎉\n";
    echo "\nConsolidated departments:\n";
    echo "• Finance & Accounts (merged Accounting + Finance & Accounts)\n";
    echo "• Information Technology (merged IT + Information Technology)\n";
    echo "• Marketing & Sales (merged Marketing + Marketing & Sales)\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='/ergon/daily-workflow/morning-planner'>🚀 Test Consolidated Departments</a></p>";
?>