<?php
/**
 * Unit tests for dof_storage::get_object_name method in storage_base.php
 */

if (!defined('DEBUG_DEVELOPER')) {
    define('DEBUG_DEVELOPER', 32767);
}
if (!defined('DEBUG_NORMAL')) {
    define('DEBUG_NORMAL', 2047);
}

if (!function_exists('debugging')) {
    $GLOBALS['dof_debugging_calls'] = array();
    function debugging($message = '', $level = DEBUG_NORMAL, $backtrace = null) {
        $GLOBALS['dof_debugging_calls'][] = array(
            'message' => $message,
            'level' => $level,
            'backtrace' => $backtrace
        );
        return true;
    }
}

require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/plugin.php';
require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/storage_base.php';

/**
 * Concrete stub for testing dof_storage abstract class.
 */
class dof_storage_test_stub extends dof_storage {
    public $records = array();

    public function tablename() {
        return 'test_table';
    }

    public function version() { return '1.0'; }
    public function compat_dof() { return '1.0'; }
    public function compat() { return '1.0'; }
    public function type() { return 'storage'; }
    public function code() { return 'test'; }
    public function need_plugins() { return array(); }
    public function list_catch_events() { return array(); }
    public function is_cron() { return false; }
    public function is_access($do, $objid = NULL, $userid = NULL) { return true; }
    public function catch_event($plugintype, $plugincode, $eventcode, $intvar, $mixedvar) { return true; }
    public function cron($loan, $messages) { return true; }
    public function todo($code, $intvar, $mixedvar) { return true; }
    public function __construct($dof = null) { $this->dof = $dof; }

    public function get($conditions, $fields = '*', $strictness = 0) {
        $id = is_array($conditions) ? reset($conditions) : intval($conditions);
        if (isset($this->records[$id])) {
            return $this->records[$id];
        }
        return false;
    }
}

class StorageBaseObjectNameTest {
    private $storage;

    public function __construct() {
        $this->storage = new dof_storage_test_stub();
    }

    private function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new Exception("Assertion failed: " . ($message ? $message . " - " : "") . "Expected " . var_export($expected, true) . ", got " . var_export($actual, true));
        }
    }

    private function assertTrue($condition, $message = '') {
        if (!$condition) {
            throw new Exception("Assertion failed: " . ($message ? $message : "Expected true"));
        }
    }

    public function run() {
        echo "Running dof_storage::get_object_name tests...\n";

        $this->testGetObjectWithObjectInput();
        $this->testGetObjectWithIntId();
        $this->testGetObjectWithNumericStringId();
        $this->testGetObjectWithNotFoundId();
        $this->testGetObjectWithInvalidParamTypeArray();
        $this->testGetObjectWithInvalidParamTypeNull();
        $this->testGetObjectWithInvalidParamTypeNonNumericString();
        $this->testGetObjectWithInvalidParamTypeBool();

        echo "All tests passed successfully!\n";
    }

    private function resetDebugging() {
        $GLOBALS['dof_debugging_calls'] = array();
    }

    private function testGetObjectWithObjectInput() {
        $this->resetDebugging();
        $obj = new stdClass();
        $obj->id = 1;
        $obj->name = 'Test Course';

        $result = $this->storage->get_object_name($obj);
        $this->assertEquals('Test Course', $result, 'Failed object input test');
        $this->assertEquals(0, count($GLOBALS['dof_debugging_calls']), 'Debugging called when not expected');
        echo "  [PASS] testGetObjectWithObjectInput\n";
    }

    private function testGetObjectWithIntId() {
        $this->resetDebugging();
        $obj = new stdClass();
        $obj->id = 10;
        $obj->name = 'Faculty of Science';
        $this->storage->records[10] = $obj;

        $result = $this->storage->get_object_name(10);
        $this->assertEquals('Faculty of Science', $result, 'Failed integer ID test');
        $this->assertEquals(0, count($GLOBALS['dof_debugging_calls']), 'Debugging called when not expected');
        echo "  [PASS] testGetObjectWithIntId\n";
    }

    private function testGetObjectWithNumericStringId() {
        $this->resetDebugging();
        $obj = new stdClass();
        $obj->id = 25;
        $obj->name = 'Department of Math';
        $this->storage->records[25] = $obj;

        $result = $this->storage->get_object_name('25');
        $this->assertEquals('Department of Math', $result, 'Failed numeric string ID test');
        $this->assertEquals(0, count($GLOBALS['dof_debugging_calls']), 'Debugging called when not expected');
        echo "  [PASS] testGetObjectWithNumericStringId\n";
    }

    private function testGetObjectWithNotFoundId() {
        $this->resetDebugging();
        $result = $this->storage->get_object_name(999);
        $this->assertEquals('[[object_not_found!]]', $result, 'Failed object not found test');
        $this->assertEquals(1, count($GLOBALS['dof_debugging_calls']), 'Debugging should have been logged once');
        $this->assertTrue(strpos($GLOBALS['dof_debugging_calls'][0]['message'], 'object not found!') !== false, 'Unexpected debug message');
        echo "  [PASS] testGetObjectWithNotFoundId\n";
    }

    private function testGetObjectWithInvalidParamTypeArray() {
        $this->resetDebugging();
        $result = $this->storage->get_object_name(array(1, 2, 3));
        $this->assertEquals('', $result, 'Failed invalid param array test');
        $this->assertEquals(1, count($GLOBALS['dof_debugging_calls']), 'Debugging should have been logged once');
        $this->assertTrue(strpos($GLOBALS['dof_debugging_calls'][0]['message'], 'wrong parameter type!') !== false, 'Unexpected debug message');
        echo "  [PASS] testGetObjectWithInvalidParamTypeArray\n";
    }

    private function testGetObjectWithInvalidParamTypeNull() {
        $this->resetDebugging();
        $result = $this->storage->get_object_name(null);
        $this->assertEquals('', $result, 'Failed invalid param null test');
        $this->assertEquals(1, count($GLOBALS['dof_debugging_calls']), 'Debugging should have been logged once');
        $this->assertTrue(strpos($GLOBALS['dof_debugging_calls'][0]['message'], 'wrong parameter type!') !== false, 'Unexpected debug message');
        echo "  [PASS] testGetObjectWithInvalidParamTypeNull\n";
    }

    private function testGetObjectWithInvalidParamTypeNonNumericString() {
        $this->resetDebugging();
        $result = $this->storage->get_object_name('invalid_id_string');
        $this->assertEquals('', $result, 'Failed invalid param non-numeric string test');
        $this->assertEquals(1, count($GLOBALS['dof_debugging_calls']), 'Debugging should have been logged once');
        $this->assertTrue(strpos($GLOBALS['dof_debugging_calls'][0]['message'], 'wrong parameter type!') !== false, 'Unexpected debug message');
        echo "  [PASS] testGetObjectWithInvalidParamTypeNonNumericString\n";
    }

    private function testGetObjectWithInvalidParamTypeBool() {
        $this->resetDebugging();
        $result = $this->storage->get_object_name(true);
        $this->assertEquals('', $result, 'Failed invalid param boolean test');
        $this->assertEquals(1, count($GLOBALS['dof_debugging_calls']), 'Debugging should have been logged once');
        $this->assertTrue(strpos($GLOBALS['dof_debugging_calls'][0]['message'], 'wrong parameter type!') !== false, 'Unexpected debug message');
        echo "  [PASS] testGetObjectWithInvalidParamTypeBool\n";
    }
}

$testRunner = new StorageBaseObjectNameTest();
$testRunner->run();
