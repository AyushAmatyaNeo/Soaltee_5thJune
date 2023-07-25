<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Repository\HrisRepository;
use Payroll\Model\PositionMonthlyValue;
use Zend\Db\Adapter\AdapterInterface;

class PositionMonthlyValueRepo extends HrisRepository {

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        if ($tableName == null) {
            $tableName = PositionMonthlyValue::TABLE_NAME;
        }
        parent::__construct($adapter, $tableName);
    }

    public function fetchValue($keys) {
        $sql = "SELECT (
                  CASE
                    WHEN ASSIGN_TYPE ='E'
                    THEN MTH_VALUE
                    ELSE ASSIGNED_VALUE
                  END) AS ASSIGNED_VALUE
                FROM
                  (SELECT MVS.ASSIGN_TYPE,
                    MVD.MTH_VALUE,
                    PMV.ASSIGNED_VALUE
                  FROM HRIS_MONTHLY_VALUE_SETUP MVS
                  LEFT JOIN
                    (SELECT *
                    FROM HRIS_MONTHLY_VALUE_DETAIL
                    WHERE MONTH_ID ={$keys['MONTH_ID']}
                    AND EMPLOYEE_ID={$keys['EMPLOYEE_ID']}
                    ) MVD
                  ON (MVS.MTH_ID=MVD.MTH_ID)
                  LEFT JOIN
                    (SELECT *
                    FROM HRIS_POSITION_MONTHLY_VALUE
                    WHERE MONTH_ID ={$keys['MONTH_ID']}
                    AND POSITION_ID=
                      (SELECT POSITION_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = {$keys['EMPLOYEE_ID']}
                      )
                    ) PMV
                  ON (MVS.MTH_ID   =PMV.MTH_ID)
                  WHERE MVS.MTH_ID ={$keys['MTH_ID']}
                  )";
        $resultList = $this->rawQuery($sql);
        if (sizeof($resultList) != 1) {
            return 0;
        }
        return isset($resultList[0]['ASSIGNED_VALUE']) ? $resultList[0]['ASSIGNED_VALUE'] : 0;
    }

    public function fetchById($id) {
        $sql = "SELECT PMV.ASSIGNED_VALUE
                FROM HRIS_POSITION_MONTHLY_VALUE PMV
                JOIN HRIS_EMPLOYEES E
                ON(PMV.POSITION_ID = E.POSITION_ID)
                WHERE PMV.MTH_ID   ={$id['MTH_ID']}
                AND PMV.MONTH_ID   ={$id['MONTH_ID']}
                AND E.EMPLOYEE_ID  = {$id['EMPLOYEE_ID']}
                ";

        $statement = $this->adapter->query($sql);
        $rawResult = $statement->execute();
        return $rawResult->current();
    }

    public function getPositionMonthlyValue($monthId) {
        $sql = "
            SELECT *
            FROM
              ( SELECT MTH_ID,POSITION_ID,ASSIGNED_VALUE FROM HRIS_POSITION_MONTHLY_VALUE WHERE MONTH_ID ={$monthId}
              ) PIVOT ( MAX(ASSIGNED_VALUE) FOR MTH_ID IN ({$this->fetchMonthlyValueAsDbArray()}) )";
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    private function fetchMonthlyValueAsDbArray() {
        $rawList = EntityHelper::rawQueryResult($this->adapter, "SELECT MTH_ID FROM HRIS_MONTHLY_VALUE_SETUP WHERE STATUS ='E'");
        $dbArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray .= "{$row['MTH_ID']}";
            } else {
                $dbArray .= "{$row['MTH_ID']},";
            }
        }
        return $dbArray;
    }

    public function setPositionMonthlyValue($monthId, $positionId, $mthId, $assignedValue) {
        $sql = "
                DECLARE
                  V_MONTH_ID HRIS_POSITION_MONTHLY_VALUE.MONTH_ID%TYPE             := {$monthId};
                  V_MTH_ID HRIS_POSITION_MONTHLY_VALUE.MTH_ID%TYPE                 := {$mthId};
                  V_POSITION_ID HRIS_POSITION_MONTHLY_VALUE.POSITION_ID%TYPE       := {$positionId};
                  V_ASSIGNED_VALUE HRIS_POSITION_MONTHLY_VALUE.ASSIGNED_VALUE%TYPE := {$assignedValue};
                  V_OLD_ASSIGNED_VALUE HRIS_POSITION_MONTHLY_VALUE.ASSIGNED_VALUE%TYPE;
                BEGIN
                  SELECT ASSIGNED_VALUE
                  INTO V_OLD_ASSIGNED_VALUE
                  FROM HRIS_POSITION_MONTHLY_VALUE
                  WHERE MTH_ID    = V_MTH_ID
                  AND POSITION_ID = V_POSITION_ID
                  AND MONTH_ID    = V_MONTH_ID;
                  UPDATE HRIS_POSITION_MONTHLY_VALUE
                  SET ASSIGNED_VALUE = V_ASSIGNED_VALUE
                  WHERE MTH_ID       = V_MTH_ID
                  AND POSITION_ID    = V_POSITION_ID
                  AND MONTH_ID       = V_MONTH_ID;
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  INSERT
                  INTO HRIS_POSITION_MONTHLY_VALUE
                    (
                      MTH_ID,
                      POSITION_ID,
                      MONTH_ID,
                      ASSIGNED_VALUE
                    )
                    VALUES
                    (
                      V_MTH_ID,
                      V_POSITION_ID,
                      V_MONTH_ID,
                      V_ASSIGNED_VALUE
                    );
                END;";
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }

}
