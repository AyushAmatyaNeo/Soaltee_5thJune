<?php

namespace Setup\Model;

use Application\Model\Model;

class JobHistory extends Model {

    const TABLE_NAME = "HRIS_JOB_HISTORY";
    const JOB_HISTORY_ID = "JOB_HISTORY_ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const START_DATE = "START_DATE";
    const END_DATE = "END_DATE";
    const SERVICE_EVENT_TYPE_ID = "SERVICE_EVENT_TYPE_ID";
    const TO_BRANCH_ID = "TO_BRANCH_ID";
    const TO_DEPARTMENT_ID = "TO_DEPARTMENT_ID";
    const TO_DESIGNATION_ID = "TO_DESIGNATION_ID";
    const TO_POSITION_ID = "TO_POSITION_ID";
    const TO_SERVICE_TYPE_ID = "TO_SERVICE_TYPE_ID";
    const TO_COMPANY_ID = "TO_COMPANY_ID";
    const TO_SALARY = "TO_SALARY";
    const STATUS = "STATUS";
    const CREATED_DT = "CREATED_DT";
    const MODIFIED_DT = "MODIFIED_DT";
    const CREATED_BY = "CREATED_BY";
    const MODIFIED_BY = "MODIFIED_BY";
    const RETIRED_FLAG = "RETIRED_FLAG";
    const DISABLED_FLAG = "DISABLED_FLAG";
    const EVENT_DATE = "EVENT_DATE";
    const FILE_ID = "FILE_ID";

    public $jobHistoryId;
    public $employeeId;
    public $startDate;
    public $endDate;
    public $serviceEventTypeId;
    public $toServiceTypeId;
    public $toBranchId;
    public $toDepartmentId;
    public $toDesignationId;
    public $toPositionId;
    public $toCompanyId;
    public $toSalary;
    public $status;
    public $createdDt;
    public $modifiedDt;
    public $createdBy;
    public $modifiedBy;
    public $retiredFlag;
    public $disabledFlag;
    public $eventDate;
    public $fileId;
    public $mappings = [
        'jobHistoryId' => self::JOB_HISTORY_ID,
        'employeeId' => self::EMPLOYEE_ID,
        'startDate' => self::START_DATE,
        'endDate' => self::END_DATE,
        'serviceEventTypeId' => self::SERVICE_EVENT_TYPE_ID,
        'toServiceTypeId' => self::TO_SERVICE_TYPE_ID,
        'toBranchId' => self::TO_BRANCH_ID,
        'toDepartmentId' => self::TO_DEPARTMENT_ID,
        'toDesignationId' => self::TO_DESIGNATION_ID,
        'toPositionId' => self::TO_POSITION_ID,
        'toCompanyId' => self::TO_COMPANY_ID,
        'toSalary' => self::TO_SALARY,
        'status' => self::STATUS,
        'createdDt' => self::CREATED_DT,
        'modifiedDt' => self::MODIFIED_DT,
        'createdBy' => self::CREATED_BY,
        'modifiedBy' => self::MODIFIED_BY,
        'retiredFlag' => self::RETIRED_FLAG,
        'disabledFlag' => self::DISABLED_FLAG,
        'eventDate' => self::EVENT_DATE,
        'fileId' => self::FILE_ID,
    ];

}
