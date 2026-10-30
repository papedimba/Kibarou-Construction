<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;

/** MAIL_DRIVER=log writes to storage/logs/mail.log (dev/tests); anything else uses PHP mail() (cPanel-friendly). */
final class Mailer
{
    public function send(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        $line = fn(string $s) => trim(preg_replace('/[\r\n]+/', ' ', $s)); // header injection guard
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $subject = $line($subject);
        if (Env::get('MAIL_DRIVER', 'mail') === 'log') {
            $log = dirname(__DIR__, 2) . '/storage/logs/mail.log';
            return file_put_contents($log, date('c') . " TO:$to SUBJECT:$subject\n$body\n---\n", FILE_APPEND) !== false;
        }
        $headers = ['MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'From: ' . $line(Env::get('MAIL_FROM', 'no-reply@localhost'))];
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }
}
