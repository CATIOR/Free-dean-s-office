<?php
/**
 * Unit test for dof_storage::get_records_select in htdocs/blocks/dof/lib/storage_base.php
 */

if (!interface_exists('dof_plugin_storage')) {
    interface dof_plugin_storage {}
}

require_once __DIR__ . '/../lib/storage_base.php';

// Mock DB class to record calls and simulate database responses/exceptions
class mock_moodle_database_select {
    public array $calls = [];
    public bool $should_throw = false;

    public function get_records_select($tablename, $select, array $params = null, $sort = '', $fields = '*', $limitfrom = 0, $limitnum = 0) {
        if ($this->should_throw) {
            throw new Exception("dml_exception simulated");
        }
        $this->calls[] = [
            'tablename' => $tablename,
            'select' => $select,
            'params' => $params,
            'sort' => $sort,
            'fields' => $fields,
            'limitfrom' => $limitfrom,
            'limitnum' => $limitnum
        ];
        return [
            1 => (object)['id' => 1, 'name' => 'Item 1'],
            2 => (object)['id' => 2, 'name' => 'Item 2']
        ];
    }
}

// Concrete subclass of dof_storage for testing
class test_dof_storage_select extends dof_storage {
    public function tablename() {
        return 'test_table_name';
    }
}

// Ensure compatibility with PHPUnit test runner environment or standalone CLI run
if (!class_exists('PHPUnit\Framework\TestCase') && !class_exists('PHPUnit_Framework_TestCase')) {
    abstract class dof_phpunit_testcase_fallback {
        protected function assertEquals($expected, $actual, string $message = '') {
            if ($expected !== $actual) {
                throw new Exception("Failed asserting that " . var_export($actual, true) . " matches expected " . var_export($expected, true) . ". {$message}");
            }
        }
        protected function assertCount($expectedCount, $haystack, string $message = '') {
            $actualCount = is_countable($haystack) ? count($haystack) : 0;
            if ($expectedCount !== $actualCount) {
                throw new Exception("Failed asserting that count " . $actualCount . " matches expected " . $expectedCount . ". {$message}");
            }
        }
        protected function assertTrue($condition, string $message = '') {
            if ($condition !== true) {
                throw new Exception("Failed asserting that condition is true. {$message}");
            }
        }
    }
    class_alias('dof_phpunit_testcase_fallback', 'PHPUnit\Framework\TestCase');
}

use PHPUnit\Framework\TestCase;

class storage_base_get_records_select_test extends TestCase {

    public function test_default_parameters() {
        $db_mock = new mock_moodle_database_select();
        $GLOBALS['DB'] = $db_mock;
        $storage = new test_dof_storage_select();

        $result = $storage->get_records_select('status = ?');
        $this->assertCount(1, $db_mock->calls, "DB call count");
        $last_call = end($db_mock->calls);
        $this->assertEquals('test_table_name', $last_call['tablename'], "Tablename");
        $this->assertEquals('status = ?', $last_call['select'], "Select clause");
        $this->assertEquals(null, $last_call['params'], "Default params");
        $this->assertEquals('', $last_call['sort'], "Default sort");
        $this->assertEquals('*', $last_call['fields'], "Default fields");
        $this->assertEquals(0, $last_call['limitfrom'], "Default limitfrom");
        $this->assertEquals(0, $last_call['limitnum'], "Default limitnum");
        $this->assertCount(2, $result, "Return value count");
    }

    public function test_custom_parameters() {
        $db_mock = new mock_moodle_database_select();
        $GLOBALS['DB'] = $db_mock;
        $storage = new test_dof_storage_select();

        $params = ['active', 'admin'];
        $result = $storage->get_records_select('status = ? AND role = ?', $params, 'id DESC', 'id, name', 10, 50);
        $this->assertCount(1, $db_mock->calls, "DB call count");
        $last_call = end($db_mock->calls);
        $this->assertEquals('test_table_name', $last_call['tablename'], "Tablename");
        $this->assertEquals('status = ? AND role = ?', $last_call['select'], "Select clause");
        $this->assertEquals($params, $last_call['params'], "Params passed");
        $this->assertEquals('id DESC', $last_call['sort'], "Sort order");
        $this->assertEquals('id, name', $last_call['fields'], "Fields list");
        $this->assertEquals(10, $last_call['limitfrom'], "Limitfrom");
        $this->assertEquals(50, $last_call['limitnum'], "Limitnum");
        $this->assertCount(2, $result, "Return value count");
    }

    public function test_exception_propagation() {
        $db_mock = new mock_moodle_database_select();
        $db_mock->should_throw = true;
        $GLOBALS['DB'] = $db_mock;
        $storage = new test_dof_storage_select();

        $exception_thrown = false;
        try {
            $storage->get_records_select('1=1');
        } catch (Exception $e) {
            $exception_thrown = true;
            $this->assertEquals("dml_exception simulated", $e->getMessage(), "Exception message");
        }
        $this->assertTrue($exception_thrown, "Exception was propagated");
    }
}

// Standalone CLI runner support when executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $test = new storage_base_get_records_select_test();
    $test->test_default_parameters();
    $test->test_custom_parameters();
    $test->test_exception_propagation();
    echo "All tests in storage_base_get_records_select_test passed successfully!\n";
}
