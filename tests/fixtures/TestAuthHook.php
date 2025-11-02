<?php
/**
 * Test Auth Hook
 *
 * Simple mock authentication hook for testing purposes.
 */
class TestAuthHook
{
    public static $callCount = 0;
    public static $lastData = null;

    public function handle($data)
    {
        self::$callCount++;
        self::$lastData = $data;

        // Add a marker to prove this was called
        $data['params']['test_auth_called'] = true;

        return $data;
    }
}
