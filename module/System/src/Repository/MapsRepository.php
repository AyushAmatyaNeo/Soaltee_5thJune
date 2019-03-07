<?php
namespace System\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use AttendanceManagement\Model\Attendance;
use AttendanceManagement\Model\AttendanceDetail;
use Setup\Model\HrEmployees;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
 
class MapsRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(AttendanceDetail::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function fetchCheckInLocation($employeeId, $attd_date){
      $sql = "select LOCATION,ATTENDANCE_TIME from (select * from HRIS_ATTENDANCE WHERE EMPLOYEE_ID = $employeeId AND ATTENDANCE_DT = '$attd_date' order by ATTENDANCE_TIME asc ) where ROWNUM <= 1";
      $statement = $this->adapter->query($sql);
      return $statement->execute();
    }

    public function fetchCheckOutLocation($employeeId, $attd_date){
      $sql = "select LOCATION,ATTENDANCE_TIME from (select * from HRIS_ATTENDANCE WHERE EMPLOYEE_ID = $employeeId AND ATTENDANCE_DT = '$attd_date' order by ATTENDANCE_TIME desc ) where ROWNUM <= 1";
      $statement = $this->adapter->query($sql);
      return $statement->execute();
    }

    public function add(Model $model){}
    public function edit(Model $model, $combo){}
    public function delete($id){}
    public function fetchById($combo){}
    public function fetchAll(){}
}
