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
        $sql = "SELECT * FROM HRIS_LOAN_CASH_PAYMENT WHERE ID = $id";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
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

    public function getPaymentId($id){
        $sql = "SELECT ID FROM HRIS_LOAN_CASH_PAYMENT WHERE LOAN_REQ_ID = $id";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function rectify($id, $data){
        $sql = "
        DECLARE
        V_NEW_REQ_ID NUMBER;
        V_REQ_ID NUMBER;
        V_REMAINING_AMOUNT NUMBER;
        BEGIN
        UPDATE HRIS_LOAN_CASH_PAYMENT SET PAYMENT_AMOUNT = {$data['paymentAmount']}, 
        INTEREST = {$data['interest']}, REMARKS = '{$data['remarks']}', 
        RECEIPT_NO = '{$data['receiptNo']}' WHERE ID = {$id};

        SELECT NEW_LOAN_REQ_ID INTO V_NEW_REQ_ID FROM HRIS_LOAN_CASH_PAYMENT WHERE ID = {$id};
        SELECT LOAN_REQ_ID INTO V_REQ_ID FROM HRIS_LOAN_CASH_PAYMENT WHERE ID = {$id};

        SELECT 
        ROUND(SUM(AMOUNT) - {$data['paymentAmount']}) INTO V_REMAINING_AMOUNT 
        FROM HRIS_LOAN_PAYMENT_DETAIL 
        WHERE PAID_FLAG = 'N' AND LOAN_REQUEST_ID = V_REQ_ID;

        UPDATE HRIS_EMPLOYEE_LOAN_REQUEST SET REQUESTED_AMOUNT = V_REMAINING_AMOUNT,
        REPAYMENT_MONTHS = {$data['repaymentMonths']} 
        WHERE LOAN_REQUEST_ID = V_NEW_REQ_ID;

        DELETE FROM HRIS_LOAN_PAYMENT_DETAIL WHERE LOAN_REQUEST_ID = V_NEW_REQ_ID;

        HRIS_LOAN_PAYMENT_DETAILS(V_NEW_REQ_ID);
        END;";
        //echo $sql; die;
        $statement = $this->adapter->query($sql);
        $statement->execute();
    }
}
