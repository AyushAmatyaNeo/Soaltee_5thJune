<?php

namespace SelfService\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use SelfService\Model\LoanRequest;
use Setup\Model\HrEmployees;
use Setup\Model\Loan;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class LoanRequestRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(LoanRequest::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        $this->tableGateway->update([LoanRequest::STATUS => 'C'], [LoanRequest::LOAN_REQUEST_ID => $id]);
    }

    public function edit(Model $model, $id) {
        
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(LR.LOAN_DATE, 'DD-MON-YYYY')) AS LOAN_DATE"),
            new Expression("LR.STATUS AS STATUS"),
            new Expression("INITCAP(TO_CHAR(LR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
            new Expression("INITCAP(TO_CHAR(LR.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("INITCAP(TO_CHAR(LR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("LR.REQUESTED_AMOUNT AS REQUESTED_AMOUNT"),
            new Expression("LR.LOAN_REQUEST_ID AS LOAN_REQUEST_ID"),
            new Expression("LR.REASON AS REASON"),
            new Expression("LR.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("LR.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("LR.APPROVED_BY AS APPROVED_BY"),
            new Expression("LR.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("LR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("LR.LOAN_ID AS LOAN_ID")
                ], true);

        $select->from(['LR' => LoanRequest::TABLE_NAME])
                ->join(['E' => HrEmployees::TABLE_NAME], "E." . HrEmployees::EMPLOYEE_ID . "=LR." . LoanRequest::EMPLOYEE_ID, ["FIRST_NAME" => new Expression("INITCAP(E.FIRST_NAME)"), "MIDDLE_NAME" => new Expression("INITCAP(E.MIDDLE_NAME)"), "LAST_NAME" => new Expression("INITCAP(E.LAST_NAME)")])
                ->join(['L' => Loan::TABLE_NAME], "L." . Loan::LOAN_ID . "=LR." . LoanRequest::LOAN_ID, [Loan::LOAN_CODE, "LOAN_NAME" => new Expression("INITCAP(L.LOAN_NAME)")])
                ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=LR.RECOMMENDED_BY", ['FN1' => new Expression("INITCAP(E1.FIRST_NAME)"), 'MN1' => new Expression("INITCAP(E1.MIDDLE_NAME)"), 'LN1' => new Expression("INITCAP(E1.LAST_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=LR.APPROVED_BY", ['FN2' => new Expression("INITCAP(E2.FIRST_NAME)"), 'MN2' => new Expression("INITCAP(E2.MIDDLE_NAME)"), 'LN2' => new Expression("INITCAP(E2.LAST_NAME)")], "left");

        $select->where([
            "LR.LOAN_REQUEST_ID =" . $id
        ]);
        $select->order("LR.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getAllByEmployeeId($employeeId) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(LR.LOAN_DATE, 'DD-MON-YYYY')) AS LOAN_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(LR.LOAN_DATE, 'DD-MON-YYYY')) AS LOAN_DATE_BS"),
            new Expression("LR.STATUS AS STATUS"),
            new Expression("INITCAP(TO_CHAR(LR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(LR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(LR.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("INITCAP(TO_CHAR(LR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("LR.REQUESTED_AMOUNT AS REQUESTED_AMOUNT"),
            new Expression("LR.LOAN_REQUEST_ID AS LOAN_REQUEST_ID"),
            new Expression("LR.REASON AS REASON"),
            new Expression("LR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("LR.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("LR.LOAN_ID AS LOAN_ID")
                ], true);

        $select->from(['LR' => LoanRequest::TABLE_NAME])
                ->join(['E' => HrEmployees::TABLE_NAME], "E." . HrEmployees::EMPLOYEE_ID . "=LR." . LoanRequest::EMPLOYEE_ID, ["FIRST_NAME" => new Expression("INITCAP(E.FIRST_NAME)"), "MIDDLE_NAME" => new Expression("INITCAP(E.MIDDLE_NAME)"), "LAST_NAME" => new Expression("INITCAP(E.LAST_NAME)")])
                ->join(['L' => Loan::TABLE_NAME], "L." . Loan::LOAN_ID . "=LR." . LoanRequest::LOAN_ID, [Loan::LOAN_CODE, "LOAN_NAME" => new Expression("INITCAP(L.LOAN_NAME)")])
                ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=LR.RECOMMENDED_BY", ['FN1' => new Expression("INITCAP(E1.FIRST_NAME)"), 'MN1' => new Expression("INITCAP(E1.MIDDLE_NAME)"), 'LN1' => new Expression("INITCAP(E1.LAST_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=LR.APPROVED_BY", ['FN2' => new Expression("INITCAP(E2.FIRST_NAME)"), 'MN2' => new Expression("INITCAP(E2.MIDDLE_NAME)"), 'LN2' => new Expression("INITCAP(E2.LAST_NAME)")], "left");

        $select->where([
            "E.EMPLOYEE_ID=" . $employeeId
        ]);
        $select->where([
            "(TRUNC(SYSDATE)- LR.REQUESTED_DATE) < (
                      CASE
                        WHEN LR.STATUS = 'C'
                        THEN 20
                        ELSE 365
                      END)"
        ]);
        $select->order("LR.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }
    
    public function getDefaultInterestRate($loan_id){
        $sql = "SELECT INTEREST_RATE FROM HRIS_LOAN_MASTER_SETUP WHERE LOAN_ID = $loan_id";
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }

    public function getLoanDetails(){
        $sql = "SELECT LOAN_ID, IS_RATE_FLEXIBLE, INTEREST_RATE, MIN_AMOUNT, MAX_AMOUNT FROM HRIS_LOAN_MASTER_SETUP";
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }
}
