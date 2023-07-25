<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\Company;
use Setup\Model\Loan;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class LoanRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter=$adapter;
        $this->tableGateway = new TableGateway(Loan::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        unset($array[Loan::LOAN_ID]);
        unset($array[Loan::CREATED_DATE]);
        $this->tableGateway->update($array, [Loan::LOAN_ID => $id]);
    }

    public function delete($id) {
        $this->tableGateway->update([Loan::STATUS => 'D'], [Loan::LOAN_ID => $id]);
    }

    public function fetchAll() {
        return $this->tableGateway->select();
    }

    public function fetchActiveRecord() {        
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Loan::class, [Loan::LOAN_NAME],NULL,NULL,NULL,NULL,'L',FALSE,FALSE), false);
        $select->from(['L' => Loan::TABLE_NAME]);
        $select->join(['C' => Company::TABLE_NAME], "C.".Company::COMPANY_ID."=L.". Loan::COMPANY_ID, [Company::COMPANY_NAME => new Expression('(C.COMPANY_NAME)')], 'left');
        $select->where(["L.".Loan::STATUS."='E'"]);
        $select->order("L.".Loan::LOAN_NAME . " ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $rowset = $statement->execute();
        $result = [];
        $i = 1;
        foreach ($rowset as $row) {
            array_push($result, [
                'SN' => $i,
                'LOAN_ID' => $row['LOAN_ID'],
                'LOAN_CODE' => $row['LOAN_CODE'],
                'LOAN_NAME' => $row['LOAN_NAME'],
                'MIN_AMOUNT' => $row['MIN_AMOUNT'],
                'MAX_AMOUNT' => $row['MAX_AMOUNT'],
                'INTEREST_RATE' => $row['INTEREST_RATE'],
                'REPAYMENT_AMOUNT' => $row['REPAYMENT_AMOUNT'],
                'REPAYMENT_PERIOD' => $row['REPAYMENT_PERIOD'],
                'REMARKS' => $row['REMARKS'],
                'COMPANY_NAME' => $row['COMPANY_NAME']
            ]);
            $i += 1;
        }
        return $result;
    }

    public function fetchById($id) {
        $row = $this->tableGateway->select(function(Select $select)use($id) {
            $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Loan::class, [Loan::LOAN_NAME]), false);
            $select->where([Loan::LOAN_ID => $id]);
        });
        return $row->current();
    }

    public function getPayCodesList(){
        $sql = "SELECT PAY_ID, PAY_EDESC FROM HRIS_PAY_SETUP";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function getSelectedPayCodes($id){
        $sql = "SELECT PAY_ID_AMT, PAY_ID_INT FROM hris_loan_master_setup WHERE LOAN_ID = $id";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function getRateFlexibleFlag($id){
        $sql = "SELECT IS_RATE_FLEXIBLE FROM hris_loan_master_setup WHERE LOAN_ID = $id";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result)[0]['IS_RATE_FLEXIBLE'];
    }
}
