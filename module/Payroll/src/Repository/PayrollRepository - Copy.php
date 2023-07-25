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
                Select salary from hris_employees where employee_id = {$employeeId}
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

    public function getMonthNo($monthId) {
        $sql = "select FISCAL_YEAR_MONTH_NO from hris_month_code where month_id={$monthId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['MONTH_NO'];
    }

    public function getFiscalYearMonthNo($monthId) {
        $sql = "select FISCAL_YEAR_MONTH_NO from hris_month_code where month_id={$monthId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['FISCAL_YEAR_MONTH_NO'];
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

    public function getAbsentDays($employeeId, $monthId) {
        $sql = "SELECT COUNT(*) AS ABSENT_DAYS FROM HRIS_ATTENDANCE_DETAIL WHERE EMPLOYEE_ID = {$employeeId}
        AND OVERALL_STATUS IN ('AB') 
        AND ATTENDANCE_DT  BETWEEN (SELECT FROM_DATE FROM HRIS_MONTH_CODE where month_id = {$monthId}) AND 
        (SELECT TO_DATE FROM HRIS_MONTH_CODE where month_id = {$monthId})";
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
                FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['IS_MARRIED'];
    }

    public function isSingleAndUnmarried($employeeId, $sheetNo) {
        $sql = "SELECT (CASE 
        WHEN GENDER_CODE = 'F' AND MARITAL_STATUS != 'M' THEN 1
        ELSE 0 
    END) AS IS_FEMALE_AND_UNMARRIED
    FROM HRIS_SALARY_SHEET_EMP_DETAIL 
    WHERE EMPLOYEE_ID = {$employeeId} AND SHEET_NO = {$sheetNo}";
                
        $resultList = $this->rawQuery($sql);
        
        if (sizeof($resultList) != 1) {
            throw new Exception('Result not found.');
        }
        
        return $resultList[0]['IS_FEMALE_AND_UNMARRIED'];
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
                    WHEN TYPE ='CONTRACT'
                    THEN 1
                    ELSE 0
                  END) AS IS_PERMANENT
                FROM
                  (SELECT *
                  FROM
                    (SELECT JH.*,ST.TYPE
                    FROM HRIS_JOB_HISTORY JH
                     left join Hris_Service_Types ST ON (ST.SERVICE_TYPE_ID=JH.TO_SERVICE_TYPE_ID),
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
                SELECT E.EMPLOYEE_ID, E.GROUP_ID,
                  E.EMPLOYEE_CODE || '-' || CONCAT(CONCAT(CONCAT(INITCAP(TRIM(E.FIRST_NAME)),' '),
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

   

    
     public function getBranch($employeeId) {
        $sql = "SELECT BRANCH_ID FROM HRIS_EMPLOYEES WHERE  EMPLOYEE_ID={$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['BRANCH_ID'];
    }

    public function getCafeMealPrevious($employeeId, $monthId){
        $sql = "select case
when sum(total_amount) is not null
then sum(total_amount)
else 0 END as AMT
 from (SELECT
    hcms.menu_name AS menu_name,
    e.employee_id AS employee_id,
    e.full_name AS full_name,
    SUM(held.quantity) AS quantity,
    SUM(held.total_amount) AS total_amount
FROM
    hris_cafeteria_log_detail held
    JOIN hris_employees e ON (
        e.employee_id = held.employee_id
    )
    JOIN hris_cafeteria_menu_setup hcms ON (
        held.menu_code = hcms.menu_id
    )
    left join (select * from 
(
select to_char( add_months (from_date,-1),'DD-Mon-YY') as from_date
, to_char( add_months (to_date,-1),'DD-Mon-YY') as to_date
from hris_month_code where month_id={$monthId}
)) mc on (1=1)
WHERE
held.log_date BETWEEN mc.from_date AND mc.to_date and 
e.employee_id={$employeeId}
GROUP BY
    hcms.menu_name,
    e.employee_id,
    e.full_name)";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['AMT'];
    }
    
    public function getCafeMealCurrent($employeeId, $monthId){
        $sql = "select case
when sum(total_amount) is not null
then sum(total_amount)
else 0 END as AMT
 from (SELECT
    hcms.menu_name AS menu_name,
    e.employee_id AS employee_id,
    e.full_name AS full_name,
    SUM(held.quantity) AS quantity,
    SUM(held.total_amount) AS total_amount
FROM
    hris_cafeteria_log_detail held
    JOIN hris_employees e ON (
        e.employee_id = held.employee_id
    )
    JOIN hris_cafeteria_menu_setup hcms ON (
        held.menu_code = hcms.menu_id
    )
    left join hris_month_code mc on (month_id={$monthId})
WHERE
held.log_date BETWEEN mc.from_date AND mc.to_date and 
e.employee_id={$employeeId}
GROUP BY
    hcms.menu_name,
    e.employee_id,
    e.full_name)";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['AMT'];
    }
    
    
    public function getPayEmpType($employeeId){
           $sql = "SELECT PAY_EMP_TYPE FROM HRIS_EMPLOYEES WHERE  EMPLOYEE_ID={$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['PAY_EMP_TYPE'];
        
    }
    
     public function getEmployeeServiceId($employeeId, $sheetNo){
           $sql = "SELECT SERVICE_TYPE_ID AS SERVICE_TYPE_ID
            FROM HRIS_SALARY_SHEET_EMP_DETAIL
            WHERE SHEET_NO= {$sheetNo} AND EMPLOYEE_ID={$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['SERVICE_TYPE_ID'];
        
    }

    public function getEmployeeBranchId($employeeId, $sheetNo){
        $sql = "SELECT BRANCH_ID AS BRANCH_ID
         FROM HRIS_SALARY_SHEET_EMP_DETAIL
         WHERE SHEET_NO= {$sheetNo} AND EMPLOYEE_ID={$employeeId}";
     $resultList = $this->rawQuery($sql);
     if (!(sizeof($resultList) == 1)) {
         throw new Exception('No Report Found.');
     }
     return $resultList[0]['BRANCH_ID'];
     
 }

 

    public function getDearnessAllowanceAmt($employeeId, $sheetNo) {
        $sql = "
        SELECT NVL(DEARNESS_ALLOWANCE,0) AS DEARNESS_ALLOWANCE FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = {$employeeId}
                ";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['DEARNESS_ALLOWANCE'];
    }

    public function geTClothtransAllowanceAmt($employeeId, $sheetNo) {
        $sql = "
        SELECT NVL(CLOTH_TRANS_ALLOWANCE,0) AS CLOTH_TRANS_ALLOWANCE  FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = {$employeeId}
                ";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['CLOTH_TRANS_ALLOWANCE'];
    }

    public function getFoodAllowanceAmt($employeeId, $sheetNo) {
        $sql = "
        SELECT NVL(FOOD_ALLOWANCE,0) AS FOOD_ALLOWANCE FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = {$employeeId}
                ";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['FOOD_ALLOWANCE'];
    }


    public function getHbLoanAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(LPD.AMOUNT,0) AS HB_LOAN_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 3
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;            
        }
        return $resultList[0]['HB_LOAN_AMT'];
    }

    public function getHbIntAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(LPD.interest_amount,0) AS HB_INTEREST_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 3
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}
        ";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['HB_INTEREST_AMT'];
    }

    public function getWLLoanAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(LPD.AMOUNT,0) AS WL_LOAN_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 4
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['WL_LOAN_AMT'];
    }

    public function getWLIntAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(lpd.interest_amount,0) AS WL_INTEREST_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 4
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['WL_INTEREST_AMT'];
    }

    public function getSHLLoanAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(lpd.AMOUNT,0) AS SHL_LOAN_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 5 AND ELR.LOAN_STATUS = 'OPEN'
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}
				and lpd.status ='E'";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
       
        return $resultList[0]['SHL_LOAN_AMT'];
    }

    public function getSHLIntAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(lpd.interest_amount,0) AS SHL_INTEREST_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 5 AND ELR.LOAN_STATUS = 'OPEN'
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}
				and lpd.status ='E'";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
       
        return $resultList[0]['SHL_INTEREST_AMT'];
    }


    public function getEFlag($employeeId, $monthId) {
        $sql = "SELECT CASE 
        WHEN EMPLOYEE_TYPE = 'R'
        THEN 
        0
        WHEN EMPLOYEE_TYPE = 'C'
        THEN 
        1
        WHEN EMPLOYEE_TYPE = 'E'
        THEN 
        2
        WHEN EMPLOYEE_TYPE = 'A'
        THEN
        3
		WHEN EMPLOYEE_TYPE = 'P'
        THEN
        4
        END AS EMPLOYEE_TYPE FROM HRIS_EMPLOYEES WHERE
        EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['EMPLOYEE_TYPE'];
    }

    public function getSc($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS SC
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 19
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['SC'];
    }

    public function getOt($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS OT
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 4
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['OT'];
    }

    public function getNa($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS NA
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 7
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['NA'];
    }

    public function fnlSickLeave($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS FNL_SL
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 29
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['FNL_SL'];
    }

    public function fnlAnnualLeave($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS FNL_AL
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 30
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['FNL_AL'];
    }

    public function getLtc($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS LTC
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 9
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['LTC'];
    }

    public function getLsa($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS LSA
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 10
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['LSA'];
    }

    public function getBsa($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS BSA
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 2
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['BSA'];
    }

    public function getAa($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS AA
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 3
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['AA'];
    }

    public function getMobieAllowanceArrear($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS MBA
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 1
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['MBA'];
    }

    public function getAllowanceArrear($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS ALLOWANCE_ARREAR
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 3
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['ALLOWANCE_ARREAR'];
    }

    public function getIncentive($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS INCENTIVE
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 21
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['INCENTIVE'];
    }

    public function getMCLoanAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(lpd.AMOUNT,0) AS MC_LOAN_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 2
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['MC_LOAN_AMT'];
    }

    public function getMCIntAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(lpd.interest_amount,0) AS MC_INTEREST_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 2
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['MC_INTEREST_AMT'];
    }

    public function getSLoanAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(lpd.AMOUNT,0) AS S_L_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 1
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}
				and lpd.status ='E'";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['S_L_AMT'];
    }

    public function getSIntAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(lpd.interest_amount,0) AS S_INTEREST_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 1
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['S_INTEREST_AMT'];
    }

    public function getELoanAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(lpd.AMOUNT,0) AS E_L_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 7
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['E_L_AMT'];
    }

    public function getEIntAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(lpd.interest_amount,0) AS E_INTEREST_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 7
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['E_INTEREST_AMT'];
    }


    public function getSAdvanceAmt($employeeId, $monthId) {
        $sql = "SELECT nvl(lpd.AMOUNT,0) AS S_ADVANCE_AMT FROM hris_employee_loan_request ELR 
        LEFT JOIN hris_loan_payment_detail LPD  ON ( ELR.loan_request_id = LPD.LOAN_REQUEST_ID)
        WHERE ELR.LOAN_ID = 6
        AND LPD. FROM_DATE between (SELECT FROM_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and (SELECT to_date FROM HRIS_MONTH_CODE WHERE MONTH_ID  = {$monthId} AND STATUS = 'E')
                and ELR.EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['S_ADVANCE_AMT'];
    }

	 public function getServiceChargerE($employeeId, $monthId) {
        $sql = "SELECT 
				CASE
				WHEN
				SUM(MTH_VALUE) > 0
				THEN 1
				ELSE 0 END AS SERVICE_CHARGE_EMPLOYEE
				FROM HRIS_MONTHLY_VALUE_DETAIL WHERE MONTH_ID <= {$monthId} AND EMPLOYEE_ID = {$employeeId} AND MTH_ID = 19";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['SERVICE_CHARGE_EMPLOYEE'];
    }

  

    public function getServiceChargerfortax($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS SERVICE_CHARGE_TAX
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 17
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['SERVICE_CHARGE_TAX'];
    }


    public function getFamilyPlanning($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS FAMILY_PLANNING
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 11
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['FAMILY_PLANNING'];
    }

    public function getMourningExpense($employeeId, $monthId) {
        $sql = "select nvl(SUM(MTH_VALUE),0) AS MOURNING_EXPENSE
        from hris_monthly_value_detail MVD
        left join hris_month_code MC on (MVD.MONTH_ID = MC.MONTH_ID)
         where MVD.MTH_ID = 8
         AND MC.FISCAL_YEAR_MONTH_NO <> 1
         AND EMPLOYEE_ID = {$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['MOURNING_EXPENSE'];
    }
	
		 public function getPrevioudMthTax($employeeId, $monthId) {
        $sql = "select nvl(sum(val),0) as PREV_TAX from hris_salary_sheet_detail ssd
					left join hris_salary_sheet ss on (ssd.sheet_no = ss.sheet_no)
					where ssd.pay_id = 123 and ssd.employee_id ={$employeeId}
					and ss.sheet_no in (select sheet_no from hris_salary_sheet 
					where month_id <> {$monthId})";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['PREV_TAX'];
    }
	
		public function getPrevioudMedical($employeeId) {
        $sql = "select nvl(sum(val),0) as PREV_MEDICALS from hris_salary_sheet_detail ssd
				where ssd.pay_id = 11 and ssd.employee_id ={$employeeId}";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            return 0;  
        }
        return $resultList[0]['PREV_MEDICALS'];
    }
	

}
