<?php

namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use LeaveManagement\Model\LeaveAssign;
use LeaveManagement\Model\LeaveMaster;
use Setup\Model\HrEmployees;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class LeaveBalanceRepository {

    private $adapter;
    private $tableGateway;
    private $leaveTableGateway;
    private $employeeTableGateway;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(LeaveAssign::TABLE_NAME, $adapter);
        $this->leaveTableGateway = new TableGateway(LeaveMaster::TABLE_NAME, $adapter);
        $this->employeeTableGateway = new TableGateway("HRIS_EMPLOYEES", $adapter);
    }

    public function getAllLeave($isMonthly = false) {
        $condition = $isMonthly ? " AND IS_MONTHLY = 'Y' " : "  AND IS_MONTHLY = 'N' ";
        $sql = "SELECT LEAVE_ID,INITCAP(LEAVE_ENAME) AS LEAVE_ENAME FROM HRIS_LEAVE_MASTER_SETUP WHERE STATUS='E' {$condition} ORDER BY LEAVE_ID";
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }

    public function getAllEmployee($emplyoeeId, $companyId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(HrEmployees::class, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], NULL, NULL, NULL, NULL, 'E'), false);

        $select->from(['E' => "HRIS_EMPLOYEES"]);

        $select->where([
            "E.STATUS='E'"
        ]);

        if ($serviceEventTypeId == 5 || $serviceEventTypeId == 8 || $serviceEventTypeId == 14) {
            $select->where(["E.RETIRED_FLAG='Y'"]);
        } else {
            $select->where(["E.RETIRED_FLAG='N'"]);
        }


        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $select->where([
                "E.EMPLOYEE_TYPE= '{$employeeTypeId}'"
            ]);
        }

        if ($emplyoeeId != -1) {
            $select->where([
                "E.EMPLOYEE_ID=" . $emplyoeeId
            ]);
        }
        if ($companyId != -1) {
            $select->where([
                "E.COMPANY_ID=" . $companyId
            ]);
        }
        if ($branchId != -1) {
            $select->where([
                "E.BRANCH_ID=" . $branchId
            ]);
        }
        if ($departmentId != -1) {
            $select->where([
                "E.DEPARTMENT_ID=" . $departmentId
            ]);
        }
        if ($designationId != -1) {
            $select->where([
                "E.DESIGNATION_ID=" . $designationId
            ]);
        }
        if ($positionId != -1) {
            $select->where([
                "E.POSITION_ID=" . $positionId
            ]);
        }
        if ($serviceTypeId != -1) {
            $select->where([
                "E.SERVICE_TYPE_ID=" . $serviceTypeId
            ]);
        }
        if ($serviceEventTypeId != -1) {
            $select->where([
                "E.SERVICE_EVENT_TYPE_ID=" . $serviceEventTypeId
            ]);
        }
        $select->order("E.FIRST_NAME ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function getByEmpIdLeaveId($employeeId, $leaveId) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("LA.TOTAL_DAYS AS TOTAL_DAYS"),
            new Expression("LA.BALANCE AS BALANCE"),
            new Expression("LA.LEAVE_ID AS LEAVE_ID"),
            new Expression("LA.EMPLOYEE_ID AS EMPLOYEE_ID"),
                ], true);

        $select->from(['LA' => LeaveAssign::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=LA.EMPLOYEE_ID", ['FIRST_NAME' => new Expression('INITCAP(E.FIRST_NAME)'), 'MIDDLE_NAME' => new Expression('INITCAP(E.MIDDLE_NAME)'), 'LAST_NAME' => new Expression('INITCAP(E.LAST_NAME)'), 'SERVICE_EVENT_TYPE_ID'], "left")
                ->join(['L' => 'HRIS_LEAVE_MASTER_SETUP'], "L.LEAVE_ID=LA.LEAVE_ID", ['LEAVE_CODE', 'LEAVE_ENAME' => new Expression('INITCAP(L.LEAVE_ENAME)')], "left");

        $select->where([
            "L.STATUS='E'",
            "E.STATUS='E'",
            "LA.EMPLOYEE_ID=" . $employeeId,
            "LA.LEAVE_ID=" . $leaveId
        ]);
        $select->order(['L.LEAVE_ID']);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getOnlyCarryForwardedRecord() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("LA.BALANCE AS BALANCE"),
            new Expression("LA.TOTAL_DAYS AS TOTAL_DAYS"),
            new Expression("LA.PREVIOUS_YEAR_BAL AS PREVIOUS_YEAR_BAL"),
            new Expression("LA.LEAVE_ID AS LEAVE_ID"),
            new Expression("LA.EMPLOYEE_ID AS EMPLOYEE_ID")
                ], true);

        $select->from(['LA' => LeaveAssign::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=LA.EMPLOYEE_ID", ['FIRST_NAME' => new Expression('INITCAP(E.FIRST_NAME)'), 'MIDDLE_NAME' => new Expression('INITCAP(E.MIDDLE_NAME)'), 'LAST_NAME' => new Expression('INITCAP(E.LAST_NAME)')], "left")
                ->join(['L' => 'HRIS_LEAVE_MASTER_SETUP'], "L.LEAVE_ID=LA.LEAVE_ID", ['LEAVE_CODE', 'LEAVE_ENAME' => new Expression('INITCAP(LEAVE_ENAME)')], "left");

        $select->where([
            "L.STATUS='E'",
            "E.STATUS='E'",
            "E.RETIRED_FLAG='N'",
            "L.CARRY_FORWARD='Y'"
        ]);
        $select->order(['LA.EMPLOYEE_ID']);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();


        $record = [];
        foreach ($result as $row) {
            array_push($record, [
                'EMPLOYEE_ID' => $row['EMPLOYEE_ID'],
                'LEAVE_ID' => $row['LEAVE_ID'],
                'PREVIOUS_YEAR_BAL' => $row['PREVIOUS_YEAR_BAL'],
                'TOTAL_DAYS' => $row['TOTAL_DAYS'],
                'BALANCE' => $row['BALANCE'],
            ]);
        }
        return $record;
    }

    public function getPivotedList($searchQuery, $isMonthly = false) {
        $searchConditon = EntityHelper::getSearchConditon($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId']);
        $monthlyCondition = $isMonthly ? " AND FISCAL_YEAR_MONTH_NO ={$searchQuery['fiscalYearMonthNo']} " : "";
        $leaveArrayDb = $this->fetchLeaveAsDbArray($isMonthly);

        $sql = "
           SELECT LA.*,E.FULL_NAME, E.EMPLOYEE_CODE AS EMPLOYEE_CODE FROM (SELECT *
            FROM
              (SELECT EMPLOYEE_ID,
              PREVIOUS_YEAR_BAL,
                LEAVE_ID,
                TOTAL_DAYS AS TOTAL,
                BALANCE,
                (TOTAL_DAYS+PREVIOUS_YEAR_BAL-BALANCE) AS TAKEN
              FROM HRIS_EMPLOYEE_LEAVE_ASSIGN
              WHERE EMPLOYEE_ID IN
                ( SELECT E.EMPLOYEE_ID FROM HRIS_EMPLOYEES E WHERE 1=1 AND E.STATUS='E' {$searchConditon}
                ){$monthlyCondition}
              ) PIVOT (MAX(PREVIOUS_YEAR_BAL) AS PREVIOUS_YEAR_BAL,MAX(BALANCE) AS BALANCE,MAX(TOTAL) AS TOTAL,MAX(TAKEN) AS TAKEN FOR LEAVE_ID IN ({$leaveArrayDb}) )
            ) LA LEFT JOIN HRIS_EMPLOYEES E ON (LA.EMPLOYEE_ID=E.EMPLOYEE_ID) 
";
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    private function fetchLeaveAsDbArray($isMonthly = false) {
        $condition = $isMonthly ? " AND IS_MONTHLY = 'Y' " : " AND IS_MONTHLY = 'N' ";
        $rawList = EntityHelper::rawQueryResult($this->adapter, "SELECT LEAVE_ID FROM HRIS_LEAVE_MASTER_SETUP WHERE STATUS ='E' {$condition}");
        $dbArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray .= "{$row['LEAVE_ID']} AS L{$row['LEAVE_ID']}";
            } else {
                $dbArray .= "{$row['LEAVE_ID']} AS L{$row['LEAVE_ID']},";
            }
        }
        return $dbArray;
    }
    
    public function getPivotedListBetnDates($searchQuery, $isMonthly = false) {
        $orderByString=EntityHelper::getOrderBy('E.FULL_NAME ASC',null,'E.SENIORITY_LEVEL','P.LEVEL_NO','E.JOIN_DATE','DES.ORDER_NO','E.FULL_NAME');
        $searchConditon = EntityHelper::getSearchConditon($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId'], null, null,$searchQuery['functionalTypeId']);
        $leaveArrayDb = $this->fetchLeaveAsDbArray($isMonthly);
        $fromDate="to_date('{$searchQuery['fromDate']}')";
        $toDate="to_date('{$searchQuery['toDate']}')";
        
        $sql="SELECT LB.*,E.FULL_NAME,
            E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
            INITCAP(D.DEPARTMENT_NAME)                                        AS DEPARTMENT_NAME,
            INITCAP(DES.DESIGNATION_TITLE)                                    AS DESIGNATION_TITLE,
            INITCAP(P.POSITION_NAME)                                          AS POSITION_NAME,
            FUNT.FUNCTIONAL_TYPE_EDESC                                        AS FUNCTIONAL_TYPE_EDESC
            FROM (SELECT * FROM
(SELECT la.employee_id,
    la.leave_id,
    LTBD.lEAVE_TAKEN_BETWEEN_DATES AS TAKEN,
    case when ltad.leave_taken_after_dates is not null
    then 
    la.balance+ltad.leave_taken_after_dates
    else
    la.balance
    end AS CALCULATED_BALANCE
              FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
              LEFT JOIN 
              (SELECT 
EMPLOYEE_ID,LEAVE_ID
,SUM(CASE WHEN HALF_DAY='F' OR HALF_DAY='S' THEN 0.5 ELSE 1 END) AS lEAVE_TAKEN_BETWEEN_DATES 
FROM (SELECT * FROM HRIS_EMPLOYEE_LEAVE_REQUEST WHERE STATUS='AP') LR
  JOIN 
(SELECT   {$fromDate} + ROWNUM -1  AS DATES
    FROM dual d
    CONNECT BY  rownum <=  {$toDate} -  {$fromDate} + 1) ADT ON (ADT.DATES Between START_DATE AND END_DATE)
    WHERE  ADT.DATES BETWEEN  {$fromDate} AND {$toDate}
   GROUP BY EMPLOYEE_ID,LEAVE_ID) LTBD ON (LTBD.LEAVE_ID=LA.LEAVE_ID AND LTBD.EMPLOYEE_ID=LA.EMPLOYEE_ID)
   LEFT JOIN (
   select EMPLOYEE_ID,LEAVE_ID
,SUM(CASE WHEN HALF_DAY='F' OR HALF_DAY='S' THEN leave_days/0.5 ELSE leave_days END) AS lEAVE_TAKEN_AFTER_DATES 
from (SELECT EMPLOYEE_ID,LEAVE_ID,START_DATE,END_DATE,NO_OF_DAYS,HALF_DAY,
CASE WHEN
    half_day = 'F'
OR
    half_day = 'S'
THEN (end_date - {$toDate})/2
ELSE end_date - {$toDate}
END as leave_days 
    FROM HRIS_EMPLOYEE_LEAVE_REQUEST WHERE STATUS='AP'
    AND END_DATE>{$toDate}
    AND START_DATE<={$toDate}
        UNION ALL
        SELECT
    EMPLOYEE_ID,LEAVE_ID,START_DATE,END_DATE,NO_OF_DAYS,
HALF_DAY,
CASE
WHEN
    half_day = 'F'
OR
    half_day = 'S'
THEN no_of_days/2
ELSE no_of_days
END as leave_days
    FROM HRIS_EMPLOYEE_LEAVE_REQUEST WHERE STATUS='AP'
    AND START_DATE>{$toDate}
    )
    GROUP BY EMPLOYEE_ID,LEAVE_ID) LTAD ON (LTAD.LEAVE_ID=LA.LEAVE_ID AND LTAD.EMPLOYEE_ID=LA.EMPLOYEE_ID)
    )PIVOT (MAX(TAKEN) AS TAKEN, MAX(CALCULATED_BALANCE) AS BALANCE 
    FOR LEAVE_ID
    IN ({$leaveArrayDb}) )
    )LB LEFT JOIN HRIS_EMPLOYEES E ON (LB.EMPLOYEE_ID=E.EMPLOYEE_ID)
    LEFT JOIN HRIS_DEPARTMENTS D
    ON E.DEPARTMENT_ID=D.DEPARTMENT_ID
    LEFT JOIN HRIS_DESIGNATIONS DES
    ON E.DESIGNATION_ID=DES.DESIGNATION_ID
    LEFT JOIN HRIS_POSITIONS P
    ON E.POSITION_ID=P.POSITION_ID
    LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT
    ON E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID
    WHERE E.STATUS='E' {$searchConditon} {$orderByString}";
//    echo $sql;
//    die();
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

}
