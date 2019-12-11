<?php

namespace Overtime\Repository;

use Application\Helper\EntityHelper;
use Overtime\Model\CompulsoryOvertime;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class OvertimeBulkSetupRepository {

    private $adapter;
    private $tableGateway;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function getEmployeeList($data) {

        $condition = EntityHelper::getSearchConditon($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId']);

        $sql = "SELECT 
                  E.FULL_NAME,
                  E.OVERTIME_ELIGIBLE,
                  case when E.OVERTIME_ELIGIBLE is  null then 'N'
                  else 'Y' END AS ASSIGNED,
                  C.COMPANY_NAME,
                  B.BRANCH_NAME,
                  DEP.DEPARTMENT_NAME,
                  E.EMPLOYEE_ID,
                  E.EMPLOYEE_CODE,
                  P.POSITION_NAME,
                  DES.DESIGNATION_TITLE
                FROM HRIS_EMPLOYEES E
                LEFT JOIN HRIS_COMPANY C
                ON (E.COMPANY_ID=C.COMPANY_ID)
                LEFT JOIN HRIS_BRANCHES B
                ON (E.BRANCH_ID=B.BRANCH_ID)
                LEFT JOIN HRIS_DEPARTMENTS DEP
                ON (E.DEPARTMENT_ID=DEP.DEPARTMENT_ID)
                LEFT JOIN HRIS_POSITIONS P 
                ON (P.POSITION_ID=E.POSITION_ID)
                LEFT JOIN HRIS_DESIGNATIONS DES
                ON (DES.DESIGNATION_ID=E.DESIGNATION_ID)
                WHERE 1            =1 
                 AND E.STATUS ='E'
                {$condition}
                ORDER BY E.FULL_NAME";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function updateOvertime($employeeId) {

        $sql = "UPDATE HRIS_EMPLOYEES set OVERTIME_ELIGIBLE = 'Y' where EMPLOYEE_ID = {$employeeId} ";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return ;
    }

    public function makeNull($employeeId) {
        $sql = "UPDATE HRIS_EMPLOYEES set OVERTIME_ELIGIBLE = NULL where EMPLOYEE_ID = {$employeeId}";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return ;
    }

}
