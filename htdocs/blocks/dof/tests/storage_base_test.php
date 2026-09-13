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
 * Тестирование метода dof_storage::count_records_select
 *
 * @package    block_dof
 * @subpackage test
 */

defined('MOODLE_INTERNAL') || define('MOODLE_INTERNAL', true);

require_once(__DIR__ . '/../lib/plugin.php');
require_once(__DIR__ . '/../lib/storage.php');
require_once(__DIR__ . '/../lib/storage_base.php');

/**
 * Заглушка для глобального объекта БД $DB.
 */
class mock_dof_database {
    public $last_tablename;
    public $last_select;
    public $last_params;
    public $last_countitem;
    public $return_value = 0;

    public function count_records_select($table, $select, array $params = null, $countitem = "COUNT('x')") {
        $this->last_tablename = $table;
        $this->last_select    = $select;
        $this->last_params    = $params;
        $this->last_countitem = $countitem;

        return $this->return_value;
    }
}

/**
 * Тестовый класс-наследник dof_storage для проверки функционала.
 */
class test_dof_storage_impl extends dof_storage {
    public function tablename() {
        return 'dof_test_table';
    }

    // Заглушки для методов dof_plugin интерфейса
    public function install() { return true; }
    public function upgrade($oldversion) { return true; }
    public function version() { return '1.0'; }
    public function compat_dof() { return '1.0'; }
    public function compat() { return '1.0'; }
    public function type() { return 'storage'; }
    public function code() { return 'test_table'; }
    public function need_plugins() { return array(); }
    public function list_catch_events() { return array(); }
    public function is_cron() { return false; }
    public function is_access($do, $objid = NULL, $userid = NULL) { return true; }
    public function catch_event($plugintype, $plugincode, $eventcode, $intvar, $mixedvar) { return true; }
    public function cron($loan, $messages) { return true; }
    public function todo($code, $intvar, $mixedvar) { return true; }
    public function __construct($dof = null) { $this->dof = $dof; }
}

// Определение базового тестового класса для совместимости с PHPUnit / standalone окружением
if (!class_exists('PHPUnit_Framework_TestCase') && !class_exists('PHPUnit\Framework\TestCase')) {
    if (class_exists('basic_testcase')) {
        abstract class block_dof_storage_base_testcase extends basic_testcase {}
    } else {
        abstract class block_dof_storage_base_testcase {
            public function assertEquals($expected, $actual, $message = '') {
                if ($expected !== $actual) {
                    $expectedStr = var_export($expected, true);
                    $actualStr   = var_export($actual, true);
                    throw new Exception($message . " Expected: {$expectedStr}, got: {$actualStr}");
                }
            }
        }
    }
} else {
    if (class_exists('PHPUnit\Framework\TestCase')) {
        abstract class block_dof_storage_base_testcase extends \PHPUnit\Framework\TestCase {}
    } else {
        abstract class block_dof_storage_base_testcase extends \PHPUnit_Framework_TestCase {}
    }
}

/**
 * Набор модульных тестов для проверки dof_storage::count_records_select.
 */
class storage_base_test extends block_dof_storage_base_testcase {

    public function test_count_records_select_default_params() {
        global $DB;
        $mock_db = new mock_dof_database();
        $DB = $mock_db;
        $storage = new test_dof_storage_impl();

        $mock_db->return_value = 5;
        $result = $storage->count_records_select('status = 1');

        $this->assertEquals(5, $result, 'Return value mismatch');
        $this->assertEquals('dof_test_table', $mock_db->last_tablename, 'Tablename mismatch');
        $this->assertEquals('status = 1', $mock_db->last_select, 'Select query mismatch');
        $this->assertEquals(null, $mock_db->last_params, 'Params mismatch');
        $this->assertEquals("COUNT('x')", $mock_db->last_countitem, 'Default countitem mismatch');
    }

    public function test_count_records_select_with_params() {
        global $DB;
        $mock_db = new mock_dof_database();
        $DB = $mock_db;
        $storage = new test_dof_storage_impl();

        $mock_db->return_value = 12;
        $params = array('type' => 'student', 'active' => 1);
        $result = $storage->count_records_select('type = :type AND active = :active', $params);

        $this->assertEquals(12, $result, 'Return value mismatch');
        $this->assertEquals('dof_test_table', $mock_db->last_tablename, 'Tablename mismatch');
        $this->assertEquals('type = :type AND active = :active', $mock_db->last_select, 'Select query mismatch');
        $this->assertEquals($params, $mock_db->last_params, 'Params mismatch');
        $this->assertEquals("COUNT('x')", $mock_db->last_countitem, 'Default countitem mismatch');
    }

    public function test_count_records_select_custom_countitem() {
        global $DB;
        $mock_db = new mock_dof_database();
        $DB = $mock_db;
        $storage = new test_dof_storage_impl();

        $mock_db->return_value = 3;
        $params = array(10);
        $result = $storage->count_records_select('departmentid = ?', $params, 'COUNT(DISTINCT userid)');

        $this->assertEquals(3, $result, 'Return value mismatch');
        $this->assertEquals('dof_test_table', $mock_db->last_tablename, 'Tablename mismatch');
        $this->assertEquals('departmentid = ?', $mock_db->last_select, 'Select query mismatch');
        $this->assertEquals($params, $mock_db->last_params, 'Params mismatch');
        $this->assertEquals('COUNT(DISTINCT userid)', $mock_db->last_countitem, 'Custom countitem mismatch');
    }

    public function test_count_records_select_zero_count() {
        global $DB;
        $mock_db = new mock_dof_database();
        $DB = $mock_db;
        $storage = new test_dof_storage_impl();

        $mock_db->return_value = 0;
        $result = $storage->count_records_select('id IS NULL');

        $this->assertEquals(0, $result, 'Return value mismatch');
        $this->assertEquals('dof_test_table', $mock_db->last_tablename, 'Tablename mismatch');
        $this->assertEquals('id IS NULL', $mock_db->last_select, 'Select query mismatch');
    }

    public static function run_all() {
        $original_db = $GLOBALS['DB'] ?? null;
        $tester = new self();
        try {
            $tester->test_count_records_select_default_params();
            echo "[PASS] test_count_records_select_default_params\n";

            $tester->test_count_records_select_with_params();
            echo "[PASS] test_count_records_select_with_params\n";

            $tester->test_count_records_select_custom_countitem();
            echo "[PASS] test_count_records_select_custom_countitem\n";

            $tester->test_count_records_select_zero_count();
            echo "[PASS] test_count_records_select_zero_count\n";

            echo "All dof_storage::count_records_select tests passed successfully!\n";
        } finally {
            $GLOBALS['DB'] = $original_db;
        }
    }
}

// Запуск тестов при прямом вызове из CLI
if (isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    storage_base_test::run_all();
}
