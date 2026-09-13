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
 * Unit tests for dof_storage::is_exists_select method.
 */

require_once(__DIR__ . '/../lib/plugin.php');
require_once(__DIR__ . '/../lib/storage.php');
require_once(__DIR__ . '/../lib/storage_base.php');

/**
 * Mock DB class for testing storage_base methods.
 */
class mock_dof_storage_test_db {
    public $calls = array();
    public $return_value = true;

    public function record_exists_select($table, $select, array $params = null) {
        $this->calls[] = array(
            'table'  => $table,
            'select' => $select,
            'params' => $params
        );
        return $this->return_value;
    }
}

/**
 * Dummy concrete class extending dof_storage to test method implementations.
 */
class test_dof_storage_impl extends dof_storage {
    public function tablename() {
        return 'dof_test_table';
    }

    public function install() { return true; }
    public function upgrade($oldversion) { return true; }
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
    public function __construct($dof = null) {}
}

// Ensure base TestCase compatibility if PHPUnit is loaded or standalone CLI execution.
if (!class_exists('PHPUnit\Framework\TestCase') && !class_exists('PHPUnit_Framework_TestCase')) {
    abstract class dof_storage_test_base_case {
        protected function setUp(): void {}
        protected function tearDown(): void {}
        public function assertTrue($condition, $message = '') {
            if (!$condition) {
                throw new Exception($message ?: 'Failed asserting that condition is true.');
            }
        }
        public function assertFalse($condition, $message = '') {
            if ($condition) {
                throw new Exception($message ?: 'Failed asserting that condition is false.');
            }
        }
        public function assertEquals($expected, $actual, $message = '') {
            if ($expected !== $actual) {
                throw new Exception($message ?: 'Failed asserting that ' . print_r($actual, true) . ' matches expected ' . print_r($expected, true));
            }
        }
        public function assertCount($expectedCount, $haystack, $message = '') {
            if (count($haystack) !== $expectedCount) {
                throw new Exception($message ?: 'Failed asserting count ' . count($haystack) . ' matches expected count ' . $expectedCount);
            }
        }
        public function assertNull($actual, $message = '') {
            if ($actual !== null) {
                throw new Exception($message ?: 'Failed asserting that value is null.');
            }
        }
    }
    class_alias('dof_storage_test_base_case', 'PHPUnit\Framework\TestCase');
}

/**
 * Test suite for dof_storage::is_exists_select.
 */
class storage_base_test extends \PHPUnit\Framework\TestCase {
    private $original_db;

    protected function setUp(): void {
        parent::setUp();
        if (isset($GLOBALS['DB'])) {
            $this->original_db = $GLOBALS['DB'];
        }
    }

    protected function tearDown(): void {
        if ($this->original_db !== null) {
            $GLOBALS['DB'] = $this->original_db;
        } else {
            unset($GLOBALS['DB']);
        }
        parent::tearDown();
    }

    public function test_is_exists_select_returns_true_when_record_exists() {
        $mock_db = new mock_dof_storage_test_db();
        $mock_db->return_value = true;
        $GLOBALS['DB'] = $mock_db;

        $storage = new test_dof_storage_impl();
        $result = $storage->is_exists_select("status = ?", array('active'));

        $this->assertTrue($result, "is_exists_select should return true when record exists");
    }

    public function test_is_exists_select_returns_false_when_record_does_not_exist() {
        $mock_db = new mock_dof_storage_test_db();
        $mock_db->return_value = false;
        $GLOBALS['DB'] = $mock_db;

        $storage = new test_dof_storage_impl();
        $result = $storage->is_exists_select("status = ?", array('inactive'));

        $this->assertFalse($result, "is_exists_select should return false when record does not exist");
    }

    public function test_is_exists_select_with_null_params() {
        $mock_db = new mock_dof_storage_test_db();
        $mock_db->return_value = true;
        $GLOBALS['DB'] = $mock_db;

        $storage = new test_dof_storage_impl();
        $result = $storage->is_exists_select("1 = 1");

        $this->assertTrue($result, "is_exists_select should support null default params");
        $this->assertCount(1, $mock_db->calls);
        $this->assertNull($mock_db->calls[0]['params'], "params passed to DB should be null");
    }

    public function test_is_exists_select_passes_arguments_correctly() {
        $mock_db = new mock_dof_storage_test_db();
        $GLOBALS['DB'] = $mock_db;

        $storage = new test_dof_storage_impl();
        $select = "name = :name AND age > :age";
        $params = array('name' => 'John', 'age' => 20);

        $storage->is_exists_select($select, $params);

        $this->assertCount(1, $mock_db->calls);
        $call = $mock_db->calls[0];
        $this->assertEquals('dof_test_table', $call['table'], "tablename should match storage tablename");
        $this->assertEquals($select, $call['select'], "select query fragment should match");
        $this->assertEquals($params, $call['params'], "params array should match");
    }

    public function runAllTests() {
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (str_starts_with($method, 'test_')) {
                $this->setUp();
                try {
                    $this->$method();
                } finally {
                    $this->tearDown();
                }
            }
        }
        echo "All storage_base_test tests passed successfully!\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $test = new storage_base_test();
    $test->runAllTests();
}
