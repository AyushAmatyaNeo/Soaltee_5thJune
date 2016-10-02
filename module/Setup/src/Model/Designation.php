<?php

namespace Setup\Model;

use Application\Model\Model;
use Zend\Form\Annotation;
use Zend\View\Model\ModelInterface;

class Designation extends Model
{
    const TABLE_NAME="HR_DESIGNATIONS";

    const DESIGNATION_ID="DESIGNATION_ID";
    const DESIGNATION_CODE="DESIGNATION_CODE";
    const DESIGNATION_TITLE="DESIGNATION_TITLE";
    const BASIC_SALARY="BASIC_SALARY";
    const CREATED_DT="CREATED_DT";
    const MODIFIED_DT="MODIFIED_DT";
    const STATUS="STATUS";
    const PARENT_DESIGNATION="PARENT_DESIGNATION";
    const WITHIN_BRANCH="WITHIN_BRANCH";

    public $designationId;
    public $designationCode;
    public $designationTitle;
    public $basicSalary;
    public $status;
    public $createdDt;
    public $modifiedDt;
    public $parentDesignation;
    public $withinBranch;


    public $mappings =[
        'designationId'=>self::DESIGNATION_ID,
        'designationCode'=>self::DESIGNATION_CODE,
        'designationTitle'=>self::DESIGNATION_TITLE,
        'basicSalary'=>self::BASIC_SALARY,
        'createdDt'=>self::CREATED_DT,
        'modifiedDt'=>self::MODIFIED_DT,
        'parentDesignation'=>self::PARENT_DESIGNATION,
        'withinBranch'=>self::WITHIN_BRANCH,
        'status'=>self::STATUS,
    ];
}