<?php
    public function viewFollowupHistory($id) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
            $stmt->execute([$id]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contact) {
                header('Location: /ergon/contacts/followups?error=Contact not found');
                exit;
            }
            
            $followups = $this->getContactFollowups($db, $id);
            
            $this->view('contact_followups/history', [
                'contact' => $contact,
                'followups' => $followups
            ]);
        } catch (Exception $e) {
            error_log('View followup history error: ' . $e->getMessage());
            header('Location: /ergon/contacts/followups?error=Error loading follow-up');
            exit;
        }
    }
?>
