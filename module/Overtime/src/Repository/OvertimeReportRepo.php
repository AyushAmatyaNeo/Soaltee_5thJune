<?php
namespace Overtime\Repository;

use Application\Helper\EntityHelper;
use Application\Repository\HrisRepository;

class OvertimeReportRepo extends HrisRepository {

    public function fetch($monthId) {
        $colsForPivot = $this->fetchColumnsForPivot($monthId);
        $sql = "SELECT *
                FROM
                  (SELECT R.EMPLOYEE_ID,
                    E.FULL_NAME,
                    'D_'
                    ||TO_NUMBER(regexp_substr(BS_DATE(R.ATTENDANCE_DT),'[^-]+', 1, 3))  AS MONTH_DAY,
                    (
                    CASE
                      WHEN R.OVERALL_STATUS IN ('PR','LP','LA')
                      AND R.TOTAL_HOUR      IS NOT NULL
                      THEN R.TOTAL_HOUR-R.TOTAL_WORKING_HR
                      ELSE 0
                    END) AS OT_HOUR
                  FROM
                    (SELECT AD.EMPLOYEE_ID,
                      AD.ATTENDANCE_DT,
                      AD.TOTAL_HOUR,
                      (
                      CASE
                        WHEN AD.HALFDAY_PERIOD ='F'
                        THEN ABS(EXTRACT( HOUR FROM (S.END_TIME -S.HALF_DAY_IN_TIME) ))*60 + ABS(EXTRACT( MINUTE FROM (S.END_TIME -S.HALF_DAY_IN_TIME) ))
                        WHEN AD.HALFDAY_PERIOD ='S'
                        THEN ABS(EXTRACT( HOUR FROM (S.HALF_DAY_OUT_TIME -S.START_TIME) ))*60 + ABS(EXTRACT( MINUTE FROM (S.HALF_DAY_OUT_TIME -S.START_TIME) ))
                        WHEN AD.GRACE_PERIOD='E'
                        THEN ABS(EXTRACT( HOUR FROM (S.END_TIME -S.GRACE_START_TIME) ))*60 + ABS(EXTRACT( MINUTE FROM (S.END_TIME -S.GRACE_START_TIME) ))
                        WHEN AD.GRACE_PERIOD='L'
                        THEN ABS(EXTRACT( HOUR FROM (S.GRACE_END_TIME -S.START_TIME) ))*60 + ABS(EXTRACT( MINUTE FROM (S.GRACE_END_TIME -S.START_TIME) ))
                        ELSE S.TOTAL_WORKING_HR
                      END) AS TOTAL_WORKING_HR,
                      AD.OVERALL_STATUS
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    LEFT JOIN HRIS_SHIFTS S
                    ON (AD.SHIFT_ID =S.SHIFT_ID)
                    JOIN HRIS_MONTH_CODE M
                    ON (AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE)
                    WHERE M.MONTH_ID={$monthId}
                    ) R
                  LEFT JOIN HRIS_EMPLOYEES E
                  ON (E.EMPLOYEE_ID                     =R.EMPLOYEE_ID)
                  ) PIVOT (MAX(OT_HOUR ) FOR MONTH_DAY IN ({$colsForPivot}))
                ORDER BY FULL_NAME";
        return $this->rawQuery($sql);
    }

    public function fetchColumns($monthId): array {
        $sql = "SELECT 'D_'
                  ||ROWNUM AS MONTH_DAY_FIELD,
                  ROWNUM AS MONTH_DAY_TITLE
                FROM DUAL
                  CONNECT BY ROWNUM <=
                  (SELECT (TO_DATE-FROM_DATE)+1 FROM HRIS_MONTH_CODE WHERE MONTH_ID={$monthId}
                  )";

        return $this->rawQuery($sql);
    }

    private function fetchColumnsForPivot($monthId) {
        $sql = "SELECT LISTAGG('''D_'
                  ||MONTH_DAY
                  ||''' AS D_'
                  ||MONTH_DAY, ',') WITHIN GROUP (
                ORDER BY MONTH_DAY) MONTH_DAY_IN
                FROM
                  (SELECT ROWNUM AS MONTH_DAY
                  FROM DUAL
                    CONNECT BY ROWNUM <=
                    (SELECT (TO_DATE-FROM_DATE)+1 FROM HRIS_MONTH_CODE WHERE MONTH_ID={$monthId}
                    )
                  )";

        return $this->rawQuery($sql)[0]['MONTH_DAY_IN'];
    }

    public function fetchMonthlyForGrid($by): array {
        $monthId = $by['monthId'];
        $pivotIn = $this->fetchColumnsForPivot($monthId);
        $searchConditon = EntityHelper::getSearchConditon($by['companyId'], $by['branchId'], $by['departmentId'], $by['positionId'], $by['designationId'], $by['serviceTypeId'], $by['serviceEventTypeId'], $by['employeeTypeId'], $by['employeeId']);
        $sql = "SELECT *
                FROM
                  (SELECT AD.EMPLOYEE_ID, E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
                    E.FULL_NAME,
                    'D_'
                    ||TO_NUMBER(regexp_substr(BS_DATE(TRUNC(AD.ATTENDANCE_DATE)),'[^-]+', 1, 3)) AS MONTH_DAY,
                    OM.OVERTIME_HOUR
                  FROM
                    (SELECT AD.EMPLOYEE_ID,
                      AD.ATTENDANCE_DT AS ATTENDANCE_DATE
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    JOIN HRIS_MONTH_CODE MC
                    ON (AD.ATTENDANCE_DT BETWEEN MC.FROM_DATE AND MC.TO_DATE)
                    WHERE MC.MONTH_ID={$monthId}
                    ) AD
                  LEFT JOIN HRIS_OVERTIME_MANUAL OM
                  ON (AD.EMPLOYEE_ID    =OM.EMPLOYEE_ID
                  AND AD.ATTENDANCE_DATE=OM.ATTENDANCE_DATE)
                  JOIN HRIS_EMPLOYEES E
                  ON (AD.EMPLOYEE_ID                         =E.EMPLOYEE_ID)
                  WHERE 1=1 
                  {$searchConditon}
                  ) PIVOT (MAX(OVERTIME_HOUR) FOR MONTH_DAY IN ({$pivotIn}))";
        return $this->rawQuery($sql);
    }

    public function bulkEdit($data) {
        foreach ($data as $value) {
            $this->createOrUpdate($value['MONTH_ID'], $value['MONTH_DAY'], $value['EMPLOYEE_ID'], $value['OVERTIME_HOUR']);
        }
    }

    private function createOrUpdate($m, $d, $e, $h) {
        $hour = $h == null ? 'NULL' : $h;
        $sql = "BEGIN HRIS_OT_MANUAL_CR_OR_UP({$m},{$d},{$e},{$hour}); END;";
        $this->executeStatement($sql);
    }
}
