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
    
}
