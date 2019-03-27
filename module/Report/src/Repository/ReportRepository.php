<?php
namespace Report\Repository; 

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Application\Model\Months;
use Application\Repository\HrisRepository;
use LeaveManagement\Model\LeaveMaster;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;

class ReportRepository extends HrisRepository {
 
    public function employeeWiseDailyReport($employeeId) {
        $sql = <<<EOT
            SELECT R.*,
              M.MONTH_EDESC,
              TRUNC(R.ATTENDANCE_DT)-TRUNC(M.FROM_DATE)+1 AS DAY_COUNT
            FROM
              (SELECT AD.ATTENDANCE_DT                AS ATTENDANCE_DT,
                TO_CHAR(AD.ATTENDANCE_DT,'MONDDYYYY') AS FORMATTED_ATTENDANCE_DT,
                (SELECT M.MONTH_ID
                FROM HRIS_MONTH_CODE M
                WHERE AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE
                ) AS MONTH_ID,
                (
              CASE 
                WHEN AD.DAYOFF_FLAG ='N'
                AND AD.HOLIDAY_ID  IS NULL
                AND AD.TRAINING_ID IS NULL
                AND AD.TRAVEL_ID   IS NULL
                AND AD.IN_TIME     IS NULL
                AND AD.LEAVE_ID IS NOT NULL
                THEN 1
                ELSE 0
              END) AS ON_LEAVE,
                (
              CASE
                WHEN AD.DAYOFF_FLAG ='N'
                AND AD.LEAVE_ID    IS NULL
                AND AD.HOLIDAY_ID  IS NULL
                AND AD.TRAINING_ID IS NULL
                AND AD.TRAVEL_ID   IS NULL
                AND AD.IN_TIME     IS NOT NULL
                THEN 1
                ELSE 0
              END) AS IS_PRESENT,
                (
              CASE
                WHEN AD.DAYOFF_FLAG ='N'
                AND AD.LEAVE_ID   IS NULL
                AND AD.HOLIDAY_ID  IS NULL
                AND AD.TRAINING_ID IS NULL
                AND AD.TRAVEL_ID   IS NULL
                AND AD.IN_TIME     IS NULL
                THEN 1
                ELSE 0
              END) AS IS_ABSENT,
                (
              CASE
                WHEN AD.LEAVE_ID   IS NULL
                AND AD.HOLIDAY_ID  IS NULL
                AND AD.TRAINING_ID IS NULL
                AND AD.TRAVEL_ID   IS NULL
                AND AD.IN_TIME     IS NULL 
                  AND  AD.DAYOFF_FLAG='Y'
                THEN 1
                ELSE 0
              END) AS IS_DAYOFF
              FROM HRIS_ATTENDANCE_DETAIL AD
              WHERE AD.EMPLOYEE_ID = $employeeId
              ) R
            JOIN HRIS_MONTH_CODE M
            ON (M.MONTH_ID = R.MONTH_ID)
EOT;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function departmentWiseDailyReport(int $monthId, int $departmentId = null, int $branchId = null) {
        $sql = <<<EOT
                      SELECT 
                      TRUNC(AD.ATTENDANCE_DT)-TRUNC(M.FROM_DATE)+1                              AS DAY_COUNT, 
                      E.EMPLOYEE_ID                                                             AS EMPLOYEE_ID ,
                      E.FIRST_NAME                                                                   AS FIRST_NAME,
                      E.EMPLOYEE_CODE                                                                   AS EMPLOYEE_CODE,
                      E.MIDDLE_NAME                                                                  AS MIDDLE_NAME,
                      E.LAST_NAME                                                                    AS LAST_NAME,
                      CONCAT(CONCAT(CONCAT(E.FIRST_NAME,' '),CONCAT(E.MIDDLE_NAME, '')),E.LAST_NAME) AS FULL_NAME,
                      AD.ATTENDANCE_DT                                                               AS ATTENDANCE_DT,
                      (
                      CASE 
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL
                        AND AD.LEAVE_ID IS NOT NULL
                        THEN 1
                        ELSE 0
                      END) AS ON_LEAVE,
                      (
                      CASE
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.LEAVE_ID    IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NOT NULL
                        THEN 1
                        ELSE 0
                      END) AS IS_PRESENT,
                      (
                      CASE
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.LEAVE_ID   IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL
                        THEN 1
                        ELSE 0
                      END) AS IS_ABSENT,
                      (
                      CASE
                        WHEN AD.LEAVE_ID   IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL 
                          AND  AD.DAYOFF_FLAG='Y'
                        THEN 1
                        ELSE 0
                      END) AS IS_DAYOFF
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    JOIN HRIS_EMPLOYEES E
                    ON (AD.EMPLOYEE_ID = E.EMPLOYEE_ID),
                      ( SELECT FROM_DATE,TO_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID=$monthId
                      ) M
                    WHERE AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE
                    AND E.DEPARTMENT_ID=$departmentId
                    ORDER BY AD.ATTENDANCE_DT,
                      E.EMPLOYEE_ID
EOT;
//        echo $sql;
//        die();
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function branchWiseEmployeeMonthReport($branchId) {
        $sql = <<<EOT
                SELECT J.*,
                  JE.FIRST_NAME AS FIRST_NAME,
                    JE.MIDDLE_NAME AS MIDDLE_NAME,
                    JE.LAST_NAME AS LAST_NAME,
                    CONCAT(CONCAT(CONCAT(JE.FIRST_NAME,' '),CONCAT(JE.MIDDLE_NAME, '')),JE.LAST_NAME) AS FULL_NAME,
                  JM.MONTH_EDESC
                FROM
                  (SELECT I.EMPLOYEE_ID,
                    I.MONTH_ID ,
                    SUM(I.ON_LEAVE)    AS ON_LEAVE,
                    SUM (I.IS_PRESENT) AS IS_PRESENT,
                    SUM(I.IS_ABSENT)   AS IS_ABSENT
                  FROM
                    (SELECT E.EMPLOYEE_ID AS EMPLOYEE_ID,
                      (SELECT M.MONTH_ID
                      FROM HRIS_MONTH_CODE M
                      WHERE AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE
                      ) AS MONTH_ID,
                      (
                  CASE 
                    WHEN AD.DAYOFF_FLAG ='N'
                    AND AD.HOLIDAY_ID  IS NULL
                    AND AD.TRAINING_ID IS NULL
                    AND AD.TRAVEL_ID   IS NULL
                    AND AD.IN_TIME     IS NULL
                    AND AD.LEAVE_ID IS NOT NULL
                    THEN 1
                    ELSE 0
                  END) AS ON_LEAVE,
                      (
                  CASE
                    WHEN AD.DAYOFF_FLAG ='N'
                    AND AD.LEAVE_ID    IS NULL
                    AND AD.HOLIDAY_ID  IS NULL
                    AND AD.TRAINING_ID IS NULL
                    AND AD.TRAVEL_ID   IS NULL
                    AND AD.IN_TIME     IS NOT NULL
                    THEN 1
                    ELSE 0
                  END) AS IS_PRESENT,
                     (
                  CASE
                    WHEN AD.DAYOFF_FLAG ='N'
                    AND AD.LEAVE_ID   IS NULL
                    AND AD.HOLIDAY_ID  IS NULL
                    AND AD.TRAINING_ID IS NULL
                    AND AD.TRAVEL_ID   IS NULL
                    AND AD.IN_TIME     IS NULL
                    THEN 1
                    ELSE 0
                  END) AS IS_ABSENT
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    JOIN HRIS_EMPLOYEES E
                    ON (AD.EMPLOYEE_ID = E.EMPLOYEE_ID)
                    WHERE E.BRANCH_ID=$branchId
                    ) I
                  GROUP BY I.EMPLOYEE_ID,
                    I.MONTH_ID
                  ) J
                JOIN HRIS_EMPLOYEES JE
                ON (J.EMPLOYEE_ID = JE.EMPLOYEE_ID)
                JOIN HRIS_MONTH_CODE JM
                ON (J.MONTH_ID = JM.MONTH_ID)
EOT;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function getCompanyBranchDepartment() {
        $sql = <<<EOT
            SELECT C.COMPANY_ID,
              C.COMPANY_NAME,
              B.BRANCH_ID,
              B.BRANCH_NAME,
              D.DEPARTMENT_ID,
              D.DEPARTMENT_NAME
            FROM HRIS_COMPANY C
            LEFT JOIN HRIS_BRANCHES B
            ON (C.COMPANY_ID =B.COMPANY_ID)
            LEFT  JOIN HRIS_DEPARTMENTS D
            ON (D.BRANCH_ID=B.BRANCH_ID)
EOT;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function getMonthList() {
        $sql = <<<EOT
            SELECT AM.MONTH_ID,M.MONTH_EDESC FROM
            (SELECT  UNIQUE (SELECT M.MONTH_ID
                FROM HRIS_MONTH_CODE M
                WHERE AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE
                ) AS MONTH_ID
            FROM HRIS_ATTENDANCE_DETAIL AD) AM JOIN HRIS_MONTH_CODE M ON (M.MONTH_ID=AM.MONTH_ID) 
EOT;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function getEmployeeList() {
        $sql = <<<EOT
            SELECT E.EMPLOYEE_ID                                                             AS EMPLOYEE_ID,
              E.FIRST_NAME                                                                   AS FIRST_NAME,
              E.MIDDLE_NAME                                                                  AS MIDDLE_NAME,
              E.LAST_NAME                                                                    AS LAST_NAME,
              EMPLOYEE_CODE||'-'||FULL_NAME                                                                      AS FULL_NAME,
              E.COMPANY_ID                                                                   AS COMPANY_ID,
              E.BRANCH_ID                                                                    AS BRANCH_ID,
              E.DEPARTMENT_ID                                                                AS DEPARTMENT_ID
            FROM HRIS_EMPLOYEES E
EOT;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function fetchAllLeave() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(LeaveMaster::class, [LeaveMaster::LEAVE_ENAME], NULL, NULL, NULL, NULL, 'L', false, false, null, ["REPLACE(l.leave_ename, ' ', '') AS LEAVE_TRIM_ENAME"]), false);
        $select->from(['L' => LeaveMaster::TABLE_NAME]);
        $select->where(["L.STATUS='E'"]);
        $select->order(LeaveMaster::LEAVE_ID . " ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return Helper::extractDbData($result);
//        return $result;
    }

    private function leaveIn($allLeave) {
        $leaveCount = count($allLeave);

        $leaveIn = "";
        $i = 1;
        foreach ($allLeave as $leave) {
            $leaveIn .= $leave['LEAVE_ID'];
            if ($i < $leaveCount) {
                $leaveIn .= ',';
            }
            $i++;
        }
        return $leaveIn;
    }

    private function convertLeaveIdToName($allLeave, $leaveData, $name) {
        $columnData = [];
        foreach ($leaveData as $report) {
            $tempData = [
//                'EMPLOYEE_ID' => $report['EMPLOYEE_ID'],
                'NAME' => $report[$name]
            ];
            foreach ($allLeave as $leave) {
                $tempData[$leave['LEAVE_TRIM_ENAME']] = $report[$leave['LEAVE_ID']];
            }
            array_push($columnData, $tempData);
        }
//            print_r($columnData);
//            die();
        return $columnData;
    }

    public function filterLeaveReportEmployee($data) {

        $allLeave = $this->fetchAllLeave();
        $leaveIn = $this->leaveIn($allLeave);

        $companyCondition = "";
        $branchCondition = "";
        $departmentCondition = "";
        $designationCondition = "";
        $positionCondition = "";
        $serviceTypeCondition = "";
        $serviceEventTypeConditon = "";
        $employeeCondition = "";
        $employeeTypeCondition = "";

        $fromCondition = "";
        $toCondition = "";

        if (isset($data['companyId']) && $data['companyId'] != null && $data['companyId'] != -1) {
            $companyCondition = "AND E.COMPANY_ID = {$data['companyId']}";
        }
        if (isset($data['branchId']) && $data['branchId'] != null && $data['branchId'] != -1) {
            $branchCondition = "AND E.BRANCH_ID = {$data['branchId']}";
        }
        if (isset($data['departmentId']) && $data['departmentId'] != null && $data['departmentId'] != -1) {
            $departmentCondition = "AND E.DEPARTMENT_ID = {$data['departmentId']}";
        }
        if (isset($data['designationId']) && $data['designationId'] != null && $data['designationId'] != -1) {
            $designationCondition = "AND E.DESIGNATION_ID = {$data['designationId']}";
        }
        if (isset($data['positionId']) && $data['positionId'] != null && $data['positionId'] != -1) {
            $positionCondition = "AND E.POSITION_ID = {$data['positionId']}";
        }
        if (isset($data['serviceTypeId']) && $data['serviceTypeId'] != null && $data['serviceTypeId'] != -1) {
            $serviceTypeCondition = "AND E.SERVICE_TYPE_ID = {$data['serviceTypeId']}";
        }
        if (isset($data['serviceEventTypeId']) && $data['serviceEventTypeId'] != null && $data['serviceEventTypeId'] != -1) {
            $serviceEventTypeConditon = "AND E.SERVICE_EVENT_TYPE_ID = {$data['serviceEventTypeId']}";
        }
        if (isset($data['employeeId']) && $data['employeeId'] != null && $data['employeeId'] != -1) {
            $employeeCondition = "AND E.EMPLOYEE_ID = {$data['employeeId']}";
        }
        if (isset($data['employeeTypeId']) && $data['employeeTypeId'] != null && $data['employeeTypeId'] != -1) {
            $employeeTypeCondition = "AND E.EMPLOYEE_TYPE = '{$data['employeeTypeId']}'";
        }
        $condition = $companyCondition . $branchCondition . $departmentCondition . $designationCondition . $positionCondition . $serviceTypeCondition . $serviceEventTypeConditon . $employeeCondition . $employeeTypeCondition;

        if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
            $fromDate = Helper::getExpressionDate($data['fromDate']);
            $fromCondition = "AND AD.ATTENDANCE_DT >= {$fromDate->getExpression()}";
        }
        if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
            $toDate = Helper::getExpressionDate($data['toDate']);
            $toCondition = "AND AD.ATTENDANCE_DT <= {$toDate->getExpression()}";
        }
        $dateCondition = $fromCondition . $toCondition;
        $sql = <<<EOT
                
                            SELECT
                e.full_name,
                leave.*
            FROM
                hris_employees e
                LEFT JOIN (
                    select * from (SELECT
                        ad.employee_id,
                        ad.leave_id,
                        COUNT(ad.leave_id) AS leave_days
                    FROM
                        hris_attendance_detail ad
                    WHERE
                            ad.leave_id IS NOT NULL
                            {$dateCondition}
                    GROUP BY
                        ad.employee_id,
                        ad.leave_id)PIVOT ( SUM ( leave_days )
                        FOR leave_id
                        IN ( {$leaveIn})
                    )
                ) leave ON (
                    e.employee_id = leave.employee_id
                )
            WHERE
                1 = 1
              {$condition}
EOT;

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        $extractedResult = Helper::extractDbData($result);
        return $this->convertLeaveIdToName($allLeave, $extractedResult, 'FULL_NAME');
    }

    public function filterLeaveReportBranch($data) {

        $allLeave = $this->fetchAllLeave();
        $leaveIn = $this->leaveIn($allLeave);
        $fromCondition = "";
        $toCondition = "";


        if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
            $fromDate = Helper::getExpressionDate($data['fromDate']);
            $fromCondition = "AND AD.ATTENDANCE_DT >= {$fromDate->getExpression()}";
        }
        if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
            $toDate = Helper::getExpressionDate($data['toDate']);
            $toCondition = "AND AD.ATTENDANCE_DT <= {$toDate->getExpression()}";
        }
        $dateCondition = $fromCondition . $toCondition;
        $sql = <<<EOT
                
                SELECT BB.BRANCH_NAME,AA.* FROM (SELECT
                          *
                        FROM
                          (
                            SELECT
                              AD.LEAVE_ID,
                              B.BRANCH_ID,
                              COUNT(AD.LEAVE_ID) AS LEAVE_DAYS
                            FROM
                              HRIS_ATTENDANCE_DETAIL AD
                            JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
                            JOIN HRIS_BRANCHES B ON (B.BRANCH_ID=E.BRANCH_ID)
                            WHERE
                              AD.LEAVE_ID IS NOT NULL
                            {$dateCondition}
                               GROUP BY
                              B.BRANCH_ID,
                              AD.LEAVE_ID
                          )
                          PIVOT ( SUM ( LEAVE_DAYS )
                                                FOR leave_id
                                                IN ({$leaveIn}))) AA
                                                RIGHT JOIN HRIS_BRANCHES BB ON (AA.BRANCH_ID=BB.BRANCH_ID AND BB.STATUS='E')
    
                
                          
EOT;

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        $extractedResult = Helper::extractDbData($result);
        return $this->convertLeaveIdToName($allLeave, $extractedResult, 'BRANCH_NAME');
    }

    public function filterLeaveReportDepartmnet($data) {
        $allLeave = $this->fetchAllLeave();
        $leaveIn = $this->leaveIn($allLeave);
        $fromCondition = "";
        $toCondition = "";


        if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
            $fromDate = Helper::getExpressionDate($data['fromDate']);
            $fromCondition = "AND AD.ATTENDANCE_DT >= {$fromDate->getExpression()}";
        }
        if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
            $toDate = Helper::getExpressionDate($data['toDate']);
            $toCondition = "AND AD.ATTENDANCE_DT <= {$toDate->getExpression()}";
        }
        $dateCondition = $fromCondition . $toCondition;
        $sql = <<<EOT
                                SELECT
                  AA.*,BB.DEPARTMENT_NAME
                FROM (SELECT
                  *
                FROM
                  (
                    SELECT
                      AD.LEAVE_ID,
                      D.DEPARTMENT_ID,
                      COUNT(AD.LEAVE_ID) AS LEAVE_DAYS
                    FROM
                      HRIS_ATTENDANCE_DETAIL AD
                    JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
                    JOIN HRIS_DEPARTMENTS D ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
                    WHERE
                      AD.LEAVE_ID IS NOT NULL
                        {$dateCondition}
                       GROUP BY
                      D.DEPARTMENT_ID,
                      AD.LEAVE_ID
                  )
                  PIVOT ( SUM ( LEAVE_DAYS )
                        FOR leave_id
                        IN ({$leaveIn}))) AA
                        RIGHT JOIN HRIS_DEPARTMENTS BB ON (AA.DEPARTMENT_ID=BB.DEPARTMENT_ID AND BB.STATUS='E')
    
                
                          
EOT;

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        $extractedResult = Helper::extractDbData($result);
        return $this->convertLeaveIdToName($allLeave, $extractedResult, 'DEPARTMENT_NAME');
    }

    public function filterLeaveReportDesignation($data) {
        $allLeave = $this->fetchAllLeave();
        $leaveIn = $this->leaveIn($allLeave);
        $fromCondition = "";
        $toCondition = "";


        if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
            $fromDate = Helper::getExpressionDate($data['fromDate']);
            $fromCondition = "AND AD.ATTENDANCE_DT >= {$fromDate->getExpression()}";
        }
        if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
            $toDate = Helper::getExpressionDate($data['toDate']);
            $toCondition = "AND AD.ATTENDANCE_DT <= {$toDate->getExpression()}";
        }
        $dateCondition = $fromCondition . $toCondition;
        $sql = <<<EOT
                
                SELECT
  AA.*,BB.DESIGNATION_TITLE
FROM (SELECT
  *
FROM
  (
    SELECT
      AD.LEAVE_ID,
      D.DESIGNATION_ID,
      COUNT(AD.LEAVE_ID) AS LEAVE_DAYS
    FROM
      HRIS_ATTENDANCE_DETAIL AD
    JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
    JOIN HRIS_DESIGNATIONS D ON (D.DESIGNATION_ID=E.DESIGNATION_ID)
    WHERE
      AD.LEAVE_ID IS NOT NULL
                {$dateCondition}
       GROUP BY
      D.DESIGNATION_ID,
      AD.LEAVE_ID
  )
  PIVOT ( SUM ( LEAVE_DAYS )
                        FOR leave_id
                        IN ({$leaveIn}))) AA
                        RIGHT JOIN HRIS_DESIGNATIONS BB ON (AA.DESIGNATION_ID=BB.DESIGNATION_ID AND BB.STATUS='E')
    
        
        
EOT;

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        $extractedResult = Helper::extractDbData($result);
        return $this->convertLeaveIdToName($allLeave, $extractedResult, 'DESIGNATION_TITLE');
    }

    public function filterLeaveReportPosition($data) {
        $allLeave = $this->fetchAllLeave();
        $leaveIn = $this->leaveIn($allLeave);
        $fromCondition = "";
        $toCondition = "";


        if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
            $fromDate = Helper::getExpressionDate($data['fromDate']);
            $fromCondition = "AND AD.ATTENDANCE_DT >= {$fromDate->getExpression()}";
        }
        if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
            $toDate = Helper::getExpressionDate($data['toDate']);
            $toCondition = "AND AD.ATTENDANCE_DT <= {$toDate->getExpression()}";
        }
        $dateCondition = $fromCondition . $toCondition;
        $sql = <<<EOT
                
                SELECT
  AA.*,BB.POSITION_NAME
FROM (SELECT
  *
FROM
  (
    SELECT
      AD.LEAVE_ID,
      P.POSITION_ID,
      COUNT(AD.LEAVE_ID) AS LEAVE_DAYS
    FROM
      HRIS_ATTENDANCE_DETAIL AD
    JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
    JOIN HRIS_POSITIONS P ON (P.POSITION_ID=E.POSITION_ID)
    WHERE
      AD.LEAVE_ID IS NOT NULL
        {$dateCondition}
       GROUP BY
      P.POSITION_ID,
      AD.LEAVE_ID
  )
  PIVOT ( SUM ( LEAVE_DAYS )
                        FOR leave_id
                        IN ({$leaveIn}))) AA
                        RIGHT JOIN HRIS_POSITIONS BB ON (AA.POSITION_ID=BB.POSITION_ID AND BB.STATUS='E')
        
        
EOT;

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        $extractedResult = Helper::extractDbData($result);
        return $this->convertLeaveIdToName($allLeave, $extractedResult, 'POSITION_NAME');
    }

    public function FetchNepaliMonth() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Months::class, NULL, [Months::FROM_DATE, Months::TO_DATE], NULL, NULL, NULL, 'M', true), false);
        $select->from(['M' => Months::TABLE_NAME])
            ->join(['FY' => FiscalYear::TABLE_NAME], 'FY.' . FiscalYear::FISCAL_YEAR_ID . '=M.' . Months::FISCAL_YEAR_ID, ["MONTH_NAME" => new Expression('CONCAT(FY.FISCAL_YEAR_NAME,M.MONTH_EDESC)')], "left");
        $select->where(["M.STATUS='E'", "FY.STATUS='E'", "TRUNC(SYSDATE)>M.FROM_DATE"]);
        $select->order("M." . Months::FROM_DATE . " DESC");
        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    private function totalHiredEmployees($fromDate, $toDate) {
        $sql = "select count(*)as TOTAL from hris_employees 
            where JOIN_DATE BETWEEN " . $fromDate->getExpression() . " and " . $toDate->getExpression() . " and status='E'";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
    }

    public function CalculateHireEmployees($data) {
        $returnArr = [];
        foreach ($data as $details) {
            $name = $details->name;
            $tempData = [
                'NAME' => $name
            ];
            $fromDate = Helper::getExpressionDate($details->fromDate);
            $toDate = Helper::getExpressionDate($details->toDate);
            $total = $this->totalHiredEmployees($fromDate, $toDate);
            $tempData['TOTAL'] = $total['TOTAL'];
            $sql = "select full_name,JOIN_DATE from hris_employees 
            where JOIN_DATE BETWEEN " . $fromDate->getExpression() . " and " . $toDate->getExpression() . " and status='E'";
            $statement = $this->adapter->query($sql);
            $result = $statement->execute();
            $tempData['DATA'] = Helper::extractDbData($result);
            array_push($returnArr, $tempData);
        }
        return $returnArr;
    }

    public function branchWiseDailyReport($monthId, $branchId) {

        $sql = <<<EOT
                      SELECT 
                      TRUNC(AD.ATTENDANCE_DT)-TRUNC(M.FROM_DATE)+1                              AS DAY_COUNT, 
                      E.EMPLOYEE_ID                                                             AS EMPLOYEE_ID ,
                      E.FIRST_NAME                                                                   AS FIRST_NAME,
                      E.MIDDLE_NAME                                                                  AS MIDDLE_NAME,
                      E.LAST_NAME                                                                    AS LAST_NAME,
                      CONCAT(CONCAT(CONCAT(E.FIRST_NAME,' '),CONCAT(E.MIDDLE_NAME, '')),E.LAST_NAME) AS FULL_NAME,
                      AD.ATTENDANCE_DT                                                               AS ATTENDANCE_DT,
                      (
                      CASE 
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL
                        AND AD.LEAVE_ID IS NOT NULL
                        THEN 1
                        ELSE 0
                      END) AS ON_LEAVE,
                      (
                      CASE
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.LEAVE_ID    IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NOT NULL
                        THEN 1
                        ELSE 0
                      END) AS IS_PRESENT,
                      (
                      CASE
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.LEAVE_ID   IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL
                        THEN 1
                        ELSE 0
                      END) AS IS_ABSENT,
                      (
                      CASE
                        WHEN AD.LEAVE_ID   IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL 
                          AND  AD.DAYOFF_FLAG='Y'
                        THEN 1
                        ELSE 0
                      END) AS IS_DAYOFF
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    JOIN HRIS_EMPLOYEES E
                    ON (AD.EMPLOYEE_ID = E.EMPLOYEE_ID),
                      ( SELECT FROM_DATE,TO_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID=$monthId
                      ) M
                    WHERE AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE
                    AND E.BRANCH_ID=$branchId
                    ORDER BY AD.ATTENDANCE_DT,
                      E.EMPLOYEE_ID
EOT;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function checkIfEmpowerTableExists() {
        return $this->checkIfTableExists('HR_MONTHLY_MODIFIED_PAY_VALUE');
    }

    public function loadData($fiscalYearId, $fiscalYearMonthNo) {
        $sql = "
            BEGIN
              HRIS_PREPARE_PAYROLL_DATA({$fiscalYearId},{$fiscalYearMonthNo});
            END;
            ";
        $this->executeStatement($sql);
    }

    public function reportWithOT($data) {
        $fromCondition = "";
        $toCondition = "";

        $otFromCondition = "";
        $otToCondition = "";

        $condition = EntityHelper::getSearchConditon($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId'], $data['genderId'], $data['locationId']);

        if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
            $fromDate = Helper::getExpressionDate($data['fromDate']);
            $fromCondition = "AND A.ATTENDANCE_DT >= {$fromDate->getExpression()}";
            $otFromCondition = "AND OVERTIME_DATE >= {$fromDate->getExpression()} ";
        }
        if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
            $toDate = Helper::getExpressionDate($data['toDate']);
            $toCondition = "AND A.ATTENDANCE_DT <= {$toDate->getExpression()}";
            $otToCondition = "AND OVERTIME_DATE <= {$toDate->getExpression()} ";
        }



        $sql = <<<EOT
            SELECT C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              A.EMPLOYEE_ID,
              E.FULL_NAME,
              A.DAYOFF,
              A.PRESENT,
              A.HOLIDAY,
              A.LEAVE,
              A.PAID_LEAVE,
              A.UNPAID_LEAVE,
              A.ABSENT,
              NVL(ROUND(A.TOTAL_MIN/60,2),0) AS OVERTIME_HOUR,
              A.TRAVEL,
              A.TRAINING,
              A.WORK_ON_HOLIDAY,
              A.WORK_ON_DAYOFF
            FROM
              (SELECT A.EMPLOYEE_ID,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN( 'DO','WD')
                  THEN 1
                  ELSE 0
                END) AS DAYOFF,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('PR','BA','LA','TV','VP','TN','TP','LP')
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS PRESENT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('HD','WH')
                  THEN 1
                  ELSE 0
                END) AS HOLIDAY,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  AND L.PAID             = 'Y'
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS PAID_LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  AND L.PAID             = 'N'
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS UNPAID_LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS = 'AB'
                  THEN 1
                  ELSE 0
                END) AS ABSENT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS= 'TV'
                  THEN 1
                  ELSE 0
                END) AS TRAVEL,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS ='TN'
                  THEN 1
                  ELSE 0
                END) AS TRAINING,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS = 'WH'
                  THEN 1
                  ELSE 0
                END) WORK_ON_HOLIDAY,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS ='WD'
                  THEN 1
                  ELSE 0
                END) WORK_ON_DAYOFF,
                 SUM(
                  CASE
                    WHEN OTM.OVERTIME_HOUR IS NULL
                    THEN OT.TOTAL_HOUR
                    ELSE OTM.OVERTIME_HOUR*60
                  END ) AS TOTAL_MIN
              FROM HRIS_ATTENDANCE_PAYROLL A
              LEFT JOIN (SELECT
    employee_id,
    overtime_date,
    SUM(total_hour) AS total_hour
FROM
    hris_overtime where status ='AP'
GROUP BY
    employee_id,
    overtime_date) OT
              ON (A.EMPLOYEE_ID   =OT.EMPLOYEE_ID
              AND A.ATTENDANCE_DT =OT.OVERTIME_DATE)
              LEFT JOIN HRIS_OVERTIME_MANUAL OTM
              ON (A.EMPLOYEE_ID   =OTM.EMPLOYEE_ID
              AND A.ATTENDANCE_DT =OTM.ATTENDANCE_DATE)
              LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
              ON (A.LEAVE_ID= L.LEAVE_ID)
              WHERE 1       =1 {$fromCondition} {$toCondition}
              GROUP BY A.EMPLOYEE_ID
              ) A
            LEFT JOIN HRIS_EMPLOYEES E
            ON(A.EMPLOYEE_ID = E.EMPLOYEE_ID)
            LEFT JOIN HRIS_COMPANY C
            ON(E.COMPANY_ID= C.COMPANY_ID)
            LEFT JOIN HRIS_DEPARTMENTS D
            ON (E.DEPARTMENT_ID= D.DEPARTMENT_ID)
            WHERE 1            =1 {$condition}
            ORDER BY C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              E.FULL_NAME 
EOT;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function toEmpower($fiscalYearId, $fiscalYearMonthNo) {
        $sql = "BEGIN HRIS_TO_EMPOWER({$fiscalYearId},{$fiscalYearMonthNo}); END;";
        $this->executeStatement($sql);
    }

    public function departmentMonthReport($fiscalYearId) {
        $sql = <<<EOT
            SELECT D.DEPARTMENT_NAME,
              R.*
            FROM
              (SELECT *
              FROM
                (SELECT EMC.FISCAL_YEAR_MONTH_NO,
                  EMC.DEPARTMENT_ID,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS IN( 'DO','WD')
                    THEN 1
                    ELSE 0
                  END) AS DAYOFF,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS IN ('PR','BA','LA','TV','VP','TN','TP','LP')
                    THEN (
                      CASE
                        WHEN A.OVERALL_STATUS = 'LP'
                        AND A.HALFDAY_PERIOD IS NOT NULL
                        THEN 0.5
                        ELSE 1
                      END)
                    ELSE 0
                  END) AS PRESENT,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS IN ('HD','WH')
                    THEN 1
                    ELSE 0
                  END) AS HOLIDAY,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS IN ('LV','LP')
                    AND A.GRACE_PERIOD    IS NULL
                    THEN (
                      CASE
                        WHEN A.OVERALL_STATUS = 'LP'
                        AND A.HALFDAY_PERIOD IS NOT NULL
                        THEN 0.5
                        ELSE 1
                      END)
                    ELSE 0
                  END) AS LEAVE,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS = 'AB'
                    THEN 1
                    ELSE 0
                  END) AS ABSENT,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS = 'WH'
                    THEN 1
                    ELSE 0
                  END) WORK_ON_HOLIDAY,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS ='WD'
                    THEN 1
                    ELSE 0
                  END) WORK_ON_DAYOFF
                FROM
                  (SELECT * FROM HRIS_MONTH_CODE,HRIS_EMPLOYEES
                  ) EMC
                LEFT JOIN HRIS_ATTENDANCE_DETAIL A
                ON ((A.ATTENDANCE_DT BETWEEN EMC.FROM_DATE AND EMC.TO_DATE)
                AND (EMC.EMPLOYEE_ID=A.EMPLOYEE_ID))
                LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
                ON (A.LEAVE_ID          = L.LEAVE_ID)
                WHERE EMC.FISCAL_YEAR_ID={$fiscalYearId}
                GROUP BY EMC.DEPARTMENT_ID,
                  EMC.FISCAL_YEAR_MONTH_NO
                ) PIVOT (MAX(PRESENT) AS PRESENT,MAX(ABSENT) AS ABSENT,MAX(LEAVE) AS LEAVE,MAX(DAYOFF) AS DAYOFF,MAX(HOLIDAY) AS HOLIDAY,MAX(WORK_ON_HOLIDAY) AS WOH,MAX(WORK_ON_DAYOFF) AS WOD FOR FISCAL_YEAR_MONTH_NO IN (1 AS one,2 AS two,3 AS three,4 AS four,5 AS five,6 AS six,7 AS seven,8 AS eight,9 AS nine,10 AS ten,11 AS eleven,12 AS twelve))
              ) R
            JOIN HRIS_DEPARTMENTS D
            ON (R.DEPARTMENT_ID=D.DEPARTMENT_ID)       
EOT;

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function employeeMonthlyReport($searchQuery) {
        $searchConditon = EntityHelper::getSearchConditon($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId']);
        $sql = <<<EOT
                SELECT D.FULL_NAME, D.EMPLOYEE_CODE,
                  R.*
                FROM
                  (SELECT *
                  FROM
                    (SELECT EMC.FISCAL_YEAR_MONTH_NO,
                      EMC.EMPLOYEE_ID,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS IN( 'DO','WD')
                        THEN 1
                        ELSE 0
                      END) AS DAYOFF,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS IN ('PR','BA','LA','TV','VP','TN','TP','LP')
                        THEN (
                          CASE
                            WHEN A.OVERALL_STATUS = 'LP'
                            AND A.HALFDAY_PERIOD IS NOT NULL
                            THEN 0.5
                            ELSE 1
                          END)
                        ELSE 0
                      END) AS PRESENT,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS IN ('HD','WH')
                        THEN 1
                        ELSE 0
                      END) AS HOLIDAY,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS IN ('LV','LP')
                        AND A.GRACE_PERIOD    IS NULL
                        THEN (
                          CASE
                            WHEN A.OVERALL_STATUS = 'LP'
                            AND A.HALFDAY_PERIOD IS NOT NULL
                            THEN 0.5
                            ELSE 1
                          END)
                        ELSE 0
                      END) AS LEAVE,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS = 'AB'
                        THEN 1
                        ELSE 0
                      END) AS ABSENT,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS = 'WH'
                        THEN 1
                        ELSE 0
                      END) WORK_ON_HOLIDAY,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS ='WD'
                        THEN 1
                        ELSE 0
                      END) WORK_ON_DAYOFF
                    FROM
                      (SELECT * FROM HRIS_MONTH_CODE MC,HRIS_EMPLOYEES E
                            WHERE 1=1 
                            {$searchConditon}
                      ) EMC
                    LEFT JOIN HRIS_ATTENDANCE_DETAIL A
                    ON ((A.ATTENDANCE_DT BETWEEN EMC.FROM_DATE AND EMC.TO_DATE)
                    AND (EMC.EMPLOYEE_ID=A.EMPLOYEE_ID))
                    LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
                    ON (A.LEAVE_ID          = L.LEAVE_ID)
                    WHERE EMC.FISCAL_YEAR_ID={$searchQuery['fiscalYearId']}
                    GROUP BY EMC.EMPLOYEE_ID,
                      EMC.FISCAL_YEAR_MONTH_NO
                    ) PIVOT (MAX(PRESENT) AS PRESENT,MAX(ABSENT) AS ABSENT,MAX(LEAVE) AS LEAVE,MAX(DAYOFF) AS DAYOFF,MAX(HOLIDAY) AS HOLIDAY,MAX(WORK_ON_HOLIDAY) AS WOH,MAX(WORK_ON_DAYOFF) AS WOD FOR FISCAL_YEAR_MONTH_NO IN (1 AS one,2 AS two,3 AS three,4 AS four,5 AS five,6 AS six,7 AS seven,8 AS eight,9 AS nine,10 AS ten,11 AS eleven,12 AS twelve))
                  ) R
                JOIN HRIS_EMPLOYEES D
                ON (R.EMPLOYEE_ID=D.EMPLOYEE_ID)                   
EOT;

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }
    
    public function employeeDailyReport($searchQuery){
        $monthDetail=$this->getMonthDetails($searchQuery['monthCodeId']);
        
         $pivotString = '';
        for ($i = 1; $i <= $monthDetail['DAYS']; $i++) {
            if ($i != $monthDetail['DAYS']) {
                $pivotString .= $i . ' AS ' . 'D' . $i . ', ';
            } else {
                $pivotString .= $i . ' AS ' . 'D' . $i;
            }
        }
        
        
        
        $searchConditon = EntityHelper::getSearchConditon($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId']);
        $sql = <<<EOT
             SELECT PL.*,
                CL.PRESENT,
                CL.ABSENT,
                CL.LEAVE,
                CL.DAYOFF,
                CL.HOLIDAY,
                CL.WORK_DAYOFF,
                CL.WORK_HOLIDAY,
                 (CL.PRESENT+CL.ABSENT+CL.LEAVE+CL.DAYOFF+CL.HOLIDAY+CL.WORK_DAYOFF+CL.WORK_HOLIDAY) as TOTAL
      FROM
      (SELECT * FROM 
(SELECT 
E.FULL_NAME,
AD.EMPLOYEE_ID,
E.EMPLOYEE_CODE,
CASE WHEN AD.OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
THEN 'PR' ELSE AD.OVERALL_STATUS END AS OVERALL_STATUS,
--AD.ATTENDANCE_DT,
(AD.ATTENDANCE_DT-MC.FROM_DATE+1) AS DAY_COUNT
FROM HRIS_ATTENDANCE_DETAIL AD
LEFT JOIN HRIS_MONTH_CODE MC ON (AD.ATTENDANCE_DT BETWEEN MC.FROM_DATE AND MC.TO_DATE)
JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
WHERE MC.MONTH_ID={$searchQuery['monthCodeId']}
{$searchConditon}
    )
PIVOT (MAX (OVERALL_STATUS)  FOR DAY_COUNT
                        IN ({$pivotString}))
                        ) PL
   LEFT JOIN (SELECT
    EMPLOYEE_ID,
    COUNT(case  when OVERALL_STATUS  IN ('TV','TN','PR','BA','LA','TP','LP','VP') then 1 end) AS PRESENT,
    COUNT(case OVERALL_STATUS when 'AB' then 1 end) AS ABSENT,
    COUNT(case OVERALL_STATUS when 'LV' then 1 end) AS LEAVE,
    COUNT(case OVERALL_STATUS when 'DO' then 1 end) AS DAYOFF,
    COUNT(case OVERALL_STATUS when 'HD' then 1 end) AS HOLIDAY,
    COUNT(case OVERALL_STATUS when 'WD' then 1 end) AS WORK_DAYOFF,
    COUNT(case OVERALL_STATUS when 'WH' then 1 end) AS WORK_HOLIDAY
        FROM HRIS_ATTENDANCE_DETAIL
        WHERE
         ATTENDANCE_DT BETWEEN   TO_DATE('{$monthDetail['FROM_DATE']}','DD-MON-YY') AND   TO_DATE('{$monthDetail['TO_DATE']}','DD-MON-YY')
        GROUP BY EMPLOYEE_ID)CL ON (PL.EMPLOYEE_ID=CL.EMPLOYEE_ID)
                
EOT;
         
//         echo $sql;
//         die();
                $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return ['monthDetail'=>$monthDetail,'data'=>Helper::extractDbData($result)];
    }
    
    
    public function getMonthDetails($monthId){
        $sql="SELECT 
    FROM_DATE,TO_DATE,TO_DATE-FROM_DATE+1 AS DAYS FROM 
    HRIS_MONTH_CODE WHERE MONTH_ID={$monthId}";
    
    $statement = $this->adapter->query($sql);
    $result = $statement->execute()->current();
     return $result;   
    }
    
    public function employeeYearlyReport($employeeId,$fiscalYearId){
        $sql = <<<EOT
                
                SELECT PL.*,
           CL.PRESENT,
                CL.ABSENT,
                CL.LEAVE,
                CL.DAYOFF,
                CL.HOLIDAY,
                CL.WORK_DAYOFF,
                CL.WORK_HOLIDAY,
                 (CL.PRESENT+CL.ABSENT+CL.LEAVE+CL.DAYOFF+CL.HOLIDAY+CL.WORK_DAYOFF+CL.WORK_HOLIDAY) as TOTAL
           FROM  (SELECT * FROM (SELECT  
E.FULL_NAME,
AD.EMPLOYEE_ID,
CASE WHEN AD.OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
THEN 'PR' ELSE AD.OVERALL_STATUS END AS OVERALL_STATUS,
                MC.MONTH_ID,
                MC.YEAR||MC.MONTH_EDESC AS MONTH_DTL,
                MC.FISCAL_YEAR_MONTH_NO,
(AD.ATTENDANCE_DT-MC.FROM_DATE+1) AS DAY_COUNT
FROM HRIS_ATTENDANCE_DETAIL AD
LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
JOIN (SELECT * FROM HRIS_MONTH_CODE WHERE FISCAL_YEAR_ID={$fiscalYearId} ) MC ON (AD.ATTENDANCE_DT BETWEEN MC.FROM_DATE AND MC.TO_DATE)
WHERE AD.EMPLOYEE_ID = {$employeeId})
PIVOT (MAX(OVERALL_STATUS) FOR DAY_COUNT
                        IN (1 AS D1, 2 AS D2, 3 AS D3, 4 AS D4, 5 AS D5, 6 AS D6, 7 AS D7, 8 AS D8, 9 AS D9, 10 AS D10, 11 AS D11, 12 AS D12, 13 AS D13, 14 AS D14, 15 AS D15, 16 AS D16, 17 AS D17, 18 AS D18, 19 AS D19, 20 AS D20, 21 AS D21, 22 AS D22, 23 AS D23, 24 AS D24, 25 AS D25, 26 AS D26, 27 AS D27, 28 AS D28, 29 AS D29, 30 AS D30, 31 AS D31,
                        32 AS D32)
                        ) ORDER BY FISCAL_YEAR_MONTH_NO) PL
                        LEFT JOIN 
                        (SELECT 
CAD.EMPLOYEE_ID,CMC.MONTH_ID,
    COUNT(case  when CAD.OVERALL_STATUS  IN ('TV','TN','PR','BA','LA','TP','LP','VP') then 1 end) AS PRESENT,
    COUNT(case OVERALL_STATUS when 'AB' then 1 end) AS ABSENT,
    COUNT(case OVERALL_STATUS when 'LV' then 1 end) AS LEAVE,
    COUNT(case OVERALL_STATUS when 'DO' then 1 end) AS DAYOFF,
    COUNT(case OVERALL_STATUS when 'HD' then 1 end) AS HOLIDAY,
    COUNT(case OVERALL_STATUS when 'WD' then 1 end) AS WORK_DAYOFF,
    COUNT(case OVERALL_STATUS when 'WH' then 1 end) AS WORK_HOLIDAY
FROM HRIS_ATTENDANCE_DETAIL CAD
JOIN HRIS_MONTH_CODE CMC ON (CMC.FISCAL_YEAR_ID={$fiscalYearId} AND CAD.ATTENDANCE_DT BETWEEN CMC.FROM_DATE AND CMC.TO_DATE)
WHERE EMPLOYEE_ID={$employeeId} 
GROUP BY CAD.EMPLOYEE_ID,CMC.MONTH_ID)CL ON (PL.MONTH_ID=CL.MONTH_ID)

           
EOT;
                $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }
    
    
    public function getMonthlyAllowance($searchQuery) {
        $fromDate=$searchQuery['fromDate'];
        $toDate=$searchQuery['toDate'];
        
        $searchConditon = EntityHelper::getSearchConditon($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId']);
        $sql="SELECT  
EMPLOYEE_ID,
EMPLOYEE_CODE,
FULL_NAME,
        COMPANY_NAME,
        BRANCH_NAME,
        DEPARTMENT_NAME,
        DESIGNATION_TITLE,
        POSITION_NAME,
        SUM(SYSTEM_OVERTIME) AS SYSTEM_OVERTIME,
        SUM(MANUAL_OVERTIME) AS MANUAL_OVERTIME,
        SUM(FOOD_ALLOWANCE) AS FOOD_ALLOWANCE,
        SUM(SHIFT_ALLOWANCE) AS SHIFT_ALLOWANCE,
        SUM(NIGHT_SHIFT_ALLOWANCE) AS NIGHT_SHIFT_ALLOWANCE,
        SUM(HOLIDAY_COUNT) AS HOLIDAY_COUNT
FROM 
(SELECT
E.EMPLOYEE_ID,
E.EMPLOYEE_CODE,
        E.FULL_NAME,
        C.COMPANY_NAME,
        B.BRANCH_NAME,
        D.DEPARTMENT_NAME,
        DES.DESIGNATION_TITLE,
        P.POSITION_NAME,
        CASE WHEN 
        AD.OT_MINUTES >=0 then ROUND(AD.OT_MINUTES/60,2)
        ELSE
        0
        END AS SYSTEM_OVERTIME,
        CASE WHEN 
        OM.OVERTIME_HOUR IS NOT NULL THEN
        ROUND(OM.OVERTIME_HOUR,2)
        WHEN AD.OT_MINUTES >=0 then ROUND(AD.OT_MINUTES/60,2)
        ELSE
        0
        END
        AS MANUAL_OVERTIME,
        AD.FOOD_ALLOWANCE,
        AD.SHIFT_ALLOWANCE,
        AD.NIGHT_SHIFT_ALLOWANCE,
        AD.HOLIDAY_COUNT
        FROM HRIS_ATTENDANCE_DETAIL AD
        LEFT JOIN HRIS_OVERTIME_MANUAL OM ON (OM.EMPLOYEE_ID=AD.EMPLOYEE_ID AND OM.ATTENDANCE_DATE=AD.ATTENDANCE_DT)
        LEFT JOIN HRIS_EMPLOYEES E ON (AD.EMPLOYEE_ID=E.EMPLOYEE_ID)
        LEFT JOIN HRIS_COMPANY C ON (C.COMPANY_ID=E.COMPANY_ID)
        LEFT JOIN HRIS_BRANCHES B ON (B.BRANCH_ID=E.BRANCH_ID)
        LEFT JOIN HRIS_DEPARTMENTS D ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
        LEFT JOIN HRIS_DESIGNATIONS DES ON (DES.DESIGNATION_ID=E.DESIGNATION_ID)
        LEFT JOIN HRIS_POSITIONS P ON (P.POSITION_ID=E.POSITION_ID)
        WHERE AD.Attendance_Dt
        BETWEEN TO_DATE('{$fromDate}','DD-MON-YYYY') AND TO_DATE('{$toDate}','DD-MON-YYYY') {$searchConditon}
        ) TAB_A
        GROUP BY EMPLOYEE_ID,
EMPLOYEE_CODE,
FULL_NAME,
        COMPANY_NAME,
        BRANCH_NAME, 
        DEPARTMENT_NAME,
        DESIGNATION_TITLE,
        POSITION_NAME ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }
     

public function departmentWiseAttdReport($companyId, $date1, $date2) {
        if($companyId == 0){
          $sql = <<<EOT
          SELECT *
          FROM (SELECT 
          DEPARTMENT_NAME,
          OVERALL_STATUS,
          COUNT(OVERALL_STATUS) AS TOTAL
          FROM (select
          HE.DEPARTMENT_ID,
          HD.DEPARTMENT_NAME,
          CASE 
          WHEN HED.OVERALL_STATUS 
          IN ('TV','TN','PR','BA','LA','TP','LP','VP')
          THEN 'PR' 
          ELSE HED.OVERALL_STATUS END AS OVERALL_STATUS
          from HRIS_ATTENDANCE_DETAIL HED 
          JOIN HRIS_EMPLOYEES HE ON (HE.EMPLOYEE_ID=HED.EMPLOYEE_ID)
          JOIN HRIS_DEPARTMENTS HD ON(HD.DEPARTMENT_ID=HE.DEPARTMENT_ID)
          FULL OUTER JOIN HRIS_COMPANY HC ON(HC.COMPANY_ID=HD.COMPANY_ID) 
          WHERE HED.ATTENDANCE_DT BETWEEN '$date1' and '$date2'
          )
          GROUP BY OVERALL_STATUS,DEPARTMENT_NAME)
          PIVOT (
          MAX(TOTAL) FOR OVERALL_STATUS IN ('PR' as PR,'WD' as WD,'HD' as HD,'LV' as LV,'WH' as WH,'DO' as DO,'AB' as AB)
          )
EOT;
        }
        else{
        $sql = <<<EOT
        SELECT *
        FROM (SELECT 
        DEPARTMENT_NAME,
        OVERALL_STATUS,
        COUNT(OVERALL_STATUS) AS TOTAL
        FROM (select
        HE.DEPARTMENT_ID,
        HD.DEPARTMENT_NAME,
        CASE 
        WHEN HED.OVERALL_STATUS 
        IN ('TV','TN','PR','BA','LA','TP','LP','VP')
        THEN 'PR' 
        ELSE HED.OVERALL_STATUS END AS OVERALL_STATUS
        from HRIS_ATTENDANCE_DETAIL HED 
        JOIN HRIS_EMPLOYEES HE ON (HE.EMPLOYEE_ID=HED.EMPLOYEE_ID)
        JOIN HRIS_DEPARTMENTS HD ON(HD.DEPARTMENT_ID=HE.DEPARTMENT_ID)
        FULL OUTER JOIN HRIS_COMPANY HC ON(HC.COMPANY_ID=HD.COMPANY_ID) 
        WHERE HED.ATTENDANCE_DT BETWEEN '$date1' and '$date2' 
        AND HE.COMPANY_ID = $companyId
        )
        GROUP BY OVERALL_STATUS,DEPARTMENT_NAME)
        PIVOT (
        MAX(TOTAL) FOR OVERALL_STATUS IN ('PR' as PR,'WD' as WD,'HD' as HD,'LV' as LV,'WH' as WH,'DO' as DO,'AB' as AB)
        )
EOT;
        }

//        echo $sql;
//        die();
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }
    
    public function getAllCompanies(){
      $sql = "SELECT COMPANY_ID, COMPANY_NAME FROM HRIS_COMPANY";

      $statement = $this->adapter->query($sql);
      $result = $statement->execute();
      return Helper::extractDbData($result);
    } 

    public function fetchBirthdays($by) {
      $orderByString=EntityHelper::getOrderBy('E.FULL_NAME ASC',null,'E.SENIORITY_LEVEL','P.LEVEL_NO','E.JOIN_DATE','DES.ORDER_NO','E.FULL_NAME');
      $columIfSynergy="";
      $joinIfSyngery="";
      if ($this->checkIfTableExists("FA_CHART_OF_ACCOUNTS_SETUP")) {
          $columIfSynergy="FCAS.ACC_EDESC AS BANK_ACCOUNT,";
          $joinIfSyngery="LEFT JOIN FA_CHART_OF_ACCOUNTS_SETUP FCAS 
              ON(FCAS.ACC_CODE=E.ID_ACC_CODE AND C.COMPANY_CODE=FCAS.COMPANY_CODE)";
      }
      $fromDate = !empty($_POST['fromDate']) ? $_POST['fromDate'] : '01-Jan-2019'; 
      $toDate = !empty($_POST['toDate']) ? $_POST['toDate'] : '31-Dec-2019'; 

      $condition = EntityHelper::getSearchConditon($by['companyId'], $by['branchId'], $by['departmentId'], $by['positionId'], $by['designationId'], $by['serviceTypeId'], $by['serviceEventTypeId'], $by['employeeTypeId'], $by['employeeId'], $by['genderId'], $by['locationId']);
      $sql = "SELECT  
          {$columIfSynergy}
              E.ID_ACCOUNT_NO  AS ID_ACCOUNT_NO,
                E.EMPLOYEE_ID                                                AS EMPLOYEE_ID,
                E.EMPLOYEE_CODE                                                   AS EMPLOYEE_CODE,
                INITCAP(E.FULL_NAME)                                              AS FULL_NAME,
                INITCAP(G.GENDER_NAME)                                            AS GENDER_NAME,
                TO_CHAR(E.BIRTH_DATE, 'DD-MON-YYYY')                              AS BIRTH_DATE_AD,
                BS_DATE(E.BIRTH_DATE)                                             AS BIRTH_DATE_BS,
                TO_CHAR(E.JOIN_DATE, 'DD-MON-YYYY')                               AS JOIN_DATE_AD,
                BS_DATE(E.JOIN_DATE)                                              AS JOIN_DATE_BS,
                INITCAP(CN.COUNTRY_NAME)                                          AS COUNTRY_NAME,
                RG.RELIGION_NAME                                                  AS RELIGION_NAME,
                BG.BLOOD_GROUP_CODE                                               AS BLOOD_GROUP_CODE,
                E.MOBILE_NO                                                       AS MOBILE_NO,
                E.TELEPHONE_NO                                                    AS TELEPHONE_NO,
                E.SOCIAL_ACTIVITY                                                 AS SOCIAL_ACTIVITY,
                E.EXTENSION_NO                                                    AS EXTENSION_NO,
                E.EMAIL_OFFICIAL                                                  AS EMAIL_OFFICIAL,
                E.EMAIL_PERSONAL                                                  AS EMAIL_PERSONAL,
                E.SOCIAL_NETWORK                                                  AS SOCIAL_NETWORK,
                E.ADDR_PERM_HOUSE_NO                                              AS ADDR_PERM_HOUSE_NO,
                E.ADDR_PERM_WARD_NO                                               AS ADDR_PERM_WARD_NO,
                E.ADDR_PERM_STREET_ADDRESS                                        AS ADDR_PERM_STREET_ADDRESS,
                CNP.COUNTRY_NAME                                                  AS ADDR_PERM_COUNTRY_NAME,
                ZP.ZONE_NAME                                                      AS ADDR_PERM_ZONE_NAME,
                DP.DISTRICT_NAME                                                  AS ADDR_PERM_DISTRICT_NAME,
                INITCAP(VMP.VDC_MUNICIPALITY_NAME)                                AS VDC_MUNICIPALITY_NAME_PERM,
                E.ADDR_TEMP_HOUSE_NO                                              AS ADDR_TEMP_HOUSE_NO,
                E.ADDR_TEMP_WARD_NO                                               AS ADDR_TEMP_WARD_NO,
                E.ADDR_TEMP_STREET_ADDRESS                                        AS ADDR_TEMP_STREET_ADDRESS,
                CNT.COUNTRY_NAME                                                  AS ADDR_TEMP_COUNTRY_NAME,
                ZT.ZONE_NAME                                                      AS ADDR_TEMP_ZONE_NAME,
                DT.DISTRICT_NAME                                                  AS ADDR_TEMP_DISTRICT_NAME,
                VMT.VDC_MUNICIPALITY_NAME                                         AS VDC_MUNICIPALITY_NAME_TEMP,
                E.EMRG_CONTACT_NAME                                               AS EMRG_CONTACT_NAME,
                E.EMERG_CONTACT_RELATIONSHIP                                      AS EMERG_CONTACT_RELATIONSHIP,
                E.EMERG_CONTACT_ADDRESS                                           AS EMERG_CONTACT_ADDRESS,
                E.EMERG_CONTACT_NO                                                AS EMERG_CONTACT_NO,
                E.FAM_FATHER_NAME                                                 AS FAM_FATHER_NAME,
                E.FAM_FATHER_OCCUPATION                                           AS FAM_FATHER_OCCUPATION,
                E.FAM_MOTHER_NAME                                                 AS FAM_MOTHER_NAME,
                E.FAM_MOTHER_OCCUPATION                                           AS FAM_MOTHER_OCCUPATION,
                E.FAM_GRAND_FATHER_NAME                                           AS FAM_GRAND_FATHER_NAME,
                E.FAM_GRAND_MOTHER_NAME                                           AS FAM_GRAND_MOTHER_NAME,
                E.MARITAL_STATUS                                                  AS MARITAL_STATUS,
                E.FAM_SPOUSE_NAME                                                 AS FAM_SPOUSE_NAME,
                E.FAM_SPOUSE_OCCUPATION                                           AS FAM_SPOUSE_OCCUPATION,
                INITCAP(TO_CHAR(E.FAM_SPOUSE_BIRTH_DATE, 'DD-MON-YYYY'))          AS FAM_SPOUSE_BIRTH_DATE,
                INITCAP(TO_CHAR(E.FAM_SPOUSE_WEDDING_ANNIVERSARY, 'DD-MON-YYYY')) AS FAM_SPOUSE_WEDDING_ANNIVERSARY,
                E.ID_CARD_NO                                                      AS ID_CARD_NO,
                E.ID_LBRF                                                         AS ID_LBRF,
                E.ID_BAR_CODE                                                     AS ID_BAR_CODE,
                E.ID_PROVIDENT_FUND_NO                                            AS ID_PROVIDENT_FUND_NO,
                E.ID_DRIVING_LICENCE_NO                                           AS ID_DRIVING_LICENCE_NO,
                E.ID_DRIVING_LICENCE_TYPE                                         AS ID_DRIVING_LICENCE_TYPE,
                INITCAP(TO_CHAR(E.ID_DRIVING_LICENCE_EXPIRY, 'DD-MON-YYYY'))      AS ID_DRIVING_LICENCE_EXPIRY,
                E.ID_THUMB_ID                                                     AS ID_THUMB_ID,
                E.ID_PAN_NO                                                       AS ID_PAN_NO,
                E.ID_ACCOUNT_NO                                                   AS ID_ACCOUNT_NO,
                E.ID_RETIREMENT_NO                                                AS ID_RETIREMENT_NO,
                E.ID_CITIZENSHIP_NO                                               AS ID_CITIZENSHIP_NO,
                INITCAP(TO_CHAR(E.ID_CITIZENSHIP_ISSUE_DATE, 'DD-MON-YYYY'))      AS ID_CITIZENSHIP_ISSUE_DATE,
                E.ID_CITIZENSHIP_ISSUE_PLACE                                      AS ID_CITIZENSHIP_ISSUE_PLACE,
                E.ID_PASSPORT_NO                                                  AS ID_PASSPORT_NO,
                INITCAP(TO_CHAR(E.ID_PASSPORT_EXPIRY, 'DD-MON-YYYY'))             AS ID_PASSPORT_EXPIRY,
                INITCAP(C.COMPANY_NAME)                                           AS COMPANY_NAME,
                INITCAP(B.BRANCH_NAME)                                            AS BRANCH_NAME,
                INITCAP(D.DEPARTMENT_NAME)                                        AS DEPARTMENT_NAME,
                INITCAP(DES.DESIGNATION_TITLE)                                    AS DESIGNATION_TITLE,
                INITCAP(P.POSITION_NAME)                                          AS POSITION_NAME,
                P.LEVEL_NO                                                        AS LEVEL_NO,
                INITCAP(ST.SERVICE_TYPE_NAME)                                     AS SERVICE_TYPE_NAME,
                (CASE WHEN E.EMPLOYEE_TYPE='R' THEN 'REGULAR' ELSE 'WORKER' END)  AS EMPLOYEE_TYPE,
                LOC.LOCATION_EDESC                                                AS LOCATION_EDESC,
                FUNT.FUNCTIONAL_TYPE_EDESC                                        AS FUNCTIONAL_TYPE_EDESC,
                FUNL.FUNCTIONAL_LEVEL_NO                                          AS FUNCTIONAL_LEVEL_NO,
                FUNL.FUNCTIONAL_LEVEL_EDESC                                       AS FUNCTIONAL_LEVEL_EDESC,
                E.SALARY                                                          AS SALARY,
                E.SALARY_PF                                                       AS SALARY_PF,
                E.REMARKS                                                         AS REMARKS
              FROM HRIS_EMPLOYEES E
              LEFT JOIN HRIS_COMPANY C
              ON E.COMPANY_ID=C.COMPANY_ID
              LEFT JOIN HRIS_BRANCHES B
              ON E.BRANCH_ID=B.BRANCH_ID
              LEFT JOIN HRIS_DEPARTMENTS D
              ON E.DEPARTMENT_ID=D.DEPARTMENT_ID
              LEFT JOIN HRIS_DESIGNATIONS DES
              ON E.DESIGNATION_ID=DES.DESIGNATION_ID
              LEFT JOIN HRIS_POSITIONS P
              ON E.POSITION_ID=P.POSITION_ID
              LEFT JOIN HRIS_SERVICE_TYPES ST
              ON E.SERVICE_TYPE_ID=ST.SERVICE_TYPE_ID
              LEFT JOIN HRIS_GENDERS G
              ON E.GENDER_ID=G.GENDER_ID
              LEFT JOIN HRIS_BLOOD_GROUPS BG
              ON E.BLOOD_GROUP_ID=BG.BLOOD_GROUP_ID
              LEFT JOIN HRIS_RELIGIONS RG
              ON E.RELIGION_ID=RG.RELIGION_ID
              LEFT JOIN HRIS_COUNTRIES CN
              ON E.COUNTRY_ID=CN.COUNTRY_ID
              LEFT JOIN HRIS_COUNTRIES CNP
              ON (E.ADDR_PERM_COUNTRY_ID=CNP.COUNTRY_ID)
              LEFT JOIN HRIS_ZONES ZP
              ON (E.ADDR_PERM_ZONE_ID=ZP.ZONE_ID)
              LEFT JOIN HRIS_DISTRICTS DP
              ON (E.ADDR_PERM_DISTRICT_ID=DP.DISTRICT_ID)
              LEFT JOIN HRIS_VDC_MUNICIPALITIES VMP
              ON E.ADDR_PERM_VDC_MUNICIPALITY_ID=VMP.VDC_MUNICIPALITY_ID
              LEFT JOIN HRIS_COUNTRIES CNT
              ON (E.ADDR_TEMP_COUNTRY_ID=CNT.COUNTRY_ID)
              LEFT JOIN HRIS_ZONES ZT
              ON (E.ADDR_TEMP_ZONE_ID=ZT.ZONE_ID)
              LEFT JOIN HRIS_DISTRICTS DT
              ON (E.ADDR_TEMP_DISTRICT_ID=DT.DISTRICT_ID)
              LEFT JOIN HRIS_VDC_MUNICIPALITIES VMT
              ON E.ADDR_TEMP_VDC_MUNICIPALITY_ID=VMT.VDC_MUNICIPALITY_ID
              LEFT JOIN HRIS_LOCATIONS LOC
              ON E.LOCATION_ID=LOC.LOCATION_ID
              LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT
              ON E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID
              LEFT JOIN HRIS_FUNCTIONAL_LEVELS FUNL
              ON E.FUNCTIONAL_LEVEL_ID=FUNL.FUNCTIONAL_LEVEL_ID
              {$joinIfSyngery}
              WHERE 1=1 AND 
              to_number(to_char(TO_DATE(E.BIRTH_DATE, 'DD-MON-YY'), 'MMDD')) 
              BETWEEN to_number(to_char(TO_DATE('{$fromDate}', 'DD-MON-YY') , 'MMDD'))
              AND to_number(to_char(TO_DATE('{$toDate}', 'DD-MON-YY') , 'MMDD'))
              AND E.STATUS='E' 
              {$condition}
              {$orderByString}"; 
              
      return $this->rawQuery($sql);
  }  

  public function fetchJobDurationReport($by) {
    $orderByString=EntityHelper::getOrderBy('E.FULL_NAME ASC',null,'E.SENIORITY_LEVEL','P.LEVEL_NO','E.JOIN_DATE','DES.ORDER_NO','E.FULL_NAME');

    $condition = EntityHelper::getSearchConditon($by['companyId'], $by['branchId'], $by['departmentId'], $by['positionId'], $by['designationId'], $by['serviceTypeId'], $by['serviceEventTypeId'], $by['employeeTypeId'], $by['employeeId'], $by['genderId'], $by['locationId']);
    $sql = "SELECT E.EMPLOYEE_CODE, E.FULL_NAME, E.JOIN_DATE DOJ, E.BIRTH_DATE DOB,
    TRUNC((SYSDATE-BIRTH_DATE)/365)||' Years '||TRUNC(((SYSDATE-BIRTH_DATE)/365-TRUNC((SYSDATE-BIRTH_DATE)/365))*365)||' Days' AGE ,
    TRUNC((SYSDATE-JOIN_DATE)/365)||' Years '||TRUNC(((SYSDATE-JOIN_DATE)/365-TRUNC((SYSDATE-JOIN_DATE)/365))*365)||' Days' SERVICE_DURATION
    FROM HRIS_EMPLOYEES E 
    LEFT JOIN HRIS_DESIGNATIONS DES
      ON E.DESIGNATION_ID=DES.DESIGNATION_ID 
      LEFT JOIN HRIS_POSITIONS P
      ON E.POSITION_ID=P.POSITION_ID
      WHERE E.STATUS='E' AND E.RETIRED_FLAG='N' AND E.RESIGNED_FLAG='N'
    AND 1=1  
            {$condition}
            {$orderByString}"; 
    //echo $sql; die;
    return $this->rawQuery($sql);
  }

  public function fetchLeaveReportCard($by){
    $condition = EntityHelper::getSearchConditon($by['companyId'], $by['branchId'], $by['departmentId'], $by['positionId'], $by['designationId'], $by['serviceTypeId'], $by['serviceEventTypeId'], $by['employeeTypeId'], $by['employeeId'], $by['genderId'], $by['locationId']);

    $sql = "SELECT LA.ID AS ID, LA.EMPLOYEE_ID AS EMPLOYEE_ID, E.EMPLOYEE_CODE AS 
    EMPLOYEE_CODE,E.JOIN_DATE AS JOIN_DATE, LA.LEAVE_ID AS LEAVE_ID, 
    INITCAP(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY')) AS FROM_DATE_AD, BS_DATE(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY')) 
    AS FROM_DATE_BS, INITCAP(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY')) AS TO_DATE_AD, BS_DATE(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY')) 
    AS TO_DATE_BS, LA.HALF_DAY AS HALF_DAY, (CASE WHEN (LA.HALF_DAY IS NULL OR LA.HALF_DAY = 'N') THEN 'Full Day' WHEN (LA.HALF_DAY = 'F') 
    THEN 'First Half' ELSE 'Second Half' END) AS HALF_DAY_DETAIL, LA.GRACE_PERIOD AS GRACE_PERIOD, (CASE WHEN LA.GRACE_PERIOD = 'E' 
    THEN 'Early' WHEN LA.GRACE_PERIOD = 'L' THEN 'Late' ELSE '-' END) AS GRACE_PERIOD_DETAIL, LA.NO_OF_DAYS AS NO_OF_DAYS, 
    INITCAP(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT_AD, BS_DATE(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY'))
    AS REQUESTED_DT_BS, LA.REMARKS AS REMARKS, LA.STATUS AS STATUS, LEAVE_STATUS_DESC(LA.STATUS) AS STATUS_DETAIL, LA.RECOMMENDED_BY 
    AS RECOMMENDED_BY, INITCAP(TO_CHAR(LA.RECOMMENDED_DT, 'DD-MON-YYYY')) AS RECOMMENDED_DT, LA.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS, 
    LA.APPROVED_BY AS APPROVED_BY, INITCAP(TO_CHAR(LA.APPROVED_DT, 'DD-MON-YYYY')) AS APPROVED_DT, LA.APPROVED_REMARKS AS APPROVED_REMARKS, 
    (CASE WHEN LA.STATUS = 'XX' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT, (CASE WHEN LA.STATUS IN ('RQ','RC','AP') THEN 'Y' ELSE 'N' END) AS 
    ALLOW_DELETE, L.LEAVE_CODE AS LEAVE_CODE, INITCAP(L.LEAVE_ENAME) AS LEAVE_ENAME, INITCAP(E.FULL_NAME) AS FULL_NAME, 
    INITCAP(E2.FULL_NAME) AS RECOMMENDED_BY_NAME, INITCAP(E3.FULL_NAME) AS APPROVED_BY_NAME, RA.RECOMMEND_BY AS RECOMMENDER_ID, 
    RA.APPROVED_BY AS APPROVER_ID, INITCAP(RECM.FULL_NAME) AS RECOMMENDER_NAME, INITCAP(APRV.FULL_NAME) AS APPROVER_NAME 
    FROM HRIS_EMPLOYEE_LEAVE_REQUEST LA INNER JOIN HRIS_LEAVE_MASTER_SETUP  L ON L.LEAVE_ID=LA.LEAVE_ID LEFT JOIN 
    HRIS_EMPLOYEES  E ON LA.EMPLOYEE_ID=E.EMPLOYEE_ID LEFT JOIN HRIS_EMPLOYEES  E2 ON 
    E2.EMPLOYEE_ID=LA.RECOMMENDED_BY LEFT JOIN HRIS_EMPLOYEES  E3 ON E3.EMPLOYEE_ID=LA.APPROVED_BY LEFT JOIN 
    HRIS_RECOMMENDER_APPROVER  RA ON RA.EMPLOYEE_ID=LA.EMPLOYEE_ID LEFT JOIN HRIS_EMPLOYEES  RECM ON 
    RECM.EMPLOYEE_ID=RA.RECOMMEND_BY LEFT JOIN HRIS_EMPLOYEES APRV ON APRV.EMPLOYEE_ID=RA.APPROVED_BY 
    WHERE L.STATUS='E' {$condition} AND (TRUNC(SYSDATE)- LA.REQUESTED_DT) < (
                          CASE
                            WHEN LA.STATUS = 'C'
                            THEN 20
                            ELSE 365
                          END) ORDER BY LA.REQUESTED_DT DESC";  
 
  return $this->rawQuery($sql);    
  }

  public function fetchWeeklyWorkingHoursReport($by){
    $condition = EntityHelper::getSearchConditon($by['companyId'], $by['branchId'], $by['departmentId'], $by['positionId'], $by['designationId'], $by['serviceTypeId'], $by['serviceEventTypeId'], $by['employeeTypeId'], $by['employeeId'], $by['genderId'], $by['locationId']);

    $toDate = !empty($_POST['toDate']) ? $_POST['toDate'] : date('d-M-y', strtotime('now')) ;
    $toDate = date('d-M-y', strtotime($toDate));
    $fromDate = strtotime($toDate);
    $fromDate = strtotime("-6 day", $fromDate);
    $fromDate = date('d-M-y', $fromDate);
 
    $sql = "select * from (SELECT E.EMPLOYEE_CODE,AD.EMPLOYEE_ID,E.FULL_NAME,
    TO_CHAR(ATTENDANCE_DT,'DY') AS WEEKNAME,
    E.DEPARTMENT_ID,
    D.DEPARTMENT_NAME,
      CASE WHEN AD.OVERALL_STATUS='DO'
      THEN
      0
      ELSE
      HS.TOTAL_WORKING_HR/60 
      END AS ASSIGNED_HOUR ,
         CASE WHEN AD.TOTAL_HOUR IS NOT NULL THEN
         ROUND (AD.TOTAL_HOUR / 60)
         ELSE
         0
         END
         AS WORKED_HOUR,
         AD.OVERALL_STATUS
       --  AD.ATTENDANCE_DT
    FROM HRIS_ATTENDANCE_DETAIL AD
     LEFT JOIN  HRIS_EMPLOYEES E ON (AD.EMPLOYEE_ID=E.EMPLOYEE_ID)
     LEFT JOIN  HRIS_SHIFTS HS ON (AD.SHIFT_ID = HS.SHIFT_ID)
    LEFT JOIN HRIS_DEPARTMENTS D  ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
    LEFT JOIN HRIS_DESIGNATIONS DES ON (E.DESIGNATION_ID=DES.DESIGNATION_ID) 
      LEFT JOIN HRIS_POSITIONS P ON (E.POSITION_ID=P.POSITION_ID)
    WHERE 
     E.STATUS='E'
    AND E.RETIRED_FLAG='N' {$condition} 
    AND E.RESIGNED_FLAG='N' 
    AND ATTENDANCE_DT BETWEEN TO_DATE('{$fromDate}', 'DD-MON-YY') 
    AND TO_DATE('{$toDate}', 'DD-MON-YY')
    ORDER BY DEPARTMENT_ID,FULL_NAME, ATTENDANCE_DT)
    PIVOT ( MAX( ASSIGNED_HOUR ) AS AH, MAX( WORKED_HOUR ) AS WH,MAX( OVERALL_STATUS ) AS OS
    FOR WEEKNAME 
    IN ( 'TUE' AS TUE,'WED' AS WED,'THU' AS THU,'FRI' AS FRI,'SAT' AS SAT,'SUN' AS SUN,'MON' AS MON)
    )";  
    
    return $this->rawQuery($sql);    
  }

  public function getDays(){
    $toDate = !empty($_POST['toDate']) ? $_POST['toDate'] : date('d-M-y', strtotime('now')) ;
    $toDate="TO_DATE('{$toDate}')";
    
    $sql = "SELECT   trunc($toDate-6) + ROWNUM -1  AS DATES,
    ROWNUM AS DAY_COUNT,
    trunc($toDate-6) AS FROM_DATE,
    TO_CHAR(trunc($toDate-6) + ROWNUM -1,'D') AS WEEKDAY,
    TO_CHAR(trunc($toDate-6) + ROWNUM -1,'DAY') AS WEEKNAME
    FROM dual d
    CONNECT BY  rownum <=  $toDate -  trunc($toDate-6) + 1";
    
    return $this->rawQuery($sql); 
  }
}




