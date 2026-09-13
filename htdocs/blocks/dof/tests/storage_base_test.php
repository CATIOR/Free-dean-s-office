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
 * Unit tests for dof_storage::idname() in storage_base.php
 *
 * @package    block_dof
 * @category   test
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/dof/lib/plugin.php');
require_once($CFG->dirroot . '/blocks/dof/lib/storage.php');
require_once($CFG->dirroot . '/blocks/dof/lib/storage_base.php');

/**
 * Test class for storage_base idname method.
 *
 * @group block_dof
 */
class block_dof_storage_base_testcase extends advanced_testcase {

    /**
     * Test idname default return value.
     */
    public function test_idname() {
        $storage = $this->getMockForAbstractClass('dof_storage', array(), '', false);
        $this->assertEquals('id', $storage->idname());
    }

    /**
     * Test idname overridden return value in a subclass.
     */
    public function test_idname_subclass_override() {
        $storage = new class extends dof_storage {
            public function tablename() {
                return 'test_table';
            }
            public function idname() {
                return 'custom_id';
            }
            public function install() {}
            public function upgrade($oldversion) {}
            public function uninstall() {}
            public function version() {}
            public function compat_dof() {}
            public function compat() {}
            public function type() {}
            public function code() {}
            public function need_plugins() {}
            public function list_catch_events() {}
            public function is_cron() {}
            public function is_access($do, $objid = NULL, $userid = NULL) {}
            public function catch_event($plugintype, $plugincode, $eventcode, $intvar, $mixedvar) {}
            public function cron($loan, $messages) {}
            public function todo($code, $intvar, $mixedvar) {}
            public function __construct($dof = null) {}
        };

        $this->assertEquals('custom_id', $storage->idname());
    }
}
