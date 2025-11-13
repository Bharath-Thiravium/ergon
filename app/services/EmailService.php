<?php
class EmailService {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? 587;
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'noreply@ergon.local';
        $this->fromName = $_ENV['FROM_NAME'] ?? 'Ergon System';
    }
    
    public function sendPasswordResetEmail($email, $name, $resetToken) {
        $resetUrl = $this->getBaseUrl() . "/ergon/auth/reset-password?token=" . $resetToken;
        
        $subject = "Password Reset Request - Ergon";
        $message = $this->getPasswordResetTemplate($name, $resetUrl);
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    public function sendAccountLockedEmail($email, $name, $unlockTime) {
        $subject = "Account Security Alert - Ergon";
        $message = $this->getAccountLockedTemplate($name, $unlockTime);
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    private function sendEmail($to, $subject, $message) {
        $headers = [
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $success = mail($to, $subject, $message, implode("\r\n", $headers));
        
        if (!$success) {
            error_log("Failed to send email to: $to, Subject: $subject");
        }
        
        return $success;
    }
    
    private function getPasswordResetTemplate($name, $resetUrl) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>Password Reset Request</h2>
                <p>Hello {$name},</p>
                <p>You requested a password reset for your Ergon account. Click the button below to reset your password:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetUrl}' style='background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                </div>
                <p>This link will expire in 1 hour for security reasons.</p>
                <p>If you didn't request this reset, please ignore this email.</p>
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #666;'>This is an automated message from Ergon System.</p>
            </div>
        </body>
        </html>";
    }
    
    private function getAccountLockedTemplate($name, $unlockTime) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Account Security Alert</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #ff4444;'>Account Security Alert</h2>
                <p>Hello {$name},</p>
                <p>Your account has been temporarily locked due to multiple failed login attempts.</p>
                <p><strong>Account will be unlocked at:</strong> {$unlockTime}</p>
                <p>If this wasn't you, please contact your system administrator immediately.</p>
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #666;'>This is an automated security message from Ergon System.</p>
            </div>
        </body>
        </html>";
    }
    
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
?>