<?php
namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Repository\HrisRepository;
use LeaveManagement\Model\LeaveApply;
use Setup\Model\HrEmployees;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql; 

class LeaveReportCardRepository extends HrisRepository {

  public function fetchLeaveReportCard($by){
    $leaveId = $by['data']['leaveId'];
    $leaveId = implode($leaveId, ',');
    $leaveIdFilter = "";
    if($leaveId != '' && $leaveId != null){
      $leaveIdFilter.=" and l.leave_id IN ($leaveId)";
    }
    $employees = $by['data']['employeeId'];
    //$employees = implode(',', $employees);

    $sql = "SELECT LA.ID AS ID, E.EMPLOYEE_CODE AS EMPLOYEE_ID, E.EMPLOYEE_CODE AS 
    EMPLOYEE_CODE,E.JOIN_DATE AS JOIN_DATE, LA.LEAVE_ID AS LEAVE_ID, 
    (CASE WHEN  E.ADDR_PERM_STREET_ADDRESS IS NULL THEN '-' ELSE E.ADDR_PERM_STREET_ADDRESS END) AS ADDR_PERM_STREET_ADDRESS,
    (CASE WHEN  E.ADDR_TEMP_STREET_ADDRESS IS NULL THEN '-' ELSE E.ADDR_TEMP_STREET_ADDRESS END) AS ADDR_TEMP_STREET_ADDRESS,
    D.DESIGNATION_TITLE AS DESIGNATION_TITLE,HD.DEPARTMENT_NAME AS DEPARTMENT,
    INITCAP(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY')) AS FROM_DATE_AD, BS_DATE(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY')) 
    AS FROM_DATE_BS, INITCAP(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY')) AS TO_DATE_AD, BS_DATE(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY')) 
    AS TO_DATE_BS, LA.HALF_DAY AS HALF_DAY, (CASE WHEN (LA.HALF_DAY IS NULL OR LA.HALF_DAY = 'N') THEN 'Full Day' WHEN (LA.HALF_DAY = 'F') 
    THEN 'First Half' ELSE 'Second Half' END) AS HALF_DAY_DETAIL, LA.GRACE_PERIOD AS GRACE_PERIOD, (CASE WHEN LA.GRACE_PERIOD = 'E' 
    THEN 'Early' WHEN LA.GRACE_PERIOD = 'L' THEN 'Late' ELSE '-' END) AS GRACE_PERIOD_DETAIL, LA.NO_OF_DAYS AS NO_OF_DAYS, 
    INITCAP(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT_AD, BS_DATE(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY'))
    AS REQUESTED_DT_BS, (CASE WHEN LA.REMARKS IS null THEN '-' ELSE LA.REMARKS END) AS REMARKS, LA.STATUS AS STATUS, LEAVE_STATUS_DESC(LA.STATUS) AS STATUS_DETAIL, LA.RECOMMENDED_BY 
    AS RECOMMENDED_BY, INITCAP(TO_CHAR(LA.RECOMMENDED_DT, 'DD-MON-YYYY')) AS RECOMMENDED_DT, LA.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS, 
    LA.APPROVED_BY AS APPROVED_BY, INITCAP(TO_CHAR(LA.APPROVED_DT, 'DD-MON-YYYY')) AS APPROVED_DT, LA.APPROVED_REMARKS AS APPROVED_REMARKS, 
    L.LEAVE_CODE AS LEAVE_CODE, INITCAP(L.LEAVE_ENAME) AS LEAVE_ENAME, INITCAP(E.FULL_NAME) AS FULL_NAME, 
    INITCAP(E2.FULL_NAME) AS RECOMMENDED_BY_NAME, INITCAP(E3.FULL_NAME) AS APPROVED_BY_NAME, RA.RECOMMEND_BY AS RECOMMENDER_ID, 
    RA.APPROVED_BY AS APPROVER_ID, INITCAP(RECM.FULL_NAME) AS RECOMMENDER_NAME, INITCAP(APRV.FULL_NAME) AS APPROVER_NAME 
    FROM HRIS_EMPLOYEE_LEAVE_REQUEST LA INNER JOIN HRIS_LEAVE_MASTER_SETUP  L ON L.LEAVE_ID=LA.LEAVE_ID LEFT JOIN 
    HRIS_EMPLOYEES  E ON LA.EMPLOYEE_ID=E.EMPLOYEE_ID LEFT JOIN HRIS_EMPLOYEES  E2 ON 
    E2.EMPLOYEE_ID=LA.RECOMMENDED_BY LEFT JOIN HRIS_EMPLOYEES  E3 ON E3.EMPLOYEE_ID=LA.APPROVED_BY LEFT JOIN 
    HRIS_RECOMMENDER_APPROVER  RA ON RA.EMPLOYEE_ID=LA.EMPLOYEE_ID LEFT JOIN HRIS_EMPLOYEES  RECM ON 
    RECM.EMPLOYEE_ID=RA.RECOMMEND_BY LEFT JOIN HRIS_EMPLOYEES APRV ON APRV.EMPLOYEE_ID=RA.APPROVED_BY 
    left JOIN HRIS_DESIGNATIONS D ON E.DESIGNATION_ID = D.DESIGNATION_ID  
    left JOIN HRIS_DEPARTMENTS HD ON E.DEPARTMENT_ID = HD.DEPARTMENT_ID
    WHERE L.STATUS='E' and la.status='AP'  AND E.EMPLOYEE_ID IN ($employees) {$leaveIdFilter} ORDER BY LA.REQUESTED_DT ASC";  
                            //echo $sql; die;
    return $this->rawQuery($sql);    
  }
  
  public function fetchIfEmpty($by){
    $leaveId = $by['data']['leaveId'];
    $leaveId = implode($leaveId, ',');
    $leaveIdFilter = "";
    if($leaveId != '' && $leaveId != null){
      $leaveIdFilter.=" and l.leave_id IN ($leaveId)";
    }
    $employees = $by['data']['employeeId'];
    //$employees = implode(',', $employees);

    $sql = "SELECT E.EMPLOYEE_CODE AS EMPLOYEE_ID, E.EMPLOYEE_CODE AS 
    EMPLOYEE_CODE,E.JOIN_DATE AS JOIN_DATE, 
    (CASE WHEN  E.ADDR_PERM_STREET_ADDRESS IS NULL THEN '-' ELSE E.ADDR_PERM_STREET_ADDRESS END) AS ADDR_PERM_STREET_ADDRESS,
    (CASE WHEN  E.ADDR_TEMP_STREET_ADDRESS IS NULL THEN '-' ELSE E.ADDR_TEMP_STREET_ADDRESS END) AS ADDR_TEMP_STREET_ADDRESS,
    D.DESIGNATION_TITLE AS DESIGNATION_TITLE,HD.DEPARTMENT_NAME AS DEPARTMENT,
    INITCAP(E.FULL_NAME) AS FULL_NAME from
    HRIS_EMPLOYEES  E   
    left JOIN HRIS_DESIGNATIONS D ON E.DESIGNATION_ID = D.DESIGNATION_ID  
    left JOIN HRIS_DEPARTMENTS HD ON E.DEPARTMENT_ID = HD.DEPARTMENT_ID
    WHERE   E.EMPLOYEE_ID IN ($employees) ";  
                            //echo $sql; die;
    return $this->rawQuery($sql);    
  }

  public function fetchLeaves($empId, $leaveId){
    $leaveId = implode($leaveId, ',');
    $leaveIdFilter = "";
    if($leaveId != '' && $leaveId != null){
      $leaveIdFilter.=" and lms.leave_id IN ($leaveId)";
    }
    $sql = "select 
    Lms.Leave_Ename,Lms.LEAVE_ID,
    la.Total_Days, nvl(la.PREVIOUS_YEAR_BAL, 0) as PREVIOUS_YEAR_BAL,
    la.Total_Days + nvl(la.PREVIOUS_YEAR_BAL, 0) as Balance
    from hris_leave_master_setup lms
    left join Hris_Employee_Leave_Assign la on (lms.leave_id=la.leave_id )
    where 1=1 and lms.status = 'E' and la.employee_id= $empId 
    {$leaveIdFilter} and la.leave_id in (21, 22, 23, 15) 
    order by Lms.LEAVE_ID asc";
    //echo $sql; die;
    return $this->rawQuery($sql);
  }
} 
