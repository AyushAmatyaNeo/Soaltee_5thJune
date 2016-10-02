<?php
/**
 * Created by PhpStorm.
 * User: punam
 * Date: 9/30/16
 * Time: 12:20 PM
 */
namespace SelfService\Repository;

use Zend\Db\Adapter\AdapterInterface;
use Application\Repository\RepositoryInterface;
use Application\Model\Model;
use LeaveManagement\Model\LeaveAssign;
use LeaveManagement\Model\LeaveApply;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class LeaveRequestRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway = new TableGateway(LeaveApply::TABLE_NAME,$adapter);
        $this->tableGatewayLeaveAssign = new TableGateway(LeaveAssign::TABLE_NAME,$adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model)
    {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id)
    {
        // TODO: Implement edit() method.
    }

    public function fetchAll()
    {
        // TODO: Implement fetchAll() method.
    }

    //to get the all applied leave request list
    public function selectAll($employeeId){

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TO_CHAR(LA.START_DATE, 'DD-MON-YYYY') AS FROM_DATE"),
            new Expression("TO_CHAR(LA.END_DATE, 'DD-MON-YYYY') AS TO_DATE"),
            new Expression("LA.STATUS AS STATUS"),
            new Expression("LA.ID AS ID"),
        ], true);

        $select->from(['LA' => LeaveApply::TABLE_NAME])
            ->join(['E'=>"HR_EMPLOYEES"],"E.EMPLOYEE_ID=LA.EMPLOYEE_ID",['FIRST_NAME','MIDDLE_NAME','LAST_NAME'])
            ->join(['L'=>'HR_LEAVE_MASTER_SETUP'],"L.LEAVE_ID=LA.LEAVE_ID",['LEAVE_CODE','LEAVE_ENAME']);

        $select->where([
            "L.STATUS='E'",
            "E.EMPLOYEE_ID=".$employeeId
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    //to get the leave detail based on assigned employee id
    public function getLeaveDetail($employeeId,$leaveId){
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([new Expression("LA.BALANCE AS BALANCE")], true);

        $select->from(['LA' => LeaveAssign::TABLE_NAME])
            ->join(['E'=>"HR_EMPLOYEES"],"E.EMPLOYEE_ID=LA.EMPLOYEE_ID",['FIRST_NAME','MIDDLE_NAME','LAST_NAME'])
            ->join(['L'=>'HR_LEAVE_MASTER_SETUP'],"L.LEAVE_ID=LA.LEAVE_ID",['LEAVE_CODE','LEAVE_ENAME','ALLOW_HALFDAY']);

        $select->where([
            "L.STATUS='E'",
            "E.EMPLOYEE_ID=".$employeeId,
            "L.LEAVE_ID=".$leaveId
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    //to get the leave list based on assigned employee id for select option
    public function getLeaveList($employeeId){
        $sql  = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['LA'=>LeaveAssign::TABLE_NAME])
            ->join(['L'=>'HR_LEAVE_MASTER_SETUP'],"L.LEAVE_ID=LA.LEAVE_ID",['LEAVE_CODE','LEAVE_ENAME']);
        $select->where([
           "L.STATUS='E'",
            "LA.EMPLOYEE_ID=".$employeeId
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $resultset = $statement->execute();

        $entitiesArray = array();
        foreach ($resultset as $result) {
            $entitiesArray[$result['LEAVE_ID']] = $result['LEAVE_ENAME'];
        }
        return $entitiesArray;
    }

    public function fetchById($id)
    {
        // TODO: Implement fetchById() method.
    }

    public function delete($id)
    {
        $this->tableGateway->update([LeaveApply::STATUS=>'RC'],[LeaveApply::ID=>$id]);
    }
}