<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Repository\HrisRepository;
use Exception;
use Zend\Db\Adapter\AdapterInterface;

class PayrollRepository extends HrisRepository {

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        parent::__construct($adapter, $tableName);
    }

    public function fetchBasicSalary($employeeId, $sheetNo) {
        $sql = "
                SELECT NVL(SALARY,0) AS SALARY
                FROM HRIS_SALARY_SHEET_EMP_DETAIL
                WHERE EMPLOYEE_ID={$employeeId}
                AND SHEET_NO = {$sheetNo}
                ";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['SALARY'];
    }

    public function getMonthDays($employeeId, $sheetNo) {
        $sql = "
            SELECT TOTAL_DAYS AS MONTH_DAYS
            FROM HRIS_SALARY_SHEET_EMP_DETAIL
            WHERE SHEET_NO= {$sheetNo} AND EMPLOYEE_ID={$employeeId}
                ";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['MONTH_DAYS'];
    }

    public function getPresentDays($employeeId, $sheetNo) {
        $sql = "SELECT PRESENT AS PRESENT_DAYS
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['PRESENT_DAYS'];
    }

    public function getAbsentDays($employeeId, $sheetNo) {
        $sql = "SELECT ABSENT AS ABSENT_DAYS
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['ABSENT_DAYS'];
    }

    public function getPaidLeaves($employeeId, $sheetNo) {
        $sql = "SELECT PAID_LEAVE AS PAID_LEAVE
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['PAID_LEAVE'];
    }

    public function getUnpaidLeaves($employeeId, $sheetNo) {
        $sql = "SELECT UNPAID_LEAVE AS UNPAID_LEAVE
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['UNPAID_LEAVE'];
    }

    public function getDayoffs($employeeId, $sheetNo) {
        $sql = "SELECT DAYOFF AS DAYOFF
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['DAYOFF'];
    }

    public function getHolidays($employeeId, $sheetNo) {
        $sql = "SELECT HOLIDAY
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['HOLIDAY'];
    }

    public function getDaysFromJoinDate($employeeId, $sheetNo) {
        $sql = "SELECT (TRUNC(START_DATE)-TRUNC(JOIN_DATE))+1 AS DAYS_FROM_JOIN_DATE
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['DAYS_FROM_JOIN_DATE'];
    }

    public function getDaysFromPermanentDate($employeeId, $monthId) {
        $sql = "
                SELECT (TRUNC(M.FROM_DATE)- TRUNC(PERMANENT_DATE)) AS DAYS_FROM_PERMANENT_DATE
                FROM HRIS_EMPLOYEES E,
                  (SELECT FROM_DATE,TO_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID= {$monthId}
                  ) M WHERE E.EMPLOYEE_ID={$employeeId}
                ";
        $rawResult = EntityHelper::rawQueryResult($this->adapter, $sql);
        return $rawResult->current()['DAYS_FROM_PERMANENT_DATE'];
    }

    public function isMale($employeeId, $sheetNo) {
        $sql = "SELECT (CASE WHEN GENDER_CODE = 'M' THEN 1 ELSE 0 END) AS IS_MALE
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['IS_MALE'];
    }

    public function isFemale($employeeId, $sheetNo) {
        $sql = "SELECT (CASE WHEN GENDER_CODE = 'F' THEN 1 ELSE 0 END) AS IS_FEMALE
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['IS_FEMALE'];
    }

    public function isMarried($employeeId, $sheetNo) {
        $sql = "SELECT (CASE WHEN MARITAL_STATUS = 'M' THEN 1 ELSE 0 END) AS IS_MARRIED
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['IS_MARRIED'];
    }

    public function isPermanent($employeeId, $sheetNo) {
        $sql = "SELECT (
                  CASE
                    WHEN (PERMANENT_FLAG ='Y'
                    AND ( PERMANENT_DATE IS NULL OR PERMANENT_DATE <= START_DATE))
                    THEN 1
                    ELSE 0
                  END) AS IS_PERMANENT
                FROM HRIS_SALARY_SHEET_EMP_DETAIL
                WHERE EMPLOYEE_ID = {$employeeId}
                AND SHEET_NO      = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['IS_PERMANENT'];
    }

    public function isProbation($employeeId, $monthId) {
        $sql = "
                SELECT (
                  CASE
                    WHEN TO_SERVICE_TYPE_ID =2
                    THEN 1
                    ELSE 0
                  END) AS IS_PERMANENT
                FROM
                  (SELECT *
                  FROM
                    (SELECT JH.*
                    FROM HRIS_JOB_HISTORY JH,
                      (SELECT * FROM HRIS_MONTH_CODE WHERE MONTH_ID = {$monthId}
                      ) M
                    WHERE JH.EMPLOYEE_ID = {$employeeId}
                    AND JH.START_DATE   <= M.FROM_DATE
                    ORDER BY JH.START_DATE DESC
                    )
                  WHERE ROWNUM =1
                  )           
                ";
        $rawResult = EntityHelper::rawQueryResult($this->adapter, $sql);
        $result = $rawResult->current();
        if ($result == null) {
            return 0;
        }
        return $result['IS_PERMANENT'];
    }

    public function isContract($employeeId, $monthId) {
        $sql = "
                SELECT (
                  CASE
                    WHEN TO_SERVICE_TYPE_ID =3
                    THEN 1
                    ELSE 0
                  END) AS IS_PERMANENT
                FROM
                  (SELECT *
                  FROM
                    (SELECT JH.*
                    FROM HRIS_JOB_HISTORY JH,
                      (SELECT * FROM HRIS_MONTH_CODE WHERE MONTH_ID = {$monthId}
                      ) M
                    WHERE JH.EMPLOYEE_ID = {$employeeId}
                    AND JH.START_DATE   <= M.FROM_DATE
                    ORDER BY JH.START_DATE DESC
                    )
                  WHERE ROWNUM =1
                  )           
                ";
        $rawResult = EntityHelper::rawQueryResult($this->adapter, $sql);
        $result = $rawResult->current();
        if ($result == null) {
            return 0;
        }
        return $result['IS_PERMANENT'];
    }

    public function isTemporary($employeeId, $monthId) {
        $sql = "
                SELECT (
                  CASE
                    WHEN TO_SERVICE_TYPE_ID =4
                    THEN 1
                    ELSE 0
                  END) AS IS_PERMANENT
                FROM
                  (SELECT *
                  FROM
                    (SELECT JH.*
                    FROM HRIS_JOB_HISTORY JH,
                      (SELECT * FROM HRIS_MONTH_CODE WHERE MONTH_ID = {$monthId}
                      ) M
                    WHERE JH.EMPLOYEE_ID = {$employeeId}
                    AND JH.START_DATE   <= M.FROM_DATE
                    ORDER BY JH.START_DATE DESC
                    )
                  WHERE ROWNUM =1
                  )           
                ";
        $rawResult = EntityHelper::rawQueryResult($this->adapter, $sql);
        $result = $rawResult->current();
        if ($result == null) {
            return 0;
        }
        return $result['IS_PERMANENT'];
    }

    public function getWorkedDays($employeeId, $sheetNo) {
        $sql = "SELECT PRESENT+DAYOFF+HOLIDAY+PAID_LEAVE+TRAVEL+TRAINING AS WORKED_DAYS
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['WORKED_DAYS'];
    }

    public function fetchEmployeeList() {
        $sql = "
                SELECT E.EMPLOYEE_ID,
                  CONCAT(CONCAT(CONCAT(INITCAP(TRIM(E.FIRST_NAME)),' '),
                  CASE
                    WHEN E.MIDDLE_NAME IS NOT NULL
                    THEN CONCAT(INITCAP(TRIM(E.MIDDLE_NAME)), ' ')
                    ELSE ''
                  END ),INITCAP(TRIM(E.LAST_NAME))) AS FULL_NAME
                FROM HRIS_EMPLOYEES E
                WHERE E.JOIN_DATE <= TRUNC(SYSDATE)
                AND E.RETIRED_FLAG ='N'
                AND IS_ADMIN       ='N'
                AND STATUS         ='E'
                ";
        $employeeListRaw = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($employeeListRaw);
    }

    public function getBranchAllowance($employeeId) {
        $sql = "SELECT ALLOWANCE FROM HRIS_BRANCHES WHERE 
                BRANCH_ID=(SELECT  BRANCH_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID={$employeeId})";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['ALLOWANCE'];
    }

    public function getMonthNo($monthId) {
        $sql = "select FISCAL_YEAR_ID from hris_month_code where month_id={$monthId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['FISCAL_YEAR_ID'];
    }

}
