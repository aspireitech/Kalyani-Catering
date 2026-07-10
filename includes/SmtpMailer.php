<?php
/**
 * Minimal dependency-free SMTP client (STARTTLS/SSL + AUTH LOGIN) built on
 * fsockopen. Avoids requiring Composer/PHPMailer on shared hosting.
 * Falls back to PHP's mail() if SMTP_HOST is left blank.
 */
class SmtpMailer
{
    private static function readResponse($socket): string
    {
        $data = '';
        while ($line = fgets($socket, 515)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    }

    private static function command($socket, string $cmd, string $expectedCode): string
    {
        fwrite($socket, $cmd . "\r\n");
        $response = self::readResponse($socket);
        if (substr($response, 0, 3) !== $expectedCode) {
            throw new RuntimeException("SMTP error on \"$cmd\": $response");
        }
        return $response;
    }

    /**
     * @param string[] $to
     */
    public static function send(array $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
    {
        if (empty(SMTP_HOST)) {
            return self::sendViaMailFunction($to, $subject, $htmlBody, $replyTo);
        }

        try {
            $useSsl = SMTP_ENCRYPTION === 'ssl';
            $transport = $useSsl ? 'ssl://' . SMTP_HOST : SMTP_HOST;
            $socket = @fsockopen($transport, SMTP_PORT, $errno, $errstr, 15);
            if (!$socket) {
                error_log("SMTP connect failed: $errstr ($errno)");
                return self::sendViaMailFunction($to, $subject, $htmlBody, $replyTo);
            }
            self::readResponse($socket);
            self::command($socket, 'EHLO ' . parse_url(SITE_URL, PHP_URL_HOST), '250');

            if (SMTP_ENCRYPTION === 'tls') {
                self::command($socket, 'STARTTLS', '220');
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                self::command($socket, 'EHLO ' . parse_url(SITE_URL, PHP_URL_HOST), '250');
            }

            self::command($socket, 'AUTH LOGIN', '334');
            self::command($socket, base64_encode(SMTP_USERNAME), '334');
            self::command($socket, base64_encode(SMTP_PASSWORD), '235');

            self::command($socket, 'MAIL FROM:<' . SMTP_FROM_EMAIL . '>', '250');
            foreach ($to as $recipient) {
                self::command($socket, 'RCPT TO:<' . $recipient . '>', '250');
            }
            self::command($socket, 'DATA', '354');

            $headers = self::buildHeaders($to, $subject, $replyTo);
            $body = str_replace("\n.", "\n..", $htmlBody); // dot-stuffing
            fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
            self::readResponse($socket);

            self::command($socket, 'QUIT', '221');
            fclose($socket);
            return true;
        } catch (Throwable $e) {
            error_log('SMTP send failed: ' . $e->getMessage());
            return self::sendViaMailFunction($to, $subject, $htmlBody, $replyTo);
        }
    }

    private static function buildHeaders(array $to, string $subject, ?string $replyTo): string
    {
        $headers = [];
        $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
        $headers[] = 'To: ' . implode(', ', $to);
        $headers[] = 'Subject: ' . self::encodeSubject($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Date: ' . date(DATE_RFC2822);
        if ($replyTo) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        return implode("\r\n", $headers) . "\r\n";
    }

    private static function encodeSubject(string $subject): string
    {
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }

    private static function sendViaMailFunction(array $to, string $subject, string $htmlBody, ?string $replyTo): bool
    {
        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        if ($replyTo) {
            $headers .= "Reply-To: $replyTo\r\n";
        }
        return @mail(implode(',', $to), self::encodeSubject($subject), $htmlBody, $headers);
    }
}
