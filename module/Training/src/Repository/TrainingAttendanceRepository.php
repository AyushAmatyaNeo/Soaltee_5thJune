<?php

namespace Training\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\Company;
use Setup\Model\Institute;
use Setup\Model\Training;
use Training\Model\TrainingAttendance;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

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
        $customCols = ["BS_DATE(TO_CHAR(T.START_DATE, 'DD-MON-YYYY')) AS START_DATE_BS",
            "BS_DATE(TO_CHAR(T.END_DATE, 'DD-MON-YYYY')) AS END_DATE_BS",
            "TO_CHAR(T.START_DATE, 'DD-MON-YYYY') AS START_DATE_AD",
            "TO_CHAR(T.END_DATE, 'DD-MON-YYYY') AS END_DATE_AD"];
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(
                        Training::class, [
                    Training::TRAINING_NAME
                        ], NULL, NULL, NULL, NULL, 'T', FALSE, FALSE, NULL, $customCols)
                , false);


        $select->from(['T' => Training::TABLE_NAME]);
        $select->join(['I' => Institute::TABLE_NAME], "T." . Training::INSTITUTE_ID . "=I." . Institute::INSTITUTE_ID, [Institute::INSTITUTE_NAME => new Expression('INITCAP(I.' . Institute::INSTITUTE_NAME . ')')], 'left');
        $select->join(['C' => Company::TABLE_NAME], "T." . Training::COMPANY_ID . "=C." . Company::COMPANY_ID, [Company::COMPANY_NAME => new Expression('(C.' . Company::COMPANY_NAME . ')')], 'left');
        $select->where(["T.STATUS='E'"]);
        $select->order("T." . Training::TRAINING_NAME . " ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
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
        $sql = "
                SELECT (
                  CASE
                    WHEN E.MIDDLE_NAME IS NULL
                    THEN E.FIRST_NAME
                      || ' '
                      || E.LAST_NAME
                    ELSE E.FIRST_NAME
                      || ' '
                      || E.MIDDLE_NAME
                      || ' '
                      || E.LAST_NAME
                  END ) FULL_NAME,
                  ET.*
                FROM HRIS_EMPLOYEE_TRAINING_ASSIGN ET
                JOIN HRIS_EMPLOYEES E
                ON (ET.EMPLOYEE_ID   = E.EMPLOYEE_ID)
                WHERE ET.TRAINING_ID ={$id} AND ET.STATUS='E'";
        $result = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($result);
    }

    public function updateTrainingAtd($data, $trainingId) {
        $insertList = "";
        foreach ($data as $date => $emp) {
            foreach ($emp as $employeeId => $status) {
                $insert = "INSERT INTO HRIS_EMP_TRAINING_ATTENDANCE (TRAINING_ID,EMPLOYEE_ID,TRAINING_DT,ATTENDANCE_STATUS) VALUES";
                $dStatus = ($status == 'true') ? 'P' : 'A';
                $insert = $insert . "({$trainingId},{$employeeId},TO_DATE('{$date}','DD-MON-YYYY'),'{$dStatus}');";
                $insertList = $insertList . $insert;
            }
        }

        $sql = "
                BEGIN
                DELETE FROM HRIS_EMP_TRAINING_ATTENDANCE WHERE TRAINING_ID= {$trainingId};
                {$insertList}
            BEGIN
            FOR EMPLOYEE_LIST IN (select * from HRIS_EMPLOYEE_TRAINING_ASSIGN where training_id={$trainingId})
            LOOP
            HRIS_TRAINING_LEAVE_REWARD (EMPLOYEE_LIST.EMPLOYEE_ID,EMPLOYEE_LIST.TRAINING_ID);
            END LOOP;
            END;
                END;
                ";
        $result = EntityHelper::rawQueryResult($this->adapter, $sql);
        return $result;
    }

    public function fetchTrainingDates($id) {
        $sql = "
                SELECT TO_CHAR((TR.START_DATE   + (ROWNUM-1)),'DD-MON-YYYY')  AS DATES,TO_CHAR(SYSDATE,'DD-MON-YYYY') AS CURRENT_DATE,
                (CASE WHEN(TR.START_DATE + (ROWNUM-1)) <= TRUNC(SYSDATE) THEN 1 ELSE 0 END) AS STATUS
                FROM
                  (SELECT T.*,
                    (TRUNC(T.END_DATE)-TRUNC(START_DATE)) AS DIFF
                  FROM HRIS_TRAINING_MASTER_SETUP T
                  WHERE T.TRAINING_ID={$id}
                  ) TR
                  CONNECT BY ROWNUM <=TR.DIFF+1";
        $result = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($result);
    }

    public function fetchAttendance($id) {
        $sql = "SELECT EMPLOYEE_ID,TO_CHAR(TRAINING_DT,'DD-MON-YYYY') AS TRAINING_DT,ATTENDANCE_STATUS FROM HRIS_EMP_TRAINING_ATTENDANCE WHERE TRAINING_ID ={$id}";
        $result = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($result);
    }

}
