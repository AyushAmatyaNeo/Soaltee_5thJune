<?php
namespace Setup\Form;


use Application\Model\Model;
use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectProperty")
 * @Annotation\Name("HrEmployeesFormTabEight")
 */

class HrEmployeesFormTabEight extends Model{
    
    
    public $employeeId;
    
    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Training Name"})
     * @Annotation\Attributes({ "id":"trainingName", "class":"form-control" })
     */
    
    public $trainingName;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Description"})
     * @Annotation\Attributes({ "id":"description", "class":"form-control" })
     */
    public $description;
    
    /**
     * @Annotation\Required(true)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"From Date"})
     * @Annotation\Attributes({"class":"form-control","id":"trafromDate" })
     */
    public $fromDate;
    
     /**
     * @Annotation\Required(true)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"To Date"})
     * @Annotation\Attributes({"class":"form-control","id":"tratoDate" })
     */
    public $toDate;
    
    public $id;
    public $remarks;
    public $companyId;
    public $branchId;
    public $createdBy;
    public $createdDate;
    public $modifiedBy;
    public $modifiedDate;
    public $approved;
    public $approvedBy;
    public $approvedDate;
    public $status;
    
    public $mappings=[
        'id'=>'ID',
        'employeeId'=>'EMPLOYEE_ID',
        'trainingName'=>'TRAINING_NAME',
        'description'=>'DESCRIPTION',
        'fromDate'=>'FROM_DATE',
        'toDate'=>'TO_DATE',
        'remarks'=>'REMARKS',
        'companyId'=>'COMPANY_ID',
        'branchId'=>'BRANCH_ID',
        'createdBy'=>'CREATED_BY',
        'createdDate'=>'CREATED_DATE',
        'modifiedBy'=>'MODIFIED_BY',
        'modifiedDate'=>'MODIFIED_DATE',
        'approved'=>'APPROVED',
        'approvedBy'=>'APPROVED_BY',
        'approvedDate'=>'APPROVED_DATE',
        'status'=>'STATUS'
    ];
    
    
}

