<?php
/**
 * SMTP mail configuration for PHPMailer.
 * Update these values for your real SMTP provider.
 */
return [
    'enabled' => true,
    'host' => getenv('MMU_SMTP_HOST') ?: 'smtp.gmail.com',
    'port' => (int)(getenv('MMU_SMTP_PORT') ?: 587),
    'username' => getenv('MMU_SMTP_USER') ?: 'atwongerevianney@gmail.com',
    'password' => getenv('MMU_SMTP_PASS') ?: 'cfgu lixe nptw ijgs',
    'encryption' => getenv('MMU_SMTP_ENCRYPTION') ?: 'tls', // tls | ssl
    'from_email' => getenv('MMU_FROM_EMAIL') ?: 'no-reply@mmu.local',
    'from_name' => getenv('MMU_FROM_NAME') ?: 'MMU Hostel System',
];

