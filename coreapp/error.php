<?php
/**
 * Error Handling Class
 *
 * Comprehensive error and exception handling system for PHPWeave.
 * Features:
 * - Global error handler registration
 * - Exception handler with stack traces
 * - Fatal error detection and handling
 * - Error logging to file
 * - Email notifications for critical errors
 * - Development vs production error display
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Error Handling
 * @author     Clint Christopher Canada
 * @version    2.0.0
 */
class ErrorClass {
    /**
     * Whether to display errors on screen
     * @var int
     */
    public $display_error;

    /**
     * Constructor
     *
     * Initializes error handling configuration and sets up error reporting.
     * Automatically registers global error, exception, and fatal error handlers.
     *
     * @param int $display_error   Whether to display errors (0 = hide, 1 = show)
     * @param int $error_reporting Error reporting level (default: E_ALL)
     * @return void
     */
    public function __construct($display_error = 0, $error_reporting = E_ALL) {
        $this->display_error = $display_error;
        ini_set('display_errors', $this->display_error);
        error_reporting($error_reporting);

        // Register error handlers
        set_error_handler([$this, 'globalErrorHandler']);
        set_exception_handler([$this, 'globalExceptionHandler']);
        register_shutdown_function([$this, 'fatalErrorHandler']);
    }

    /**
     * Global Error Handler
     *
     * Handles PHP errors (warnings, notices, etc.) and logs them.
     * Prevents execution of PHP's internal error handler.
     *
     * @param int    $errno   Error number
     * @param string $errstr  Error message
     * @param string $errfile File where error occurred
     * @param int    $errline Line number where error occurred
     * @return bool Returns true to prevent default PHP error handler
     */
    function globalErrorHandler($errno, $errstr, $errfile, $errline) {
        $error = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $this->getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];

        // Log the error
        $this->logError($error);

        // Display user-friendly message in production
        $this->displayErrorPage();

        // Don't execute PHP's internal error handler
        return true;
    }

    /**
     * Global Exception Handler
     *
     * Catches uncaught exceptions and logs them with stack trace.
     *
     * @param Throwable $exception The uncaught exception or error
     * @return void
     */
    function globalExceptionHandler($exception) {
        $error = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        // Log the error
        $this->logError($error);

        // Display user-friendly message in production
        $this->displayErrorPage();
    }

    /**
     * Fatal Error Handler
     *
     * Catches fatal errors that would normally crash the application.
     * Registers as a shutdown function to detect fatal errors.
     *
     * @return void
     */
    function fatalErrorHandler() {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorInfo = [
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'Fatal Error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ];

            // Log the error
            $this->logError($errorInfo);

            // Display user-friendly message in production
            $this->displayErrorPage();
        }
    }

    /**
     * Get error type string from error number
     *
     * Converts PHP error constants to human-readable strings.
     *
     * @param int $errno PHP error constant (E_ERROR, E_WARNING, etc.)
     * @return string Human-readable error type
     */
    function getErrorType($errno) {
        $errorTypes = [
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        return $errorTypes[$errno] ?? 'Unknown Error';
    }

    /**
     * Log error to file
     *
     * Writes error information to error.log file in coreapp/ directory.
     * Optionally sends email notification for critical errors.
     *
     * @param array $error Error information array with keys: timestamp, type, message, file, line, trace (optional)
     * @return void
     */
    function logError($error) {
        $logFile = __DIR__ . '/error.log';
        $logMessage = "[{$error['timestamp']}] {$error['type']}: {$error['message']} in {$error['file']} on line {$error['line']}\n";

        if (isset($error['trace'])) {
            $logMessage .= "Stack trace:\n{$error['trace']}\n";
        }

        error_log($logMessage, 3, $logFile);

        // Optionally send critical errors via email
        if ($this->isErrorCritical($error['type'])) {
            $this->mailError($error);
        }
    }

    /**
     * Check if error is critical
     *
     * Determines whether an error type is considered critical
     * and requires immediate attention.
     *
     * @param string $type Error type string
     * @return bool True if error is critical
     */
    function isErrorCritical($type) {
        $criticalErrors = [
            'Error',
            'Parse Error',
            'Core Error',
            'Compile Error',
            'Fatal Error'
        ];

        return in_array($type, $criticalErrors);
    }

    /**
     * Send error notification email
     *
     * Sends an email to the administrator when a critical error occurs.
     * Update the $to and $from addresses for your environment.
     *
     * @param array $error Error information array
     * @return void
     * @psalm-suppress TaintedHtml - Email body is plain text, not HTML. Safe for admin notification.
     */
    function mailError($error) {
        $to = 'admin@yourdomain.com';
        // Sanitize HTTP_HOST to prevent header injection
        $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-zA-Z0-9.-]/', '', $_SERVER['HTTP_HOST']) : 'unknown';
        $subject = "Critical Error on " . $host;

        /** @psalm-suppress TaintedHtml - Plain text email body, not HTML output. Safe for admin notification. */
        $message = "A critical error occurred:\n\n" . print_r($error, true);
        $headers = 'From: webmaster@yourdomain.com' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

        mail($to, $subject, $message, $headers);
    }

    /**
     * Display error page
     *
     * Shows different error pages based on environment:
     * - Development: Detailed error with stack trace
     * - Production: User-friendly error page
     *
     * Checks DEBUG config or $isDevelopment variable to determine mode.
     *
     * @return void
     */
    function displayErrorPage() {
        // Check if we're in development mode (from .env DEBUG setting)
        $isDevelopment = isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG'] == 1;

        if ($isDevelopment) {
            // Display detailed error information for development
            header('HTTP/1.1 500 Internal Server Error');
            echo '<div style="padding: 20px; background: #ffebee; border: 1px solid #ef9a9a; margin: 10px;">';
            echo '<h2 style="color: #c62828;">Error Occurred</h2>';
            echo '<pre>' . print_r(error_get_last(), true) . '</pre>';
            echo '</div>';
        } else {
            // Display user-friendly error page for production
            header('HTTP/1.1 500 Internal Server Error');
            $errorHtmlPath = __DIR__ . '/error.html';
            if (file_exists($errorHtmlPath)) {
                include $errorHtmlPath;
            } else {
                echo '<h1>An error occurred</h1><p>We apologize for the inconvenience. Please try again later.</p>';
            }
            exit();
        }
    }
}
?>