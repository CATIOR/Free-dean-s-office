<?php
use PHPUnit\Framework\TestCase;

/**
 * Concrete implementation of abstract class dof_storage for testing purposes.
 */
class testable_dof_storage extends dof_storage {
    private $tablename;

    public function __construct($tablename = 'test_table') {
        $this->tablename = $tablename;
    }

    public function tablename() {
        return $this->tablename;
    }

    // Public wrapper for testing protected method where_clause if needed
    public function test_where_clause(array $conditions = null) {
        return $this->where_clause($conditions);
    }
}

/**
 * Test case for dof_storage::get_records() and related methods.
 */
class StorageBaseTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        global $DB;
        $DB = $this->getMockBuilder(stdClass::class)
                   ->addMethods(['get_records_select'])
                   ->getMock();
    }

    protected function tearDown(): void {
        global $DB;
        $DB = null;
        parent::tearDown();
    }

    /**
     * Test get_records() with default arguments and empty conditions.
     */
    public function test_get_records_default_parameters() {
        global $DB;
        $storage = new testable_dof_storage('dof_departments');

        $expectedResult = [
            1 => (object)['id' => 1, 'name' => 'Dept 1'],
            2 => (object)['id' => 2, 'name' => 'Dept 2'],
        ];

        // DB mock expectations:
        // get_records_select($table, $select, $params, $sort, $fields, $limitfrom, $limitnum)
        $DB->expects($this->once())
            ->method('get_records_select')
            ->with('dof_departments', '', [], '', '*', 0, 0)
            ->willReturn($expectedResult);

        $result = $storage->get_records();
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records() with scalar key-value conditions.
     */
    public function test_get_records_with_scalar_conditions() {
        global $DB;
        $storage = new testable_dof_storage('dof_students');

        $conditions = [
            'status' => 'active',
            'departmentid' => 5
        ];

        $expectedResult = [
            10 => (object)['id' => 10, 'status' => 'active', 'departmentid' => 5]
        ];

        $DB->expects($this->once())
            ->method('get_records_select')
            ->with('dof_students', 'status = ? AND departmentid = ?', ['active', 5], '', '*', 0, 0)
            ->willReturn($expectedResult);

        $result = $storage->get_records($conditions);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records() with array value conditions (mapped via where_clause_list).
     */
    public function test_get_records_with_array_value_conditions() {
        global $DB;
        $storage = new testable_dof_storage('dof_contracts');

        $conditions = [
            'status' => ['application', 'active', 'plan'],
            'clientid' => 42
        ];

        $expectedResult = [
            100 => (object)['id' => 100, 'status' => 'active', 'clientid' => 42],
            101 => (object)['id' => 101, 'status' => 'plan', 'clientid' => 42]
        ];

        $DB->expects($this->once())
            ->method('get_records_select')
            ->with(
                'dof_contracts',
                '(status = ? OR status = ? OR status = ?) AND clientid = ?',
                ['application', 'active', 'plan', 42],
                '',
                '*',
                0,
                0
            )
            ->willReturn($expectedResult);

        $result = $storage->get_records($conditions);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records() with null value condition (IS NULL).
     */
    public function test_get_records_with_null_condition() {
        global $DB;
        $storage = new testable_dof_storage('dof_orders');

        $conditions = [
            'deleted' => null,
            'status' => 'pending'
        ];

        $expectedResult = [
            200 => (object)['id' => 200, 'deleted' => null, 'status' => 'pending']
        ];

        $DB->expects($this->once())
            ->method('get_records_select')
            ->with(
                'dof_orders',
                'deleted IS NULL AND status = ?',
                ['pending'],
                '',
                '*',
                0,
                0
            )
            ->willReturn($expectedResult);

        $result = $storage->get_records($conditions);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records() with custom sorting, fields, limitfrom, and limitnum parameters.
     */
    public function test_get_records_with_sort_fields_limits() {
        global $DB;
        $storage = new testable_dof_storage('dof_logs');

        $conditions = ['type' => 'error'];
        $sort = 'timecreated DESC';
        $fields = 'id, type, message';
        $limitfrom = 10;
        $limitnum = 20;

        $expectedResult = [
            50 => (object)['id' => 50, 'type' => 'error', 'message' => 'Test error']
        ];

        $DB->expects($this->once())
            ->method('get_records_select')
            ->with(
                'dof_logs',
                'type = ?',
                ['error'],
                'timecreated DESC',
                'id, type, message',
                10,
                20
            )
            ->willReturn($expectedResult);

        $result = $storage->get_records($conditions, $sort, $fields, $limitfrom, $limitnum);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test get_records() returning empty array when no records match.
     */
    public function test_get_records_returns_empty_array() {
        global $DB;
        $storage = new testable_dof_storage('dof_users');

        $DB->expects($this->once())
            ->method('get_records_select')
            ->with('dof_users', 'id = ?', [99999], '', '*', 0, 0)
            ->willReturn([]);

        $result = $storage->get_records(['id' => 99999]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
