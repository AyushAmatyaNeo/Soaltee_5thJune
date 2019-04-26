<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Payroll\Model\MonthlyValueDetail;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class MonthlyValueDetailRepo {

    private $adapter;
    private $gateway;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->gateway = new TableGateway(MonthlyValueDetail::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->gateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $this->gateway->update($model->getArrayCopyForDB(), [MonthlyValueDetail::EMPLOYEE_ID => $id[0], MonthlyValueDetail::MTH_ID => $id[1]]);
    }

    public function fetchById($id) {
        $sql = "SELECT MTH_VALUE
                FROM HRIS_MONTHLY_VALUE_DETAIL
                WHERE EMPLOYEE_ID = {$id['employeeId']}
                AND MONTH_ID      = {$id['monthId']}
                AND MTH_ID        = {$id['mthId']}";

        $statement = $this->adapter->query($sql);
        $rawResult = $statement->execute();
        $result = $rawResult->current();
        return $result != null ? $result['MTH_VALUE'] : 0;
    }

    public function getMonthlyValuesDetailById($monthlyValueId, $fiscalYearId, $emp, $monthId = null) {
        $searchConditon = EntityHelper::getSearchConditon($emp['companyId'], $emp['branchId'], $emp['departmentId'], $emp['positionId'], $emp['designationId'], $emp['serviceTypeId'], $emp['serviceEventTypeId'], $emp['employeeTypeId'], $emp['employeeId'], $emp['genderId'],null, $emp['functionalTypeId']);
        $empQuery = "SELECT E.EMPLOYEE_ID FROM HRIS_EMPLOYEES E WHERE 1=1 {$searchConditon}";
        $sql = "SELECT MVD.*,EE.FULL_NAME,EE.EMPLOYEE_CODE FROM HRIS_MONTHLY_VALUE_DETAIL MVD
                LEFT JOIN HRIS_EMPLOYEES EE on (EE.EMPLOYEE_ID=MVD.EMPLOYEE_ID)  
                WHERE MVD.MTH_ID = {$monthlyValueId} AND MVD.FISCAL_YEAR_ID = {$fiscalYearId} AND MVD.EMPLOYEE_ID IN ( {$empQuery} )";
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }

    public function postMonthlyValuesDetail($data) {
        $sql = "
                DECLARE
                  V_MTH_ID HRIS_MONTHLY_VALUE_DETAIL.MTH_ID%TYPE := {$data['mthId']};
                  V_EMPLOYEE_ID HRIS_MONTHLY_VALUE_DETAIL.EMPLOYEE_ID%TYPE := {$data['employeeId']};
                  V_MTH_VALUE HRIS_MONTHLY_VALUE_DETAIL.MTH_VALUE%TYPE := {$data['mthValue']};
                  V_FISCAL_YEAR_ID HRIS_MONTHLY_VALUE_DETAIL.FISCAL_YEAR_ID%TYPE := {$data['fiscalYearId']};
                  V_MONTH_ID HRIS_MONTHLY_VALUE_DETAIL.MONTH_ID%TYPE := {$data['monthId']};
                  V_OLD_MTH_VALUE HRIS_MONTHLY_VALUE_DETAIL.MTH_VALUE%TYPE;
                BEGIN
                  SELECT MTH_VALUE
                  INTO V_OLD_MTH_VALUE
                  FROM HRIS_MONTHLY_VALUE_DETAIL
                  WHERE MTH_ID       = V_MTH_ID
                  AND EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND FISCAL_YEAR_ID = V_FISCAL_YEAR_ID
                  AND MONTH_ID       = V_MONTH_ID;
                  UPDATE HRIS_MONTHLY_VALUE_DETAIL
                  SET MTH_VALUE      = V_MTH_VALUE
                  WHERE MTH_ID       = V_MTH_ID
                  AND EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND FISCAL_YEAR_ID = V_FISCAL_YEAR_ID
                  AND MONTH_ID       = V_MONTH_ID;
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  INSERT
                  INTO HRIS_MONTHLY_VALUE_DETAIL
                    (
                      MTH_ID,
                      EMPLOYEE_ID,
                      FISCAL_YEAR_ID,
                      MONTH_ID,
                      MTH_VALUE,
                      CREATED_DT
                    )
                    VALUES
                    (
                      V_MTH_ID,
                      V_EMPLOYEE_ID,
                      V_FISCAL_YEAR_ID,
                      V_MONTH_ID,
                      V_MTH_VALUE,
                      TRUNC(SYSDATE)
                    );
                END;
";
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }

    public function getMonthDeatilByFiscalYear($fiscalYearId) {
        $sql = "SELECT * FROM HRIS_MONTH_CODE  WHERE FISCAL_YEAR_ID={$fiscalYearId} ORDER BY FISCAL_YEAR_MONTH_NO";
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }

}
