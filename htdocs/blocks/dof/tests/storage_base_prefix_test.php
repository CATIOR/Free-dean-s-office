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
 * Unit test for dof_storage::prefix() in storage_base.php
 *
 * @package    block_dof
 * @subpackage tests
 */

require_once(__DIR__ . '/../lib/plugin.php');
require_once(__DIR__ . '/../lib/storage.php');
require_once(__DIR__ . '/../lib/storage_base.php');

/**
 * Concrete stub class for testing abstract dof_storage.
 */
class test_dof_storage_prefix_stub extends dof_storage {
    public function __construct($dof = null) {}
    public function tablename() { return 'test'; }
    public function version() { return 1; }
    public function compat_dof() { return ''; }
    public function compat() { return ''; }
    public function type() { return 'storage'; }
    public function code() { return 'test'; }
    public function init($dof) {}
    public function title() { return ''; }
    public function description() { return ''; }
    public function acl() { return array(); }
    public function is_access($action, $id = null, $departmentid = null) { return true; }
    public function is_enabled() { return true; }
    public function set_enabled($state) {}
    public function is_installed() { return true; }
    public function need_plugins() { return array(); }
    public function list_catch_events() { return array(); }
    public function catch_event($plugintype, $plugincode, $eventcode, $intvar = null, $mixedvar = null) {}
    public function is_cron() { return false; }
    public function cron($loan, $messages) {}
    public function todo($todocode, $intvar = null, $mixedvar = null) {}
    public function install() { return true; }
    public function upgrade($oldversion) { return true; }
}

function run_dof_storage_prefix_tests() {
    global $CFG;

    if (!isset($CFG) || !is_object($CFG)) {
        $CFG = new stdClass();
    }

    $original_prefix_set = isset($CFG->prefix);
    $original_prefix = $original_prefix_set ? $CFG->prefix : null;

    $storage = new test_dof_storage_prefix_stub();
    $passed = 0;
    $failed = 0;

    try {
        // Test Case 1: Standard Moodle table prefix ('mdl_')
        $CFG->prefix = 'mdl_';
        if ($storage->prefix() === 'mdl_') {
            echo "[PASS] Test 1: prefix() returns 'mdl_' when \$CFG->prefix is 'mdl_'\n";
            $passed++;
        } else {
            echo "[FAIL] Test 1: Expected 'mdl_', got " . var_export($storage->prefix(), true) . "\n";
            $failed++;
        }

        // Test Case 2: Custom table prefix ('custom_prefix_')
        $CFG->prefix = 'custom_prefix_';
        if ($storage->prefix() === 'custom_prefix_') {
            echo "[PASS] Test 2: prefix() returns 'custom_prefix_' when \$CFG->prefix is 'custom_prefix_'\n";
            $passed++;
        } else {
            echo "[FAIL] Test 2: Expected 'custom_prefix_', got " . var_export($storage->prefix(), true) . "\n";
            $failed++;
        }

        // Test Case 3: Empty string prefix ('')
        $CFG->prefix = '';
        if ($storage->prefix() === '') {
            echo "[PASS] Test 3: prefix() returns '' when \$CFG->prefix is ''\n";
            $passed++;
        } else {
            echo "[FAIL] Test 3: Expected '', got " . var_export($storage->prefix(), true) . "\n";
            $failed++;
        }

        // Test Case 4: Null prefix (null)
        $CFG->prefix = null;
        if ($storage->prefix() === null) {
            echo "[PASS] Test 4: prefix() returns null when \$CFG->prefix is null\n";
            $passed++;
        } else {
            echo "[FAIL] Test 4: Expected null, got " . var_export($storage->prefix(), true) . "\n";
            $failed++;
        }
    } finally {
        // Restore original $CFG->prefix
        if ($original_prefix_set) {
            $CFG->prefix = $original_prefix;
        } else {
            unset($CFG->prefix);
        }
    }

    echo "\nResults: {$passed} passed, {$failed} failed.\n";

    if ($failed > 0) {
        exit(1);
    }
}

run_dof_storage_prefix_tests();
