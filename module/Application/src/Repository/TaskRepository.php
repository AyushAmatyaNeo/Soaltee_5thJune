<?php

namespace Application\Repository;

use Application\Model\Model;
use Application\Model\TaskModel;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Application\Helper\Helper;

class TaskRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(TaskModel::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        $this->tableGateway->update([TaskModel::DELETED_FLAG => 'Y'], [TaskModel::TASK_ID => $id]);
    }

    public function edit(Model $model, $id) {
        $data = $model->getArrayCopyForDB();
        unset($data[TaskModel::CREATED_BY]);
        unset($data[TaskModel::CREATED_DT]);
        $this->tableGateway->update($data, [TaskModel::TASK_ID => $id]);
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['T' => TaskModel::TABLE_NAME]);
        $select->where(["T." . TaskModel::TASK_ID . "='" . $id . "'"]);
//        $select->columns(Helper::convertColumnDateFormat($this->adapter, new NewsModel(), [
//                    'newsDate',
//                        ], NULL, 'N'), false);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function fetchEmployeeTask($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['T' => TaskModel::TABLE_NAME]);
        $select->where(["T." . TaskModel::EMPLOYEE_ID . "='" . $id . "'"]);
        $select->columns(Helper::convertColumnDateFormat($this->adapter, new TaskModel(), [
                    'endDate',
                        ], NULL, 'T'), false);
        $select->where(["T." . TaskModel::DELETED_FLAG . "='N'"]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result;
    }

}
