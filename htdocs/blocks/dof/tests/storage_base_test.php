<?php
/**
 * Unit tests for dof_storage::count_records_sql
 *
 * @package    block_dof
 * @category   test
 */

defined('MOODLE_INTERNAL') || define('MOODLE_INTERNAL', true);

// Include dependencies.
require_once(__DIR__ . '/../lib/plugin.php');
require_once(__DIR__ . '/../lib/storage.php');
require_once(__DIR__ . '/../lib/storage_base.php');

if (!class_exists('dml_exception')) {
    class dml_exception extends Exception {}
}

/**
 * Concrete test implementation of abstract class dof_storage.
 */
class test_dof_storage_impl extends dof_storage {
    public function __construct($dof = null) {}
    public function tablename() { return 'test_table'; }
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
}

/**
 * Mock DB class for testing count_records_sql.
 */
class mock_dof_storage_db {
    public $last_sql;
    public $last_params;
    public $return_value = 0;
    public $should_throw = false;
    public $exception_message = 'Database query error';

    public function count_records_sql($sql, array $params = null) {
        $this->last_sql = $sql;
        $this->last_params = $params;

        if ($this->should_throw) {
            throw new dml_exception($this->exception_message);
        }

        return $this->return_value;
    }
}

/**
 * Test case for dof_storage::count_records_sql
 */
class block_dof_storage_base_testcase {
    private $storage;
    private $mock_db;

    public function setUp() {
        global $DB;
        $this->mock_db = new mock_dof_storage_db();
        $DB = $this->mock_db;
        $this->storage = new test_dof_storage_impl();
    }

    /**
     * Test count_records_sql with simple SQL query and null params.
     */
    public function test_count_records_sql_basic() {
        $this->setUp();
        $sql = "SELECT COUNT(*) FROM {test_table}";
        $this->mock_db->return_value = 42;

        $result = $this->storage->count_records_sql($sql);

        assert($result === 42, "Expected count to be 42, got " . var_export($result, true));
        assert($this->mock_db->last_sql === $sql, "SQL query mismatch");
        assert($this->mock_db->last_params === null, "Params should be null");
        echo "PASS: test_count_records_sql_basic\n";
    }

    /**
     * Test count_records_sql with SQL query and array parameters.
     */
    public function test_count_records_sql_with_params() {
        $this->setUp();
        $sql = "SELECT COUNT(*) FROM {test_table} WHERE status = :status AND age > :age";
        $params = array('status' => 'active', 'age' => 18);
        $this->mock_db->return_value = 15;

        $result = $this->storage->count_records_sql($sql, $params);

        assert($result === 15, "Expected count to be 15, got " . var_export($result, true));
        assert($this->mock_db->last_sql === $sql, "SQL query mismatch");
        assert($this->mock_db->last_params === $params, "Params mismatch");
        echo "PASS: test_count_records_sql_with_params\n";
    }

    /**
     * Test count_records_sql returning zero when no records match.
     */
    public function test_count_records_sql_zero_count() {
        $this->setUp();
        $sql = "SELECT COUNT(*) FROM {test_table} WHERE id = ?";
        $params = array(99999);
        $this->mock_db->return_value = 0;

        $result = $this->storage->count_records_sql($sql, $params);

        assert($result === 0, "Expected count to be 0, got " . var_export($result, true));
        assert($this->mock_db->last_sql === $sql, "SQL query mismatch");
        assert($this->mock_db->last_params === $params, "Params mismatch");
        echo "PASS: test_count_records_sql_zero_count\n";
    }

    /**
     * Test count_records_sql propagates dml_exception on DB error.
     */
    public function test_count_records_sql_throws_exception() {
        $this->setUp();
        $sql = "SELECT COUNT(*) FROM {invalid_table}";
        $this->mock_db->should_throw = true;

        $caught = false;
        try {
            $this->storage->count_records_sql($sql);
        } catch (dml_exception $e) {
            $caught = true;
        }

        assert($caught === true, "Expected dml_exception was not thrown");
        echo "PASS: test_count_records_sql_throws_exception\n";
    }

    public function run_all_tests() {
        $this->test_count_records_sql_basic();
        $this->test_count_records_sql_with_params();
        $this->test_count_records_sql_zero_count();
        $this->test_count_records_sql_throws_exception();
        echo "All tests passed successfully!\n";
    }
}

// Run tests if called directly via PHP CLI.
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    $test = new block_dof_storage_base_testcase();
    $test->run_all_tests();
}
