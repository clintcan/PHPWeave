<?php
/**
 * Test Log Hook
 *
 * Simple mock logging hook for testing purposes.
 */
class TestLogHook
{
    public static $callCount = 0;
    public static $lastLog = null;
    public static $lastData = null;

    public function handle($data, $prefix = '')
    {
        self::$callCount++;
        self::$lastData = $data;

        // Create a log message
        $controller = $data['controller'] ?? 'UNKNOWN';
        $method = $data['method'] ?? 'UNKNOWN';
        self::$lastLog = "{$prefix}{$controller}@{$method}";

        // Add marker to data (ensure params is an array)
        if (!isset($data['params']) || !is_array($data['params'])) {
            $data['params'] = [];
        }
        $data['params']['test_log_called'] = true;

        return $data;
    }
}
