<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Repository\HrisRepository;
use Payroll\Model\PositionFlatValue;
use Zend\Db\Adapter\AdapterInterface;

class PositionFlatValueRepo extends HrisRepository {

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        if ($tableName == null) {
            $tableName = PositionFlatValue::TABLE_NAME;
        }
        parent::__construct($adapter, $tableName);
    }

    public function fetchValue($keys) {
        $sql = "SELECT (
                  CASE
                    WHEN ASSIGN_TYPE ='E'
                    THEN FLAT_VALUE
                    ELSE ASSIGNED_VALUE
                  END) AS ASSIGNED_VALUE
                FROM
                  (SELECT MVS.ASSIGN_TYPE,
                    MVD.FLAT_VALUE,
                    PMV.ASSIGNED_VALUE
                  FROM HRIS_FLAT_VALUE_SETUP MVS
                  LEFT JOIN
                    (SELECT *
                    FROM HRIS_FLAT_VALUE_DETAIL
                    WHERE FISCAL_YEAR_ID = (SELECT FISCAL_YEAR_ID FROM HRIS_MONTH_CODE WHERE MONTH_ID = {$keys['MONTH_ID']})
                    AND EMPLOYEE_ID={$keys['EMPLOYEE_ID']}
                    ) MVD
                  ON (MVS.FLAT_ID=MVD.FLAT_ID)
                  LEFT JOIN
                    (SELECT *
                    FROM HRIS_POSITION_FLAT_VALUE
                    WHERE FISCAL_YEAR_ID =(SELECT FISCAL_YEAR_ID FROM HRIS_MONTH_CODE WHERE MONTH_ID = {$keys['MONTH_ID']})
                    AND POSITION_ID=
                      (SELECT POSITION_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = {$keys['EMPLOYEE_ID']}
                      )
                    ) PMV
                  ON (MVS.FLAT_ID   =PMV.FLAT_ID)
                  WHERE MVS.FLAT_ID ={$keys['FLAT_ID']}
                  )";
        $resultList = $this->rawQuery($sql);
        if (sizeof($resultList) != 1) {
            return 0;
        }
        return isset($resultList[0]['ASSIGNED_VALUE']) ? $resultList[0]['ASSIGNED_VALUE'] : 0;
    }

    public function getPositionFlatValue($fiscalYearId) {
        $sql = "
            SELECT *
            FROM
              ( SELECT FLAT_ID,POSITION_ID,ASSIGNED_VALUE FROM HRIS_POSITION_FLAT_VALUE WHERE FISCAL_YEAR_ID ={$fiscalYearId}
              ) PIVOT ( MAX(ASSIGNED_VALUE) FOR FLAT_ID IN ({$this->fetchFlatValueAsDbArray()}) )";
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    private function fetchFlatValueAsDbArray() {
        $rawList = EntityHelper::rawQueryResult($this->adapter, "SELECT FLAT_ID FROM HRIS_FLAT_VALUE_SETUP WHERE STATUS ='E'");
        $dbArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray .= "{$row['FLAT_ID']}";
            } else {
                $dbArray .= "{$row['FLAT_ID']},";
            }
        }
        return $dbArray;
    }

    public function setPositionFlatValue($fiscalYearId, $positionId, $flatId, $assignedValue) {
        $sql = "
                DECLARE
                  V_FISCAL_YEAR_ID HRIS_POSITION_FLAT_VALUE.FISCAL_YEAR_ID%TYPE             := {$fiscalYearId};
                  V_FLAT_ID HRIS_POSITION_FLAT_VALUE.FLAT_ID%TYPE                 := {$flatId};
                  V_POSITION_ID HRIS_POSITION_FLAT_VALUE.POSITION_ID%TYPE       := {$positionId};
                  V_ASSIGNED_VALUE HRIS_POSITION_FLAT_VALUE.ASSIGNED_VALUE%TYPE := {$assignedValue};
                  V_OLD_ASSIGNED_VALUE HRIS_POSITION_FLAT_VALUE.ASSIGNED_VALUE%TYPE;
                BEGIN
                  SELECT ASSIGNED_VALUE
                  INTO V_OLD_ASSIGNED_VALUE
                  FROM HRIS_POSITION_FLAT_VALUE
                  WHERE FLAT_ID    = V_FLAT_ID
                  AND POSITION_ID = V_POSITION_ID
                  AND FISCAL_YEAR_ID    = V_FISCAL_YEAR_ID;
                  UPDATE HRIS_POSITION_FLAT_VALUE
                  SET ASSIGNED_VALUE = V_ASSIGNED_VALUE
                  WHERE FLAT_ID       = V_FLAT_ID
                  AND POSITION_ID    = V_POSITION_ID
                  AND FISCAL_YEAR_ID       = V_FISCAL_YEAR_ID;
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  INSERT
                  INTO HRIS_POSITION_FLAT_VALUE
                    (
                      FLAT_ID,
                      POSITION_ID,
                      FISCAL_YEAR_ID,
                      ASSIGNED_VALUE
                    )
                    VALUES
                    (
                      V_FLAT_ID,
                      V_POSITION_ID,
                      V_FISCAL_YEAR_ID,
                      V_ASSIGNED_VALUE
                    );
                END;";
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }

}
