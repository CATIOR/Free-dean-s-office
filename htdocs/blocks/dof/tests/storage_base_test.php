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
 * Unit tests for dof_storage::get_field method in storage_base.php
 *
 * @package    block_dof
 * @subpackage storage
 */

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
    define('DEBUG_DEVELOPER', 1);
}

// Dummy interface stubs if not loaded
if (!interface_exists('dof_plugin')) {
    interface dof_plugin {}
}
if (!interface_exists('dof_plugin_storage')) {
    interface dof_plugin_storage extends dof_plugin {}
}

require_once(__DIR__ . '/../lib/storage_base.php');

/**
 * Mock DB class for testing dof_storage::get_field
 */
class mock_dof_database_storage_base {
    public $last_table;
    public $last_return;
    public $last_conditions;
    public $last_strictness;
    public $return_value = 'default_mock_value';

    public function get_field($table, $return, $conditions, $strictness) {
        $this->last_table = $table;
        $this->last_return = $return;
        $this->last_conditions = $conditions;
        $this->last_strictness = $strictness;
        return $this->return_value;
    }
}

/**
 * Concrete class extending dof_storage for testing
 */
class test_dof_storage_base extends dof_storage {
    public function tablename() {
        return 'dof_test_table';
    }
}

// Fallback PHPUnit base TestCase definition if PHPUnit/Moodle test environment is not loaded
if (!class_exists('advanced_testcase') && !class_exists('PHPUnit\Framework\TestCase') && !class_exists('PHPUnit_Framework_TestCase')) {
    abstract class basic_phpunit_fallback_testcase {
        public function setUp(): void {}
        public function assertEquals($expected, $actual, $message = '') {
            if ($expected !== $actual) {
                $e = var_export($expected, true);
                $a = var_export($actual, true);
                throw new \Exception("Assertion failed: {$message}\nExpected: {$e}\nActual: {$a}");
            }
        }
    }
    class_alias('basic_phpunit_fallback_testcase', 'PHPUnit\Framework\TestCase');
}

// Determine base PHPUnit test class
if (class_exists('advanced_testcase')) {
    abstract class block_dof_storage_base_testcase_parent extends advanced_testcase {}
} elseif (class_exists('PHPUnit\Framework\TestCase')) {
    abstract class block_dof_storage_base_testcase_parent extends \PHPUnit\Framework\TestCase {}
} else {
    abstract class block_dof_storage_base_testcase_parent extends \PHPUnit_Framework_TestCase {}
}

/**
 * PHPUnit test case for storage_base get_field
 */
class block_dof_storage_base_test extends block_dof_storage_base_testcase_parent {

    private $storage;
    private $db;

    public function setUp(): void {
        parent::setUp();
        global $DB;
        $this->db = new mock_dof_database_storage_base();
        $DB = $this->db;
        $this->storage = new test_dof_storage_base();
    }

    /**
     * Test get_field when scalar/integer id is passed as conditions.
     */
    public function test_get_field_with_scalar_id() {
        $this->setUp();
        $this->db->return_value = 'john_doe';
        $res = $this->storage->get_field(42, 'username');

        $this->assertEquals('john_doe', $res, 'Return value should match mocked return value');
        $this->assertEquals('dof_test_table', $this->db->last_table, 'Table name passed to DB');
        $this->assertEquals('username', $this->db->last_return, 'Return field passed to DB');
        $this->assertEquals(array('id' => 42), $this->db->last_conditions, 'Scalar conditions should convert to id array');
        $this->assertEquals(IGNORE_MISSING, $this->db->last_strictness, 'Default strictness should be IGNORE_MISSING');
    }

    /**
     * Test get_field when array of conditions is passed.
     */
    public function test_get_field_with_array_conditions() {
        $this->setUp();
        $this->db->return_value = 100;
        $conditions = array('code' => 'MATH101', 'status' => 'active');
        $res = $this->storage->get_field($conditions, 'credits');

        $this->assertEquals(100, $res, 'Return value should match mocked return value');
        $this->assertEquals('dof_test_table', $this->db->last_table, 'Table name passed to DB');
        $this->assertEquals('credits', $this->db->last_return, 'Return field passed to DB');
        $this->assertEquals($conditions, $this->db->last_conditions, 'Array conditions should pass unchanged');
        $this->assertEquals(IGNORE_MISSING, $this->db->last_strictness, 'Default strictness should be IGNORE_MISSING');
    }

    /**
     * Test get_field with explicit strictness parameters (MUST_EXIST, IGNORE_MULTIPLE).
     */
    public function test_get_field_with_custom_strictness() {
        $this->setUp();
        $this->db->return_value = 'custom_strictness_val';

        $res1 = $this->storage->get_field(10, 'email', MUST_EXIST);
        $this->assertEquals(MUST_EXIST, $this->db->last_strictness, 'Strictness MUST_EXIST passed to DB');

        $res2 = $this->storage->get_field(array('type' => 'student'), 'first_name', IGNORE_MULTIPLE);
        $this->assertEquals(IGNORE_MULTIPLE, $this->db->last_strictness, 'Strictness IGNORE_MULTIPLE passed to DB');
    }

    /**
     * Test get_field returns various mock values correctly (false, null, boolean, object).
     */
    public function test_get_field_mock_return_values() {
        $this->setUp();
        // Test false return value
        $this->db->return_value = false;
        $res = $this->storage->get_field(999, 'missing_field');
        $this->assertEquals(false, $res, 'Should return false when DB returns false');

        // Test null return value
        $this->db->return_value = null;
        $res = $this->storage->get_field(1, 'nullable_field');
        $this->assertEquals(null, $res, 'Should return null when DB returns null');
    }
}

// CLI direct execution runner (when file is called directly via php CLI rather than PHPUnit runner)
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    echo "Running block_dof_storage_base_test unit tests...\n";
    $test = new block_dof_storage_base_test();
    $test->test_get_field_with_scalar_id();
    $test->test_get_field_with_array_conditions();
    $test->test_get_field_with_custom_strictness();
    $test->test_get_field_mock_return_values();
    echo "All block_dof_storage_base_test tests passed successfully!\n";
}
