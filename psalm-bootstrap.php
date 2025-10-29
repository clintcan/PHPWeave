<?php
/**
 * Psalm Bootstrap File
 *
 * This file is loaded during Psalm analysis to define constants
 * and globals that the framework expects, without actually executing code.
 */

// Define PHPWEAVE_ROOT constant for static analysis
if (!defined('PHPWEAVE_ROOT')) {
    define('PHPWEAVE_ROOT', __DIR__);
}

// Define other globals that might be referenced during analysis
$GLOBALS['baseurl'] = '/';
$GLOBALS['configs'] = [];
$GLOBALS['models'] = [];
$GLOBALS['libraries'] = [];
$GLOBALS['PW'] = new stdClass();

// Stub classes for analysis (if needed)
// No actual implementation needed - Psalm will analyze the real classes
