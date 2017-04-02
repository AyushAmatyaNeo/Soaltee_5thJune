<?php

namespace Application\Repository;

use Application\Model\Model;
use Application\Model\Months;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class DashboardRepository implements RepositoryInterface {

    private $gateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->gateway = new TableGateway(Months::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function edit(Model $model, $id) {
        
    }

    public function fetchById($id) {

    }

    public function fetchAll() {

    }

    /**
     * Fetches all the data related to display on dashboard related to EMPLOYEE_ID
     *
     * @param int $employeeId
     * @param  string $startDate
     * @param  string $endDate
     * @return array
     */
    public function fetchEmployeeDashboardData($employeeId, $startDate, $endDate) {
        $sql = "-- EMPLOYEE DETAIL
                SELECT EMPLOYEE_TBL.*,
                       NVL(LATE_TBL.LATE_IN, 0) LATE_IN,
                       NVL(EARLY_TBL.EARLY_OUT, 0) EARLY_OUT,
                       NVL(MISSED_PUNCH_TBL.MISSED_PUNCH, 0) MISSED_PUNCH,
                       NVL(PRESENT_TBL.PRESENT_DAY, 0) PRESENT_DAY,
                       NVL(ABSENT_TBL.ABSENT_DAY, 0) ABSENT_DAY,
                       NVL(LEAVE_TBL.LEAVE, 0) LEAVE,
                       NVL(WOH_TBL.WOH, 0) WOH,
                       NVL(TOUR_TBL.TOUR, 0) TOUR,
                       NVL(TRAINING_TBL.TRAINING, 0) TRAINING,
                       NVL(AVERAGE_OFFICE_HRS_TBL.AVG_HOURS, 0) AVG_HOURS,
                       NVL(AVERAGE_OFFICE_HRS_TBL.AVG_MINUTES, 0) AVG_MINUTES
                FROM
                  ( SELECT EMP.EMPLOYEE_ID,
                           ( CASE
                                 WHEN MIDDLE_NAME IS NULL THEN EMP.FIRST_NAME || ' ' || EMP.LAST_NAME
                                 ELSE EMP.FIRST_NAME || ' ' || EMP.MIDDLE_NAME || ' ' || EMP.LAST_NAME
                             END ) FULL_NAME,
                           EMP.EMAIL_OFFICIAL,
                           EMP.EMAIL_PERSONAL,
                           TO_CHAR(EMP.JOIN_DATE, 'DD-MON-YYYY') JOIN_DATE,
                           TRUNC(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE) / 12) AS SERVICE_YEARS,
                           TRUNC(MOD(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE), 12)) AS SERVICE_MONTHS,
                           TRUNC(SYSDATE) - ADD_MONTHS(EMP.JOIN_DATE, TRUNC(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE))) AS SERVICE_DAYS,
                           DSG.DESIGNATION_TITLE,
                           EFL.FILE_PATH
                   FROM HRIS_EMPLOYEES EMP,
                        HRIS_DESIGNATIONS DSG,
                        HRIS_EMPLOYEE_FILE EFL
                   WHERE EMP.DEPARTMENT_ID = DSG.DESIGNATION_ID
                     AND EMP.PROFILE_PICTURE_ID = EFL.FILE_CODE(+)
                     AND EMP.RETIRED_FLAG = 'N'
                     -- AND EMP.COMPANY_ID = 2
                     AND EMP.EMPLOYEE_ID = {$employeeId} ) EMPLOYEE_TBL 
                -- LATE IN
                LEFT JOIN
                  ( SELECT ATTEN.EMPLOYEE_ID, COUNT (*) LATE_IN
                   FROM
                     ( SELECT A.EMPLOYEE_ID,
                              S.START_TIME,
                              A.IN_TIME,
                              (((TRUNC (S.START_TIME) - S.START_TIME)) - (TRUNC (A.IN_TIME) - A.IN_TIME) ) LATE_HRS,
                              S.LATE_IN - TRUNC (S.LATE_IN) LATE_GRACE
                      FROM HRIS_ATTENDANCE_DETAIL A,
                           HRIS_SHIFTS S
                      WHERE 1 = 1
                        AND A.EMPLOYEE_ID = {$employeeId}
                        AND A.SHIFT_ID = S.SHIFT_ID
                        AND A.IN_TIME BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY') ) ATTEN
                   WHERE ATTEN.LATE_HRS > LATE_GRACE
                   GROUP BY ATTEN.EMPLOYEE_ID ) LATE_TBL ON LATE_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID 
                -- EARLY OUT
                LEFT JOIN
                  ( SELECT EMPLOYEE_ID, COUNT (*) EARLY_OUT
                   FROM
                     ( SELECT A.EMPLOYEE_ID,
                              S.END_TIME,
                              A.OUT_TIME,
                              ((TRUNC (A.OUT_TIME) - A.OUT_TIME) - ((TRUNC (S.END_TIME) - S.END_TIME)) ) EARLY_HRS,
                              S.EARLY_OUT - TRUNC (S.EARLY_OUT) EARLY_GRACE
                      FROM HRIS_ATTENDANCE_DETAIL A,
                           HRIS_SHIFTS S
                      WHERE 1 = 1
                        AND A.EMPLOYEE_ID = {$employeeId}
                        AND A.SHIFT_ID = S.SHIFT_ID
                        AND A.IN_TIME BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY') ) ATTEN
                   WHERE ATTEN.EARLY_HRS > EARLY_GRACE
                   GROUP BY EMPLOYEE_ID ) EARLY_TBL ON EARLY_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID 
                -- MISSED PUNCH
                LEFT JOIN
                  ( SELECT EMPLOYEE_ID, COUNT(*) MISSED_PUNCH
                   FROM
                     ( SELECT EMPLOYEE_ID, ATTENDANCE_DT, COUNT (*)
                      FROM HRIS_ATTENDANCE
                      WHERE EMPLOYEE_ID = {$employeeId}
                        AND ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY')
                      GROUP BY EMPLOYEE_ID,
                               ATTENDANCE_DT
                      HAVING MOD(COUNT(*), 2) <> 0)
                   GROUP BY EMPLOYEE_ID ) MISSED_PUNCH_TBL ON MISSED_PUNCH_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID 
                -- PRESENT DAY
                LEFT JOIN
                  ( SELECT EMPLOYEE_ID, COUNT (*) PRESENT_DAY
                   FROM HRIS_ATTENDANCE_DETAIL
                   WHERE IN_TIME IS NOT NULL
                     OR LEAVE_ID IS NOT NULL
                     OR HOLIDAY_ID IS NOT NULL
                     OR TRAINING_ID IS NOT NULL
                     OR TRAVEL_ID IS NOT NULL
                     AND DAYOFF_FLAG = 'N'
                     AND EMPLOYEE_ID = {$employeeId}
                     AND ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY')
                   GROUP BY EMPLOYEE_ID ) PRESENT_TBL ON PRESENT_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID 
                -- ABSENT DAY
                LEFT JOIN
                  ( SELECT EMPLOYEE_ID, COUNT (*) ABSENT_DAY
                   FROM HRIS_ATTENDANCE_DETAIL
                   WHERE IN_TIME IS NULL
                     AND LEAVE_ID IS NULL
                     AND HOLIDAY_ID IS NULL
                     AND TRAINING_ID IS NULL
                     AND TRAVEL_ID IS NULL
                     AND DAYOFF_FLAG = 'N'
                     AND EMPLOYEE_ID = {$employeeId}
                     AND ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY')
                   GROUP BY EMPLOYEE_ID ) ABSENT_TBL ON ABSENT_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID 
                -- LEAVE COUNT
                LEFT JOIN
                  ( SELECT EMPLOYEE_ID, COUNT (*) LEAVE
                   FROM HRIS_ATTENDANCE_DETAIL
                   WHERE LEAVE_ID IS NOT NULL
                     AND DAYOFF_FLAG = 'N'
                     AND EMPLOYEE_ID = {$employeeId}
                     AND ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY')
                   GROUP BY EMPLOYEE_ID ) LEAVE_TBL ON LEAVE_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID 
                -- WOH
                LEFT JOIN
                  ( SELECT EMPLOYEE_ID, COUNT (*) WOH
                   FROM HRIS_ATTENDANCE_DETAIL
                   WHERE HOLIDAY_ID IS NOT NULL
                     AND IN_TIME IS NOT NULL
                     AND OUT_TIME IS NOT NULL
                     AND DAYOFF_FLAG = 'N'
                     AND EMPLOYEE_ID = {$employeeId}
                     AND ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY')
                   GROUP BY EMPLOYEE_ID ) WOH_TBL ON WOH_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID 
                -- ON TOUR
                LEFT JOIN
                  ( SELECT EMPLOYEE_ID, COUNT (*) TOUR
                   FROM HRIS_ATTENDANCE_DETAIL
                   WHERE TRAVEL_ID IS NOT NULL
                     AND DAYOFF_FLAG = 'N'
                     AND EMPLOYEE_ID = {$employeeId}
                     AND ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY')
                   GROUP BY EMPLOYEE_ID ) TOUR_TBL ON TOUR_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID
                LEFT JOIN
                  ( SELECT EMPLOYEE_ID, COUNT (*) TRAINING
                   FROM HRIS_ATTENDANCE_DETAIL
                   WHERE HOLIDAY_ID IS NULL
                     AND TRAINING_ID IS NOT NULL
                     AND EMPLOYEE_ID = {$employeeId}
                     AND ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY')
                   GROUP BY EMPLOYEE_ID ) TRAINING_TBL ON TRAINING_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID
                -- AVERAGE OFFICE HOURS
                LEFT JOIN
                  (SELECT EMPLOYEE_ID,
                          FLOOR(AVERAGE_TOTAL_OFFICE_HRS/3600) AVG_HOURS,
                          (MOD(AVERAGE_TOTAL_OFFICE_HRS,3600)/60) AVG_MINUTES,
                          AVERAGE_TOTAL_OFFICE_HRS
                   FROM
                     (SELECT EMPLOYEE_ID,
                             AVG(SYSDATE + (OUT_TIME - IN_TIME)*24*60*60 - SYSDATE) AVERAGE_TOTAL_OFFICE_HRS
                      FROM HRIS_ATTENDANCE_DETAIL
                      WHERE EMPLOYEE_ID = {$employeeId}
                        AND ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY')
                      GROUP BY EMPLOYEE_ID)) AVERAGE_OFFICE_HRS_TBL ON AVERAGE_OFFICE_HRS_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute()->current();

        return $result;
    }


}