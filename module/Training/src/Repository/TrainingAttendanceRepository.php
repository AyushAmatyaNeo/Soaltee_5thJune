<?php

namespace Training\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\Company;
use Setup\Model\HrEmployees;
use Setup\Model\Institute;
use Setup\Model\Training;
use Training\Model\TrainingAssign;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Training\Model\TrainingAttendance;

class TrainingAttendanceRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(TrainingAttendance::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function edit(Model $model, $id) {
        
    }

    public function fetchAll() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(
                        Training::class, [
                    Training::TRAINING_NAME
                        ], [
                    Training::START_DATE,
                    Training::END_DATE
                        ], NULL, NULL, NULL, 'T')
                , false);


        $select->from(['T' => Training::TABLE_NAME]);
        $select->join(['I' => Institute::TABLE_NAME], "T." . Training::INSTITUTE_ID . "=I." . Institute::INSTITUTE_ID, [Institute::INSTITUTE_NAME => new Expression('INITCAP(I.' . Institute::INSTITUTE_NAME . ')')], 'left');
        $select->join(['C' => Company::TABLE_NAME], "T." . Training::COMPANY_ID . "=C." . Company::COMPANY_ID, [Company::COMPANY_NAME => new Expression('INITCAP(C.' . Company::COMPANY_NAME . ')')], 'left');
        $select->where(["T.STATUS='E'"]);
        $select->order("T." . Training::TRAINING_NAME . " ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
//        print_r($statement->getSql()); die();
        $result = $statement->execute();
        $arrayList = [];
        foreach ($result as $row) {
            if ($row['TRAINING_TYPE'] == 'CP') {
                $row['TRAINING_TYPE'] = 'Company Personal';
            } else if ($row['TRAINING_TYPE'] == 'CC') {
                $row['TRAINING_TYPE'] = 'Company Contribution';
            } else {
                $row['TRAINING_TYPE'] = '';
            }
            array_push($arrayList, $row);
        }
        return $arrayList;
    }

    public function fetchById($id) {
        
    }

    public function fetchTrainingAssignedEmp($id) {
        
    }

    public function updateTrainingAtd(Model $model) {
        $data = $model->getArrayCopyForDB();
        $trainingId = $data['TRAINING_ID'];
        $employeeId = $data['EMPLOYEE_ID'];
        $trainingDate = $data['TRAINING_DT'];
        if ($data['ATTENDANCE_STATUS'] == 'P') {
            return $this->tableGateway->insert($model->getArrayCopyForDB());
        } else {
            unset($data['TRAINING_ID']);
            unset($data['EMPLOYEE_ID']);
            unset($data['TRAINING_DT']);
            return $this->tableGateway->update($data, [TrainingAttendance::TRAINING_ID => $trainingId,
                        TrainingAttendance::EMPLOYEE_ID => $employeeId, TrainingAttendance::TRAINING_DT => $trainingDate
            ]);
        }
    }

}
