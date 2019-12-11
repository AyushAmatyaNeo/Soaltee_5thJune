<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;

class PayrollReportRepo implements RepositoryInterface {

    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function edit(Model $model, $id) {
        
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        
    }

    public function getVarianceColumns() {
        $data['previous'] = $this->varianceColumnsPre();
        $data['current'] = $this->varianceColumnsCur();
        $data['difference'] = $this->varianceColumnsDif();
        $data['addition'] = $this->varianceColumnsAddi();
        return $data;
    }

    public function varianceColumnsPre() {
        $sql = "select 'V'||Variance_Id||'_P'  as VARIANCE,VARIANCE_NAME from Hris_Variance where status='E' and Show_Default='Y' AND VARIABLE_TYPE='V'  order by order_no asc ";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    public function varianceColumnsCur() {
        $sql = "select 'V'||Variance_Id||'_C'  as VARIANCE,VARIANCE_NAME from Hris_Variance where status='E' and Show_Default='Y' AND VARIABLE_TYPE='V'  order by order_no asc ";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    public function varianceColumnsDif() {
        $sql = "select 'V'||Variance_Id||'_D'  as VARIANCE,VARIANCE_NAME from Hris_Variance where status='E' and Show_Default='Y' and Show_Difference='Y' AND VARIABLE_TYPE='V' order by order_no asc ";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }
    
    public function varianceColumnsAddi() {
        $sql = "SELECT
variance_id
,
    'V'
     || variance_id
     || '_P' AS PREV,
      'V'
     || variance_id
     || '_C' AS CURR
FROM
    hris_variance
WHERE
        status = 'E'
    AND
        show_default = 'Y'
    AND
        is_sum = 'Y'
    AND
        variable_type = 'V'";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    public function getVarianceReprot($data) {
        $varianceVariable = $this->fetchVarianceVariable();

        $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
        $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
        $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
        $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
        $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
        $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
        $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
        $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
        $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
        $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
        $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
        $monthId = $data['monthId'];

        $searchConditon = EntityHelper::getSearchConditonPayroll($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "SELECT 
            E.FULL_NAME,
            E.EMPLOYEE_CODE
            ,D.DEPARTMENT_NAME
            ,FUNT.FUNCTIONAL_TYPE_EDESC
            ,AD.CUR_ADDRESS
            ,AD.CUR_ACCOUNT
            ,AD.PRE_ADDRESS
            ,AD.PRE_ACCOUNT
            ,AD.ADDRESS_REMARKS
            ,AD.ACCOUNT_REMARKS
            ,VARY.*
            FROM (SELECT 
            *
            FROM 
            (SELECT 
            C.EMPLOYEE_ID
            ,C.VARIANCE_ID
            ,C.TOTAL AS C_TOTAL
            ,P.TOTAL AS P_TOTAL
            ,P.Total-C.TOTAL AS DIFFERENCE
            FROM (SELECT 
            SD.EMPLOYEE_ID
            ,Vp.Variance_Id
            ,SS.Month_ID
            ,V.Show_Difference
            ,SUM(VAL) AS TOTAL
            FROM HRIS_VARIANCE V
            LEFT JOIN HRIS_VARIANCE_PAYHEAD VP ON (V.VARIANCE_ID=VP.VARIANCE_ID)
            LEFT JOIN (select * from HRIS_SALARY_SHEET where month_id={$monthId}) SS ON (1=1)
            LEFT JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND SD.Pay_Id=VP.Pay_Id)
            WHERE V.SHOW_DEFAULT='Y' AND V.STATUS='E' AND V.VARIABLE_TYPE='V' 
            GROUP BY  SD.EMPLOYEE_ID,V.VARIANCE_NAME,Vp.Variance_Id,SS.Month_ID,V.Show_Difference) C
            LEFT JOIN 
            (
            SELECT 
            SD.EMPLOYEE_ID
            ,Vp.Variance_Id
            ,SS.Month_ID
            ,V.Show_Difference
            ,SUM(VAL) AS TOTAL
            FROM HRIS_VARIANCE V
            LEFT JOIN HRIS_VARIANCE_PAYHEAD VP ON (V.VARIANCE_ID=VP.VARIANCE_ID)
            LEFT JOIN (select * from HRIS_SALARY_SHEET where 
            month_id=(SELECT MONTH_ID FROM  HRIS_MONTH_CODE WHERE TO_DATE=(SELECT FROM_DATE-1 FROM HRIS_MONTH_CODE WHERE MONTH_ID={$monthId}))
            ) SS ON (1=1)
            LEFT JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND SD.Pay_Id=VP.Pay_Id)
            WHERE V.SHOW_DEFAULT='Y' AND V.STATUS='E' AND V.VARIABLE_TYPE='V' 
            GROUP BY  SD.EMPLOYEE_ID,V.VARIANCE_NAME,Vp.Variance_Id,SS.Month_ID,V.Show_Difference) P ON
            (C.EMPLOYEE_ID=P.EMPLOYEE_ID AND C.VARIANCE_ID=P.VARIANCE_ID ))
            PIVOT ( MAX( C_TOTAL ) AS C ,MAX( P_TOTAL ) AS P ,MAX( DIFFERENCE ) AS D
                FOR Variance_Id 
                IN ({$varianceVariable})
                )
                ) 
                VARY
                LEFT JOIN (SELECT
            CUR.EMPLOYEE_ID
            ,CUR.PERMANENT_ADDRESS AS CUR_ADDRESS
            ,CUR.ACCOUNT_NO AS CUR_ACCOUNT
            ,PREV.PERMANENT_ADDRESS AS PRE_ADDRESS
            ,PREV.ACCOUNT_NO AS PRE_ACCOUNT
            ,CASE WHEN CUR.PERMANENT_ADDRESS!=PREV.PERMANENT_ADDRESS
            THEN
            'Changed'
            ELSE
            'Not Changed'
            END as ADDRESS_REMARKS
            ,CASE WHEN CUR.ACCOUNT_NO!=PREV.ACCOUNT_NO
            THEN
            'Changed'
            ELSE
            'Not Changed'
            END as ACCOUNT_REMARKS
            FROM 
            (select * from HRIS_SALARY_SHEET SSC
            left join HRIS_SALARY_SHEET_EMP_DETAIL SEDC ON (SEDC.SHEET_NO=SSC.SHEET_NO)
            where SSC.month_id={$monthId}) CUR
            LEFT JOIN (select * from HRIS_SALARY_SHEET SSP
            left join HRIS_SALARY_SHEET_EMP_DETAIL SEDP ON (SEDP.SHEET_NO=SSP.SHEET_NO)
            where SSP.month_id=(SELECT MONTH_ID FROM  HRIS_MONTH_CODE WHERE TO_DATE=(SELECT FROM_DATE-1 FROM HRIS_MONTH_CODE WHERE MONTH_ID={$monthId})))
            PREV ON (CUR.EMPLOYEE_ID=PREV.EMPLOYEE_ID))  AD ON (AD.EMPLOYEE_ID=VARY.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=VARY.EMPLOYEE_ID)
                LEFT JOIN HRIS_DEPARTMENTS D  ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
                LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT ON (E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID)
            WHERE 1=1 AND VARY.EMPLOYEE_ID IS NOT NULL {$searchConditon}
            ";
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    public function getGbVariables() {
        $sql = "select VARIANCE_ID,VARIANCE_NAME from Hris_Variance 
where status='E' 
AND VARIABLE_TYPE='O'
AND Show_Default='N'";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    public function getGradeBasicReport($data) {
        $varianceVariable = $this->fetchOtVariable();

        $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
        $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
        $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
        $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
        $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
        $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
        $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
        $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
        $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
        $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
        $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
        $monthId = $data['monthId'];
//        $fiscalId = $data['fiscalId'];

        $searchConditon = EntityHelper::getSearchConditonPayroll($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "SELECT 
            SSED.FULL_NAME,
            E.EMPLOYEE_CODE
            ,E.BIRTH_DATE
            ,SSED.JOIN_DATE
            ,SSED.DEPARTMENT_NAME
            ,SSED.FUNCTIONAL_TYPE_EDESC
            ,GB.*
            ,SSED.SERVICE_TYPE_NAME
            ,SSED.DESIGNATION_TITlE
            ,SSED.POSITION_NAME
            ,SSED.ACCOUNT_NO
            FROM
            (
            SELECT * FROM (SELECT 
            SD.EMPLOYEE_ID
            ,Vp.Variance_Id
            ,SS.Month_ID
            ,SS.SHEET_NO
            ,SUM(VAL) AS TOTAL
            FROM HRIS_VARIANCE V
            LEFT JOIN HRIS_VARIANCE_PAYHEAD VP ON (V.VARIANCE_ID=VP.VARIANCE_ID)
            LEFT JOIN (select * from HRIS_SALARY_SHEET) SS ON (1=1)
            JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND SD.Pay_Id=VP.Pay_Id)
            WHERE  V.STATUS='E' AND V.VARIABLE_TYPE='O' 
            and SS.MONTH_ID={$monthId}
            GROUP BY SD.EMPLOYEE_ID,V.VARIANCE_NAME,Vp.Variance_Id,SS.Month_ID,SS.SHEET_NO)
            PIVOT ( MAX( TOTAL )
                FOR Variance_Id 
                IN ($varianceVariable)
                ))GB
                LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=GB.EMPLOYEE_ID)
                LEFT JOIN Hris_Salary_Sheet_Emp_Detail SSED ON 
    (SSED.SHEET_NO=GB.SHEET_NO AND SSED.EMPLOYEE_ID=GB.EMPLOYEE_ID AND SSED.MONTH_ID=GB.MONTH_ID)
                LEFT JOIN HRIS_DEPARTMENTS D  ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
                LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT ON (E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID)
                WHERE 1=1 
             {$searchConditon}
             ";

//             echo $sql;
//             die();

        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    private function fetchVarianceVariable() {
        $rawList = EntityHelper::rawQueryResult($this->adapter, "select  * from Hris_Variance where  SHOW_DEFAULT='Y' AND STATUS='E' AND VARIABLE_TYPE='V'");
        $dbArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']}";
            } else {
                $dbArray .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']},";
            }
        }
        return $dbArray;
    }

    private function fetchOtVariable() {
        $rawList = EntityHelper::rawQueryResult($this->adapter, "select  * from Hris_Variance where   STATUS='E' AND VARIABLE_TYPE='O'");
        $dbArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']}";
            } else {
                $dbArray .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']},";
            }
        }
        return $dbArray;
    }

    public function otDefaultColumns() {
        $sql = "select 'V'||Variance_Id  as VARIANCE,VARIANCE_NAME from Hris_Variance where status='E'
and Show_Default='Y'  AND VARIABLE_TYPE='O'";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    public function getOtDefaultColumns() {
        $data = $this->otDefaultColumns();
        return $data;
    }

    public function getBasicMonthly($data, $defaultColumnsList) {
        // to calculate total start
        $totalString = "0";
        $colCount = 0;
        foreach ($defaultColumnsList as $columns) {
            if ($columns['TYPE'] == 'M') {
                $totalString .= "+CASE WHEN BS.{$columns['DEFAULT_COL']} IS NOT NULL THEN BS.{$columns['DEFAULT_COL']} ELSE 0 END";
            } else {
                $colCount++;
//                $totalString .= " AS {$columns['DEFAULT_COL']},";
                $totalString .= ($colCount == $columns['TOTAL_NO']) ? " AS {$columns['DEFAULT_COL']}," : " AS {$columns['DEFAULT_COL']},0";
            }
        }
        // to calculate total end

        $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
        $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
        $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
        $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
        $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
        $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
        $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
        $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
        $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
        $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
        $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
        $fiscalId = $data['fiscalId'];

        $varianceVariable = $this->fetchOtVariableMonthly();
        $monthIdList = $this->fetchMonthIdList($fiscalId);

        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "
             select 
        {$totalString}
            E.FULL_NAME,
            E.EMPLOYEE_CODE,
            E.Id_Account_No AS ACCOUNT_NO,
            E.BIRTH_DATE,
            E.JOIN_DATE,
            D.DEPARTMENT_NAME,
            FUNT.FUNCTIONAL_TYPE_EDESC,
            DES.DESIGNATION_TITLE,
            P.POSITION_NAME,
            ST.SERVICE_TYPE_NAME,
            BS.*
            from  ( select 
              *
              from ( SELECT
            *
        FROM
            ( SELECT
                    sd.employee_id,
                    vp.variance_id,
                    ss.month_id,
                    SUM(val) AS total
                FROM
                    hris_variance v
                    LEFT JOIN hris_variance_payhead vp ON (
                        v.variance_id = vp.variance_id
                    )
                    LEFT JOIN (
                        SELECT
                            *
                        FROM
                            hris_salary_sheet
                    ) ss ON (
                        1 = 1
                    )
                    LEFT JOIN hris_salary_sheet_detail sd ON (
                            ss.sheet_no = sd.sheet_no
                        AND
                            sd.pay_id = vp.pay_id
                    )
                WHERE
                        v.status = 'E'
                    AND
                        v.variable_type = 'O' 
                        and v.Show_Default='Y'
                         GROUP BY
                    sd.employee_id,
                    v.variance_name,
                    vp.variance_id,
                    ss.month_id,
                    ss.sheet_no
                      )
                PIVOT ( MAX ( total )
                    FOR variance_id
                    IN ( {$varianceVariable['VARIABLES']} )
                )
                )
                 PIVOT ( {$varianceVariable['VARIABLES_MAX']}
                    FOR month_id
                    IN ( {$monthIdList} ))
                    ) BS
                     LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=BS.EMPLOYEE_ID)
                     LEFT JOIN HRIS_DEPARTMENTS D  ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
                     LEFT JOIN HRIS_DESIGNATIONS DES ON (E.DESIGNATION_ID=DES.DESIGNATION_ID)
                     LEFT JOIN HRIS_POSITIONS P ON (E.POSITION_ID=P.POSITION_ID)
                     LEFT JOIN HRIS_SERVICE_TYPES ST ON (E.SERVICE_TYPE_ID=ST.SERVICE_TYPE_ID)
                     LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT ON (E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID)
                     WHERE 1=1 {$searchConditon}
                ";
                     //echo $sql; die;

        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    private function fetchOtVariableMonthly() {
        $rawList = EntityHelper::rawQueryResult($this->adapter, "select  * from Hris_Variance where   STATUS='E' AND VARIABLE_TYPE='O' and Show_Default='Y'");
        $dbArray['VARIABLES'] = "";
        $dbArray['VARIABLES_MAX'] = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray['VARIABLES'] .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']}";
                $dbArray['VARIABLES_MAX'] .= "MAX (V{$row['VARIANCE_ID']}) AS V{$row['VARIANCE_ID']}";
            } else {
                $dbArray['VARIABLES'] .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']},";
                $dbArray['VARIABLES_MAX'] .= "MAX (V{$row['VARIANCE_ID']}) AS V{$row['VARIANCE_ID']},";
            }
        }
        return $dbArray;
    }

    private function fetchMonthIdList($fiscalId) {
        $rawList = EntityHelper::rawQueryResult($this->adapter, "select month_id from hris_month_code where Fiscal_Year_Id={$fiscalId}");
        $monthArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $monthArray .= "{$row['MONTH_ID']} AS M{$row['MONTH_ID']}";
            } else {
                $monthArray .= "{$row['MONTH_ID']} AS M{$row['MONTH_ID']},";
            }
        }
        return $monthArray;
    }

    public function getOtMonthlyDefaultColumns($fiscalId) {
        $sql = "SELECT 'M'||MONTH_ID||'_V'||V.VARIANCE_ID AS DEFAULT_COL,
                CASE WHEN YEAR!='TOTAL'
                THEN
                SUBSTR(MC.MONTH_EDESC, 1, 3)||'-'||YEAR
                ELSE
                YEAR||'-'||V.VARIANCE_NAME
                END
                AS MONTH_NAME
                ,TYPE,
                (SELECT count(*) FROM Hris_Variance WHERE
                Variable_Type='O' AND Show_Default='Y') AS TOTAL_NO
                FROM
                (SELECT * FROM Hris_Variance WHERE
                Variable_Type='O' AND Show_Default='Y') V
                LEFT JOIN (select MONTH_ID,
                MONTH_EDESC,
                TO_CHAR(YEAR) AS YEAR,
                'M' AS TYPE
                from hris_month_code where Fiscal_Year_Id={$fiscalId}
                union
                select 
                20 AS MONTH_ID,
                'TOTAL' AS MONTH_EDESC,
                'TOTAL' AS YEAR,
                'T' AS TYPE 
                from dual) MC ON (1=1)";

        $rawList = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($rawList);
    }

    public function getGradeBasicSummary($data) {
        $varianceVariable = $this->fetchOtVariable();

        $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
        $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
        $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
        $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
        $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
        $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
        $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
        $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
        $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
        $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
        $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
//        $fiscalId = $data['fiscalId'];
        $monthId = $data['monthId'];
        $extraMonth = $data['extraMonth'];

        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "SELECT 
            E.FULL_NAME,
            E.EMPLOYEE_CODE,
            E.Id_Account_No AS ACCOUNT_NO
            ,E.BIRTH_DATE
            ,E.JOIN_DATE
            ,D.DEPARTMENT_NAME
            ,FUNT.FUNCTIONAL_TYPE_EDESC
             ,P.POSITION_NAME
            ,ST.SERVICE_TYPE_NAME
            ,DES.DESIGNATION_TITLE
            ,GB.*
            FROM
            (
            SELECT * FROM (SELECT 
            SD.EMPLOYEE_ID
            ,Vp.Variance_Id
            ,SUM(VAL) AS TOTAL
            FROM HRIS_VARIANCE V
            LEFT JOIN HRIS_VARIANCE_PAYHEAD VP ON (V.VARIANCE_ID=VP.VARIANCE_ID)
            LEFT JOIN (select * from HRIS_SALARY_SHEET) SS ON (1=1)
            JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND SD.Pay_Id=VP.Pay_Id)
            JOIN HRIS_MONTH_CODE MC ON (SS.MONTH_ID=MC.MONTH_ID) 
            WHERE  V.STATUS='E' AND V.VARIABLE_TYPE='O' 
            AND (SS.MONTH_ID between  {$monthId} and {$extraMonth})
            GROUP BY SD.EMPLOYEE_ID,V.VARIANCE_NAME,Vp.Variance_Id)
            PIVOT ( MAX( TOTAL )
                FOR Variance_Id 
                IN ($varianceVariable)
                ))GB
                LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=GB.EMPLOYEE_ID)
                LEFT JOIN HRIS_DEPARTMENTS D  ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
                LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT ON (E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID)
                LEFT JOIN HRIS_POSITIONS P ON (E.POSITION_ID=P.POSITION_ID)
                LEFT JOIN HRIS_SERVICE_TYPES ST ON (E.SERVICE_TYPE_ID=ST.SERVICE_TYPE_ID)
                LEFT JOIN HRIS_DESIGNATIONS DES ON (E.DESIGNATION_ID=DES.DESIGNATION_ID)
                WHERE 1=1 
             {$searchConditon}
                ";
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    public function getSpecialMonthly($data) {
        $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
        $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
        $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
        $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
        $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
        $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
        $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
        $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
        $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
        $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
        $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
        $fiscalId = $data['fiscalId'];
        $monthId = $data['monthId'];

        $varianceVariable = $this->fetchOtVariableMonthly();
        $monthIdList = $this->fetchMonthIdList($fiscalId);

        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "SELECT ROWNUM AS S_NO, HLSED.ACCOUNT_NO, HLSED.FULL_NAME, (
                    SELECT HLSED.SALARY +
                    sum((case when hps.pay_type_flag = 'D' then -1 else 1 end)* 
                    val) FROM HRIS_SALARY_SHEET_DETAIL  hssd
                    join hris_pay_setup hps on hssd.pay_id = hps.pay_id where hssd.employee_id = hlsed.employee_id
                    AND 
                    hssd.pay_id IN(
                    SELECT PAY_ID FROM HRIS_SALARY_SHEET_DETAIL
                    WHERE SHEET_NO IN(
                    SELECT SHEET_NO FROM HRIS_SALARY_SHEET WHERE MONTH_ID = $monthId
                    )) 
                    and hps.include_in_salary = 'Y') CR_AMOUNT FROM HRIS_SALARY_SHEET_EMP_DETAIL HLSED
                     LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=HLSED.EMPLOYEE_ID)
                     LEFT JOIN HRIS_DEPARTMENTS D  ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
                     LEFT JOIN HRIS_DESIGNATIONS DES ON (E.DESIGNATION_ID=DES.DESIGNATION_ID)
                     LEFT JOIN HRIS_POSITIONS P ON (E.POSITION_ID=P.POSITION_ID)
                     LEFT JOIN HRIS_SERVICE_TYPES ST ON (E.SERVICE_TYPE_ID=ST.SERVICE_TYPE_ID)
                     LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT ON (E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID)
                     WHERE 1=1 {$searchConditon}";
        //echo $sql; die;
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    public function getSalaryGroupColumns($type, $default = null) {
        $defaultString = " ";
        if ($default != null) {
            $defaultString = "AND Show_Default='{$default}'";
        }
        $sql = "select VARIANCE_ID,VARIANCE_NAME from Hris_Variance 
            where status='E' 
            AND VARIABLE_TYPE='{$type}'
        {$defaultString}";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    public function getGroupReport($variableType, $data) {
        $variable = $this->fetchSalaryGroupVariable($variableType);

        $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
        $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
        $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
        $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
        $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
        $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
        $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
        $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
        $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
        $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
        $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
        $monthId = $data['monthId'];
        $salaryTypeId = $data['salaryTypeId'];
//        $fiscalId = $data['fiscalId'];
        
        $strSalaryType=($salaryTypeId!=null && $salaryTypeId!=-1)?" WHERE SALARY_TYPE_ID={$salaryTypeId}":" ";

        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "SELECT 
            E.FULL_NAME,
            E.EMPLOYEE_CODE
            ,E.ID_PAN_NO
            ,E.ID_ACCOUNT_NO
            ,BR.BRANCH_NAME
            ,E.BIRTH_DATE
            ,E.JOIN_DATE
            ,D.DEPARTMENT_NAME
            ,FUNT.FUNCTIONAL_TYPE_EDESC
            ,GB.*
            ,SSED.SERVICE_TYPE_NAME
            ,SSED.DESIGNATION_TITlE
            ,SSED.POSITION_NAME
            ,SSED.ACCOUNT_NO
            FROM
            (
            SELECT * FROM (SELECT 
            SD.EMPLOYEE_ID
            ,Vp.Variance_Id
            ,SS.Month_ID
            ,SS.SHEET_NO
            ,SUM(VAL) AS TOTAL
            FROM HRIS_VARIANCE V
            LEFT JOIN HRIS_VARIANCE_PAYHEAD VP ON (V.VARIANCE_ID=VP.VARIANCE_ID)
            LEFT JOIN (select * from HRIS_SALARY_SHEET {$strSalaryType}) SS ON (1=1)
            LEFT JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND SD.Pay_Id=VP.Pay_Id)
            WHERE  V.STATUS='E' AND V.VARIABLE_TYPE='{$variableType}' 
            and SS.MONTH_ID={$monthId}
            GROUP BY SD.EMPLOYEE_ID,V.VARIANCE_NAME,Vp.Variance_Id,SS.Month_ID,SS.SHEET_NO)
            PIVOT ( MAX( TOTAL )
                FOR Variance_Id 
                IN ($variable)
                ))GB
                LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=GB.EMPLOYEE_ID)
                LEFT JOIN Hris_Salary_Sheet_Emp_Detail SSED ON 
    (SSED.SHEET_NO=GB.SHEET_NO AND SSED.EMPLOYEE_ID=GB.EMPLOYEE_ID AND SSED.MONTH_ID=GB.MONTH_ID)
                LEFT JOIN HRIS_DEPARTMENTS D  ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
                LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT ON (E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID)
                LEFT JOIN HRIS_BRANCHES BR ON ( E.BRANCH_ID=BR.BRANCH_ID)
                WHERE 1=1 
             {$searchConditon}
             ";

      //echo $sql;
       // die();



        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    private function fetchSalaryGroupVariable($variableType) {
        $rawList = EntityHelper::rawQueryResult($this->adapter, "select  * from Hris_Variance where   STATUS='E' AND VARIABLE_TYPE='{$variableType}' order by order_no");
        $dbArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']}";
            } else {
                $dbArray .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']},";
            }
        }
        return $dbArray;
    }

    public function getDefaultColumns($type) {
        $sql = "select 'V'||Variance_Id  as VARIANCE,VARIANCE_NAME from Hris_Variance where status='E'
        and Show_Default='Y'  AND VARIABLE_TYPE='{$type}' order by order_no";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    public function getGroupDetailReport($data) {


        $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
        $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
        $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
        $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
        $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
        $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
        $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
        $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
        $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
        $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
        $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
        $monthId = $data['monthId'];
        $salaryTypeId = $data['salaryTypeId'];
//        $fiscalId = $data['fiscalId'];
        
        $strSalaryType=($salaryTypeId!=null && $salaryTypeId!=-1)?" WHERE SALARY_TYPE_ID={$salaryTypeId}":" ";

        $groupVariable = $data['groupVariable'];
        $variable = $this->fetchGroupDetailVariable($groupVariable);

//        print_r($variable);
//        die();

        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "SELECT 
            E.FULL_NAME,
            E.EMPLOYEE_CODE
            ,E.ID_PAN_NO
            ,E.BIRTH_DATE
            ,E.JOIN_DATE
            ,D.DEPARTMENT_NAME
            ,FUNT.FUNCTIONAL_TYPE_EDESC
            ,GB.*
            ,SSED.SERVICE_TYPE_NAME
            ,SSED.DESIGNATION_TITlE
            ,SSED.POSITION_NAME
            ,SSED.ACCOUNT_NO
            FROM
            (
            select * from (
            SELECT 
            SD.EMPLOYEE_ID
            ,vp.pay_id
            ,SS.Month_ID
            ,SS.SHEET_NO
            ,VAL AS TOTAL
            FROM HRIS_VARIANCE V
            LEFT JOIN HRIS_VARIANCE_PAYHEAD VP ON (V.VARIANCE_ID=VP.VARIANCE_ID)
            LEFT JOIN (select * from HRIS_SALARY_SHEET {$strSalaryType}) SS ON (1=1)
            LEFT JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND SD.Pay_Id=VP.Pay_Id)
            WHERE  V.STATUS='E' 
            and V.VARIANCE_ID={$groupVariable}
            and SS.MONTH_ID={$monthId}
            )
            pivot(
            MAX( total )
                FOR pay_id 
                IN ({$variable})
                )
            )GB
                LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=GB.EMPLOYEE_ID)
                LEFT JOIN Hris_Salary_Sheet_Emp_Detail SSED ON 
    (SSED.SHEET_NO=GB.SHEET_NO AND SSED.EMPLOYEE_ID=GB.EMPLOYEE_ID AND SSED.MONTH_ID=GB.MONTH_ID)
                LEFT JOIN HRIS_DEPARTMENTS D  ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
                LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT ON (E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID)
                WHERE 1=1 
                
             {$searchConditon}
             ";

             
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    public function getVarianceDetailColumns($varianceId) {
        $sql = "select 
            'V'||vp.pay_id  as VARIANCE,ps.pay_edesc as VARIANCE_NAME
            from 
            Hris_Variance_Payhead vp
            left join Hris_Pay_Setup ps on (vp.pay_id=ps.pay_id)
            where variance_id={$varianceId}";

        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    private function fetchGroupDetailVariable($varianceId) {
        $sql = "select 
        vp.pay_id  as VARIANCE_ID,ps.pay_edesc as VARIANCE_NAME
        from 
        Hris_Variance_Payhead vp
        left join Hris_Pay_Setup ps on (vp.pay_id=ps.pay_id)
        where variance_id={$varianceId}";
        $rawList = EntityHelper::rawQueryResult($this->adapter, $sql);
        $dbArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']}";
            } else {
                $dbArray .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']},";
            }
        }
        return $dbArray;
    }

    public function fetchMonthlySummary($type, $data) {

        $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
        $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
        $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
        $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
        $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
        $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
        $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
        $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
        $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
        $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
        $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
        $monthId = $data['monthId'];
        $salaryTypeId = $data['salaryTypeId'];
        
        $strSalaryType=($salaryTypeId!=null && $salaryTypeId!=-1)?" and ss.Salary_Type_Id={$salaryTypeId}":" ";

        $searchConditon = EntityHelper::getSearchConditonPayroll($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "SELECT 
            PS.PAY_ID,PS.PAY_CODE,PS.PAY_EDESC,PS.PAY_TYPE_FLAG
            ,CASE WHEN SUM(SD.VAL) IS NULL THEN 0 ELSE SUM(SD.VAL) END AS TOTAL
            FROM HRIS_PAY_SETUP PS 
            LEFT JOIN HRIS_SALARY_SHEET SS ON (SS.MONTH_ID={$monthId} {$strSalaryType})
            LEFT JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND PS.PAY_ID=SD.PAY_ID)
            LEFT JOIN Hris_Salary_Sheet_Emp_Detail SSED ON (SSED.SHEET_NO=SD.SHEET_NO AND SSED.EMPLOYEE_ID=SD.EMPLOYEE_ID)
            WHERE PS.PAY_Type_flag='{$type}'
             {$searchConditon} 
            GROUP BY PS.PAY_ID,PS.PAY_CODE,PS.PAY_EDESC,PS.PAY_TYPE_FLAG";
             
        $result = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($result);
    }

    public function pulldepartmentWise($data) {

        $salarySheetRepo = new SalarySheetDetailRepo($this->adapter);

        $in = $salarySheetRepo->fetchPayIdsAsArray();

        $departmentId = (isset($data['departmentId']) && $data['departmentId'] != -1 ) ? $data['departmentId'] : null;
        $monthId = $data['monthId'];

        $othersList = array();

        $departmentList = $this->fetchDepartmentList($departmentId);
        $counter = 0;
        foreach ($departmentList as $dep) {
            $tempVal = $this->getMonthlySummaryByDep($monthId, $dep['DEPARTMENT_ID'], $in,$data['salaryTypeId']);
            if (isset($tempVal['PARENT_DEPARTMENT']) && $departmentId && $counter == 0) {
                $tempVal['PARENT_DEPARTMENT'] = null;
                $counter++;
            }
            if ($tempVal) {
                array_push($othersList, $tempVal);
            }
        }
        return $othersList;
    }

    public function fetchDepartmentList($departmentId = null) {
        if ($departmentId != null) {
            $sql = "
                        select {$departmentId} as DEPARTMENT_ID from dual
                            union all
                SELECT CD.DEPARTMENT_ID FROM
                         HRIS_DEPARTMENTS CD
                        START WITH CD.PARENT_DEPARTMENT={$departmentId}
                        CONNECT BY CD.PARENT_DEPARTMENT= PRIOR CD.DEPARTMENT_ID
                        ";
        } else {
            $sql = "select * from hris_departments where status='E' ";
        }
        $result = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($result);
    }

    public function getMonthlySummaryByDep($monthId, $departmentId, $inVal,$salaryTypeId) {
        $strSalaryType=($salaryTypeId!=null && $salaryTypeId!=-1)?" AND SS.SALARY_TYPE_ID={$salaryTypeId}":" ";
        
        $sql = "select D.Department_Name,D.Parent_Department,p.* from (SELECT 
            Department_Id
            ,PAY_ID
            ,CASE WHEN SUM(VAL) IS NULL THEN 0 ELSE SUM(VAL) END AS TOTAL
            from
             (SELECT 
            {$departmentId} as Department_Id
            ,PS.PAY_ID
            ,SD.VAL
            FROM HRIS_PAY_SETUP PS 
            left JOIN HRIS_SALARY_SHEET SS ON (SS.MONTH_ID={$monthId} {$strSalaryType})
            LEFT JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND PS.PAY_ID=SD.PAY_ID)
            LEFT JOIN Hris_Salary_Sheet_Emp_Detail SSED ON (SSED.SHEET_NO=SD.SHEET_NO AND SSED.EMPLOYEE_ID=SD.EMPLOYEE_ID and Ssed.Department_Id in (
            SELECT CD.DEPARTMENT_ID FROM
                         HRIS_DEPARTMENTS CD
                        START WITH CD.PARENT_DEPARTMENT={$departmentId}
                        CONNECT BY CD.PARENT_DEPARTMENT= PRIOR CD.DEPARTMENT_ID
                        union
                        select {$departmentId} from dual
            )) where Department_Id is not null
            )
             GROUP BY Department_Id,PAY_ID
            ) P
            PIVOT (
            MAX(total) FOR PAY_ID IN ({$inVal})
            ) P 
            LEFT JOIN Hris_Departments D ON (D.Department_Id=P.Department_Id)";
        $result = EntityHelper::rawQueryResult($this->adapter, $sql);
        return $result->current();
    }

    public function getMonthList() {
        $sql = "select * from Hris_Month_Code where 
                Fiscal_Year_Id=(select max(Fiscal_Year_Id) 
                from Hris_Fiscal_Years) order by Fiscal_Year_Month_No";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    public function getJvReport($data){
        $salaryType = ($data['salaryTypeId']!=null && $data['salaryTypeId']!=-1)? $data['salaryTypeId'] : '' ;
        $monthId = $data['monthId'];
        $deptId = $data['departmentId']!=-1 ? $data['departmentId'] : '' ;
        $reportTypeId = $data['reportTypeId'];
        $sql = '';

        if($reportTypeId == 2){
            $sql .= "SELECT jv_name,
            listagg(department_name,',') within group( order by department_name) DEPARTMENT_NAME, 
            SUM(jv_value) JV_VALUE, PAY_TYPE_FLAG FROM( ";
        }

        $sql .= "select 
        Pjv.Jv_Name,
        Sed.Department_Id,
        SED.DEPARTMENT_NAME,
        PJV.Pay_Id,
        (CASE WHEN PJV.PAY_TYPE_FLAG = 'D' THEN 'DEBIT' ELSE 'CREDIT' END) PAY_TYPE_FLAG, 
        SUM(SSD.VAL) JV_VALUE
        from Hris_Salary_Sheet SS
        JOIN Hris_Salary_Sheet_Emp_Detail SED ON (SS.SHEET_NO=SED.SHEET_NO )
        JOIN Hris_Salary_Sheet_Detail SSD ON (SED.EMPLOYEE_ID=SSD.EMPLOYEE_ID AND SS.SHEET_NO=SSD.SHEET_NO)
        JOIN Hris_Payroll_Jv PJV ON (PJV.STATUS='E' AND PJV.FLAG='Y' AND PJV.DEPARTMENT_ID=SED.DEPARTMENT_ID AND SSD.PAY_ID=PJV.PAY_ID)
        WHERE SS.MONTH_ID=$monthId ";

        $sql.= $deptId!='' ? " AND Sed.Department_Id = $deptId " : '' ;
        $sql.= $salaryType!='' ? " AND SS.SALARY_TYPE_ID = $salaryType " : '' ;
        $sql.=" GROUP BY Pjv.Jv_Name,Sed.Department_Id,SED.DEPARTMENT_NAME,PJV.Pay_Id,PAY_TYPE_FLAG
        ORDER BY Sed.Department_Id";

        if($reportTypeId == 2){
            $sql .= ")
            GROUP BY jv_name,
            pay_id,
            department_name, pay_type_flag";
        }

        //echo $sql; die;
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }
    
    public function gettaxYearlyByHeads($heads,$type='arr') {
        $sql = "select 
 Variance_Id,variance_name, 'V'||Variance_Id as template_name             
from hris_variance 
            where variable_type='Y'
            and v_heads='{$heads}'
            and status='E'
            order by order_no asc";
        $result = EntityHelper::rawQueryResult($this->adapter, $sql);
        if($type=='sin'){
        return $result->current();
        }else{
        return Helper::extractDbData($result);
        }
    }
    
    private function fetchSalaryTaxYearlyVariable() {
        $rawList = EntityHelper::rawQueryResult($this->adapter, "select  * from Hris_Variance where   STATUS='E' AND VARIABLE_TYPE='Y'");
        $dbArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']}";
            } else {
                $dbArray .= "{$row['VARIANCE_ID']} AS V{$row['VARIANCE_ID']},";
            }
        }
        return $dbArray;
    }
    
    
    public function getTaxYearly($data) {
         $variable = $this->fetchSalaryTaxYearlyVariable();
         

        $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
        $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
        $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
        $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
        $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
        $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
        $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
        $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
        $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
        $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
        $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
        $monthId = $data['monthId'];
        $salaryTypeId = $data['salaryTypeId'];
//        $fiscalId = $data['fiscalId'];
        
        
        $strSalaryType=($salaryTypeId!=null && $salaryTypeId!=-1)?" WHERE SALARY_TYPE_ID={$salaryTypeId}":" ";

        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "SELECT 
            E.FULL_NAME,
            E.EMPLOYEE_CODE
            ,E.ID_PAN_NO
            ,E.ID_ACCOUNT_NO
            ,BR.BRANCH_NAME
            ,E.BIRTH_DATE
            ,E.JOIN_DATE
            ,D.DEPARTMENT_NAME
            ,SSED.FUNCTIONAL_TYPE_EDESC
            ,GB.*
            ,SSED.SERVICE_TYPE_NAME
            ,SSED.DESIGNATION_TITlE
            ,SSED.POSITION_NAME
            ,SSED.ACCOUNT_NO
            ,SSED.MARITAL_STATUS_DESC
            ,C.COMPANY_NAME
            ,MCD.YEAR||'-'||MCD.MONTH_EDESC AS YEAR_MONTH_NAME
            FROM
            (
            SELECT * FROM (SELECT 
            SD.EMPLOYEE_ID
            ,Vp.Variance_Id
            ,SS.Month_ID
            ,SS.SHEET_NO
            ,SUM(VAL) AS TOTAL
            FROM HRIS_VARIANCE V
            LEFT JOIN HRIS_VARIANCE_PAYHEAD VP ON (V.VARIANCE_ID=VP.VARIANCE_ID)
            LEFT JOIN (select * from HRIS_SALARY_SHEET {$strSalaryType}) SS ON (1=1)
            LEFT JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND SD.Pay_Id=VP.Pay_Id)
            WHERE  V.STATUS='E' AND V.VARIABLE_TYPE='Y' 
            and SS.MONTH_ID={$monthId}
            GROUP BY SD.EMPLOYEE_ID,V.VARIANCE_NAME,Vp.Variance_Id,SS.Month_ID,SS.SHEET_NO)
            PIVOT ( MAX( TOTAL )
                FOR Variance_Id 
                IN ($variable)
                ))GB
                LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=GB.EMPLOYEE_ID)
                LEFT JOIN Hris_Salary_Sheet_Emp_Detail SSED ON 
    (SSED.SHEET_NO=GB.SHEET_NO AND SSED.EMPLOYEE_ID=GB.EMPLOYEE_ID AND SSED.MONTH_ID=GB.MONTH_ID)
                LEFT JOIN HRIS_DEPARTMENTS D  ON (D.DEPARTMENT_ID=SSED.DEPARTMENT_ID)
                LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT ON (SSED.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID)
                LEFT JOIN HRIS_BRANCHES BR ON (SSED.BRANCH_ID=BR.BRANCH_ID)
                LEFT JOIN HRIS_COMPANY C ON (SSED.COMPANY_ID=E.COMPANY_ID)
                LEFT JOIN HRIS_MONTH_CODE MCD ON (MCD.MONTH_ID={$monthId})
                WHERE 1=1 
             {$searchConditon}
             ";
             


        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }
    
    

}
