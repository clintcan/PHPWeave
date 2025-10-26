<?php
/**
 * Send Email Job
 *
 * Background job for sending emails asynchronously.
 * Prevents email sending from blocking HTTP responses.
 *
 * @package    PHPWeave
 * @subpackage Jobs
 * @category   Jobs
 * @author     Clint Christopher Canada
 * @version    2.0.0
 *
 * @example
 * Async::queue('SendEmailJob', [
 *     'to' => 'user@example.com',
 *     'subject' => 'Welcome!',
 *     'message' => 'Thanks for signing up!'
 * ]);
 */
class SendEmailJob extends Job
{
    /**
     * Handle the job
     *
     * Sends an email using PHP's mail() function.
     *
     * @param array $data Email data (to, subject, message, from, headers)
     * @return void
     *
     * @example
     * $job = new SendEmailJob();
     * $job->handle([
     *     'to' => 'user@example.com',
     *     'subject' => 'Hello',
     *     'message' => 'Email body'
     * ]);
     */
    public function handle($data)
    {
        $to = $data['to'];
        $subject = $data['subject'];
        $message = $data['message'];
        $headers = $data['headers'] ?? '';

        // Add default headers if not provided
        if (empty($headers)) {
            $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }

        // Send the email
        $result = mail($to, $subject, $message, $headers);

        if (!$result) {
            throw new Exception("Failed to send email to: $to");
        }

        // Log success
        error_log("[" . date('Y-m-d H:i:s') . "] Email sent to: $to - Subject: $subject");
    }
}
