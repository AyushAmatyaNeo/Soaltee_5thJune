<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Zend\Db\Adapter\AdapterInterface;
use Application\Repository\HrisRepository;

class ExcelUploadRepository extends HrisRepository{

    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
      $this->adapter = $adapter;
    }

    public function updateEmployeeSalary($id, $salary){
        $sql = "UPDATE HRIS_EMPLOYEES SET SALARY = $salary WHERE EMPLOYEE_ID = $id"; 
        $statement = $this->adapter->query($sql);
        $statement->execute();
    }

    public function postPVMDetail($data) {
        $sql = "
                DECLARE
                  V_PAY_ID HRIS_SS_PAY_VALUE_MODIFIED.PAY_ID%TYPE := {$data['payId']};
                  V_EMPLOYEE_ID HRIS_SS_PAY_VALUE_MODIFIED.EMPLOYEE_ID%TYPE := {$data['employeeId']};
                  V_VAL HRIS_SS_PAY_VALUE_MODIFIED.VAL%TYPE := {$data['val']};
                  V_OLD_VAL HRIS_SS_PAY_VALUE_MODIFIED.VAL%TYPE;
                  V_SALARY_TYPE_ID HRIS_SS_PAY_VALUE_MODIFIED.SALARY_TYPE_ID%TYPE := 1;
                  V_MONTH_ID HRIS_MONTHLY_VALUE_DETAIL.MONTH_ID%TYPE := {$data['monthId']};
                BEGIN
                  SELECT VAL
                  INTO V_OLD_VAL
                  FROM HRIS_SS_PAY_VALUE_MODIFIED
                  WHERE PAY_ID       = V_PAY_ID
                  AND EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND MONTH_ID       = V_MONTH_ID;
                  UPDATE hris_ss_pay_value_modified
                  SET VAL      = V_VAL
                  WHERE PAY_ID       = V_PAY_ID
                  AND EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND MONTH_ID       = V_MONTH_ID;
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  INSERT
                  INTO hris_ss_pay_value_modified
                    (
                      PAY_ID,
                      EMPLOYEE_ID,
                      MONTH_ID,
                      VAL,
                      SALARY_TYPE_ID
                    )
                    VALUES
                    (
                      V_PAY_ID,
                      V_EMPLOYEE_ID,
                      V_MONTH_ID,
                      V_VAL,
                      1
                    );
                END;
";
//echo $sql; die;
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }

    public function getMonthId($fiscalYearId, $month_id){
    	$sql = "select month_id from hris_month_code where fiscal_Year_Id = $fiscalYearId and fiscal_year_month_no = $month_id";
    	return $this->rawQuery($sql)[0]['MONTH_ID'];
    }
}
