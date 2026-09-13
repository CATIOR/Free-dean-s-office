<?php
define('CLI_SCRIPT', true);

if (!defined('IGNORE_MISSING')) {
    define('IGNORE_MISSING', 1);
}
if (!defined('IGNORE_MULTIPLE')) {
    define('IGNORE_MULTIPLE', 2);
}
if (!defined('MUST_EXIST')) {
    define('MUST_EXIST', 3);
}
if (!defined('DEBUG_DEVELOPER')) {
    define('DEBUG_DEVELOPER', 32767);
}
if (!defined('SQL_PARAMS_QM')) {
    define('SQL_PARAMS_QM', 1);
}
if (!defined('SQL_PARAMS_NAMED')) {
    define('SQL_PARAMS_NAMED', 2);
}

class dof_exception_dml extends Exception {}
class dof_exception extends Exception {}

if (!function_exists('dof_debugging')) {
    function dof_debugging($message, $level = DEBUG_DEVELOPER) {}
}

if (!function_exists('debugging')) {
    function debugging($message = '', $level = DEBUG_DEVELOPER) {
        return false;
    }
}

interface dof_plugin {}

require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/storage_base.php';
