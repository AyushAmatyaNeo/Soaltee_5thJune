<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Payroll\Model\Rules;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class RulesRepository implements RepositoryInterface {

    private $adapter;
    private $gateway;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->gateway = new TableGateway(Rules::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        return $this->gateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $this->gateway->update($model->getArrayCopyForDB(), [Rules::PAY_ID => $id]);
    }

    public function fetchAll() {
        $query = "SELECT PAY_ID,
                  PAY_CODE,
                  PAY_EDESC,
                  PAY_TYPE_FLAG,
                  (
                  CASE
                    WHEN PAY_TYPE_FLAG ='A'
                    THEN 'Additon'
                    WHEN PAY_TYPE_FLAG='D'
                    THEN 'Deduction'
                    WHEN PAY_TYPE_FLAG='V'
                    THEN 'View'
                    ELSE 'Tax'
                  END) AS PAY_TYPE,
                  PRIORITY_INDEX,
                  INCLUDE_IN_TAX,
                  (
                  CASE
                    WHEN INCLUDE_IN_TAX = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_IN_TAX_DETAIL,
                  INCLUDE_IN_SALARY,
                  (
                  CASE
                    WHEN INCLUDE_IN_SALARY = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_IN_SALARY_DETAIL,
                  INCLUDE_PAST_VALUE,
                  (
                  CASE
                    WHEN INCLUDE_PAST_VALUE = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_PAST_VALUE_DETAIL,
                  INCLUDE_FUTURE_VALUE,
                  (
                  CASE
                    WHEN INCLUDE_FUTURE_VALUE = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_FUTURE_VALUE_DETAIL,
                  FORMULA,
                  REMARKS,
                  STATUS
                FROM HRIS_PAY_SETUP
                WHERE STATUS ='E' ORDER BY PRIORITY_INDEX";

        $statement = $this->adapter->query($query);
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id) {
        return $this->gateway->select(function(Select $select) use($id) {
                    $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Rules::class, [Rules::PAY_EDESC, Rules::PAY_LDESC]), false);
                    $select->where([Rules::STATUS => 'E', Rules::PAY_ID => $id]);
                })->current();
    }

    public function delete($id) {
        $rule = new Rules();
        $rule->modifiedDt = Helper::getcurrentExpressionDate();
        $rule->status = 'D';
        $this->gateway->update($rule->getArrayCopyForDB(), [Rules::PAY_ID => $id]);
    }

    public function fetchReferencingRules($payId = null) {

        if ($payId == null) {
            $sql = "
                SELECT P.PAY_ID,
                  INITCAP(P.PAY_EDESC) AS PAY_EDESC,
                  INITCAP(P.PAY_LDESC) AS PAY_LDESC
                FROM HRIS_PAY_SETUP P";
        } else {
            $sql = "
                SELECT P.PAY_ID,
                  INITCAP(P.PAY_EDESC) AS PAY_EDESC,
                  INITCAP(P.PAY_LDESC) AS PAY_LDESC
                FROM HRIS_PAY_SETUP P,
                  (SELECT PRIORITY_INDEX FROM HRIS_PAY_SETUP WHERE PAY_ID=$payId
                  ) PS
                WHERE P.PRIORITY_INDEX < PS.PRIORITY_INDEX";
        }
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

}
