<?php
/**
 * Created by PhpStorm.
 * User: punam
 * Date: 10/6/16
 * Time: 3:06 PM
 */
namespace SelfService\Form;

use Zend\Form\Annotation;

class AttendanceRequestForm
{
    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Attendance Date"})
     * @Annotation\Attributes({ "id":"attendanceDt", "class":"form-control" })
     */
    public $attendanceDt;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"In Time"})
     * @Annotation\Attributes({ "id":"inTime",  "data-format":"h:mm a", "data-template":"hh : mm A", "class":"form-control"  })
     */
    public $inTime;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Out Time"})
     * @Annotation\Attributes({ "id":"outTime",  "data-format":"h:mm a", "data-template":"hh : mm A", "class":"form-control"  })
     */
    public $outTime;

    /**
     * @Annotion\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"In Remarks"})
     * @Annotation\Attributes({ "id":"inRemarks", "class":"form-control" })
     */
    public $inRemarks;

    /**
     * @Annotion\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Out Remarks"})
     * @Annotation\Attributes({ "id":"outRemarks", "class":"form-control" })
     */
    public $outRemarks;

    /**
     * @Annotion\Type("Zend\Form\Element\Number")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Total Hour"})
     * @Annotation\Attributes({ "id":"totalHour", "class":"form-control" })
     */
    public $totalHour;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;
}