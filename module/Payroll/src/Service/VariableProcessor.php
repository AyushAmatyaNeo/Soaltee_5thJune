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
                $processedValue = $this->payrollRepo->getAbsentDays($this->employeeId, $this->monthId);
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
            /*
             * EMPLOYEE_SERVICE_ID
             */
            case PayrollGenerator::VARIABLES[24]:
                $processedValue = $this->payrollRepo->getEmployeeServiceId($this->employeeId,$this->sheetNo);
                break;

            /*
             * DEARNESS_ALLOWANCE
             */
            case PayrollGenerator::VARIABLES[25]:
                $processedValue = $this->payrollRepo->getDearnessAllowanceAmt($this->employeeId,$this->sheetNo);
                break;

            /*
             * CLOTH_TRANS_ALLOWANCE
             */
            case PayrollGenerator::VARIABLES[26]:
                $processedValue = $this->payrollRepo->geTClothtransAllowanceAmt($this->employeeId,$this->sheetNo);
                break;

            /*
             * FOOD_ALLOWANCE
             */
            case PayrollGenerator::VARIABLES[27]:
                $processedValue = $this->payrollRepo->getFoodAllowanceAmt($this->employeeId,$this->sheetNo);
                break;

            /*
             * HB_LOAN_AMT
             */
            case PayrollGenerator::VARIABLES[28]:
                $processedValue = $this->payrollRepo->getHbLoanAmt($this->employeeId,$this->monthId);
                break;
            
            /*
             * HB_INTEREST_AMT
             */
            case PayrollGenerator::VARIABLES[29]:
                $processedValue = $this->payrollRepo->getHbIntAmt($this->employeeId,$this->monthId);
                break;
            
            /*
             * WL_LOAN_AMT
             */
            case PayrollGenerator::VARIABLES[30]:
                $processedValue = $this->payrollRepo->getWLLoanAmt($this->employeeId,$this->monthId);
                break;
            
            /*
             * WL_INTEREST_AMT
             */
            case PayrollGenerator::VARIABLES[31]:
                $processedValue = $this->payrollRepo->getWLIntAmt($this->employeeId,$this->monthId);
                break;
            
            /*
             * SHL_LOAN_AMT
             */
            case PayrollGenerator::VARIABLES[32]:
                $processedValue = $this->payrollRepo->getSHLLoanAmt($this->employeeId,$this->monthId);
                break;
            
            /*
             * SHL_INTEREST_AMT
             */
            case PayrollGenerator::VARIABLES[33]:
                $processedValue = $this->payrollRepo->getSHLIntAmt($this->employeeId,$this->monthId);
                break;


            /*
             * EMPLOYEE_TYPE
             */
            case PayrollGenerator::VARIABLES[34]:
                $processedValue = $this->payrollRepo->getEFlag($this->employeeId,$this->monthId);
                break;

            /*
             * SC
             */
            case PayrollGenerator::VARIABLES[35]:
                $processedValue = $this->payrollRepo->getSc($this->employeeId,$this->monthId);
                break;

             /*
             * OT
             */
            case PayrollGenerator::VARIABLES[36]:
                $processedValue = $this->payrollRepo->getOt($this->employeeId,$this->monthId);
                break;

            /*
             * NIGHT_ALLOWANCE
             */
            case PayrollGenerator::VARIABLES[37]:
                $processedValue = $this->payrollRepo->getNa($this->employeeId,$this->monthId);
                break;

            /*
             * LTC
             */
            case PayrollGenerator::VARIABLES[38]:
                $processedValue = $this->payrollRepo->getLtc($this->employeeId,$this->monthId);
                break;

            /*
             * LSA
             */
            case PayrollGenerator::VARIABLES[39]:
                $processedValue = $this->payrollRepo->getLsa($this->employeeId,$this->monthId);
                break;

            /*
             * BSA
             */
            case PayrollGenerator::VARIABLES[40]:
                $processedValue = $this->payrollRepo->getBsa($this->employeeId,$this->monthId);
                break;

            
            /*
             * AA
             */
            case PayrollGenerator::VARIABLES[41]:
                $processedValue = $this->payrollRepo->getAa($this->employeeId,$this->monthId);
                break;
            
            /*
             * MOBILE_ALLOWANCE_ARREAR
             */
            case PayrollGenerator::VARIABLES[42]:
                $processedValue = $this->payrollRepo->getMobieAllowanceArrear($this->employeeId,$this->monthId);
                break;

            /*
             * INCENTIVES
             */
            case PayrollGenerator::VARIABLES[43]:
                $processedValue = $this->payrollRepo->getIncentive($this->employeeId,$this->monthId);
                break;

            /*
             * MC_LOAN_AMT
             */
            case PayrollGenerator::VARIABLES[44]:
                $processedValue = $this->payrollRepo->getMCLoanAmt($this->employeeId,$this->monthId);
                break;
            
            /*
             * MC_INTEREST_AMT
             */
            case PayrollGenerator::VARIABLES[45]:
                $processedValue = $this->payrollRepo->getMCIntAmt($this->employeeId,$this->monthId);
                break;

             /*
             * S_L_AMT
             */
            case PayrollGenerator::VARIABLES[46]:
                $processedValue = $this->payrollRepo->getSLoanAmt($this->employeeId,$this->monthId);
                break;
            
            /*
             * S_INTEREST_AMT
             */
            case PayrollGenerator::VARIABLES[47]:
                $processedValue = $this->payrollRepo->getSIntAmt($this->employeeId,$this->monthId);
                break;

            /*
             * E_LOAN_AMT
             */
            case PayrollGenerator::VARIABLES[48]:
                $processedValue = $this->payrollRepo->getELoanAmt($this->employeeId,$this->monthId);
                break;
            
            /*
             * E_INTEREST_AMT
             */
            case PayrollGenerator::VARIABLES[49]:
                $processedValue = $this->payrollRepo->getEIntAmt($this->employeeId,$this->monthId);
                break;

            /*
             * S_ADVANCE_AMT
             */
            case PayrollGenerator::VARIABLES[50]:
                $processedValue = $this->payrollRepo->getSAdvanceAmt($this->employeeId,$this->monthId);
                break;

			 /*
             * SERVICE_CHARGE_EMPLOYEE
             */
            case PayrollGenerator::VARIABLES[51]:
                $processedValue = $this->payrollRepo->getServiceChargerE($this->employeeId,$this->monthId);
                break;
				
			/*
             * PREV_TAX
             */
            case PayrollGenerator::VARIABLES[52]:
                $processedValue = $this->payrollRepo->getPrevioudMthTax($this->employeeId,$this->monthId);
                break;
				
			/*
             * PREV_MEDICALS
             */
            case PayrollGenerator::VARIABLES[53]:
                $processedValue = $this->payrollRepo->getPrevioudMedical($this->employeeId);
                break;


                 /*
             * EMPLOYEE_BRANCH_ID
             */
            case PayrollGenerator::VARIABLES[54]:
                $processedValue = $this->payrollRepo->getEmployeeBranchId($this->employeeId,$this->sheetNo);
                break;


                      /*
             * UNMARRIED FEMALE
             */
            case PayrollGenerator::VARIABLES[55]:
                $processedValue = $this->payrollRepo->isSingleAndUnmarried($this->employeeId,$this->sheetNo);
                break;

                   /*
             * MONTH NO
             */
            case PayrollGenerator::VARIABLES[56]:
                $processedValue = $this->payrollRepo->getFiscalYearMonthNo($this->monthId);
                break;

                  /*
             * FAMILY PLANNING
             */
            case PayrollGenerator::VARIABLES[57]:
                $processedValue = $this->payrollRepo->getFamilyPlanning($this->employeeId,$this->monthId);
                break;
   /*
             * Mourning Expense
             */
            case PayrollGenerator::VARIABLES[58]:
                $processedValue = $this->payrollRepo->getMourningExpense($this->employeeId,$this->monthId);
                break;

              /*
             * ALLOWANCE_ARREAR
             */
            case PayrollGenerator::VARIABLES[59]:
                $processedValue = $this->payrollRepo->getAllowanceArrear($this->employeeId,$this->monthId);
                break;

                 /*
             * SERVICE_CHARGE_EMPLOYEE
             */
            case PayrollGenerator::VARIABLES[60]:
                $processedValue = $this->payrollRepo->getServiceChargerfortax($this->employeeId,$this->monthId);
                break;


                   /*
             * FNL_SICK_LEAVE
             */
            case PayrollGenerator::VARIABLES[61]:
                $processedValue = $this->payrollRepo->fnlSickLeave($this->employeeId,$this->monthId);
                break;



                   /*
             * FNL_ANNUAL_LEAVE
             */
            case PayrollGenerator::VARIABLES[62]:
                $processedValue = $this->payrollRepo->fnlAnnualLeave($this->employeeId,$this->monthId);
                break;




          
            default:


                break;
        }

        return $processedValue;
    }

}
