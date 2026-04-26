<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function mmu_send_student_credentials_email(string $toEmail, string $studentName, string $hostelName, string $roomNumber, string $regNo, string $plainPassword): array
{
    $cfg = require __DIR__ . '/../config/mail.php';
    if (empty($cfg['enabled'])) {
        return ['success' => false, 'error' => 'SMTP mailing is disabled in config/mail.php'];
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = (string)$cfg['host'];
        $mail->SMTPAuth = true;
        $mail->Username = (string)$cfg['username'];
        $mail->Password = (string)$cfg['password'];
        $mail->Port = (int)$cfg['port'];

        $enc = strtolower((string)($cfg['encryption'] ?? 'tls'));
        if ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom((string)$cfg['from_email'], (string)$cfg['from_name']);
        $mail->addAddress($toEmail, $studentName ?: 'Student');
        $mail->isHTML(true);
        $mail->Subject = 'MMU Hostel Booking Approved - Login Credentials';

        $safeName = htmlspecialchars($studentName ?: 'Student', ENT_QUOTES, 'UTF-8');
        $safeHostel = htmlspecialchars($hostelName ?: '', ENT_QUOTES, 'UTF-8');
        $safeRoom = htmlspecialchars($roomNumber ?: '', ENT_QUOTES, 'UTF-8');
        $safeReg = htmlspecialchars($regNo ?: '', ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $safePwd = htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8');

        $mail->Body = "
            <p>Hello {$safeName},</p>
            <p>Your hostel booking has been approved.</p>
            <p>
              <b>Hostel:</b> {$safeHostel}<br/>
              <b>Room:</b> {$safeRoom}<br/>
              <b>Reg No:</b> {$safeReg}
            </p>
            <p>Use the same <b>Admin Login</b> form to access your student dashboard:</p>
            <p>
              <b>Email:</b> {$safeEmail}<br/>
              <b>Password:</b> {$safePwd}
            </p>
            <p>Please change this password after first login.</p>
            <p>MMU Hostel System</p>
        ";
        $mail->AltBody =
            "Hello {$studentName},\n\n" .
            "Your hostel booking has been approved.\n" .
            "Hostel: {$hostelName}\nRoom: {$roomNumber}\nReg No: {$regNo}\n\n" .
            "Use the same Admin Login form to access your student dashboard.\n" .
            "Email: {$toEmail}\nPassword: {$plainPassword}\n\n" .
            "Please change this password after first login.\n\n" .
            "MMU Hostel System";

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}

function mmu_send_manager_credentials_email(string $toEmail, string $managerName, string $roleName, string $plainPassword): array
{
    $cfg = require __DIR__ . '/../config/mail.php';
    if (empty($cfg['enabled'])) {
        return ['success' => false, 'error' => 'SMTP mailing is disabled in config/mail.php'];
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = (string)$cfg['host'];
        $mail->SMTPAuth = true;
        $mail->Username = (string)$cfg['username'];
        $mail->Password = (string)$cfg['password'];
        $mail->Port = (int)$cfg['port'];

        $enc = strtolower((string)($cfg['encryption'] ?? 'tls'));
        if ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom((string)$cfg['from_email'], (string)$cfg['from_name']);
        $mail->addAddress($toEmail, $managerName ?: 'Manager');
        $mail->isHTML(true);
        $mail->Subject = 'MMU Hostel System - Your Account Credentials';

        $safeName = htmlspecialchars($managerName ?: 'Manager', ENT_QUOTES, 'UTF-8');
        $safeRole = htmlspecialchars($roleName ?: 'User', ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $safePwd = htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8');

        $mail->Body = "
            <p>Hello {$safeName},</p>
            <p>An account has been created for you on the MMU Hostel System.</p>
            <p><b>Role:</b> {$safeRole}</p>
            <p>You can access your dashboard using the following credentials:</p>
            <p>
              <b>Email:</b> {$safeEmail}<br/>
              <b>Password:</b> {$safePwd}
            </p>
            <p>Please log in and change this password immediately for security reasons.</p>
            <p>Regards,<br/>MMU Hostel System</p>
        ";
        $mail->AltBody =
            "Hello {$managerName},\n\n" .
            "An account has been created for you on the MMU Hostel System.\n" .
            "Role: {$roleName}\n\n" .
            "You can access your dashboard using the following credentials:\n" .
            "Email: {$toEmail}\nPassword: {$plainPassword}\n\n" .
            "Please log in and change this password immediately for security reasons.\n\n" .
            "Regards,\nMMU Hostel System";

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}

function mmu_send_support_ticket_email(string $fromEmail, string $fromName, string $subject, string $message, string $supportEmail = 'devSupport@mmu.ac.ug'): array
{
    $cfg = require __DIR__ . '/../config/mail.php';
    if (empty($cfg['enabled'])) {
        return ['success' => false, 'error' => 'SMTP mailing is disabled in config/mail.php'];
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = (string)$cfg['host'];
        $mail->SMTPAuth = true;
        $mail->Username = (string)$cfg['username'];
        $mail->Password = (string)$cfg['password'];
        $mail->Port = (int)$cfg['port'];

        $enc = strtolower((string)($cfg['encryption'] ?? 'tls'));
        if ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom((string)$cfg['from_email'], (string)$cfg['from_name']);
        $mail->addAddress($supportEmail, 'MMU Tech Team');
        $mail->addReplyTo($fromEmail, $fromName);
        
        $mail->isHTML(true);
        $mail->Subject = "[MMU Support Ticket] {$subject}";

        $safeName = htmlspecialchars($fromName, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($fromEmail, ENT_QUOTES, 'UTF-8');
        $safeSub = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $safeMsg = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        $mail->Body = "
            <h3>New Support Ticket</h3>
            <p><b>From:</b> {$safeName} ({$safeEmail})</p>
            <p><b>Subject:</b> {$safeSub}</p>
            <hr/>
            <p>{$safeMsg}</p>
            <hr/>
            <p><small>Sent via MMU Hostel System Help Center</small></p>
        ";
        $mail->AltBody =
            "New Support Ticket\n\n" .
            "From: {$fromName} ({$fromEmail})\n" .
            "Subject: {$subject}\n\n" .
            "Message:\n{$message}";

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}
