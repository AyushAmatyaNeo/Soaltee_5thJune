<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Zend\Db\Adapter\AdapterInterface;

class ExcelUploadRepository{

    private $adapter;

    public function __construct(AdapterInterface $adapter) {
      $this->adapter = $adapter;
    }

    public function updateEmployeeSalary($id, $salary){
        $sql = "UPDATE HRIS_EMPLOYEES SET SALARY = $salary WHERE EMPLOYEE_ID = $id"; 
        $statement = $this->adapter->query($sql);
        $statement->execute();
    }
}
