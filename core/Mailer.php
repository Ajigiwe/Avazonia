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

        // Server settings
        $mail->isSMTP();
        $mail->Host       = defined('MAIL_HOST')       ? MAIL_HOST       : 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = !empty(MAIL_USERNAME); 
        $mail->Username   = defined('MAIL_USERNAME')   ? MAIL_USERNAME   : '';
        $mail->Password   = defined('MAIL_PASSWORD')   ? MAIL_PASSWORD   : '';
        $mail->SMTPSecure = defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'tls';
        $mail->Port       = defined('MAIL_PORT')       ? (int)MAIL_PORT  : 2525;
        $mail->CharSet    = 'UTF-8';

        // Optional debug (set MAIL_DEBUG=2 in .env for verbose output)
        $debugLevel = defined('MAIL_DEBUG') ? (int)MAIL_DEBUG : 0;
        $mail->SMTPDebug  = $debugLevel;
        if ($debugLevel > 0) {
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
