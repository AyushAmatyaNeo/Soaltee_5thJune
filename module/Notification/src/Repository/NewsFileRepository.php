<?php

namespace Notification\Repository;

use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Notification\Model\NewsFile;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class NewsFileRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway('HRIS_NEWS_FILE', $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        unset($array['CREATED_DT']);
        unset($array['CREATED_BY']);
        unset($array['FILE_CODE']);
        $this->tableGateway->update($array, ["FILE_CODE" => $id]);
    }

    public function fetchAll() {
        return $this->tableGateway->select();
    }

    public function fetchById($id) {
        $rowset = $this->tableGateway->select(function (Select $select) use ($id) {
            $select->where([NewsFile::NEWS_FILE_ID => $id]);
            $select->order('CREATED_DT DESC')->limit(1);
        });
        return $rowset->current();
    }

    public function fetchByEmpId($id) {
        $rowsetRaw = $this->tableGateway->select(['EMPLOYEE_ID' => $id]);

        return Helper::extractDbData($rowsetRaw);
    }

    public function delete($id) {
        $this->tableGateway->delete(['NEWS_FILE_ID' => $id]);
    }

    public function fetchAllNewsFiles($id) {
        $sql = "SELECT * FROM HRIS_NEWS_FILE WHERE NEWS_ID=$id ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

}
