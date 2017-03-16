<?php

/**
 * Created by PhpStorm.
 * User: punam
 * Date: 9/14/16
 * Time: 3:38 PM
 */

namespace AttendanceManagement\Repository;

use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use AttendanceManagement\Model\Attendance;
use AttendanceManagement\Model\AttendanceDetail;
use Setup\Model\HrEmployees;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class AttendanceDetailRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(AttendanceDetail::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        $this->tableGateway->update($array, [AttendanceDetail::ID => $id]);
    }

    public function fetchAll() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([new Expression("TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY') AS ATTENDANCE_DT"), new Expression("TO_CHAR(A.IN_TIME, 'HH:MI AM') AS IN_TIME"), new Expression("TO_CHAR(A.OUT_TIME, 'HH:MI AM') AS OUT_TIME"), new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"), new Expression("A.ID AS ID"), new Expression("A.IN_REMARKS AS IN_REMARKS"), new Expression("A.OUT_REMARKS AS OUT_REMARKS")], true);
        $select->from(['A' => AttendanceDetail::TABLE_NAME])
                ->join(['E' => 'HR_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => 'FIRST_NAME', "MIDDLE_NAME" => 'MIDDLE_NAME', "LAST_NAME" => 'LAST_NAME'], "left");
        $select->where(["E.STATUS='E'"]);
        $select->where(["E.RETIRED_FLAG='N'"]);
        $select->order("E.FIRST_NAME,A.ATTENDANCE_DT DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function filterRecord($employeeId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $fromDate, $toDate, $status) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([new Expression("TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY') AS ATTENDANCE_DT"), new Expression("TO_CHAR(A.IN_TIME, 'HH:MI AM') AS IN_TIME"), new Expression("TO_CHAR(A.OUT_TIME, 'HH:MI AM') AS OUT_TIME"), new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"), new Expression("A.ID AS ID"), new Expression("A.IN_REMARKS AS IN_REMARKS"), new Expression("A.TOTAL_HOUR AS TOTAL_HOUR"), new Expression("A.OUT_REMARKS AS OUT_REMARKS")], true);
        $select->from(['A' => AttendanceDetail::TABLE_NAME])
                ->join(['E' => 'HR_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => 'FIRST_NAME', "MIDDLE_NAME" => 'MIDDLE_NAME', "LAST_NAME" => 'LAST_NAME'], "left")
                ->join(['H' => 'HR_HOLIDAY_MASTER_SETUP'], 'A.HOLIDAY_ID=H.HOLIDAY_ID', ["HOLIDAY_ENAME" => 'HOLIDAY_ENAME'], "left")
                ->join(['L' => 'HR_LEAVE_MASTER_SETUP'], 'A.LEAVE_ID=L.LEAVE_ID', ["LEAVE_ENAME" => 'LEAVE_ENAME'], "left");

        if ($fromDate != null) {
            $startDate = " AND A.ATTENDANCE_DT>=TO_DATE('" . $fromDate . "','DD-MM-YYYY')";
        } else {
            $startDate = "";
        }
        if ($toDate != null) {
            $endDate = " AND A.ATTENDANCE_DT<=TO_DATE('" . $toDate . "','DD-MM-YYYY')";
        } else {
            $endDate = "";
        }
        $select->where(["E.STATUS='E'" . $startDate . $endDate]);

        if ($serviceEventTypeId == 5 || $serviceEventTypeId == 8 || $serviceEventTypeId == 14) {
            $select->where(["E.RETIRED_FLAG='Y'"]);
        } else {
            $select->where(["E.RETIRED_FLAG='N'"]);
        }
        if ($status != "All") {
            if ($status == "A") {
                $select->where(["A.IN_TIME IS NULL AND A.OUT_TIME IS NULL AND A.TRAINING_ID IS NULL AND A.HOLIDAY_ID IS NULL AND A.LEAVE_ID IS NULL"]);
            }

            if ($status == "H") {
                $select->where(["A.IN_TIME IS NULL AND A.OUT_TIME IS NULL AND A.TRAINING_ID IS NULL AND A.HOLIDAY_ID IS NOT NULL AND A.LEAVE_ID IS NULL"]);
            }

            if ($status == "L") {
                $select->where(["A.IN_TIME IS NULL AND A.OUT_TIME IS NULL AND A.TRAINING_ID IS NULL AND A.HOLIDAY_ID IS NULL AND A.LEAVE_ID IS NOT NULL"]);
            }
            
            if ($status == "P") {
                $select->where(["A.IN_TIME IS NOT NULL"]);
            }
        }

        if ($employeeId != -1) {
            $select->where(["E.EMPLOYEE_ID=" . $employeeId]);
        }

        if ($branchId != -1) {
            $select->where(["E.BRANCH_ID=" . $branchId]);
        }

        if ($departmentId != -1) {
            $select->where(["E.DEPARTMENT_ID=" . $departmentId]);
        }

        if ($designationId != -1) {
            $select->where(["E.DESIGNATION_ID=" . $designationId]);
        }

        if ($positionId != -1) {
            $select->where(["E.POSITION_ID=" . $positionId]);
        }

        if ($serviceTypeId != -1) {
            $select->where(["E.SERVICE_TYPE_ID=" . $serviceTypeId]);
        }

        if ($serviceEventTypeId != -1) {
            $select->where(["E.SERVICE_EVENT_TYPE_ID=" . $serviceEventTypeId]);
        }

        $select->order("E.FIRST_NAME,A.ATTENDANCE_DT DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        //return $statement->getSql();
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([new Expression("TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY') AS ATTENDANCE_DT"), new Expression("TO_CHAR(A.IN_TIME, 'HH:MI AM') AS IN_TIME"), new Expression("TO_CHAR(A.OUT_TIME, 'HH:MI AM') AS OUT_TIME"), new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"), new Expression("A.ID AS ID"), new Expression("A.IN_REMARKS AS IN_REMARKS"), new Expression("A.OUT_REMARKS AS OUT_REMARKS"), new Expression("A.TOTAL_HOUR AS TOTAL_HOUR")], true);
        $select->from(['A' => AttendanceDetail::TABLE_NAME])
                ->join(['E' => 'HR_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => 'FIRST_NAME'], "left");
        $select->where([AttendanceDetail::ID => $id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function delete($id) {
        
    }

    public function getDtlWidEmpIdDate($employeeId, $attendanceDt) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([new Expression("TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY') AS ATTENDANCE_DT"), new Expression("TO_CHAR(A.IN_TIME, 'HH:MI AM') AS IN_TIME"), new Expression("TO_CHAR(A.OUT_TIME, 'HH:MI AM') AS OUT_TIME"), new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"), new Expression("A.ID AS ID"), new Expression("A.IN_REMARKS AS IN_REMARKS"), new Expression("A.OUT_REMARKS AS OUT_REMARKS")], true);
        $select->from(['A' => AttendanceDetail::TABLE_NAME])
                ->join(['E' => 'HR_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => 'FIRST_NAME', "MIDDLE_NAME" => 'MIDDLE_NAME', "LAST_NAME" => 'LAST_NAME'], "left");
        $select->where([
            'A.EMPLOYEE_ID=' . $employeeId,
            "A.ATTENDANCE_DT=TO_DATE('" . $attendanceDt . "','DD-MM-YYYY')"
        ]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function addAttendance($model) {
        $attendanceTableGateway = new TableGateway(Attendance::TABLE_NAME, $this->adapter);
        return $attendanceTableGateway->insert($model->getArrayCopyForDB());
    }

    public function getNoOfDaysInDayInterval(int $employeeId, $startDate, $endDate, $includeHoliday = true) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
        $select->where(['A.' . AttendanceDetail::EMPLOYEE_ID . "=$employeeId"]);
        $select->where(['A.' . AttendanceDetail::ATTENDANCE_DT . " BETWEEN " . $startDate->getExpression() . " AND " . $endDate->getExpression()]);

        if ($includeHoliday) {
            $select->where(['A.' . AttendanceDetail::HOLIDAY_ID . " IS NULL"]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->count();
    }

//    public function getNoOfDaysAbsent(int $employeeId, Expression $startDate, Expression $endDate) {
//        $sql = new Sql($this->adapter);
//        $select = $sql->select();
//        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
//        $select->where(['A.' . AttendanceDetail::EMPLOYEE_ID . "=$employeeId"]);
//        $select->where(['A.' . AttendanceDetail::ATTENDANCE_DT . " BETWEEN " . $startDate->getExpression() . " AND " . $endDate->getExpression()]);
//        $select->where(['A.' . AttendanceDetail::LEAVE_ID . " IS NOT NULL"]);
//
//        $statement = $sql->prepareStatementForSqlObject($select);
//        $result = $statement->execute();
//
//        return $result->count();
//    }
    public function getNoOfDaysAbsent(int $employeeId, Expression $startDate, Expression $endDate) {
        $startDt=$startDate->getExpression();
        $endDt=$endDate->getExpression();
        $sql = "SELECT SUM(LEAVE.LEAVE_COUNT) LEAVE_COUNT FROM (
                (SELECT COUNT(LR.EMPLOYEE_ID) AS LEAVE_COUNT FROM HR_EMPLOYEE_LEAVE_REQUEST LR,
                (SELECT  HAD.EMPLOYEE_ID, HAD.LEAVE_ID,HAD.ATTENDANCE_DT FROM HR_ATTENDANCE_DETAIL HAD
                WHERE HAD.EMPLOYEE_ID=$employeeId 
                AND (HAD.ATTENDANCE_DT BETWEEN 
                $startDt AND $endDt)
                AND HAD.LEAVE_ID IS NOT NULL
                ) AD
                WHERE
                LR.EMPLOYEE_ID = AD.EMPLOYEE_ID AND 
                LR.LEAVE_ID= AD.LEAVE_ID AND 
                LR.HALF_DAY = 'N') UNION (SELECT COUNT(LR.EMPLOYEE_ID)/2 AS LEAVE_COUNT FROM HR_EMPLOYEE_LEAVE_REQUEST LR,
                (SELECT  HAD.EMPLOYEE_ID, HAD.LEAVE_ID,HAD.ATTENDANCE_DT FROM HR_ATTENDANCE_DETAIL HAD
                WHERE HAD.EMPLOYEE_ID=7 
                AND (HAD.ATTENDANCE_DT BETWEEN 
                $startDt AND $endDt)
                AND HAD.LEAVE_ID IS NOT NULL
                ) AD
                WHERE
                LR.EMPLOYEE_ID = AD.EMPLOYEE_ID AND 
                LR.LEAVE_ID= AD.LEAVE_ID AND 
                LR.HALF_DAY != 'N') 
                ) LEAVE";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        $extractedRes = Helper::extractDbData($result);
        if (sizeof($extractedRes) > 0) {
            return $extractedRes[0]['LEAVE_COUNT'];
        } else {
            return 0;
        }
    }

    public function getNoOfDaysPresent(int $employeeId, Expression $startDate, Expression $endDate) {
        return $this->getNoOfDaysInDayInterval($employeeId, $startDate, $endDate) - $this->getNoOfDaysAbsent($employeeId, $startDate, $endDate);
    }

    public function getEmployeesAttendanceByDate($date, bool $flag, $branchId = null) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([Helper::timeExpression(AttendanceDetail::IN_TIME, 'A'), new Expression("A.ID AS ID")], true);
        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
        $select->join(['E' => HrEmployees::TABLE_NAME], "A." . AttendanceDetail::EMPLOYEE_ID . "=" . "E." . HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME]);
        $select->where(['A.' . AttendanceDetail::LEAVE_ID . " IS NULL", 'A.' . AttendanceDetail::HOLIDAY_ID . " IS NULL", 'A.' . AttendanceDetail::TRAINING_ID . " IS NULL"]);

        $select->where(['A.' . AttendanceDetail::ATTENDANCE_DT . " = " . $date->getExpression()]);

        if ($flag) {
            $select->where(['A.' . AttendanceDetail::IN_TIME . " IS NOT NULL"]);
        } else {
            $select->where(['A.' . AttendanceDetail::IN_TIME . " IS NULL"]);
        }

        if ($branchId != null) {
            $select->where(["E." . AttendanceDetail::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::BRANCH_ID . "= $branchId)"]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
//        print $statement->getSql();
        return $statement->execute();
    }

    public function getleaveIdCount(int $employeeId, Expression $startDate, Expression $endDate) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
        $select->columns([Helper::columnExpression(AttendanceDetail::LEAVE_ID, "A", "COUNT", AttendanceDetail::LEAVE_ID . "_NO"), AttendanceDetail::LEAVE_ID], true);
        $select->where(['A.' . AttendanceDetail::EMPLOYEE_ID . "=$employeeId"]);
        $select->where(['A.' . AttendanceDetail::ATTENDANCE_DT . " BETWEEN " . $startDate->getExpression() . " AND " . $endDate->getExpression()]);
        $select->where(['A.' . AttendanceDetail::LEAVE_ID . " IS NOT NULL"]);
        $select->group(['A.' . AttendanceDetail::LEAVE_ID]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function getTotalNoOfWorkingDays(Expression $startDate, Expression $endDate) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
        $select->columns([Helper::columnExpression(AttendanceDetail::ATTENDANCE_DT, "DISTINCT  A", null, null)]);
        $select->where(['A.' . AttendanceDetail::ATTENDANCE_DT . " BETWEEN " . $startDate->getExpression() . " AND " . $endDate->getExpression()]);
//        $select->where(['A.' . AttendanceDetail::HOLIDAY_ID . " IS NULL"]);
//        $select->group(['A.' . AttendanceDetail::ATTENDANCE_DT]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->count();
    }

    public function checkAndUpdateLeaves(Expression $date) {
        
    }
    
    public function fetchByEmpIdAttendanceDT($employeeId, $attendanceDt) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([new Expression("TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY') AS ATTENDANCE_DT"), new Expression("TO_CHAR(A.IN_TIME, 'HH:MI AM') AS IN_TIME"), new Expression("TO_CHAR(A.OUT_TIME, 'HH:MI AM') AS OUT_TIME"), new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"), new Expression("A.ID AS ID"), new Expression("A.IN_REMARKS AS IN_REMARKS"), new Expression("A.OUT_REMARKS AS OUT_REMARKS")], true);
        $select->from(['A' => AttendanceDetail::TABLE_NAME])
                ->join(['E' => 'HR_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => 'FIRST_NAME', "MIDDLE_NAME" => 'MIDDLE_NAME', "LAST_NAME" => 'LAST_NAME'], "left");
        $select->where([
            'A.EMPLOYEE_ID=' . $employeeId,
            "A.ATTENDANCE_DT=".$attendanceDt
        ]);
        $statement = $sql->prepareStatementForSqlObject($select);
//        print_r($statement->getSql()); die();
        $result = $statement->execute();
        return $result->current();
    }

}
