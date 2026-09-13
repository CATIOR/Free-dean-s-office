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
 * Unit tests for dof_storage::is_exists_sql in storage_base.php.
 *
 * @package    block_dof
 * @category   test
 */

defined('MOODLE_INTERNAL') || define('MOODLE_INTERNAL', true);

// Ensure dof_plugin_storage and dof_storage interfaces/classes are loaded.
$storagebase = __DIR__ . '/../lib/storage_base.php';
$storageiface = __DIR__ . '/../lib/storage.php';
$pluginiface = __DIR__ . '/../lib/plugin.php';

if (file_exists($pluginiface) && !interface_exists('dof_plugin')) {
    require_once($pluginiface);
}
if (file_exists($storageiface) && !interface_exists('dof_plugin_storage')) {
    require_once($storageiface);
}
if (file_exists($storagebase) && !class_exists('dof_storage')) {
    require_once($storagebase);
}

if (!class_exists('dml_exception')) {
    class dml_exception extends Exception {}
}

/**
 * Dummy concrete subclass of dof_storage for testing abstract class dof_storage.
 */
class test_dof_storage_concrete extends dof_storage {
    public function __construct($dof = null) {
        // Dummy constructor
    }
    public function tablename() { return 'test_table'; }
    public function version() { return '2026010100'; }
    public function compat_dof() { return 'aquarium'; }
    public function compat() { return 'paradusefish'; }
    public function type() { return 'storage'; }
    public function code() { return 'test_storage'; }
    public function need_plugins() { return array(); }
    public function list_catch_events() { return array(); }
    public function is_cron() { return false; }
    public function is_access($do, $objid = NULL, $userid = NULL) { return true; }
    public function catch_event($plugintype, $plugincode, $eventcode, $intvar, $mixedvar) { return true; }
    public function cron($loan, $messages) { return true; }
    public function todo($code, $intvar, $mixedvar) { return true; }
}

/**
 * Mock database driver class for testing global $DB calls.
 */
class test_mock_moodle_database {
    public $last_sql;
    public $last_params;
    public $return_value = true;
    public $should_throw_exception = false;

    public function record_exists_sql($sql, array $params = null) {
        $this->last_sql = $sql;
        $this->last_params = $params;

        if ($this->should_throw_exception) {
            throw new dml_exception('Database query error');
        }

        return (bool)$this->return_value;
    }
}

// Base testcase class setup for compatibility with Moodle PHPUnit & standalone environment.
if (class_exists('advanced_testcase')) {
    abstract class block_dof_storage_base_test_parent extends advanced_testcase {}
} elseif (class_exists('PHPUnit\Framework\TestCase')) {
    abstract class block_dof_storage_base_test_parent extends PHPUnit\Framework\TestCase {}
} elseif (class_exists('PHPUnit_Framework_TestCase')) {
    abstract class block_dof_storage_base_test_parent extends PHPUnit_Framework_TestCase {}
} else {
    abstract class block_dof_storage_base_test_parent {
        public function assertTrue($condition, $message = '') {
            if (!$condition) {
                throw new Exception("Assertion failed: expected true. " . $message);
            }
        }
        public function assertFalse($condition, $message = '') {
            if ($condition) {
                throw new Exception("Assertion failed: expected false. " . $message);
            }
        }
        public function assertEquals($expected, $actual, $message = '') {
            if ($expected !== $actual) {
                throw new Exception("Assertion failed: expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ". " . $message);
            }
        }
        public function assertNull($actual, $message = '') {
            if ($actual !== null) {
                throw new Exception("Assertion failed: expected null, got " . var_export($actual, true) . ". " . $message);
            }
        }
    }
}

/**
 * Test case for dof_storage::is_exists_sql.
 */
class block_dof_storage_base_testcase extends block_dof_storage_base_test_parent {
    protected $storage;
    protected $mock_db;
    protected $original_db;

    protected function setUp(): void {
        if (method_exists(parent::class, 'setUp')) {
            parent::setUp();
        }
        global $DB;
        $this->original_db = isset($DB) ? $DB : null;
        $this->mock_db = new test_mock_moodle_database();
        $DB = $this->mock_db;

        $this->storage = new test_dof_storage_concrete();
    }

    protected function tearDown(): void {
        global $DB;
        $DB = $this->original_db;
        if (method_exists(parent::class, 'tearDown')) {
            parent::tearDown();
        }
    }

    /**
     * Test that is_exists_sql returns true when a matching record exists.
     */
    public function test_is_exists_sql_returns_true_when_record_exists() {
        $this->mock_db->return_value = true;

        $sql = "SELECT * FROM {test_table} WHERE status = ?";
        $params = ['active'];

        $result = $this->storage->is_exists_sql($sql, $params);

        $this->assertTrue($result, 'is_exists_sql should return true when record exists.');
        $this->assertEquals($sql, $this->mock_db->last_sql, 'SQL string passed to DB should match.');
        $this->assertEquals($params, $this->mock_db->last_params, 'Params passed to DB should match.');
    }

    /**
     * Test that is_exists_sql returns false when no matching record exists.
     */
    public function test_is_exists_sql_returns_false_when_record_not_found() {
        $this->mock_db->return_value = false;

        $sql = "SELECT * FROM {test_table} WHERE id = ?";
        $params = [999];

        $result = $this->storage->is_exists_sql($sql, $params);

        $this->assertFalse($result, 'is_exists_sql should return false when record does not exist.');
        $this->assertEquals($sql, $this->mock_db->last_sql, 'SQL string passed to DB should match.');
        $this->assertEquals($params, $this->mock_db->last_params, 'Params passed to DB should match.');
    }

    /**
     * Test that is_exists_sql handles null parameters array default argument.
     */
    public function test_is_exists_sql_with_null_params() {
        $this->mock_db->return_value = true;

        $sql = "SELECT * FROM {test_table}";

        $result = $this->storage->is_exists_sql($sql);

        $this->assertTrue($result, 'is_exists_sql should return true with default null params.');
        $this->assertEquals($sql, $this->mock_db->last_sql, 'SQL string passed to DB should match.');
        $this->assertNull($this->mock_db->last_params, 'Params passed to DB should be null.');
    }

    /**
     * Test that is_exists_sql propagates dml_exception thrown by $DB->record_exists_sql.
     */
    public function test_is_exists_sql_propagates_dml_exception() {
        $this->mock_db->should_throw_exception = true;

        $sql = "INVALID SQL QUERY";
        $exception_thrown = false;

        try {
            $this->storage->is_exists_sql($sql);
        } catch (dml_exception $e) {
            $exception_thrown = true;
        }

        $this->assertTrue($exception_thrown, 'is_exists_sql should propagate dml_exception from DB driver.');
    }

    /**
     * Runner helper when executed directly via PHP CLI.
     */
    public static function run_all_tests() {
        $testcase = new static();
        $methods = get_class_methods($testcase);
        $passed = 0;
        $failed = 0;

        echo "Running block_dof_storage_base_testcase...\n";

        foreach ($methods as $method) {
            if (strpos($method, 'test_') === 0) {
                try {
                    $testcase->setUp();
                    $testcase->$method();
                    $testcase->tearDown();
                    echo "  [PASS] {$method}\n";
                    $passed++;
                } catch (Throwable $e) {
                    echo "  [FAIL] {$method}: " . $e->getMessage() . "\n";
                    $failed++;
                }
            }
        }

        echo "Tests complete: {$passed} passed, {$failed} failed.\n";
        return $failed === 0;
    }
}

// If run directly from command line, execute tests.
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $success = block_dof_storage_base_testcase::run_all_tests();
    exit($success ? 0 : 1);
}
