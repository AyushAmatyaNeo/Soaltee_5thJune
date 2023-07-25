<?php

namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use LeaveManagement\Model\LeaveMaster;
use Setup\Model\Company;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class LeaveMasterRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(LeaveMaster::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        unset($array[LeaveMaster::LEAVE_ID]);
        unset($array[LeaveMaster::CREATED_DT]);
        unset($array[LeaveMaster::STATUS]);
        if (!array_key_exists(LeaveMaster::DEFAULT_DAYS, $array)) {
            $array[LeaveMaster::DEFAULT_DAYS] = 0;
        }
        $this->tableGateway->update($array, [LeaveMaster::LEAVE_ID => $id]);
    }

    public function fetchAll() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(LeaveMaster::class, [LeaveMaster::LEAVE_ENAME], NULL, NULL, NULL, NULL, 'L', false), false);
        $select->from(['L' => LeaveMaster::TABLE_NAME]);
        $select->where(["L.STATUS='E'"]);
        $select->order(LeaveMaster::LEAVE_ENAME . " ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id) {
        $rowset = $this->tableGateway->select(function(Select $select)use($id) {
            $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(LeaveMaster::class, [LeaveMaster::LEAVE_ENAME]), false);
            $select->where([LeaveMaster::LEAVE_ID => $id, LeaveMaster::STATUS => 'E']);
        });
        return $result = $rowset->current();
    }

    public function fetchActiveRecord() {
        return $rowset = $this->tableGateway->select(function(Select $select) {
            $select->where([LeaveMaster::STATUS => 'E']);
            $select->order(LeaveMaster::LEAVE_ENAME . " ASC");
        });
    }

    public function delete($id) {
        $this->tableGateway->update([LeaveMaster::STATUS => 'D'], [LeaveMaster::LEAVE_ID => $id]);
    }

    public function checkIfCashable(int $leaveId) {
        $leave = $this->tableGateway->select([LeaveMaster::LEAVE_ID => $leaveId, LeaveMaster::STATUS => 'E'])->current();
        return ($leave[LeaveMaster::CASHABLE] == 'Y') ? true : false;
    }

    public function getSubstituteLeave() {
        $result = $this->tableGateway->select([LeaveMaster::STATUS => 'E', LeaveMaster::IS_SUBSTITUTE => 'Y']);
        return $result->current();
    }

}
