<?php
/**
 * Enhanced Error Handling System
 * Provides comprehensive error handling with logging and user feedback
 */

class ErrorHandler {
    private $log_file;
    private $log_level;

    public function __construct() {
        $this->log_file = __DIR__ . '/../logs/error.log';
        $this->log_level = 'INFO'; // DEBUG, INFO, WARNING, ERROR, CRITICAL

        // Create logs directory if it doesn't exist
        if (!file_exists(dirname($this->log_file))) {
            mkdir(dirname($this->log_file), 0755, true);
        }

        // Set error handler
        set_error_handler([$this, 'handle_error']);
        set_exception_handler([$this, 'handle_exception']);
        register_shutdown_function([$this, 'handle_shutdown']);
    }

    /**
     * Handle PHP errors
     */
    public function handle_error($errno, $errstr, $errfile, $errline) {
        $error_types = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED'
        ];

        $error_type = $error_types[$errno] ?? 'UNKNOWN';

        $this->log_error($error_type, $errstr, [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ]);

        // Don't execute PHP internal error handler
        return true;
    }

    /**
     * Handle exceptions
     */
    public function handle_exception($exception) {
        $this->log_error('EXCEPTION', $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->display_error_page($exception);
    }

    /**
     * Handle fatal errors on shutdown
     */
    public function handle_shutdown() {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->log_error('FATAL', $error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type']
            ]);

            $this->display_error_page(new Exception('Fatal error occurred'));
        }
    }

    /**
     * Log error to file
     */
    public function log_error($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'unknown';

        $log_entry = sprintf(
            "[%s] %s: %s | IP: %s | URI: %s | User-Agent: %s | Context: %s\n",
            $timestamp,
            $level,
            $message,
            $ip,
            $request_uri,
            $user_agent,
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);

        // Also log to system error log for critical errors
        if (in_array($level, ['ERROR', 'CRITICAL', 'FATAL'])) {
            error_log($message, 0);
        }
    }

    /**
     * Display user-friendly error page
     */
    private function display_error_page($exception) {
        // Don't display errors in production
        if (getenv('APP_ENV') === 'production') {
            http_response_code(500);
            include __DIR__ . '/../views/errors/500.php';
            exit;
        }

        // Display detailed error in development
        http_response_code(500);
        echo '<div style="padding: 20px; font-family: Arial, sans-serif;">';
        echo '<h1>Application Error</h1>';
        echo '<h2>' . htmlspecialchars($exception->getMessage()) . '</h2>';
        echo '<p>File: ' . htmlspecialchars($exception->getFile()) . ' (Line: ' . $exception->getLine() . ')</p>';
        echo '<h3>Stack Trace:</h3>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
        echo '</div>';
        exit;
    }

    /**
     * Get error logs
     */
    public function get_error_logs($limit = 50, $level = null) {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $logs = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = array_reverse($logs);

        if ($limit) {
            $logs = array_slice($logs, 0, $limit);
        }

        if ($level) {
            $logs = array_filter($logs, function($log) use ($level) {
                return strpos($log, "[$level]") !== false;
            });
        }

        return array_map(function($log) {
            preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+): (.+) \|/', $log, $matches);
            return [
                'timestamp' => $matches[1] ?? null,
                'level' => $matches[2] ?? null,
                'message' => $matches[3] ?? null,
                'raw' => $log
            ];
        }, $logs);
    }

    /**
     * Clear error logs
     */
    public function clear_error_logs() {
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
    }

    /**
     * Get error statistics
     */
    public function get_error_stats($days = 7) {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $logs = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats = [];

        foreach ($logs as $log) {
            preg_match('/\[(\d{4}-\d{2}-\d{2}) \d{2}:\d{2}:\d{2}\] (\w+):/', $log, $matches);

            if (isset($matches[1]) && isset($matches[2])) {
                $date = $matches[1];
                $level = $matches[2];

                if (!isset($stats[$date])) {
                    $stats[$date] = [];
                }

                if (!isset($stats[$date][$level])) {
                    $stats[$date][$level] = 0;
                }

                $stats[$date][$level]++;
            }
        }

        return $stats;
    }
}

/**
 * Validation error class
 */
class ValidationException extends Exception {
    private $errors;

    public function __construct($errors, $message = 'Validation failed') {
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function getErrors() {
        return $this->errors;
    }
}

/**
 * Authorization error class
 */
class AuthorizationException extends Exception {
    public function __construct($message = 'Access denied') {
        parent::__construct($message);
    }
}

/**
 * Database error class
 */
class DatabaseException extends Exception {
    public function __construct($message = 'Database error') {
        parent::__construct($message);
    }
}

/**
 * Initialize error handler
 */
$error_handler = new ErrorHandler();

/**
 * Helper functions
 */
function log_error($level, $message, $context = []) {
    global $error_handler;
    $error_handler->log_error($level, $message, $context);
}

function display_error($message, $type = 'error') {
    $_SESSION['flash'][$type] = $message;
}

function get_error_logs($limit = 50, $level = null) {
    global $error_handler;
    return $error_handler->get_error_logs($limit, $level);
}

function clear_error_logs() {
    global $error_handler;
    return $error_handler->clear_error_logs();
}

function get_error_stats($days = 7) {
    global $error_handler;
    return $error_handler->get_error_stats($days);
}
?>
