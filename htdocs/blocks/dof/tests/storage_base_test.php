<?php
/**
 * Unit tests for dof_storage::count_list method.
 *
 * @package    block_dof
 * @subpackage tests
 */

defined('MOODLE_INTERNAL') || define('MOODLE_INTERNAL', true);

// Ensure constants and helper functions exist if environment is not fully bootstrapped.
if (!defined('SQL_PARAMS_QM')) {
    define('SQL_PARAMS_QM', 1);
}
if (!defined('SQL_PARAMS_NAMED')) {
    define('SQL_PARAMS_NAMED', 2);
}
if (!function_exists('debugging')) {
    function debugging($message = '', $level = 0) {
        return false;
    }
}
if (!interface_exists('dof_plugin_storage')) {
    interface dof_plugin_storage {}
}
if (!class_exists('dof_exception_dml')) {
    class dof_exception_dml extends Exception {}
}

require_once __DIR__ . '/../lib/storage_base.php';

/**
 * Concrete testable implementation of dof_storage for testing.
 */
class testable_dof_storage extends dof_storage {
    private $tablename = 'test_table';

    public function tablename() {
        return $this->tablename;
    }
}

/**
 * Mock DB class to record count_records_select parameters.
 */
class mock_moodle_database {
    public $last_tablename;
    public $last_select;
    public $last_params;
    public $last_countitem;
    public $return_value = 5;

    public function count_records_select($table, $select = '', array $params = null, $countitem = "COUNT('x')") {
        $this->last_tablename = $table;
        $this->last_select = $select;
        $this->last_params = $params;
        $this->last_countitem = $countitem;
        return $this->return_value;
    }
}

// Determine appropriate TestCase parent class for PHPUnit compatibility.
if (class_exists('advanced_testcase')) {
    class storage_base_testcase_parent extends advanced_testcase {}
} elseif (class_exists('basic_testcase')) {
    class storage_base_testcase_parent extends basic_testcase {}
} elseif (class_exists('PHPUnit\Framework\TestCase')) {
    class storage_base_testcase_parent extends \PHPUnit\Framework\TestCase {}
} elseif (class_exists('PHPUnit_Framework_TestCase')) {
    class storage_base_testcase_parent extends PHPUnit_Framework_TestCase {}
} else {
    // Fallback base testcase when running CLI standalone without PHPUnit.
    class storage_base_testcase_parent {
        public function assertEquals($expected, $actual, $message = '') {
            if ($expected !== $actual) {
                throw new Exception("Assertion failed: " . ($message ?: "Expected " . var_export($expected, true) . ", got " . var_export($actual, true)));
            }
        }
    }
}

/**
 * PHPUnit Testcase for dof_storage::count_list.
 */
class block_dof_storage_base_test extends storage_base_testcase_parent {
    private $storage;
    private $mock_db;

    protected function setUp(): void {
        global $DB;
        $this->mock_db = new mock_moodle_database();
        $DB = $this->mock_db;
        $this->storage = new testable_dof_storage();
    }

    /**
     * Test calling count_list with default arguments.
     */
    public function test_count_list_default_parameters() {
        if (!$this->storage) {
            $this->setUp();
        }
        $this->mock_db->return_value = 10;
        $result = $this->storage->count_list();

        $this->assertEquals(10, $result, "Expected return value to be 10");
        $this->assertEquals('test_table', $this->mock_db->last_tablename, "Expected table name 'test_table'");
        $this->assertEquals('', $this->mock_db->last_select, "Expected empty select clause for empty conditions");
        $this->assertEquals(array(), $this->mock_db->last_params, "Expected empty params array");
        $this->assertEquals('COUNT(*)', $this->mock_db->last_countitem, "Expected countitem default 'COUNT(*)'");
    }

    /**
     * Test calling count_list with conditions array.
     */
    public function test_count_list_with_conditions() {
        if (!$this->storage) {
            $this->setUp();
        }
        $this->mock_db->return_value = 3;
        $conditions = array('status' => 'active', 'departmentid' => 12);

        $result = $this->storage->count_list($conditions);

        $this->assertEquals(3, $result, "Expected return value to be 3");
        $this->assertEquals('test_table', $this->mock_db->last_tablename);
        $this->assertEquals('status = ? AND departmentid = ?', $this->mock_db->last_select, "Expected 'status = ? AND departmentid = ?'");
        $this->assertEquals(array('active', 12), $this->mock_db->last_params, "Expected params ['active', 12]");
        $this->assertEquals('COUNT(*)', $this->mock_db->last_countitem);
    }

    /**
     * Test calling count_list with custom countitem parameter.
     */
    public function test_count_list_with_custom_countitem() {
        if (!$this->storage) {
            $this->setUp();
        }
        $this->mock_db->return_value = 7;
        $conditions = array('status' => 'active');
        $custom_countitem = 'COUNT(DISTINCT studentid)';

        $result = $this->storage->count_list($conditions, $custom_countitem);

        $this->assertEquals(7, $result, "Expected return value to be 7");
        $this->assertEquals('COUNT(DISTINCT studentid)', $this->mock_db->last_countitem, "Expected countitem to be passed through");
    }

    /**
     * Test calling count_list when DB returns 0.
     */
    public function test_count_list_returns_int() {
        if (!$this->storage) {
            $this->setUp();
        }
        $this->mock_db->return_value = 0;
        $result = $this->storage->count_list(array('nonexistent' => 'val'));

        $this->assertEquals(0, $result, "Expected return value to be 0");
    }
}

// Allow standalone CLI execution when invoked directly.
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "Running dof_storage::count_list tests...\n";
    $test = new block_dof_storage_base_test();
    $test->test_count_list_default_parameters();
    echo "[PASS] test_count_list_default_parameters\n";
    $test->test_count_list_with_conditions();
    echo "[PASS] test_count_list_with_conditions\n";
    $test->test_count_list_with_custom_countitem();
    echo "[PASS] test_count_list_with_custom_countitem\n";
    $test->test_count_list_returns_int();
    echo "[PASS] test_count_list_returns_int\n";
    echo "All tests passed successfully!\n";
}
