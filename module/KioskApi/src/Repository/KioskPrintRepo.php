<?php

namespace KioskApi\Repository;

use phpDocumentor\Reflection\Types\Integer;
use Zend\Db\Adapter\AdapterInterface;
use Application\Helper\Helper;

class KioskPrintRepo {

    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function fetchCount($data, $employeeId) {
        $loanCondition = '';
        if($data['LoanId'] != null){
            $loanCondition = " AND LOAN_ID = {$data['LoanId']}";
        }

        $salaryTypeCondition = '';
        If($data['PrintType'] == 'PS'){
            $salaryTypeCondition = " AND SALARY_TYPE_ID = 1";
        }
//        print_r($data);
//        die();
        $sql = "
            SELECT COUNT(*) AS COUNT
            FROM HRIS_KIOSK_PRINT_STAT
            WHERE EMPLOYEE_ID = {$employeeId}
            AND PRINT_TYPE    = '{$data['PrintType']}'
            AND MONTH_ID      = 19
            {$salaryTypeCondition}
            {$loanCondition}
            ";

     //   print_r($sql);
      //  die();
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDBData($result);
    }

    public function insertData($data, $employeeId){
        $loanKey = '';
        if($data['LoanId'] != null){
            $loanKey = " , LOAN_ID ";
        }
        $loanInsert = '';
        if($data['LoanId'] != null){
            $loanInsert = ", {$data['LoanId']}";
        }
        $salaryTypeKey = '';
        if($data['PrintType'] == 'PS'){
            $salaryTypeKey = " , SALARY_TYPE_ID ";
        }
        $salaryTypeInsert = '';
        if($data['PrintType'] == 'PS'){
            $salaryTypeInsert = ", 1";
        }

        $sql = "INSERT INTO HRIS_KIOSK_PRINT_STAT(PRINT_ID,MONTH_ID,EMPLOYEE_ID,PRINT_TYPE {$loanKey} {$salaryTypeKey}) VALUES ((SELECT NVL(MAX(PRINT_ID),0)+1 FROM HRIS_KIOSK_PRINT_STAT),19,{$employeeId},'{$data['PrintType']}'{$loanInsert}{$salaryTypeInsert})";

        $statement = $this->adapter->query($sql);
        $statement->execute();
    }

}
