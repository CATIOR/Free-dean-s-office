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

use PHPUnit\Framework\TestCase;

// Define Moodle/DOF constants if not already defined.
if (!defined('SQL_PARAMS_QM')) {
    define('SQL_PARAMS_QM', 2);
}
if (!defined('SQL_PARAMS_NAMED')) {
    define('SQL_PARAMS_NAMED', 1);
}
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
    define('DEBUG_DEVELOPER', 300);
}

if (!function_exists('debugging')) {
    function debugging($message = '', $level = 0) {
        return false;
    }
}

if (!function_exists('dof_debugging')) {
    function dof_debugging($message = '', $level = 0) {
        return false;
    }
}

if (!class_exists('dml_exception')) {
    class dml_exception extends Exception {}
}

if (!class_exists('dof_exception_dml')) {
    class dof_exception_dml extends dml_exception {}
}

require_once __DIR__ . '/../lib/plugin.php';
require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/storage_base.php';

/**
 * Dummy database class representing Moodle's $DB for mocking in tests.
 */
class mock_moodle_database {
    public function get_records_select($table, $select, array $params = null, $sort = '', $fields = '*', $limitfrom = 0, $limitnum = 0) {
        return array();
    }
}

/**
 * Concrete implementation of abstract dof_storage for testing purposes.
 */
class testable_dof_storage extends dof_storage {
    private $tablename;

    public function __construct($tablename = 'test_table', $dof = null) {
        $this->tablename = $tablename;
        $this->dof = $dof;
    }

    public function tablename() {
        return $this->tablename;
    }

    public function install() { return true; }
    public function upgrade($oldversion) { return true; }
    public function version() { return '2023010100'; }
    public function compat_dof() { return '2.0'; }
    public function compat() { return '2.0'; }
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
 * Test class for dof_storage::get_records method in storage_base.php.
 */
class storage_base_test extends TestCase {

    private $storage;
    private $dbBackup;

    protected function setUp(): void {
        parent::setUp();
        global $DB;
        $this->dbBackup = isset($DB) ? $DB : null;
        $this->storage = new testable_dof_storage('test_table');
    }

    protected function tearDown(): void {
        global $DB;
        $DB = $this->dbBackup;
        parent::tearDown();
    }

    /**
     * Test get_records with default arguments and empty conditions.
     */
    public function test_get_records_default_parameters() {
        global $DB;

        $expectedResult = array(
            1 => (object) array('id' => 1, 'name' => 'Record 1'),
            2 => (object) array('id' => 2, 'name' => 'Record 2')
        );

        $DB = $this->createMock(mock_moodle_database::class);
        $DB->expects($this->once())
           ->method('get_records_select')
           ->with('test_table', '', array(), '', '*', 0, 0)
           ->willReturn($expectedResult);

        $result = $this->storage->get_records();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records with a single scalar condition.
     */
    public function test_get_records_with_single_scalar_condition() {
        global $DB;

        $conditions = array('status' => 'active');
        $expectedResult = array(
            1 => (object) array('id' => 1, 'status' => 'active')
        );

        $DB = $this->createMock(mock_moodle_database::class);
        $DB->expects($this->once())
           ->method('get_records_select')
           ->with('test_table', 'status = ?', array('active'), '', '*', 0, 0)
           ->willReturn($expectedResult);

        $result = $this->storage->get_records($conditions);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records with multiple scalar conditions combined with AND.
     */
    public function test_get_records_with_multiple_conditions() {
        global $DB;

        $conditions = array(
            'status' => 'active',
            'type' => 'student'
        );
        $expectedResult = array(
            5 => (object) array('id' => 5, 'status' => 'active', 'type' => 'student')
        );

        $DB = $this->createMock(mock_moodle_database::class);
        $DB->expects($this->once())
           ->method('get_records_select')
           ->with('test_table', 'status = ? AND type = ?', array('active', 'student'), '', '*', 0, 0)
           ->willReturn($expectedResult);

        $result = $this->storage->get_records($conditions);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records with array condition values (list of values for a field).
     */
    public function test_get_records_with_array_condition_values() {
        global $DB;

        $conditions = array('status' => array('active', 'pending'));
        $expectedResult = array(
            1 => (object) array('id' => 1, 'status' => 'active'),
            2 => (object) array('id' => 2, 'status' => 'pending')
        );

        $DB = $this->createMock(mock_moodle_database::class);
        $DB->expects($this->once())
           ->method('get_records_select')
           ->with('test_table', '(status = ? OR status = ?)', array('active', 'pending'), '', '*', 0, 0)
           ->willReturn($expectedResult);

        $result = $this->storage->get_records($conditions);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records with NULL condition value (IS NULL check).
     */
    public function test_get_records_with_null_condition_value() {
        global $DB;

        $conditions = array('deleted_at' => null);
        $expectedResult = array(
            3 => (object) array('id' => 3, 'deleted_at' => null)
        );

        $DB = $this->createMock(mock_moodle_database::class);
        $DB->expects($this->once())
           ->method('get_records_select')
           ->with('test_table', 'deleted_at IS NULL', array(), '', '*', 0, 0)
           ->willReturn($expectedResult);

        $result = $this->storage->get_records($conditions);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records passing custom sort, fields, limitfrom, and limitnum parameters.
     */
    public function test_get_records_with_sort_fields_and_limits() {
        global $DB;

        $conditions = array('category' => 'books');
        $sort = 'name ASC';
        $fields = 'id, name, price';
        $limitfrom = 10;
        $limitnum = 20;

        $expectedResult = array(
            11 => (object) array('id' => 11, 'name' => 'Book 1', 'price' => 100)
        );

        $DB = $this->createMock(mock_moodle_database::class);
        $DB->expects($this->once())
           ->method('get_records_select')
           ->with('test_table', 'category = ?', array('books'), 'name ASC', 'id, name, price', 10, 20)
           ->willReturn($expectedResult);

        $result = $this->storage->get_records($conditions, $sort, $fields, $limitfrom, $limitnum);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records returning an empty array when no matching records are found.
     */
    public function test_get_records_returns_empty_array_when_no_records() {
        global $DB;

        $conditions = array('status' => 'nonexistent');

        $DB = $this->createMock(mock_moodle_database::class);
        $DB->expects($this->once())
           ->method('get_records_select')
           ->with('test_table', 'status = ?', array('nonexistent'), '', '*', 0, 0)
           ->willReturn(array());

        $result = $this->storage->get_records($conditions);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get_records propagating dml_exception when database query fails.
     */
    public function test_get_records_propagates_exception() {
        global $DB;

        $this->expectException(dml_exception::class);
        $this->expectExceptionMessage('Database error');

        $DB = $this->createMock(mock_moodle_database::class);
        $DB->expects($this->once())
           ->method('get_records_select')
           ->willThrowException(new dml_exception('Database error'));

        $this->storage->get_records(array('id' => 999));
    }
}
