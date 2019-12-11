<?php

namespace Payroll\Service;

use Payroll\Repository\PayrollRepository;
use Setup\Repository\EmployeeRepository;

class VariableProcessor {

    private $adapter;
    private $employeeId;
    private $employeeRepo;
    private $monthId;
    private $sheetNo;
    private $payrollRepo;

    public function __construct($adapter, $employeeId, int $monthId, int $sheetNo) {
        $this->adapter = $adapter;
        $this->employeeId = $employeeId;
        $this->monthId = $monthId;
        $this->sheetNo = $sheetNo;
        $this->employeeRepo = new EmployeeRepository($adapter);
        $this->payrollRepo = new PayrollRepository($this->adapter);
    }

    public function processVariable($variable) {
        $processedValue = "";
        switch ($variable) {
            /*
             * BASIC_SALARY
             */
            case PayrollGenerator::VARIABLES[0]:
                $processedValue = $this->payrollRepo->fetchBasicSalary($this->employeeId, $this->sheetNo);
                break;
            /*
             * MONTH_DAYS
             */
            case PayrollGenerator::VARIABLES[1]:
                $processedValue = $this->payrollRepo->getMonthDays($this->employeeId, $this->sheetNo);
                break;
            /*
             * PRESENT_DAYS
             */
            case PayrollGenerator::VARIABLES[2]:
                $processedValue = $this->payrollRepo->getPresentDays($this->employeeId, $this->sheetNo);
                break;
            /*
             * ABSENT_DAYS
             */
            case PayrollGenerator::VARIABLES[3]:
                $processedValue = $this->payrollRepo->getAbsentDays($this->employeeId, $this->sheetNo);
                break;
            /*
             * PAID_LEAVES
             */
            case PayrollGenerator::VARIABLES[4]:
                $processedValue = $this->payrollRepo->getPaidLeaves($this->employeeId, $this->sheetNo);
                break;
            /*
             * UNPAID_LEAVES
             */
            case PayrollGenerator::VARIABLES[5]:
                $processedValue = $this->payrollRepo->getUnpaidLeaves($this->employeeId, $this->sheetNo);
                break;
            /*
             * DAY_OFFS
             */
            case PayrollGenerator::VARIABLES[6]:
                $processedValue = $this->payrollRepo->getDayoffs($this->employeeId, $this->sheetNo);
                break;
            /*
             * HOLIDAYS
             */
            case PayrollGenerator::VARIABLES[7]:
                $processedValue = $this->payrollRepo->getHolidays($this->employeeId, $this->sheetNo);
                break;
            /*
             * DAYS_FROM_JOIN_DATE
             */
            case PayrollGenerator::VARIABLES[8]:
                $processedValue = $this->payrollRepo->getDaysFromJoinDate($this->employeeId, $this->sheetNo);
                break;
            /*
             * DAYS_FROM_PERMANENT_DATE
             */
            case PayrollGenerator::VARIABLES[9]:
                $processedValue = $this->payrollRepo->getDaysFromPermanentDate($this->employeeId, $this->monthId);
                break;
            /*
             * IS_MALE
             */
            case PayrollGenerator::VARIABLES[10]:
                $processedValue = $this->payrollRepo->isMale($this->employeeId, $this->sheetNo);
                break;
            /*
             * IS_FEMALE
             */
            case PayrollGenerator::VARIABLES[11]:
                $processedValue = $this->payrollRepo->isFemale($this->employeeId, $this->sheetNo);
                break;
            /*
             * IS_MARRIED
             */
            case PayrollGenerator::VARIABLES[12]:
                $processedValue = $this->payrollRepo->isMarried($this->employeeId, $this->sheetNo);
                break;
            /*
             * IS_PERMANENT
             */
            case PayrollGenerator::VARIABLES[13]:
                $processedValue = $this->payrollRepo->isPermanent($this->employeeId, $this->sheetNo);
                break;
            /*
             * IS_PROBATION
             */
            case PayrollGenerator::VARIABLES[14]:
                $processedValue = $this->payrollRepo->isProbation($this->employeeId, $this->monthId);
                break;
            /*
             * IS_CONTRACT
             */
            case PayrollGenerator::VARIABLES[15]:
                $processedValue = $this->payrollRepo->isContract($this->employeeId, $this->monthId);
                break;
            /*
             * IS_TEMPORARY
             */
            case PayrollGenerator::VARIABLES[16]:
                $processedValue = $this->payrollRepo->isTemporary($this->employeeId, $this->monthId);
                break;
            /*
             * TOTAL_DAYS_TO_PAY
             */
            case PayrollGenerator::VARIABLES[17]:
                $processedValue = $this->payrollRepo->getWorkedDays($this->employeeId, $this->sheetNo);
                break;
            /*
             * BRANCH_ALLOWANCE
             */
            case PayrollGenerator::VARIABLES[18]:
                $processedValue = $this->payrollRepo->getBranchAllowance($this->employeeId);
                break;
             /*
             * MONTH
             */
            case PayrollGenerator::VARIABLES[19]:
                $processedValue = $this->payrollRepo->getMonthNo($this->monthId);
                break;
            
                 break;
            /*
             * BRANCH_ID
             */
            case PayrollGenerator::VARIABLES[20]:
                $processedValue = $this->payrollRepo->getBranch($this->employeeId);
                break;
            /*
             * Cafe Meal Previous
             */
            case PayrollGenerator::VARIABLES[21]:
                $processedValue = $this->payrollRepo->getCafeMealPrevious($this->employeeId,$this->monthId);
                break;
            /*
             * cafe Meal Current
             */
            case PayrollGenerator::VARIABLES[22]:
                $processedValue = $this->payrollRepo->getCafeMealCurrent($this->employeeId,$this->monthId);
                break;
            /*
             * PAYROLL_EMPLOYEE_TYPE
             */
            case PayrollGenerator::VARIABLES[23]:
                $processedValue = $this->payrollRepo->getPayEmpType($this->employeeId);
                break;
            default:


                break;
        }

        return $processedValue;
    }

}
