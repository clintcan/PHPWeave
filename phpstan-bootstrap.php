<?php
/**
 * PHPStan bootstrap file
 * 
 * Defines constants and sets up environment for static analysis
 */

// Define PHPWEAVE_ROOT constant for static analysis
// This mimics the definition in public/index.php
if (!defined('PHPWEAVE_ROOT')) {
    define('PHPWEAVE_ROOT', __DIR__);
}

// Define other globals that might be needed during analysis
$GLOBALS['baseurl'] = '/';
$GLOBALS['configs'] = [];
$GLOBALS['models'] = [];
$GLOBALS['PW'] = new stdClass();