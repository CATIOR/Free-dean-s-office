<?php
////////////////////////////////////////////////////////////////////////////
//                                                                        //
// NOTICE OF COPYRIGHT                                                    //
//                                                                        //
// Dean`s Office for Moodle                                               //
// Электронный деканат                                                    //
// <http://deansoffice.ru/>                                               //
//                                                                        //
// Copyright (C) 2008-2999  Alex Djachenko (Алексей Дьяченко)             //
// alex-pub@my-site.ru                                                    //
//                                                                        //
// This program is free software: you can redistribute it and/or modify   //
// it under the terms of the GNU General Public License as published by   //
// the Free Software Foundation, either version 3 of the Licensen.        //
//                                                                        //
// This program is distributed in the hope that it will be useful,        //
// but WITHOUT ANY WARRANTY; without even the implied warranty of         //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          //
// GNU General Public License for more details.                           //
//                                                                        //
// You should have received a copy of the GNU General Public License      //
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  //
//                                                                        //
////////////////////////////////////////////////////////////////////////////

/**
 * Unit tests for dof_storage::get_records_sql in storage_base.php
 *
 * @package    block_dof
 * @subpackage tests
 */

if (file_exists(__DIR__ . '/../../../config.php')) {
    require_once(__DIR__ . '/../../../config.php');
} else {
    if (!defined('MOODLE_INTERNAL')) {
        define('MOODLE_INTERNAL', true);
    }
    if (!defined('DEBUG_DEVELOPER')) {
        define('DEBUG_DEVELOPER', 32767);
    }
    if (!defined('IGNORE_MISSING')) {
        define('IGNORE_MISSING', 0);
    }
    if (!defined('IGNORE_MULTIPLE')) {
        define('IGNORE_MULTIPLE', 1);
    }
    if (!defined('MUST_EXIST')) {
        define('MUST_EXIST', 2);
    }
    if (!defined('SQL_PARAMS_QM')) {
        define('SQL_PARAMS_QM', 1);
    }
    if (!defined('SQL_PARAMS_NAMED')) {
        define('SQL_PARAMS_NAMED', 2);
    }

    if (!class_exists('dof_exception_dml')) {
        class dof_exception_dml extends Exception {}
    }
    if (!class_exists('dof_exception')) {
        class dof_exception extends Exception {}
    }
    if (!class_exists('dml_exception')) {
        class dml_exception extends Exception {}
    }
    if (!function_exists('dof_debugging')) {
        function dof_debugging($msg, $level) {}
    }
    if (!function_exists('dof_mtrace')) {
        function dof_mtrace($level, $msg) {}
    }
}

require_once(__DIR__ . '/../lib/plugin.php');
require_once(__DIR__ . '/../lib/storage.php');
require_once(__DIR__ . '/../lib/storage_base.php');

// Define a BaseTestCase class if PHPUnit is not loaded in the environment
if (!class_exists('PHPUnit\Framework\TestCase') && !class_exists('PHPUnit_Framework_TestCase')) {
    abstract class dof_storage_base_testcase {
        protected function assertEquals($expected, $actual, $message = '') {
            if ($expected !== $actual) {
                throw new Exception($message ?: "Failed asserting that " . var_export($actual, true) . " matches expected " . var_export($expected, true));
            }
        }
        protected function assertSame($expected, $actual, $message = '') {
            if ($expected !== $actual) {
                throw new Exception($message ?: "Failed asserting that " . var_export($actual, true) . " is identical to " . var_export($expected, true));
            }
        }
        protected function assertTrue($condition, $message = '') {
            if ($condition !== true) {
                throw new Exception($message ?: "Failed asserting that condition is true");
            }
        }
        protected function assertIsArray($actual, $message = '') {
            if (!is_array($actual)) {
                throw new Exception($message ?: "Failed asserting that value is array");
            }
        }
        protected function expectException($exceptionClass) {
            $this->expectedException = $exceptionClass;
        }
        public $expectedException = null;
    }
} else {
    class_alias(
        class_exists('PHPUnit\Framework\TestCase') ? 'PHPUnit\Framework\TestCase' : 'PHPUnit_Framework_TestCase',
        'dof_storage_base_testcase'
    );
}

/**
 * Concrete implementation of abstract dof_storage for testing.
 */
class test_dof_storage_get_records_sql extends dof_storage {
    public function tablename() { return 'test_table'; }
    public function version() { return '1.0'; }
    public function compat_dof() { return '2.4'; }
    public function compat() { return array(); }
    public function type() { return 'storage'; }
    public function code() { return 'test'; }
    public function need_plugins() { return array(); }
    public function list_catch_events() { return array(); }
    public function is_cron() { return false; }
    public function is_access($do, $objid = null, $userid = null) { return true; }
    public function catch_event($plugintype, $plugincode, $eventcode, $intvar = null, $mixedvar = null) { return true; }
    public function cron($loan = null, $messages = null) { return true; }
    public function todo($code = null, $intvar = null, $mixedvar = null) { return array(); }
    public function __construct($dof = null) {}
}

/**
 * Mock database object to capture and test get_records_sql calls.
 */
class mock_dof_database {
    public $last_sql = null;
    public $last_params = null;
    public $last_limitfrom = null;
    public $last_limitnum = null;
    public $return_value = array();
    public $throw_exception = false;

    public function get_records_sql($sql, array $params = null, $limitfrom = 0, $limitnum = 0) {
        $this->last_sql = $sql;
        $this->last_params = $params;
        $this->last_limitfrom = $limitfrom;
        $this->last_limitnum = $limitnum;

        if ($this->throw_exception) {
            throw new dml_exception('Mock DML error');
        }

        return $this->return_value;
    }
}

/**
 * Test case for dof_storage::get_records_sql
 */
class block_dof_storage_base_test extends dof_storage_base_testcase {
    private $storage;
    private $mock_db;

    protected function setUp(): void {
        global $DB;
        $this->mock_db = new mock_dof_database();
        $DB = $this->mock_db;
        $this->storage = new test_dof_storage_get_records_sql();
    }

    /**
     * Test get_records_sql with default optional parameters.
     */
    public function test_get_records_sql_default_params() {
        $this->setUp();
        $sql = "SELECT id, name FROM {test_table}";

        $result = $this->storage->get_records_sql($sql);

        $this->assertSame($sql, $this->mock_db->last_sql, "SQL query should match");
        $this->assertSame(null, $this->mock_db->last_params, "Params should be null by default");
        $this->assertSame(0, $this->mock_db->last_limitfrom, "limitfrom should be 0 by default");
        $this->assertSame(0, $this->mock_db->last_limitnum, "limitnum should be 0 by default");
        $this->assertIsArray($result, "Result should be an array");
    }

    /**
     * Test get_records_sql passing explicit params, limitfrom, and limitnum.
     */
    public function test_get_records_sql_with_params_and_limits() {
        $this->setUp();
        $sql = "SELECT id, name FROM {test_table} WHERE status = :status";
        $params = array('status' => 'active');
        $limitfrom = 10;
        $limitnum = 25;

        $result = $this->storage->get_records_sql($sql, $params, $limitfrom, $limitnum);

        $this->assertSame($sql, $this->mock_db->last_sql, "SQL query should match");
        $this->assertSame($params, $this->mock_db->last_params, "Params should match provided array");
        $this->assertSame(10, $this->mock_db->last_limitfrom, "limitfrom should be 10");
        $this->assertSame(25, $this->mock_db->last_limitnum, "limitnum should be 25");
    }

    /**
     * Test get_records_sql returns record dataset properly.
     */
    public function test_get_records_sql_returns_records() {
        $this->setUp();
        $rec1 = (object)array('id' => 1, 'name' => 'Item 1');
        $rec2 = (object)array('id' => 2, 'name' => 'Item 2');
        $expected = array(1 => $rec1, 2 => $rec2);

        $this->mock_db->return_value = $expected;

        $sql = "SELECT id, name FROM {test_table}";
        $result = $this->storage->get_records_sql($sql);

        $this->assertSame(2, count($result), "Should return 2 records");
        $this->assertSame('Item 1', $result[1]->name, "Record 1 name should match");
        $this->assertSame('Item 2', $result[2]->name, "Record 2 name should match");
    }

    /**
     * Test get_records_sql propagates dml_exception if DB query fails.
     */
    public function test_get_records_sql_exception_propagation() {
        $this->setUp();
        $this->mock_db->throw_exception = true;

        $exception_thrown = false;
        try {
            $this->storage->get_records_sql("SELECT invalid FROM {test_table}");
        } catch (dml_exception $e) {
            $exception_thrown = true;
        }

        $this->assertTrue($exception_thrown, "dml_exception should be thrown when DB fails");
    }
}

// CLI test runner block if executed directly via PHP CLI
if (isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $tester = new block_dof_storage_base_test();
    $methods = get_class_methods($tester);
    foreach ($methods as $method) {
        if (strpos($method, 'test_') === 0) {
            $tester->$method();
        }
    }
    echo "ALL TESTS PASSED SUCCESSFULLY.\n";
}
