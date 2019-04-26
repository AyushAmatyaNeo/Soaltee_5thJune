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
        return $data;
    }

    public function varianceColumnsPre() {
        $sql = "select 'V'||Variance_Id||'_P'  as VARIANCE,VARIANCE_NAME from Hris_Variance where status='E' and Show_Default='Y' AND VARIABLE_TYPE='V' ";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    public function varianceColumnsCur() {
        $sql = "select 'V'||Variance_Id||'_C'  as VARIANCE,VARIANCE_NAME from Hris_Variance where status='E' and Show_Default='Y' AND VARIABLE_TYPE='V' ";
        $data = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($data);
    }

    public function varianceColumnsDif() {
        $sql = "select 'V'||Variance_Id||'_D'  as VARIANCE,VARIANCE_NAME from Hris_Variance where status='E' and Show_Default='Y' and Show_Difference='Y' AND VARIABLE_TYPE='V' ";
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

        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

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
            WHERE 1=1 {$searchConditon}
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

        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "SELECT 
            E.FULL_NAME,
            E.EMPLOYEE_CODE
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
            LEFT JOIN (select * from HRIS_SALARY_SHEET) SS ON (1=1)
            LEFT JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND SD.Pay_Id=VP.Pay_Id)
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
        $fiscalId = $data['fiscalId'];

        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);
        
        $sql="SELECT 
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
            LEFT JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SS.SHEET_NO=SD.SHEET_NO AND SD.Pay_Id=VP.Pay_Id)
            LEFT JOIN HRIS_MONTH_CODE MC ON (SS.MONTH_ID=MC.MONTH_ID) 
            WHERE  V.STATUS='E' AND V.VARIABLE_TYPE='O'  AND Mc.Fiscal_Year_Id={$fiscalId} 
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

}
