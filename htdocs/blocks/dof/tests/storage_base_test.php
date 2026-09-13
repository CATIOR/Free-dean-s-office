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
    define('DEBUG_DEVELOPER', 32767);
}
if (!defined('DEBUG_NORMAL')) {
    define('DEBUG_NORMAL', 2047);
}

$GLOBALS['dof_debugging_calls'] = array();

if (!function_exists('debugging')) {
    function debugging($message = '', $level = DEBUG_NORMAL, $backtrace = null) {
        $GLOBALS['dof_debugging_calls'][] = array(
            'message' => $message,
            'level' => $level,
            'backtrace' => $backtrace
        );
    }
}

require_once __DIR__ . '/../lib/plugin.php';
require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/storage_base.php';

/**
 * Stub implementation of dof_storage for testing dof_storage::get().
 */
class testable_dof_storage extends dof_storage
{
    public $custom_idname = 'id';
    public $last_get_record_args = null;
    public $get_record_return_value = null;

    public function __construct($dof = null)
    {
        // No-op for test stub
    }

    public function tablename()
    {
        return 'test_table';
    }

    public function idname()
    {
        return $this->custom_idname;
    }

    public function get_record($conditions, $fields = '*', $strictness = IGNORE_MISSING)
    {
        $this->last_get_record_args = array(
            'conditions' => $conditions,
            'fields' => $fields,
            'strictness' => $strictness
        );
        return $this->get_record_return_value;
    }

    // Dummy implementations for dof_plugin interface methods
    public function version() { return '1.0'; }
    public function compat_dof() { return '1.0'; }
    public function compat() { return '1.0'; }
    public function type() { return 'storage'; }
    public function code() { return 'testable'; }
    public function need_plugins() { return array(); }
    public function list_catch_events() { return array(); }
    public function is_cron() { return false; }
    public function is_access($do, $objid = NULL, $userid = NULL) { return true; }
    public function catch_event($plugintype, $plugincode, $eventcode, $intvar, $mixedvar) { return true; }
    public function cron($loan, $messages) { return true; }
    public function todo($code, $intvar, $mixedvar) { return true; }
}

/**
 * Unit tests for dof_storage::get() in storage_base.php
 */
class storage_base_test extends TestCase
{
    /**
     * @var testable_dof_storage
     */
    private $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new testable_dof_storage();
        $GLOBALS['dof_debugging_calls'] = array();
    }

    /**
     * Test retrieving record by scalar integer ID.
     */
    public function test_get_with_scalar_integer_id(): void
    {
        $this->storage->get(123);

        $this->assertNotNull($this->storage->last_get_record_args);
        $this->assertSame(array('id' => 123), $this->storage->last_get_record_args['conditions']);
        $this->assertSame('*', $this->storage->last_get_record_args['fields']);
        $this->assertSame(IGNORE_MISSING, $this->storage->last_get_record_args['strictness']);
        $this->assertEmpty($GLOBALS['dof_debugging_calls']);
    }

    /**
     * Test retrieving record by scalar numeric string ID.
     */
    public function test_get_with_scalar_string_id(): void
    {
        $this->storage->get('456');

        $this->assertNotNull($this->storage->last_get_record_args);
        $this->assertSame(array('id' => 456), $this->storage->last_get_record_args['conditions']);
        $this->assertEmpty($GLOBALS['dof_debugging_calls']);
    }

    /**
     * Test retrieving record with custom fields and strictness.
     */
    public function test_get_with_custom_fields_and_strictness(): void
    {
        $this->storage->get(10, 'id, name', MUST_EXIST);

        $this->assertNotNull($this->storage->last_get_record_args);
        $this->assertSame(array('id' => 10), $this->storage->last_get_record_args['conditions']);
        $this->assertSame('id, name', $this->storage->last_get_record_args['fields']);
        $this->assertSame(MUST_EXIST, $this->storage->last_get_record_args['strictness']);
    }

    /**
     * Test retrieving record when storage has a custom primary key name (idname()).
     */
    public function test_get_with_custom_idname(): void
    {
        $this->storage->custom_idname = 'personid';
        $this->storage->get(789);

        $this->assertNotNull($this->storage->last_get_record_args);
        $this->assertSame(array('personid' => 789), $this->storage->last_get_record_args['conditions']);
    }

    /**
     * Test retrieving record with array conditions triggers dof_debugging warning.
     */
    public function test_get_with_array_conditions(): void
    {
        $conditions = array('id' => 5, 'status' => 'active');
        $this->storage->get($conditions);

        $this->assertNotNull($this->storage->last_get_record_args);
        $this->assertSame($conditions, $this->storage->last_get_record_args['conditions']);

        $this->assertCount(1, $GLOBALS['dof_debugging_calls']);
        $this->assertStringContainsString('Do not use multiplie conditions in dof_storage::get()', $GLOBALS['dof_debugging_calls'][0]['message']);
        $this->assertSame(DEBUG_DEVELOPER, $GLOBALS['dof_debugging_calls'][0]['level']);
    }

    /**
     * Test retrieving record with object conditions casts object to array and triggers dof_debugging warning.
     */
    public function test_get_with_object_conditions(): void
    {
        $objConditions = (object)array('id' => 42, 'code' => 'TEST');
        $this->storage->get($objConditions);

        $this->assertNotNull($this->storage->last_get_record_args);
        $this->assertSame(array('id' => 42, 'code' => 'TEST'), $this->storage->last_get_record_args['conditions']);

        $this->assertCount(1, $GLOBALS['dof_debugging_calls']);
        $this->assertStringContainsString('Do not use multiplie conditions in dof_storage::get()', $GLOBALS['dof_debugging_calls'][0]['message']);
        $this->assertSame(DEBUG_DEVELOPER, $GLOBALS['dof_debugging_calls'][0]['level']);
    }

    /**
     * Test that get() returns the exact return value of get_record().
     */
    public function test_get_returns_record_or_false(): void
    {
        $dummyRecord = (object)array('id' => 1, 'name' => 'Sample Record');
        $this->storage->get_record_return_value = $dummyRecord;

        $result = $this->storage->get(1);
        $this->assertSame($dummyRecord, $result);

        $this->storage->get_record_return_value = false;
        $result = $this->storage->get(999);
        $this->assertFalse($result);
    }
}
