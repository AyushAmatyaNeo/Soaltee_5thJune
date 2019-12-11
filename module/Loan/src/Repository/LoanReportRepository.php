<?php

namespace Loan\Repository;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Application\Repository\RepositoryInterface;
use Setup\Model\HrEmployees;

class LoanReportRepository implements RepositoryInterface {

    private $adapter;

    public function __construct(\Zend\Db\Adapter\AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function add(\Application\Model\Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function edit(\Application\Model\Model $model, $id) {
        
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        
    }

    public function fetchEmployeeLoanDetails($data){
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $loanId = $data['loanId'];
        $loanRequestStatusId = $data['loanRequestStatusId'];
        $employeeTypeId = $data['employeeTypeId'];
        $loanStatus = $data['loanStatus'];

        $sql = "SELECT DISTINCT
        e.employee_code,
        e.full_name,
        initcap(l.loan_name) AS loan_name,
        lr.requested_amount,
        lr.loan_request_id,
        NVL(ROUND((
            SELECT
                SUM(amount)
            FROM
                hris_loan_payment_detail
            WHERE
                    paid_flag = 'Y'
                AND
                    loan_request_id = lr.loan_request_id
        )), 0)AS paid_amount,
        ( 
            SELECT
                ROUND(SUM(amount))
            FROM
                hris_loan_payment_detail
            WHERE
                    paid_flag = 'N'
                AND
                    loan_request_id = lr.loan_request_id
        )  AS balance,
        (select amount from hris_loan_payment_detail where loan_request_id = lr.loan_request_id
        and from_date = trunc(sysdate,'month')) AS current_installment,
        initcap(TO_CHAR(lr.loan_date,'DD-MON-YYYY') ) AS loan_date_ad,
        bs_date(TO_CHAR(lr.loan_date,'DD-MON-YYYY') ) AS loan_date_bs,
        initcap(TO_CHAR(lr.requested_date,'DD-MON-YYYY') ) AS requested_date_ad,
        bs_date(TO_CHAR(lr.requested_date,'DD-MON-YYYY') ) AS requested_date_bs,
        lr.loan_status AS status,
        lr.employee_id AS employee_id
    FROM
        hris_employee_loan_request lr
        LEFT OUTER JOIN hris_loan_master_setup l ON l.loan_id = lr.loan_id
        LEFT OUTER JOIN hris_employees e ON e.employee_id = lr.employee_id
        LEFT OUTER JOIN hris_loan_payment_detail hlpd ON hlpd.loan_request_id = lr.loan_request_id
    WHERE
        1 = 1 ";
    
        if ($fromDate != null) {
            $sql .= " AND LR.LOAN_DATE>=TO_DATE('" . $fromDate . "','DD-MM-YYYY')";
        }

        if ($toDate != null) {
            $sql .= "AND LR.LOAN_DATE<=TO_DATE('" . $toDate . "','DD-MM-YYYY')";
        }

        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $sql .= "AND E.EMPLOYEE_TYPE='" . $employeeTypeId . "' ";
        }

        if ($employeeId != -1) {
            $sql .= "AND E." . HrEmployees::EMPLOYEE_ID . " = $employeeId";
        }

        if ($companyId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::COMPANY_ID . "= $companyId)";
        }
        if ($branchId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::BRANCH_ID . "= $branchId)";
        }
        if ($departmentId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DEPARTMENT_ID . "= $departmentId)";
        }
        if ($designationId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DESIGNATION_ID . "= $designationId)";
        }
        if ($positionId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::POSITION_ID . "= $positionId)";
        }
        if ($serviceTypeId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_TYPE_ID . "= $serviceTypeId)";
        }
        if ($serviceEventTypeId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_EVENT_TYPE_ID . "= $serviceEventTypeId)";
        }
        if ($loanStatus != 'BOTH') {
            $sql .= " AND LR.LOAN_STATUS = '".$loanStatus."'";
        }

        $sql .= " ORDER BY LR.LOAN_REQUEST_ID DESC";
        $statement = $this->adapter->query($sql); 
        return $statement->execute();
    }
  
    public function fetchLoanVoucher($emp_id, $fromDate, $toDate, $loanId){
    $sql = "SELECT DT, particulars, debit_amount, credit_amount, balance FROM(
        SELECT to_date(HLPD.FROM_DATE) AS DT, 'Opening Balance' as PARTICULARS,
    TRUNC(SUM(HLPD.PRINCIPLE_AMOUNT), 2) AS DEBIT_AMOUNT,
    0 AS CREDIT_AMOUNT, 0 AS BALANCE
    FROM 
    HRIS_LOAN_PAYMENT_DETAIL HLPD JOIN 
    HRIS_EMPLOYEE_LOAN_REQUEST HELR ON(HELR.LOAN_REQUEST_ID = HLPD.LOAN_REQUEST_ID)
        WHERE 
        to_char(to_date(hlpd.from_date,'dd-mon-yy'),'mm') = 7
        AND HLPD.FROM_DATE >= trunc(TO_DATE('{$fromDate}'),'month') 
        AND hlpd.paid_flag = 'Y'
            AND  HELR.LOAN_ID = $loanId
        AND HELR.EMPLOYEE_ID = $emp_id
        AND HLPD.LOAN_REQUEST_ID IN(
        SELECT LOAN_REQUEST_ID FROM hris_employee_loan_request
        WHERE EMPLOYEE_ID = $emp_id)
    GROUP BY HLPD.FROM_DATE
    
    UNION ALL
    
    SELECT LAST_DAY(HLPD.FROM_DATE) AS DT, 'Interest Due' as PARTICULARS,
    TRUNC(SUM(HLPD.INTEREST_AMOUNT), 2) AS DEBIT_AMOUNT, 
    0 AS CREDIT_AMOUNT, 0 AS BALANCE
    FROM 
    HRIS_LOAN_PAYMENT_DETAIL HLPD JOIN 
    HRIS_EMPLOYEE_LOAN_REQUEST HELR ON(HELR.LOAN_REQUEST_ID = HLPD.LOAN_REQUEST_ID)
        WHERE hlpd.PAID_FLAG = 'Y' AND  HELR.LOAN_ID = $loanId AND trunc(HLPD.FROM_DATE, 'month') IN(
    select trunc(add_Months('{$fromDate}', level-1),'month') result
    from DUAL
    connect by level <= MONTHS_BETWEEN('{$toDate}', '{$fromDate}')+1
    ) AND HELR.EMPLOYEE_ID = $emp_id
    GROUP BY HLPD.FROM_DATE
    
    UNION ALL
    
    SELECT LAST_DAY(HLPD.FROM_DATE) AS DT, 'Interest Paid' as PARTICULARS,
        0 AS DEBIT_AMOUNT,
        TRUNC(SUM(HLPD.INTEREST_AMOUNT), 2) AS CREDIT_AMOUNT, 0 AS BALANCE
    FROM 
    HRIS_LOAN_PAYMENT_DETAIL HLPD JOIN 
    HRIS_EMPLOYEE_LOAN_REQUEST HELR ON(HELR.LOAN_REQUEST_ID = HLPD.LOAN_REQUEST_ID)
        WHERE hlpd.PAID_FLAG = 'Y' AND  HELR.LOAN_ID = $loanId AND trunc(HLPD.FROM_DATE, 'month') IN(
    select trunc(add_Months('{$fromDate}', level-1),'month') result
    from DUAL
    connect by level <= MONTHS_BETWEEN('{$toDate}', '{$fromDate}')+1
    ) AND HELR.EMPLOYEE_ID = $emp_id
    GROUP BY HLPD.FROM_DATE
    
    UNION ALL
    
    SELECT LAST_DAY(HLPD.FROM_DATE) AS DT, 'Amount Paid' as PARTICULARS,
        0 AS DEBIT_AMOUNT,
        TRUNC(SUM(HLPD.AMOUNT), 2) AS CREDIT_AMOUNT, 0 AS BALANCE
    FROM 
    HRIS_LOAN_PAYMENT_DETAIL HLPD JOIN 
    HRIS_EMPLOYEE_LOAN_REQUEST HELR ON(HELR.LOAN_REQUEST_ID = HLPD.LOAN_REQUEST_ID)
    WHERE hlpd.PAID_FLAG = 'Y' AND  trunc(HELR.LOAN_ID) = $loanId AND trunc(HLPD.FROM_DATE, 'month') IN(
    select trunc(add_Months('{$fromDate}', level-1),'month') result
    from DUAL
    connect by level <= MONTHS_BETWEEN('{$toDate}', '{$fromDate}')+1
    ) AND HELR.EMPLOYEE_ID = $emp_id
    GROUP BY HLPD.FROM_DATE
    ORDER BY DT, DEBIT_AMOUNT DESC, CREDIT_AMOUNT DESC)
            
    UNION ALL

    (SELECT
    LOAN_DATE AS dt,
    'Loan Taken' AS particulars,
    REQUESTED_AMOUNT AS debit_amount,
    0 AS credit_amount,
    0 AS balance
FROM
    hris_employee_loan_request
WHERE
        loan_id = $loanId
    AND
        LOAN_DATE BETWEEN '{$fromDate}' AND '{$toDate}'    
    AND
        employee_id = $emp_id)

        UNION ALL

    (SELECT
    PAYMENT_DATE AS dt,
    'Cash Interest Paid' AS particulars,
    INTEREST AS debit_amount,
    0 AS credit_amount,
    0 AS balance
    FROM
    HRIS_LOAN_CASH_PAYMENT
    WHERE
        LOAN_REQ_ID IN (SELECT LOAN_REQUEST_ID FROM hris_employee_loan_request
        WHERE LOAN_ID = $loanId and EMPLOYEE_ID = $emp_id)
    AND
        PAYMENT_DATE BETWEEN '{$fromDate}' AND '{$toDate}'    
    )

    UNION ALL

    (SELECT
    PAYMENT_DATE AS dt,
    'Cash Interest Paid' AS particulars,
    0 AS debit_amount,
    INTEREST AS credit_amount,
    0 AS balance
    FROM
    HRIS_LOAN_CASH_PAYMENT
    WHERE
        LOAN_REQ_ID IN (SELECT LOAN_REQUEST_ID FROM hris_employee_loan_request
        WHERE LOAN_ID = $loanId and EMPLOYEE_ID = $emp_id)
    AND
        PAYMENT_DATE BETWEEN '{$fromDate}' AND '{$toDate}'    
    )

    UNION ALL

    (SELECT
    PAYMENT_DATE AS dt,
    'Cash Amount Paid' AS particulars,
    0 AS debit_amount,
    PAYMENT_AMOUNT AS credit_amount,
    0 AS balance
    FROM
    HRIS_LOAN_CASH_PAYMENT
    WHERE
        LOAN_REQ_ID IN (SELECT LOAN_REQUEST_ID FROM hris_employee_loan_request
        WHERE LOAN_ID = $loanId and EMPLOYEE_ID = $emp_id)
    AND
        PAYMENT_DATE BETWEEN '{$fromDate}' AND '{$toDate}'    
    )

ORDER BY
    dt,
    debit_amount DESC,
    credit_amount DESC
            ";        
    //echo $sql; die;
    $statement = $this->adapter->query($sql); 
    return $statement->execute();
    }
    
    public function getLoanlist(){
    $sql = "SELECT LOAN_ID, LOAN_NAME FROM HRIS_LOAN_MASTER_SETUP ORDER BY LOAN_ID";
    $statement = $this->adapter->query($sql); 
    return $statement->execute();
    }
      
    public function fetchOpeningBalance($emp_id, $fromDate, $loanId){
        $sql = " SELECT 
    TRUNC(SUM(HLPD.PRINCIPLE_AMOUNT), 2) AS OPENING_BALANCE
    FROM 
    HRIS_LOAN_PAYMENT_DETAIL HLPD JOIN 
    HRIS_EMPLOYEE_LOAN_REQUEST HELR ON(HELR.LOAN_REQUEST_ID = HLPD.LOAN_REQUEST_ID)
        WHERE 
        HLPD.FROM_DATE = trunc(TO_DATE('{$fromDate}'),'month') 
        AND hlpd.paid_flag = 'Y'
            AND  HELR.LOAN_ID = $loanId
        AND HELR.EMPLOYEE_ID = $emp_id
        AND HLPD.LOAN_REQUEST_ID IN(
        SELECT LOAN_REQUEST_ID FROM hris_employee_loan_request
        WHERE EMPLOYEE_ID = $emp_id) 
        group by hlpd.from_date";
        
        $statement = $this->adapter->query($sql); 
        return $statement->execute();
    }

    public function getCashPaymentsList($data) {
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $loanId = $data['loanId'];
        $loanRequestStatusId = $data['loanRequestStatusId'];
        $employeeTypeId = $data['employeeTypeId'];

        $sql = "SELECT
                e.employee_code AS employee_code,
                e.FULL_NAME AS FULL_NAME,
                initcap(l.loan_name) AS LOAN_NAME,
                lr.requested_amount AS TOTAL_AMOUNT,
                HLCP.PAYMENT_AMOUNT AS PAID_AMOUNT,
                (lr.requested_amount-HLCP.PAYMENT_AMOUNT) AS BALANCE,
                INITCAP(TO_CHAR(HLCP.payment_date, 'DD-MON-YYYY'))        AS PAID_DATE_AD,
                BS_DATE(TO_CHAR(HLCP.payment_date, 'DD-MON-YYYY'))        AS PAID_DATE_BS,
                HLCP.REMARKS AS REMARKS
                
                FROM HRIS_EMPLOYEE_LOAN_REQUEST LR
                JOIN HRIS_LOAN_CASH_PAYMENT HLCP 
                ON LR.LOAN_REQUEST_ID = HLCP.LOAN_REQ_ID
                LEFT OUTER JOIN HRIS_LOAN_MASTER_SETUP L
                ON L.LOAN_ID=LR.LOAN_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=LR.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=LR.RECOMMENDED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=LR.APPROVED_BY
                LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                ON LR.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                WHERE L.STATUS   ='E'
                AND E.STATUS     ='E'
                AND (RECM.STATUS =
                  CASE
                    WHEN RECM.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR RECM.STATUS  IS NULL)
                AND (APRV.STATUS =
                  CASE
                    WHEN APRV.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR APRV.STATUS   IS NULL)";

        if ($loanId != -1) {
            $sql .= " AND LR.LOAN_ID ='" . $loanId . "'";
        }

        if ($fromDate != null) {
            $sql .= " AND LR.LOAN_DATE>=TO_DATE('" . $fromDate . "','DD-MM-YYYY')";
        }

        if ($toDate != null) {
            $sql .= "AND LR.LOAN_DATE<=TO_DATE('" . $toDate . "','DD-MM-YYYY')";
        }

        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $sql .= "AND E.EMPLOYEE_TYPE='" . $employeeTypeId . "' ";
        }

        if ($employeeId != -1) {
            $sql .= "AND E." . HrEmployees::EMPLOYEE_ID . " = $employeeId";
        }

        if ($companyId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::COMPANY_ID . "= $companyId)";
        }
        if ($branchId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::BRANCH_ID . "= $branchId)";
        }
        if ($departmentId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DEPARTMENT_ID . "= $departmentId)";
        }
        if ($designationId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DESIGNATION_ID . "= $designationId)";
        }
        if ($positionId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::POSITION_ID . "= $positionId)";
        }
        if ($serviceTypeId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_TYPE_ID . "= $serviceTypeId)";
        }
        if ($serviceEventTypeId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_EVENT_TYPE_ID . "= $serviceEventTypeId)";
        }

        $sql .= " ORDER BY LR.LOAN_REQUEST_ID DESC";
        //echo $sql; die;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }
}
