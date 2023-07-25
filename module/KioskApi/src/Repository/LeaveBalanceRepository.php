<?php

namespace KioskApi\Repository;

use Zend\Db\Adapter\AdapterInterface;
use Application\Helper\Helper;

class LeaveBalanceRepository {

    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function fetchLeaveDetails($employeeId) {
        $sql = "
            SELECT LA.EMPLOYEE_ID,
            E.FULL_NAME, 
            LA.LEAVE_ID, 
            LMS.LEAVE_ENAME AS LEAVE_NAME,  
            LA.PREVIOUS_YEAR_BAL + LA.TOTAL_DAYS  AS TOTAL_DAYS, 
            LA.BALANCE AS REMAINING_BALANCE
            FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA 
            LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS 
            ON LA.LEAVE_ID = LMS.LEAVE_ID 
            LEFT JOIN HRIS_EMPLOYEES E 
            ON LA.EMPLOYEE_ID = E.EMPLOYEE_ID 
            WHERE LA.EMPLOYEE_ID = {$employeeId}
			  AND LMS.STATUS='E'
             AND LA.LEAVE_ID IN (36,37,38)
            ORDER BY LA.LEAVE_ID
            ";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

}
