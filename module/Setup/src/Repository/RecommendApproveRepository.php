<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\RecommendApprove;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class RecommendApproveRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(RecommendApprove::TABLE_NAME, $adapter);
        $this->employeeTableGateway = new TableGateway("HRIS_EMPLOYEES", $adapter);
        $this->adapter = $adapter;
    }

    public function getDesignationList($employeeId) {
        $sql = "SELECT  DESIGNATION_ID, (DESIGNATION_TITLE) AS DESIGNATION_TITLE, PARENT_DESIGNATION, WITHIN_BRANCH, WITHIN_DEPARTMENT, LEVEL 
                FROM HRIS_DESIGNATIONS WHERE (LEVEL=2 OR LEVEL=3)
                START WITH DESIGNATION_ID = (SELECT E.DESIGNATION_ID FROM HRIS_EMPLOYEES E WHERE E.EMPLOYEE_ID=" . $employeeId . ")
                CONNECT BY PRIOR  PARENT_DESIGNATION=DESIGNATION_ID";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    //to get recommender and approver based on designation and branch id
    public function getEmployeeList($withinBranch, $withinDepartment, $designationId, $branchId, $departmentId) {
        $sql = "SELECT EMPLOYEE_ID,INITCAP(FIRST_NAME) AS FIRST_NAME,INITCAP(MIDDLE_NAME) AS MIDDLE_NAME,INITCAP(LAST_NAME) AS LAST_NAME FROM HRIS_EMPLOYEES WHERE STATUS='E' AND RETIRED_FLAG='N' AND DESIGNATION_ID=" . $designationId;

        if ($withinBranch != null && $withinBranch != "N") {
            $sql .= " AND BRANCH_ID=" . $branchId;
        }

        if ($withinDepartment != null && $withinDepartment != "N") {
            $sql .= " AND DEPARTMENT_ID=" . $departmentId;
        }

        $statement = $this->adapter->query($sql);
        $resultset = $statement->execute();

        return $resultset;
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function fetchAll() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("RA.STATUS AS STATUS"),
            new Expression("RA.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("RA.RECOMMEND_BY AS RECOMMEND_BY"),
            new Expression("RA.APPROVED_BY AS APPROVED_BY"),
                ], true);
        $select->from(['RA' => RecommendApprove::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=RA.EMPLOYEE_ID", ['FIRST_NAME' => new Expression("INITCAP(E.FIRST_NAME)"), 'MIDDLE_NAME' => new Expression("INITCAP(E.MIDDLE_NAME)"), 'LAST_NAME' => new Expression("INITCAP(E.LAST_NAME)")], "left")
                ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=RA.RECOMMEND_BY", ['FIRST_NAME_R' => new Expression("INITCAP(E1.FIRST_NAME)"), "MIDDLE_NAME_R" => new Expression("INITCAP(E1.MIDDLE_NAME)"), "LAST_NAME_R" => new Expression("INITCAP(E1.LAST_NAME)"), "RETIRED_R" => "RETIRED_FLAG", "STATUS_R" => "STATUS"], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=RA.APPROVED_BY", ['FIRST_NAME_A' => new Expression("INITCAP(E2.FIRST_NAME)"), "MIDDLE_NAME_A" => new Expression("INITCAP(E2.MIDDLE_NAME)"), "LAST_NAME_A" => new Expression("INITCAP(E2.LAST_NAME)"), "RETIRED_A" => "RETIRED_FLAG", "STATUS_A" => "STATUS"], "left");

        $select->where([
            "RA.STATUS='E'",
            "E.STATUS='E'",
            "E.RETIRED_FLAG='N' AND
              (((E1.STATUS =
                CASE
                  WHEN E1.STATUS IS NOT NULL
                  THEN ('E')
                END
              OR E1.STATUS IS NULL)
              AND
              (E1.RETIRED_FLAG =
                CASE
                  WHEN E1.RETIRED_FLAG IS NOT NULL
                  THEN ('N')
                END
              OR E1.RETIRED_FLAG IS NULL))
            OR
              ((E2.STATUS =
                CASE
                  WHEN E2.STATUS IS NOT NULL
                  THEN ('E')
                END
              OR E2.STATUS IS NULL)
            AND
              (E2.RETIRED_FLAG =
                CASE
                  WHEN E2.RETIRED_FLAG IS NOT NULL
                  THEN ('N')
                END
              OR E2.RETIRED_FLAG IS NULL)))"
        ]);
        $select->order("E.FIRST_NAME ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    //to get the employee list for select option
    public function getEmployees($id = null) {
        $entitiesArray = array();
        if ($id != null) {
            $empresult = $this->employeeTableGateway->select(['EMPLOYEE_ID' => $id])->current();
            $entitiesArray[$empresult['EMPLOYEE_ID']] = $empresult['FIRST_NAME'] . " " . $empresult['MIDDLE_NAME'] . " " . $empresult['LAST_NAME'];
        }
        $sql = "SELECT EMPLOYEE_ID,INITCAP(FIRST_NAME) AS FIRST_NAME,INITCAP(MIDDLE_NAME) AS MIDDLE_NAME,INITCAP(LAST_NAME) AS LAST_NAME FROM 
                HRIS_EMPLOYEES WHERE STATUS='E' AND RETIRED_FLAG='N'
                AND EMPLOYEE_ID NOT IN 
                (SELECT EMPLOYEE_ID FROM HRIS_RECOMMENDER_APPROVER WHERE STATUS='E')";

        $statement = $this->adapter->query($sql);
        $resultset = $statement->execute();

        foreach ($resultset as $result) {
            $entitiesArray[$result['EMPLOYEE_ID']] = $result['FIRST_NAME'] . " " . $result['MIDDLE_NAME'] . " " . $result['LAST_NAME'];
        }
        return $entitiesArray;
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        $this->tableGateway->update($array, [RecommendApprove::EMPLOYEE_ID => $id]);
    }

    public function delete($id) {
        $this->tableGateway->update([RecommendApprove::STATUS => 'D'], [RecommendApprove::EMPLOYEE_ID => $id]);
    }

    public function fetchById($id) {
        $row = $this->tableGateway->select([RecommendApprove::EMPLOYEE_ID => $id]);
        return $row->current();
    }

    public function getDetailByEmployeeID($employeeId, $recommenderId = null, $approverId = null) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("RA.STATUS AS STATUS"),
            new Expression("RA.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("RA.RECOMMEND_BY AS RECOMMEND_BY"),
            new Expression("RA.APPROVED_BY AS APPROVED_BY"),
                ], true);
        $select->from(['RA' => RecommendApprove::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=RA.EMPLOYEE_ID", ['FIRST_NAME' => new Expression("INITCAP(E.FIRST_NAME)"), 'MIDDLE_NAME' => new Expression("INITCAP(E.MIDDLE_NAME)"), 'LAST_NAME' => new Expression("INITCAP(E.LAST_NAME)")], "left")
                ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=RA.RECOMMEND_BY", ['FIRST_NAME_R' => new Expression("INITCAP(E1.FIRST_NAME)"), "MIDDLE_NAME_R" => new Expression("INITCAP(E1.MIDDLE_NAME)"), "LAST_NAME_R" => new Expression("INITCAP(E1.LAST_NAME)"), "RETIRED_R" => "RETIRED_FLAG", "STATUS_R" => "STATUS"], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=RA.APPROVED_BY", ['FIRST_NAME_A' => new Expression("INITCAP(E2.FIRST_NAME)"), "MIDDLE_NAME_A" => new Expression("INITCAP(E2.MIDDLE_NAME)"), "LAST_NAME_A" => new Expression("INITCAP(E2.LAST_NAME)"), "RETIRED_A" => "RETIRED_FLAG", "STATUS_A" => "STATUS"], "left");

        $select->where([
            "RA.STATUS='E'",
            "E.STATUS='E'",
            "E.RETIRED_FLAG='N'",
            "RA.EMPLOYEE_ID=" . $employeeId . " AND
              (((E1.STATUS =
                CASE
                  WHEN E1.STATUS IS NOT NULL
                  THEN ('E')
                END
              OR E1.STATUS IS NULL)
              AND
              (E1.RETIRED_FLAG =
                CASE
                  WHEN E1.RETIRED_FLAG IS NOT NULL
                  THEN ('N')
                END
              OR E1.RETIRED_FLAG IS NULL))
            OR
              ((E2.STATUS =
                CASE
                  WHEN E2.STATUS IS NOT NULL
                  THEN ('E')
                END
              OR E2.STATUS IS NULL)
            AND
              (E2.RETIRED_FLAG =
                CASE
                  WHEN E2.RETIRED_FLAG IS NOT NULL
                  THEN ('N')
                END
              OR E2.RETIRED_FLAG IS NULL)))"
        ]);

        if ($recommenderId != null && $recommenderId != -1) {
            $select->where([
                "RA.RECOMMEND_BY=" . $recommenderId]);
        }

        if ($approverId != null && $approverId != -1) {
            $select->where([
                "RA.APPROVED_BY=" . $approverId]);
        }

        $select->order("E.FIRST_NAME ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getFilteredList($search) {
        $condition = "";
        $condition .= EntityHelper::getSearchConditon($search['companyId'], $search['branchId'], $search['departmentId'], $search['positionId'], $search['designationId'], $search['serviceTypeId'], $search['serviceEventTypeId'], $search['employeeTypeId'], $search['employeeId'], null, null, $search['functionalTypeId']);
        if (isset($search['recommenderId']) && $search['recommenderId'] != null && $search['recommenderId'] != -1) {
            if (gettype($search['recommenderId']) === 'array') {
                $csv = "";
                for ($i = 0; $i < sizeof($search['recommenderId']); $i++) {
                    if ($i == 0) {
                        $csv = "'{$search['recommenderId'][$i]}'";
                    } else {
                        $csv .= ",'{$search['recommenderId'][$i]}'";
                    }
                }
                $condition .= "AND RA.RECOMMEND_BY IN ({$csv})";
            } else {
                $condition .= "AND RA.RECOMMEND_BY IN ('{$search['recommenderId']}')";
            }
        }
        if (isset($search['approverId']) && $search['approverId'] != null && $search['approverId'] != -1) {
            if (gettype($search['approverId']) === 'array') {
                $csv = "";
                for ($i = 0; $i < sizeof($search['approverId']); $i++) {
                    if ($i == 0) {
                        $csv = "'{$search['approverId'][$i]}'";
                    } else {
                        $csv .= ",'{$search['approverId'][$i]}'";
                    }
                }
                $condition .= "AND RA.APPROVED_BY IN ({$csv})";
            } else {
                $condition .= "AND RA.APPROVED_BY IN ('{$search['approverId']}')";
            }
        }
 
        $sql = "  
            SELECT AR.A_R_ID,AR.A_R_NAME,AA.A_A_ID,AA.A_A_NAME,
            EC.COMPANY_NAME, 
                E.EMPLOYEE_ID,
                E.FULL_NAME    AS EMPLOYEE_NAME,
                E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
                RE.EMPLOYEE_ID AS RECOMMENDER_ID,
                RE.FULL_NAME   AS RECOMMENDER_NAME,
                AE.EMPLOYEE_ID AS APPROVER_ID,
                AE.FULL_NAME   AS APPROVER_NAME
              FROM HRIS_RECOMMENDER_APPROVER RA
              LEFT JOIN HRIS_EMPLOYEES E
              ON (E.EMPLOYEE_ID=RA.EMPLOYEE_ID)
              LEFT JOIN HRIS_EMPLOYEES RE
              ON (RE.EMPLOYEE_ID = RA.RECOMMEND_BY)
              LEFT JOIN HRIS_EMPLOYEES AE
              ON (AE.EMPLOYEE_ID=RA.APPROVED_BY)
              LEFT JOIN HRIS_COMPANY EC
              ON (EC.COMPANY_ID=E.COMPANY_ID)
              

LEFT JOIN (
            SELECT 
IARA.EMPLOYEE_ID,
LISTAGG(IARA.R_A_ID, ',') WITHIN GROUP (ORDER BY IARAE.FULL_NAME) AS A_R_ID,
LISTAGG(IARAE.FULL_NAME, ',') WITHIN GROUP (ORDER BY IARAE.FULL_NAME) AS A_R_NAME
FROM HRIS_ALTERNATE_R_A IARA
JOIN HRIS_EMPLOYEES IARAE ON (IARA.R_A_ID=IARAE.EMPLOYEE_ID AND IARA.R_A_FLAG='R')
GROUP BY IARA.EMPLOYEE_ID) AR ON (AR.EMPLOYEE_ID=E.EMPLOYEE_ID)
LEFT JOIN (
            SELECT 
IARA.EMPLOYEE_ID,
LISTAGG(IARA.R_A_ID, ',') WITHIN GROUP (ORDER BY IARAE.FULL_NAME) AS A_A_ID,
LISTAGG(IARAE.FULL_NAME, ',') WITHIN GROUP (ORDER BY IARAE.FULL_NAME) AS A_A_NAME
FROM HRIS_ALTERNATE_R_A IARA
JOIN HRIS_EMPLOYEES IARAE ON (IARA.R_A_ID=IARAE.EMPLOYEE_ID AND IARA.R_A_FLAG='A')
GROUP BY IARA.EMPLOYEE_ID) AA ON (AA.EMPLOYEE_ID=E.EMPLOYEE_ID)

              WHERE 1           =1
              AND RA.STATUS        = 'E' AND E.STATUS='E '
              {$condition}";
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }
    
    public function getAlternateRecmApprover($employee_id,$rA){
        $sql = "  
            SELECT R_A_ID FROM HRIS_ALTERNATE_R_A WHERE R_A_FLAG='{$rA}' and employee_id={$employee_id}";
        return Helper::extractDbData(EntityHelper::rawQueryResult($this->adapter, $sql));
    }
    
    

}
