<?php
// core/Mailer.php
// Central mail dispatcher using PHPMailer + Mailtrap SMTP

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../lib/phpmailer/Exception.php';
require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../lib/phpmailer/SMTP.php';

class Mailer {

    /**
     * Send an HTML email.
     *
     * @param  string       $toEmail    Recipient email address
     * @param  string       $toName     Recipient display name
     * @param  string       $subject    Email subject
     * @param  string       $htmlBody   Full HTML body
     * @param  string|null  $textBody   Optional plain-text fallback
     * @return bool         true on success
     * @throws Exception    on failure (check MAIL_DEBUG in .env)
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
        $mail = new PHPMailer(true); // true = throw exceptions

        $mailerType = defined('MAIL_MAILER') ? MAIL_MAILER : 'smtp';

        if ($mailerType === 'mailersend') {
            return self::sendViaMailersend($toEmail, $toName, $subject, $htmlBody);
        }

        if ($mailerType === 'brevo') {
            return self::sendViaBrevo($toEmail, $toName, $subject, $htmlBody);
        }


        if ($mailerType === 'mail') {
            $mail->isMail(); 
        } else {

            // Server settings (SMTP)
            $mail->isSMTP();
            $mail->Host       = defined('MAIL_HOST') ? MAIL_HOST : 'localhost';
            $mail->Port       = defined('MAIL_PORT') ? (int)MAIL_PORT : 25;
            
            // Auto-disable Auth for localhost Port 25 (Local Relay)
            if ($mail->Host === 'localhost' && $mail->Port === 25) {
                $mail->SMTPAuth = false;
            } else {
                $mail->SMTPAuth = !empty(MAIL_USERNAME);
                $mail->Username = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
                $mail->Password = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
                $mail->SMTPSecure = defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : '';
            }
        }

        $mail->CharSet = 'UTF-8';

        // Optional debug
        $debugLevel = defined('MAIL_DEBUG') ? (int)MAIL_DEBUG : 0;
        $mail->SMTPDebug  = ($mailerType === 'smtp') ? $debugLevel : 0;
        if ($debugLevel > 0 && $mailerType === 'smtp') {
            $mail->Debugoutput = 'error_log';
        }

        // Sender
        $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : SITE_EMAIL;
        $fromName  = defined('MAIL_FROM_NAME')  ? MAIL_FROM_NAME  : APP_NAME;
        $mail->setFrom($fromEmail, $fromName);
        $mail->addReplyTo($fromEmail, $fromName);



        // Recipient
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</tr>'], "\n", $htmlBody));

        return $mail->send();
    }

    /**
     * Send email via MailerSend's Transactional API (HTTP/HTTPS)
     * This bypasses the SMTP firewall block.
     */
    private static function sendViaMailersend(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
        $apiKey = defined('MAILERSEND_API_KEY') ? MAILERSEND_API_KEY : '';
        if (empty($apiKey)) {
            error_log('[Mailer] MailerSend API Key is missing.');
            return false;
        }

        $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : SITE_EMAIL;
        $fromName  = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : APP_NAME;

        $payload = [
            'from' => ['email' => $fromEmail, 'name' => $fromName],
            'to'   => [['email' => $toEmail, 'name' => $toName]],
            'subject' => $subject,
            'html' => $htmlBody
        ];

        $ch = curl_init('https://api.mailersend.com/v1/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log('[Mailer] MailerSend API Error (Code ' . $httpCode . '): ' . $response);
            return false;
        }
    }


    /**
     * Send email via Brevo's Transactional API (HTTP/HTTPS)
     * This bypasses the SMTP firewall block.
     */
    private static function sendViaBrevo(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
        $apiKey = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
        if (empty($apiKey)) {
            error_log('[Mailer] Brevo API Key is missing.');
            return false;
        }

        $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : SITE_EMAIL;
        $fromName  = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : APP_NAME;

        $payload = [
            'sender' => ['name' => $fromName, 'email' => $fromEmail],
            'to'     => [['email' => $toEmail, 'name' => $toName]],
            'subject' => $subject,
            'htmlContent' => $htmlBody
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log('[Mailer] Brevo API Error: ' . $response);
            return false;
        }
    }


    /**
     * Render an email template and return the HTML string.
     *
     * @param  string  $template  Template name (without .php), relative to /emails/
     * @param  array   $data      Variables to extract into the template
     * @return string
     */
    public static function render(string $template, array $data = []): string {
        $file = __DIR__ . '/../emails/' . $template . '.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("Email template not found: {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Convenience: render then send.
     */
    public static function sendTemplate(string $toEmail, string $toName, string $subject, string $template, array $data = []): bool {
        $html = self::render($template, $data);
        return self::send($toEmail, $toName, $subject, $html);
    }
}
