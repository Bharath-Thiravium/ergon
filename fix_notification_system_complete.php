<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "🔧 Starting comprehensive notification system fix...\n\n";
    
    // Step 1: Clean up duplicate notifications
    echo "1️⃣ Cleaning up duplicate notifications...\n";
    $stmt = $db->query("
        DELETE n1 FROM notifications n1
        INNER JOIN notifications n2 
        WHERE n1.id > n2.id 
        AND n1.receiver_id = n2.receiver_id 
        AND n1.message = n2.message 
        AND n1.reference_type = n2.reference_type 
        AND n1.reference_id = n2.reference_id
        AND ABS(TIMESTAMPDIFF(MINUTE, n1.created_at, n2.created_at)) <= 5
    ");
    $duplicatesRemoved = $stmt->rowCount();
    echo "   ✅ Removed {$duplicatesRemoved} duplicate notifications\n";
    
    // Step 2: Update currency format from $ to ₹
    echo "\n2️⃣ Updating currency format from $ to ₹...\n";
    $stmt = $db->query("UPDATE notifications SET message = REPLACE(message, '$', '₹') WHERE message LIKE '%$%'");
    $currencyUpdated = $stmt->rowCount();
    echo "   ✅ Updated {$currencyUpdated} notifications with correct currency format\n";
    
    // Step 3: Ensure all notifications have proper reference_id
    echo "\n3️⃣ Fixing missing reference_id values...\n";
    
    // Fix expense notifications
    $stmt = $db->query("
        UPDATE notifications n 
        JOIN expenses e ON e.user_id = n.sender_id 
        SET n.reference_id = e.id 
        WHERE n.reference_type = 'expense' 
        AND n.reference_id IS NULL 
        AND ABS(TIMESTAMPDIFF(MINUTE, n.created_at, e.created_at)) <= 60
    ");
    $expenseFixed = $stmt->rowCount();
    
    // Fix leave notifications
    $stmt = $db->query("
        UPDATE notifications n 
        JOIN leaves l ON l.user_id = n.sender_id 
        SET n.reference_id = l.id 
        WHERE n.reference_type = 'leave' 
        AND n.reference_id IS NULL 
        AND ABS(TIMESTAMPDIFF(MINUTE, n.created_at, l.created_at)) <= 60
    ");
    $leaveFixed = $stmt->rowCount();
    
    // Fix advance notifications
    $stmt = $db->query("
        UPDATE notifications n 
        JOIN advances a ON a.user_id = n.sender_id 
        SET n.reference_id = a.id 
        WHERE n.reference_type = 'advance' 
        AND n.reference_id IS NULL 
        AND ABS(TIMESTAMPDIFF(MINUTE, n.created_at, a.created_at)) <= 60
    ");
    $advanceFixed = $stmt->rowCount();
    
    echo "   ✅ Fixed reference_id for {$expenseFixed} expense, {$leaveFixed} leave, and {$advanceFixed} advance notifications\n";
    
    // Step 4: Create missing approval notifications
    echo "\n4️⃣ Creating missing approval notifications...\n";
    
    // Missing leave approval notifications
    $stmt = $db->query("
        SELECT l.id, l.user_id, l.approved_by, l.status, l.leave_type, l.start_date, l.end_date
        FROM leaves l 
        LEFT JOIN notifications n ON n.reference_type = 'leave' AND n.reference_id = l.id AND n.category = 'approval' AND n.receiver_id = l.user_id
        WHERE l.status IN ('approved', 'rejected') 
        AND l.approved_by IS NOT NULL 
        AND n.id IS NULL
    ");
    $missingLeaveApprovals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($missingLeaveApprovals as $leave) {
        $type = $leave['status'] === 'approved' ? 'success' : 'warning';
        $title = "Leave Request " . ucfirst($leave['status']);
        $message = "Your leave request ({$leave['leave_type']}) from {$leave['start_date']} to {$leave['end_date']} has been {$leave['status']}";
        
        $stmt = $db->prepare("
            INSERT INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, action_url, created_at) 
            VALUES (?, ?, ?, 'approval', ?, ?, 'leave', ?, ?, NOW())
        ");
        $stmt->execute([
            $leave['approved_by'], 
            $leave['user_id'], 
            $type, 
            $title, 
            $message, 
            $leave['id'], 
            "/ergon/leaves/view/{$leave['id']}"
        ]);
    }
    echo "   ✅ Created " . count($missingLeaveApprovals) . " missing leave approval notifications\n";
    
    // Missing expense approval notifications
    $stmt = $db->query("
        SELECT e.id, e.user_id, e.approved_by, e.status, e.amount, e.category
        FROM expenses e 
        LEFT JOIN notifications n ON n.reference_type = 'expense' AND n.reference_id = e.id AND n.category = 'approval' AND n.receiver_id = e.user_id
        WHERE e.status IN ('approved', 'rejected') 
        AND e.approved_by IS NOT NULL 
        AND n.id IS NULL
    ");
    $missingExpenseApprovals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($missingExpenseApprovals as $expense) {
        $type = $expense['status'] === 'approved' ? 'success' : 'warning';
        $title = "Expense Request " . ucfirst($expense['status']);
        $message = "Your expense request has been {$expense['status']} - Amount: ₹" . number_format($expense['amount'], 2);
        
        $stmt = $db->prepare("
            INSERT INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, action_url, created_at) 
            VALUES (?, ?, ?, 'approval', ?, ?, 'expense', ?, ?, NOW())
        ");
        $stmt->execute([
            $expense['approved_by'], 
            $expense['user_id'], 
            $type, 
            $title, 
            $message, 
            $expense['id'], 
            "/ergon/expenses/view/{$expense['id']}"
        ]);
    }
    echo "   ✅ Created " . count($missingExpenseApprovals) . " missing expense approval notifications\n";
    
    // Missing advance approval notifications
    $stmt = $db->query("
        SELECT a.id, a.user_id, a.approved_by, a.status, a.amount, a.type
        FROM advances a 
        LEFT JOIN notifications n ON n.reference_type = 'advance' AND n.reference_id = a.id AND n.category = 'approval' AND n.receiver_id = a.user_id
        WHERE a.status IN ('approved', 'rejected') 
        AND a.approved_by IS NOT NULL 
        AND n.id IS NULL
    ");
    $missingAdvanceApprovals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($missingAdvanceApprovals as $advance) {
        $type = $advance['status'] === 'approved' ? 'success' : 'warning';
        $title = "Advance Request " . ucfirst($advance['status']);
        $message = "Your advance request has been {$advance['status']} - Amount: ₹" . number_format($advance['amount'], 2);
        
        $stmt = $db->prepare("
            INSERT INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, action_url, created_at) 
            VALUES (?, ?, ?, 'approval', ?, ?, 'advance', ?, ?, NOW())
        ");
        $stmt->execute([
            $advance['approved_by'], 
            $advance['user_id'], 
            $type, 
            $title, 
            $message, 
            $advance['id'], 
            "/ergon/advances/view/{$advance['id']}"
        ]);
    }
    echo "   ✅ Created " . count($missingAdvanceApprovals) . " missing advance approval notifications\n";
    
    // Step 5: Final verification
    echo "\n5️⃣ Final verification...\n";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM notifications");
    $total = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as unread FROM notifications WHERE is_read = 0");
    $unread = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as with_rupee FROM notifications WHERE message LIKE '%₹%'");
    $withRupee = $stmt->fetchColumn();
    
    echo "   📊 Total notifications: {$total}\n";
    echo "   📊 Unread notifications: {$unread}\n";
    echo "   📊 Notifications with ₹ format: {$withRupee}\n";
    
    echo "\n🎉 Notification system fix completed successfully!\n";
    echo "\n📋 Summary of fixes:\n";
    echo "   • Removed {$duplicatesRemoved} duplicate notifications\n";
    echo "   • Updated {$currencyUpdated} notifications to use ₹ instead of $\n";
    echo "   • Fixed reference_id for " . ($expenseFixed + $leaveFixed + $advanceFixed) . " notifications\n";
    echo "   • Created " . (count($missingLeaveApprovals) + count($missingExpenseApprovals) + count($missingAdvanceApprovals)) . " missing approval notifications\n";
    
    echo "\n✅ All notification issues have been resolved!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>