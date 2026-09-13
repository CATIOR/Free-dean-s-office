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
// the Free Software Foundation, either version 3 of the License.         //
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
 * Unit test for dof_storage::is_exists method in htdocs/blocks/dof/lib/storage_base.php
 *
 * @package    block_dof
 * @subpackage tests
 */

defined('MOODLE_INTERNAL') || define('MOODLE_INTERNAL', true);

// Dummy interface dof_plugin if not already loaded
if (!interface_exists('dof_plugin')) {
    interface dof_plugin {}
}

require_once(__DIR__ . '/../lib/storage.php');
require_once(__DIR__ . '/../lib/storage_base.php');

if (!class_exists('\PHPUnit\Framework\TestCase') && !class_exists('PHPUnit_Framework_TestCase') && !class_exists('advanced_testcase')) {
    class dof_storage_is_exists_testcase_parent {
        public function setUp(): void {}
        public function assertTrue($condition, $message = '') {
            if (!$condition) {
                throw new Exception("Assertion failed (assertTrue): $message");
            }
        }
        public function assertFalse($condition, $message = '') {
            if ($condition) {
                throw new Exception("Assertion failed (assertFalse): $message");
            }
        }
        public function assertEquals($expected, $actual, $message = '') {
            if ($expected !== $actual) {
                throw new Exception("Assertion failed (assertEquals): Expected " . var_export($expected, true) . " got " . var_export($actual, true) . ". $message");
            }
        }
    }
} else if (class_exists('advanced_testcase')) {
    class_alias('advanced_testcase', 'dof_storage_is_exists_testcase_parent');
} else if (class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', 'dof_storage_is_exists_testcase_parent');
} else {
    class_alias('PHPUnit_Framework_TestCase', 'dof_storage_is_exists_testcase_parent');
}

/**
 * Mock DB class to track and respond to record_exists queries.
 */
class test_dof_storage_mock_db {
    public $calls = array();

    public function record_exists($table, array $conditions = array()) {
        $this->calls[] = array('table' => $table, 'conditions' => $conditions);

        if ($table === 'dof_test_default') {
            if (isset($conditions['id']) && $conditions['id'] == 100) {
                return true;
            }
            if (isset($conditions['code']) && $conditions['code'] === 'ABC' && isset($conditions['status']) && $conditions['status'] === 1) {
                return true;
            }
            if (empty($conditions)) {
                return true;
            }
        } elseif ($table === 'dof_test_custom_id') {
            if (isset($conditions['custom_key']) && $conditions['custom_key'] == 200) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Test storage implementation using default idname 'id'.
 */
class test_dof_storage_default extends dof_storage {
    public function tablename() {
        return 'dof_test_default';
    }
}

/**
 * Test storage implementation overriding idname().
 */
class test_dof_storage_custom_id extends dof_storage {
    public function tablename() {
        return 'dof_test_custom_id';
    }

    public function idname() {
        return 'custom_key';
    }
}

class storage_base_is_exists_test extends dof_storage_is_exists_testcase_parent {
    private $mock_db;
    private $storage_default;
    private $storage_custom;

    public function setUp(): void {
        parent::setUp();
        global $DB;
        $this->mock_db = new test_dof_storage_mock_db();
        $DB = $this->mock_db;
        $this->storage_default = new test_dof_storage_default();
        $this->storage_custom = new test_dof_storage_custom_id();
    }

    public function test_is_exists_integer_id() {
        $res = $this->storage_default->is_exists(100);
        $this->assertTrue($res, "is_exists(100) should return true for existing record");
        $last_call = end($this->mock_db->calls);
        $this->assertEquals('dof_test_default', $last_call['table']);
        $this->assertEquals(array('id' => 100), $last_call['conditions']);

        $res_not_found = $this->storage_default->is_exists(999);
        $this->assertFalse($res_not_found, "is_exists(999) should return false for non-existing record");
    }

    public function test_is_exists_string_id() {
        $res = $this->storage_default->is_exists('100');
        $this->assertTrue($res, "is_exists('100') string ID should return true");
        $last_call = end($this->mock_db->calls);
        $this->assertEquals(array('id' => 100), $last_call['conditions']);
    }

    public function test_is_exists_array_conditions() {
        $conds = array('code' => 'ABC', 'status' => 1);
        $res = $this->storage_default->is_exists($conds);
        $this->assertTrue($res, "is_exists(\$conds) should return true");
        $last_call = end($this->mock_db->calls);
        $this->assertEquals($conds, $last_call['conditions']);

        $conds_not_found = array('code' => 'XYZ', 'status' => 0);
        $res_not_found = $this->storage_default->is_exists($conds_not_found);
        $this->assertFalse($res_not_found, "is_exists(\$conds_not_found) should return false");
    }

    public function test_is_exists_empty_array() {
        $res = $this->storage_default->is_exists(array());
        $this->assertTrue($res, "is_exists([]) should pass empty array conditions");
        $last_call = end($this->mock_db->calls);
        $this->assertEquals(array(), $last_call['conditions']);
    }

    public function test_is_exists_custom_idname() {
        $res = $this->storage_custom->is_exists(200);
        $this->assertTrue($res, "is_exists(200) on custom idname storage should return true");
        $last_call = end($this->mock_db->calls);
        $this->assertEquals('dof_test_custom_id', $last_call['table']);
        $this->assertEquals(array('custom_key' => 200), $last_call['conditions']);

        $res_not_found = $this->storage_custom->is_exists(500);
        $this->assertFalse($res_not_found, "is_exists(500) on custom idname storage should return false");
    }
}

// Allow direct execution via PHP CLI if run standalone
if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    $test = new storage_base_is_exists_test();
    $methods = get_class_methods($test);
    $count = 0;
    foreach ($methods as $method) {
        if (strpos($method, 'test_') === 0) {
            $test->setUp();
            $test->$method();
            $count++;
        }
    }
    echo "OK: $count test methods passed successfully!\n";
}
