<?php
require_once __DIR__ . '/../config/database.php';

class TemplateRenderer {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function render($event) {
        $template = $this->getTemplate($event['template'] ?? null, $event['locale'] ?? 'en');
        
        if (!$template) {
            return $this->renderFallback($event);
        }
        
        $variables = $this->prepareVariables($event['payload']);
        
        return [
            'subject' => $this->replacePlaceholders($template['subject'], $variables),
            'text' => $this->replacePlaceholders($template['body_text'], $variables),
            'html' => $this->replacePlaceholders($template['body_html'], $variables),
            'link' => $this->generateLink($event)
        ];
    }
    
    private function getTemplate($templateKey, $locale = 'en') {
        if (!$templateKey) return null;
        
        $stmt = $this->db->prepare("
            SELECT subject, body_text, body_html, variables 
            FROM notification_templates 
            WHERE template_key = ? AND locale = ?
        ");
        $stmt->execute([$templateKey, $locale]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Fallback to English if locale not found
        if (!$template && $locale !== 'en') {
            $stmt->execute([$templateKey, 'en']);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $template;
    }
    
    private function renderFallback($event) {
        $message = $this->generateFallbackMessage($event);
        return [
            'subject' => ucfirst($event['action']) . ' notification',
            'text' => $message,
            'html' => '<p>' . htmlspecialchars($message) . '</p>',
            'link' => $this->generateLink($event)
        ];
    }
    
    private function generateFallbackMessage($event) {
        $payload = $event['payload'];
        
        switch ($event['module'] . '.' . $event['action']) {
            case 'leave.approval_request':
                return "{$payload['userName']} submitted a leave request for approval";
            case 'expense.approval_request':
                return "{$payload['userName']} submitted an expense claim of ₹{$payload['amount']}";
            case 'advance.approval_request':
                return "{$payload['userName']} submitted a salary advance request of ₹{$payload['amount']}";
            case 'task.assigned':
                return "You have been assigned a new task: {$payload['taskTitle']}";
            default:
                return "New {$event['action']} notification";
        }
    }
    
    private function prepareVariables($payload) {
        $variables = [];
        
        foreach ($payload as $key => $value) {
            // Sanitize and format variables
            if (is_string($value)) {
                $variables[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } elseif (is_numeric($value)) {
                $variables[$key] = number_format($value, 2);
            } else {
                $variables[$key] = $value;
            }
        }
        
        return $variables;
    }
    
    private function replacePlaceholders($template, $variables) {
        if (!$template) return '';
        
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        
        // Remove any remaining placeholders
        $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);
        
        return $template;
    }
    
    private function generateLink($event) {
        $baseUrl = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        
        switch ($event['module']) {
            case 'leave':
                return "{$protocol}://{$baseUrl}/ergon/leaves";
            case 'expense':
                return "{$protocol}://{$baseUrl}/ergon/expenses";
            case 'advance':
                return "{$protocol}://{$baseUrl}/ergon/advances";
            case 'task':
                return "{$protocol}://{$baseUrl}/ergon/tasks";
            default:
                return "{$protocol}://{$baseUrl}/ergon/notifications";
        }
    }
}
?>