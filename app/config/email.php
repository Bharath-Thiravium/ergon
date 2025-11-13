<?php
// Email configuration for Ergon system
// Copy this file to email_local.php and update with your settings

return [
    'smtp' => [
        'host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
        'port' => $_ENV['SMTP_PORT'] ?? 587,
        'username' => $_ENV['SMTP_USERNAME'] ?? '',
        'password' => $_ENV['SMTP_PASSWORD'] ?? '',
        'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
    ],
    'from' => [
        'email' => $_ENV['FROM_EMAIL'] ?? 'noreply@ergon.local',
        'name' => $_ENV['FROM_NAME'] ?? 'Ergon System',
    ],
    'templates' => [
        'password_reset' => 'emails/password_reset.html',
        'account_locked' => 'emails/account_locked.html',
    ]
];
?>