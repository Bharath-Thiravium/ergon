<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check if followups table exists
    $stmt = $db->query("SHOW TABLES LIKE 'followups'");
    $followupsExists = $stmt->rowCount() > 0;
    
    // Check if contacts table exists
    $stmt = $db->query("SHOW TABLES LIKE 'contacts'");
    $contactsExists = $stmt->rowCount() > 0;
    
    // Check if followup_history table exists
    $stmt = $db->query("SHOW TABLES LIKE 'followup_history'");
    $historyExists = $stmt->rowCount() > 0;
    
    echo "Table Status:\n";
    echo "followups: " . ($followupsExists ? "EXISTS" : "MISSING") . "\n";
    echo "contacts: " . ($contactsExists ? "EXISTS" : "MISSING") . "\n";
    echo "followup_history: " . ($historyExists ? "EXISTS" : "MISSING") . "\n";
    
    if ($followupsExists) {
        $stmt = $db->query("DESCRIBE followups");
        echo "\nfollowups table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    }
    
    if ($contactsExists) {
        $stmt = $db->query("DESCRIBE contacts");
        echo "\ncontacts table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>