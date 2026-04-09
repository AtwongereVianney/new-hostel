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

