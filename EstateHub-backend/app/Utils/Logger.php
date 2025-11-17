<?php
namespace App\Utils;

class Logger
{
    private static $instance = null;
    private $logFile;

    private function __construct()
    {
        // Use LOG_PATH if defined, otherwise default to storage/logs
        $logDir = defined('LOG_PATH') ? LOG_PATH : __DIR__ . '/../../logs';
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Use daily log files
        $this->logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }
        return self::$instance;
    }

    /**
     * Log info message
     */
    public static function info($message, $context = [])
    {
        $msg = $message;
        if (!empty($context)) {
            $msg .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        self::write('INFO', $msg);
    }

    /**
     * Log error message
     */
    public static function error($message, $context = [])
    {
        $msg = $message;
        if (!empty($context)) {
            $msg .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        self::write('ERROR', $msg);
        // Also write to PHP error log for errors
        error_log("[ERROR] $msg");
    }

    /**
     * Log warning message
     */
    public static function warning($message, $context = [])
    {
        $msg = $message;
        if (!empty($context)) {
            $msg .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        self::write('WARNING', $msg);
    }

    /**
     * Alias for warning
     */
    public static function warn($message, $context = [])
    {
        self::warning($message, $context);
    }

    /**
     * Log debug message
     */
    public static function debug($message, $context = [])
    {
        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
            $msg = $message;
            if (!empty($context)) {
                $msg .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            self::write('DEBUG', $msg);
        }
    }

    /**
     * Log critical message
     */
    public static function critical($message, $context = [])
    {
        $msg = $message;
        if (!empty($context)) {
            $msg .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        self::write('CRITICAL', $msg);
        error_log("[CRITICAL] $msg");
    }

    /**
     * Log notice message
     */
    public static function notice($message, $context = [])
    {
        $msg = $message;
        if (!empty($context)) {
            $msg .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        self::write('NOTICE', $msg);
    }

    /**
     * Main write method
     */
    private static function write($level, $message)
    {
        try {
            $logger = self::getInstance();
            $time = date('Y-m-d H:i:s');
            $entry = "[$time] [$level] $message" . PHP_EOL;
            @file_put_contents($logger->logFile, $entry, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the application
            error_log("Logger failed: " . $e->getMessage());
        }
    }

    /**
     * Log exception
     */
    public static function logException(\Throwable $e)
    {
        $msg = sprintf(
            'Exception: %s in %s:%d | Trace: %s',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        self::error($msg);
    }

    /**
     * Log authentication attempt
     */
    public static function logAuth($userId, $success = true, $message = '')
    {
        $level = $success ? 'INFO' : 'WARNING';
        $msg = sprintf(
            'Auth %s | User: %s | IP: %s | %s',
            $success ? 'SUCCESS' : 'FAILED',
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            $message
        );
        self::write($level, $msg);
    }

    /**
     * Clear old log files
     */
    public static function clearOldLogs($days = 30)
    {
        $logDir = defined('LOG_PATH') ? LOG_PATH : __DIR__ . '/../../logs';
        
        if (!is_dir($logDir)) {
            return;
        }

        $files = glob($logDir . '/*.log');
        $cutoffTime = time() - ($days * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                @unlink($file);
            }
        }
    }

    /**
     * Get today's log file path
     */
    public static function getLogFile()
    {
        return self::getInstance()->logFile;
    }

    /**
     * Read recent log entries
     */
    public static function readLog($lines = 100)
    {
        $logFile = self::getLogFile();
        
        if (!file_exists($logFile)) {
            return [];
        }

        $content = @file($logFile);
        if ($content === false) {
            return [];
        }

        return array_slice($content, -$lines);
    }
}