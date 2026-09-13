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
 * Тестирование метода storage_base get_record
 *
 * @package    block_dof
 * @subpackage tests
 */

if (!defined('IGNORE_MISSING')) {
    define('IGNORE_MISSING', 0);
}
if (!defined('IGNORE_MULTIPLE')) {
    define('IGNORE_MULTIPLE', 1);
}
if (!defined('MUST_EXIST')) {
    define('MUST_EXIST', 2);
}
if (!defined('DEBUG_DEVELOPER')) {
    define('DEBUG_DEVELOPER', 32767);
}
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

if (!function_exists('dof_debugging')) {
    function dof_debugging($message = '', $level = 0) {
        return false;
    }
}

if (!interface_exists('dof_plugin_storage')) {
    interface dof_plugin_storage {}
}

if (!class_exists('dml_exception')) {
    class dml_exception extends Exception {}
}

require_once(__DIR__ . '/../lib/storage_base.php');

if (!class_exists('mock_dof_database')) {
    class mock_dof_database {
        public $queries = [];

        public function get_record_select($table, $select, $params = null, $fields = '*', $strictness = IGNORE_MISSING) {
            $this->queries[] = [
                'table' => $table,
                'select' => $select,
                'params' => $params,
                'fields' => $fields,
                'strictness' => $strictness,
            ];

            if ($strictness === MUST_EXIST && $select === 'non_existent = ?') {
                throw new dml_exception('recordnotfound');
            }

            if ($select === 'non_existent = ?') {
                return false;
            }

            $record = new stdClass();
            $record->id = 42;
            $record->status = 'active';
            $record->type = 'test';
            return $record;
        }

        public function get_columns($table) {
            return [
                'id' => (object)['meta_type' => 'I'],
                'status' => (object)['meta_type' => 'C'],
                'type' => (object)['meta_type' => 'C'],
                'code' => (object)['meta_type' => 'C'],
            ];
        }
    }
}

if (!class_exists('concrete_dof_storage')) {
    class concrete_dof_storage extends dof_storage {
        public function tablename() {
            return 'dof_test_table';
        }
    }
}

/**
 * Класс модульного тестирования метода get_record()
 */
class test_storage_base_get_record {
    private $storage;
    private $db;

    public function __construct() {
        global $DB;
        $this->db = new mock_dof_database();
        $DB = $this->db;
        $this->storage = new concrete_dof_storage();
    }

    public function run() {
        $this->test_get_record_simple_condition();
        $this->test_get_record_multiple_conditions();
        $this->test_get_record_custom_fields();
        $this->test_get_record_array_condition_where_clause();
        $this->test_get_record_strictness_must_exist();
        $this->test_get_record_not_found_ignore_missing();

        echo "All storage_base get_record tests passed successfully!\n";
    }

    public function test_get_record_simple_condition() {
        $result = $this->storage->get_record(['status' => 'active']);

        $last_query = end($this->db->queries);
        assert($result !== false, 'Record should be found');
        assert($result->id === 42, 'ID should match');
        assert($last_query['table'] === 'dof_test_table', 'Table name should match');
        assert($last_query['select'] === 'status = ?', 'SQL select clause should match');
        assert($last_query['params'] === ['active'], 'Params should match');
        assert($last_query['fields'] === '*', 'Default fields should be *');
        assert($last_query['strictness'] === IGNORE_MISSING, 'Default strictness should be IGNORE_MISSING');
    }

    public function test_get_record_multiple_conditions() {
        $result = $this->storage->get_record(['status' => 'active', 'type' => 'test']);

        $last_query = end($this->db->queries);
        assert($result !== false, 'Record should be found');
        assert($last_query['select'] === 'status = ? AND type = ?', 'Select should combine conditions with AND');
        assert($last_query['params'] === ['active', 'test'], 'Params array should contain both values');
    }

    public function test_get_record_custom_fields() {
        $result = $this->storage->get_record(['status' => 'active'], 'id, status');

        $last_query = end($this->db->queries);
        assert($last_query['fields'] === 'id, status', 'Custom fields parameter should be passed to DB');
    }

    public function test_get_record_array_condition_where_clause() {
        $result = $this->storage->get_record(['status' => ['active', 'pending']]);

        $last_query = end($this->db->queries);
        assert($last_query['select'] === '(status = ? OR status = ?)', 'Array values should generate OR condition in where_clause');
        assert($last_query['params'] === ['active', 'pending'], 'Params should contain both array items');
    }

    public function test_get_record_strictness_must_exist() {
        $exception_thrown = false;
        try {
            $this->storage->get_record(['non_existent' => 123], '*', MUST_EXIST);
        } catch (dml_exception $e) {
            $exception_thrown = true;
        }

        assert($exception_thrown === true, 'dml_exception should be thrown when strictness is MUST_EXIST and record is not found');
    }

    public function test_get_record_not_found_ignore_missing() {
        $result = $this->storage->get_record(['non_existent' => 123], '*', IGNORE_MISSING);

        assert($result === false, 'get_record should return false when record is missing and strictness is IGNORE_MISSING');
    }
}

// Выполняем тесты только при прямом запуске файла CLI
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    $test = new test_storage_base_get_record();
    $test->run();
}
