<?php
require_once 'app/config/database.php';

echo "<h2>🔍 COMPREHENSIVE DEPARTMENT TASK AUDIT</h2>";
echo "<pre>";

try {
    $db = Database::connect();
    
    // Define comprehensive task categories based on your business requirements
    $requiredCategories = [
        'Accounting' => [
            'Financial Reporting', 'GST Follow-up', 'Invoice Creation', 'Ledger Follow-up', 
            'Ledger Update', 'Mail Checking', 'Payment Follow-up', 'PO Creation', 
            'PO Follow-up', 'Quotation Creation', 'Bank Reconciliation', 'Expense Tracking',
            'Petty Cash Management', 'Vendor Payment', 'Customer Payment Processing'
        ],
        
        'Finance & Accounts' => [
            'Accounting', 'Audit', 'Budgeting', 'Financial Analysis', 'Invoice Processing', 
            'Tax Planning', 'Financial Reporting', 'Cash Flow Management', 'Investment Analysis',
            'Cost Analysis', 'Profit & Loss Review', 'Balance Sheet Preparation', 'GST Filing',
            'TDS Processing', 'Loan Management', 'Asset Management'
        ],
        
        'Human Resources' => [
            'Compliance', 'Employee Relations', 'Performance Review', 'Policy Development', 
            'Recruitment', 'Training', 'Payroll Processing', 'Leave Management', 
            'Employee Onboarding', 'Exit Interviews', 'Benefits Administration', 
            'Disciplinary Actions', 'Employee Engagement', 'Skill Development'
        ],
        
        'Information Technology' => [
            'Bug Fixing', 'Code Review', 'Deployment', 'Development', 'Documentation', 
            'Maintenance', 'Testing', 'System Administration', 'Database Management',
            'Security Updates', 'Backup Management', 'Network Management', 'User Support',
            'Software Installation', 'Hardware Maintenance', 'Performance Monitoring'
        ],
        
        'IT' => [
            'Bug Fixing', 'Code Review', 'Development', 'Documentation', 'Hosting', 
            'Maintenance', 'Planning', 'Testing', 'System Analysis', 'Database Design',
            'API Development', 'Frontend Development', 'Backend Development', 'DevOps',
            'Cloud Management', 'Security Implementation'
        ],
        
        'Liaison' => [
            'Client Meeting', 'Courier Services', 'Document Collection', 'Document Submission', 
            'Documentation', 'Follow-up', 'Government Office Visit', 'Portal Upload',
            'Regulatory Compliance', 'License Renewal', 'Certificate Processing',
            'Legal Documentation', 'Stakeholder Communication', 'Vendor Coordination'
        ],
        
        'Marketing' => [
            'Campaign Planning', 'Client Presentation', 'Content Creation', 'Email Marketing', 
            'Event Planning', 'Lead Generation', 'Market Research', 'Social Media Management',
            'Brand Management', 'Digital Marketing', 'SEO/SEM', 'Public Relations',
            'Customer Surveys', 'Competitor Analysis', 'Product Promotion'
        ],
        
        'Marketing & Sales' => [
            'Campaign Planning', 'Client Meeting', 'Content Creation', 'Customer Support', 
            'Lead Generation', 'Proposal Writing', 'Sales Presentation', 'Deal Negotiation',
            'Customer Onboarding', 'Account Management', 'Sales Reporting', 'CRM Management',
            'Territory Management', 'Product Demo', 'Contract Management'
        ],
        
        'Operations' => [
            'Facility Management', 'Inventory Management', 'Logistics', 'Process Improvement', 
            'Quality Control', 'Vendor Management', 'Supply Chain Management', 
            'Resource Planning', 'Workflow Optimization', 'Cost Management',
            'Performance Monitoring', 'Risk Management', 'Compliance Monitoring'
        ],
        
        'Statutory' => [
            'Attendance Collection', 'Audit Support', 'Compliance Filing', 'Document Preparation', 
            'EPF Work', 'ESI Work', 'Fees Payment', 'Mail Checking', 'Tax Filing',
            'Regulatory Reporting', 'License Management', 'Legal Compliance',
            'Statutory Audits', 'Government Liaison', 'Policy Updates'
        ],
        
        'Virtual Office' => [
            'Address Services', 'Administrative Support', 'Appointment Scheduling', 'Call Handling', 
            'Document Scanning', 'Mail Management', 'Meeting Coordination', 'Reception Services',
            'Customer Service', 'Data Entry', 'File Management', 'Communication Support',
            'Virtual Assistance', 'Remote Support', 'Digital Services'
        ]
    ];
    
    // Get current categories from database
    $stmt = $db->query("SELECT department_name, category_name FROM task_categories WHERE is_active = 1 ORDER BY department_name, category_name");
    $currentCategories = $stmt->fetchAll();
    
    $currentByDept = [];
    foreach ($currentCategories as $cat) {
        $currentByDept[$cat['department_name']][] = $cat['category_name'];
    }
    
    echo "=== DEPARTMENT TASK CATEGORY AUDIT ===\n\n";
    
    $totalMissing = 0;
    $auditResults = [];
    
    foreach ($requiredCategories as $dept => $required) {
        $current = $currentByDept[$dept] ?? [];
        $missing = array_diff($required, $current);
        $extra = array_diff($current, $required);
        
        echo strtoupper($dept) . " DEPARTMENT:\n";
        echo str_repeat("-", 60) . "\n";
        echo "Required: " . count($required) . " | Current: " . count($current) . " | Missing: " . count($missing) . "\n\n";
        
        if (!empty($missing)) {
            echo "❌ MISSING CATEGORIES (" . count($missing) . "):\n";
            foreach ($missing as $cat) {
                echo "   • $cat\n";
            }
            echo "\n";
            $totalMissing += count($missing);
        } else {
            echo "✅ All required categories present\n\n";
        }
        
        if (!empty($extra)) {
            echo "ℹ️  EXTRA CATEGORIES (not in requirements):\n";
            foreach ($extra as $cat) {
                echo "   • $cat\n";
            }
            echo "\n";
        }
        
        echo "✅ CURRENT CATEGORIES:\n";
        foreach ($current as $cat) {
            echo "   • $cat\n";
        }
        echo "\n" . str_repeat("=", 60) . "\n\n";
        
        $auditResults[$dept] = [
            'required' => count($required),
            'current' => count($current),
            'missing' => $missing,
            'extra' => $extra
        ];
    }
    
    // Generate SQL to add missing categories
    if ($totalMissing > 0) {
        echo "=== SQL TO ADD MISSING CATEGORIES ===\n\n";
        
        $insertStatements = [];
        foreach ($auditResults as $dept => $result) {
            if (!empty($result['missing'])) {
                foreach ($result['missing'] as $category) {
                    $insertStatements[] = "('$category', '$dept', 'Required task category for $dept department')";
                }
            }
        }
        
        if (!empty($insertStatements)) {
            echo "INSERT INTO task_categories (category_name, department_name, description) VALUES\n";
            echo implode(",\n", $insertStatements) . ";\n\n";
        }
    }
    
    // Summary
    echo "=== AUDIT SUMMARY ===\n";
    echo "Total Departments Audited: " . count($requiredCategories) . "\n";
    echo "Total Missing Categories: $totalMissing\n";
    
    $completeCount = 0;
    foreach ($auditResults as $dept => $result) {
        if (empty($result['missing'])) {
            $completeCount++;
        }
    }
    
    echo "Departments Complete: $completeCount/" . count($requiredCategories) . "\n";
    echo "Completion Rate: " . round(($completeCount / count($requiredCategories)) * 100) . "%\n";
    
    if ($totalMissing === 0) {
        echo "\n🎉 ALL DEPARTMENTS HAVE COMPLETE TASK CATEGORIES! 🎉\n";
    } else {
        echo "\n⚠️  $totalMissing categories need to be added across departments.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='/ergon/daily-workflow/morning-planner'>🚀 Test Morning Planner</a></p>";
?>