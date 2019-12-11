<?php

namespace Payroll\Repository;

use Application\Helper\Helper;
use Application\Model\Model;
use Application\Model\Months;
use Application\Repository\HrisRepository;
use Payroll\Model\SalarySheet;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

class SalarySheetRepo extends HrisRepository {

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        parent::__construct($adapter, SalarySheet::TABLE_NAME);
    }

    public function add(Model $model) {
        return $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        return $this->tableGateway->delete([SalarySheet::MONTH_ID => $id]);
    }

    public function fetchAll() {
        return $this->tableGateway->select(function (Select $select) {
                    $select->columns(Helper::convertColumnDateFormat($this->adapter, new SalarySheet(), [
                                'startDate',
                                'endDate',
                            ]), false);
                });
    }

    public function fetchById($id) {
        return $this->tableGateway->select([SalarySheet::SHEET_NO => $id]);
    }

    public function fetchByIds(array $ids) {
        return $this->tableGateway->select($ids);
    }

    public function fetchOneBy(array $ids) {
        return $this->tableGateway->select($ids)->current();
    }

    public function joinWithMonth($monthId = null, $employeeJoinDate = null) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([]);
        $select->from(['S' => SalarySheet::TABLE_NAME])
                ->join(['M' => Months::TABLE_NAME], 'S.' . SalarySheet::MONTH_ID . '=M.' . Months::MONTH_ID);
        if ($monthId != null) {
            $select->where([Months::MONTH_ID => $monthId]);
        }
        $select->where(["M." . Months::STATUS . " = " . "'E'"]);
        $select->where(["S." . SalarySheet::STATUS . " = " . "'E'"]);

        if ($employeeJoinDate != null) {
            $select->where(["'" . $employeeJoinDate . "'" . " <= " . "M." . Months::TO_DATE]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function generateSalShReport($sheetNo) {
        $this->executeStatement("BEGIN
                            HRIS_GEN_SAL_SH_REPORT({$sheetNo});
                        END;");
    }

    public function updateLoanPaymentFlag($employeeId, $sheetNo) {
        $this->executeStatement("BEGIN
                            hris_loan_payment_flag_change({$employeeId},{$sheetNo});
                        END;");
    }

    public function fetchAllSalaryType() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(['*']);
        $select->from('HRIS_SALARY_TYPE');
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }
    
    public function fetchEmployeeByGroup($monthId,$group,$salaryTypeId) {
        $sql = "SELECT 
            employee_id,employee_code,full_name,'Y' AS CHECKED_FLAG
            FROM HRIS_EMPLOYEES 
            WHERE 
            STATUS='E' AND 
employee_id not in (SELECT employee_id FROM HRIS_SALARY_SHEET_EMP_DETAIL SED
JOIN HRIS_SALARY_SHEET SS ON (SS.SHEET_NO=SED.SHEET_NO) 
where SS.month_id={$monthId} AND SS.SALARY_TYPE_ID=$salaryTypeId)            
AND GROUP_ID IN ({$group})";
        $data = $this->rawQuery($sql);
        return $data;
    }
    
    public function fetchGeneratedSheetByGroup($monthId,$group,$salaryTypeId){
        $sql="select 
                ss.sheet_no,ssg.Group_Name,Mc.Month_Edesc,St.Salary_Type_Name,
                ss.approved, ss.locked 
                from HRIS_SALARY_SHEET ss 
                join hris_salary_sheet_group ssg on (ssg.group_id=ss.group_id)
                join Hris_Month_Code mc on (mc.month_id=ss.month_id)
                join HRIS_SALARY_TYPE st on (St.Salary_Type_Id=Ss.Salary_Type_Id)
                where ss.Month_Id={$monthId} and ss.salary_type_id=$salaryTypeId  and ss.Group_Id in ($group)";
        $data = $this->rawQuery($sql);
        return $data;
    }
    
    public function insertPayrollEmp($empList,$monthId,$salaryTypeId) {
        $deleteSql = "delete from HRIS_PAYROLL_EMP_LIST";
        $this->executeStatement($deleteSql);
        foreach ($empList as $employeeId) {
//            $tempSql = "INSERT INTO HRIS_PAYROLL_EMP_LIST VALUES ({$employeeId})";
            $tempSql = "INSERT INTO HRIS_PAYROLL_EMP_LIST 
                    select * from (select {$employeeId} as employee_id from dual) 
where employee_id not in (select employee_id from Hris_Salary_Sheet_Emp_Detail ssed
join HRIS_SALARY_SHEET ss on (ss.SHEET_NO=ssed.SHEET_NO)
where ss.month_id={$monthId} and ss.SALARY_TYPE_ID={$salaryTypeId})";
            $this->executeStatement($tempSql);
        }
        $toGenerateGroupSql="select  distinct group_id 
                from 
                 hris_employees where employee_id in
                 (select employee_id from HRIS_PAYROLL_EMP_LIST)";
        $groupData = $this->rawQuery($toGenerateGroupSql);
        return $groupData;
    }
    
    public function deleteSheetBySheetNo($sheetNo){
        $sql="
        BEGIN            
        delete from HRIS_TAX_SHEET where sheet_no={$sheetNo};
        delete from HRIS_SALARY_SHEET_DETAIL where sheet_no={$sheetNo};
        delete from HRIS_SALARY_SHEET_EMP_DETAIL where sheet_no={$sheetNo};
        delete from HRIS_SALARY_SHEET where sheet_no={$sheetNo};
        END;
";
        $this->executeStatement($sql);
        return true;
    }

    public function bulkApproveLock($sheetNo, $col, $val){
        $sql = "UPDATE HRIS_SALARY_SHEET set $col = '$val' where sheet_no={$sheetNo}";
        $this->executeStatement($sql);
        return true;
    }

    public function checkApproveLock($sheetNo){
        $sql = "SELECT SHEET_NO, APPROVED, LOCKED FROM HRIS_SALARY_SHEET WHERE SHEET_NO = $sheetNo";
        $data = $this->rawQuery($sql);
        return Helper::extractDbData($data);
    }
    
    public function fetchSheetWiseEmployeeList($sheetNo){
        $sql = "select SS.SHEET_NO,SS.MONTH_ID,SS.SALARY_TYPE_ID,SSED.EMPLOYEE_ID from HRIS_SALARY_SHEET SS
JOIN HRIS_SALARY_SHEET_EMP_DETAIL SSED ON (SS.SHEET_NO=SSED.SHEET_NO)
where SS.SHEET_NO={$sheetNo}";
        $data = $this->rawQuery($sql);
        return Helper::extractDbData($data);
    }
}
