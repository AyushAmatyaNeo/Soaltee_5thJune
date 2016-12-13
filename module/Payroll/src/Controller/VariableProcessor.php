<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 11/8/16
 * Time: 2:59 PM
 */

namespace Payroll\Controller;

use Application\Helper\DateHelper;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Months;
use Application\Repository\MonthRepository;
use AttendanceManagement\Repository\AttendanceDetailRepository;
use Setup\Model\HrEmployees;
use Setup\Model\ServiceType;
use Setup\Repository\EmployeeRepository;

class VariableProcessor {

    const VARIABLES = [
        "BASIC_PER_MONTH",
        "NO_OF_DAYS_IN_CURRENT_MONTH",
        "NO_OF_DAYS_ABSENT",
        "NO_OF_DAYS_WORKED",
        "NO_OF_PAID_LEAVES",
        "NO_OF_HOLIDAYS",
        "TOTAL_DAYS_TO_PAY",
        "GENDER",
        "EMP_TYPE",
        "MARITUAL_STATUS",
        "TOTAL_DAYS_FROM_JOIN_DATE"
    ];

    private $adapter;
    private $employeeId;
    private $employeeRepo;
    private $monthId;

    public function __construct($adapter, $employeeId, int $monthId) {
        $this->adapter = $adapter;
        $this->employeeId = $employeeId;
        $this->monthId = $monthId;
        $this->employeeRepo = new EmployeeRepository($adapter);
    }

    public function processVariable($variable) {
        $processedValue = "";
        switch ($variable) {
            case PayrollGenerator::VARIABLES[0]:
                $processedValue = $this->employeeRepo->fetchById($this->employeeId)[HrEmployees::SALARY];
                $processedValue = ($processedValue == null) ? 0 : $processedValue;
                break;
            case PayrollGenerator::VARIABLES[1]:
                $currentMonth = date('m');
                $monthsRepo = new MonthRepository($this->adapter);
                $firstLastDate = $monthsRepo->fetchByMonthId($this->monthId);
                $attendanceDetail = new AttendanceDetailRepository($this->adapter);
                $firstDayExp = Helper::getExpressionDate($firstLastDate[Months::FROM_DATE]);
                $lastDayExp = Helper::getExpressionDate($firstLastDate[Months::TO_DATE]);

                $days = $attendanceDetail->getNoOfDaysInDayInterval($this->employeeId, $firstDayExp, $lastDayExp);

                $processedValue = $days;

                break;
            case PayrollGenerator::VARIABLES[2]:
                $currentMonth = date('m');
                $firstLastDate = DateHelper::getMonthFirstLastDate($currentMonth);
                $attendanceDetail = new AttendanceDetailRepository($this->adapter);
                $firstDayExp = Helper::getExpressionDate($firstLastDate['firstDay']);
                $lastDayExp = Helper::getExpressionDate($firstLastDate['lastDay']);

                $days = $attendanceDetail->getNoOfDaysAbsent($this->employeeId, $firstDayExp, $lastDayExp);
                $processedValue = $days;
                break;
            case PayrollGenerator::VARIABLES[3]:
                $currentMonth = date('m');
                $firstLastDate = DateHelper::getMonthFirstLastDate($currentMonth);
                $attendanceDetail = new AttendanceDetailRepository($this->adapter);
                $firstDayExp = Helper::getExpressionDate($firstLastDate['firstDay']);
                $lastDayExp = Helper::getExpressionDate($firstLastDate['lastDay']);

                $days = $attendanceDetail->getNoOfDaysPresent($this->employeeId, $firstDayExp, $lastDayExp);
                $processedValue = $days;

                break;
            case PayrollGenerator::VARIABLES[4]:


                break;
            case PayrollGenerator::VARIABLES[5]:


                break;
            case PayrollGenerator::VARIABLES[6]:


                break;
            case PayrollGenerator::VARIABLES[7]:
                break;
            case PayrollGenerator::VARIABLES[8]:


                break;
            case PayrollGenerator::VARIABLES[9]:
                $maritualStatus = EntityHelper::getTableKVList($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::MARITAL_STATUS], [HrEmployees::EMPLOYEE_ID => $this->employeeId], null)[$this->employeeId];
                $processedValue = ($maritualStatus == "M") ? 1 : 0;
//                print "<pre>";
//                print $processedValue;
//                exit;
                break;
            case PayrollGenerator::VARIABLES[10]:


                break;
            case PayrollGenerator::VARIABLES[11]:
                $serviceTypeId = EntityHelper::getTableKVList($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::SERVICE_TYPE_ID], [HrEmployees::EMPLOYEE_ID => $this->employeeId], null)[$this->employeeId];
                if ($serviceTypeId == null) {
                    $processedValue = "";
                } else {
                    $serviceTypeCode = EntityHelper::getTableKVList($this->adapter, ServiceType::TABLE_NAME, ServiceType::SERVICE_TYPE_ID, [ServiceType::SERVICE_TYPE_CODE], [ServiceType::SERVICE_TYPE_ID => $serviceTypeId], null)[$serviceTypeId];
                    $processedValue = $serviceTypeCode;
                }
                break;
            default:


                break;
        }

        return $processedValue;
    }

}
