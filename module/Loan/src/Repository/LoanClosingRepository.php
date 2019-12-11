<?php

namespace Loan\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Loan\Model\LoanClosing AS LoanClosingModel;
use Setup\Model\HrEmployees;
use Setup\Model\Loan;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class LoanClosingRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(LoanClosingModel::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }
 
    public function delete($id) {
        $this->tableGateway->update([LoanClosingModel::STATUS => 'C'], [LoanClosingModel::LOAN_REQ_ID => $id]);
    }

    public function edit(Model $model, $id) {
        $this->tableGateway->update($model->getArrayCopyForDB(), [LoanClosingModel::LOAN_REQ_ID => $id]);
    }

    public function fetchAll() {
        
    }
    public function fetchById($id) {

    }
    public function getEmployeeByLoanRequestId($id){
        $sql = "SELECT EMPLOYEE_ID FROM HRIS_EMPLOYEE_LOAN_REQUEST WHERE LOAN_REQUEST_ID = $id";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function updateLoanStatus($loanReqId){
        $sql = "UPDATE HRIS_EMPLOYEE_LOAN_REQUEST SET LOAN_STATUS = 'CLOSED' WHERE LOAN_REQUEST_ID  = $loanReqId";
        $statement = $this->adapter->query($sql);
        $statement->execute();
    }

    public function getRemainingAmount($old_loan_req_id, $paymentAmount){
        $sql = "SELECT 
        ROUND(SUM(AMOUNT)-$paymentAmount) AS REMAINING_AMOUNT 
        FROM HRIS_LOAN_PAYMENT_DETAIL 
        WHERE PAID_FLAG = 'N' AND LOAN_REQUEST_ID = $old_loan_req_id";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function getUnpaidAmount($old_loan_req_id){
        $sql = "SELECT 
        ROUND(SUM(AMOUNT)) AS UNPAID_AMOUNT 
        FROM HRIS_LOAN_PAYMENT_DETAIL 
        WHERE PAID_FLAG = 'N' AND LOAN_REQUEST_ID = $old_loan_req_id";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function getRateByLoanReqId($loanReqId){
        $sql = "SELECT INTEREST_RATE FROM HRIS_EMPLOYEE_LOAN_REQUEST WHERE LOAN_REQUEST_ID = $loanReqId";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function getOldLoanId($id){
        $sql = "SELECT LOAN_ID FROM HRIS_EMPLOYEE_LOAN_REQUEST WHERE LOAN_REQUEST_ID = $id";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }
}
