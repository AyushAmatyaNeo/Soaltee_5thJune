<?php

namespace Payroll\Service;

use Application\Helper\Helper;
use Payroll\Model\Rules;
use Payroll\Repository\FlatValueRepository;
use Payroll\Repository\MonthlyValueRepository;
use Payroll\Repository\PositionFlatValueRepo;
use Payroll\Repository\PositionMonthlyValueRepo;
use Payroll\Repository\RulesRepository;

class PayrollGenerator {

    private $adapter;
    private $positionFlatValueDetRepo;
    private $positionMonthlyValueRepo;
    private $ruleRepo;
    private $sspvmRepo;
    private $employeeId;
    private $monthId = 0;
    private $sheetNo;
    private $formattedFlatValueList;
    private $formattedMonthlyvalueList;
    private $formattedVariableList;
    private $formattedSystemRuleList;
    private $formattedReferencingRuleList;
    private $ruleDetailList = [];

    const VARIABLES = [
        "BASIC_SALARY",
        "MONTH_DAYS",
        "PRESENT_DAYS",
        "ABSENT_DAYS",
        "PAID_LEAVES",
        "UNPAID_LEAVES",
        "DAY_OFFS",
        "HOLIDAYS",
        "DAYS_FROM_JOIN_DATE",
        "DAYS_FROM_PERMANENT_DATE",
        "IS_MALE",
        "IS_FEMALE",
        "IS_MARRIED",
        "IS_PERMANENT",
        "IS_PROBATION",
        "IS_CONTRACT",
        "IS_TEMPORARY",
        "TOTAL_DAYS_TO_PAY",
        "BRANCH_ALLOWANCE",
        "MONTH",
        "BRANCH_ID",
        "CAFE_MEAL_PREVIOUS",
        "CAFE_MEAL_CURRENT",
        "PAYROLL_EMPLOYEE_TYPE"
    ];
    const SYSTEM_RULE = [
        "TOTAL_ANNUAL_AMOUNT",
        "TOTAL_AMOUNT",
        "SELF_PREV_TOTAL",
        "MULTIPLICATION_FACTOR",
        "LOAN_AMT",
        "LOAN_INT",
        "PREVIOUS_TOTAL",
        "PREVIOUS_MONTH_AMOUNT"
    ];

    public function __construct($adapter) {
        $this->adapter = $adapter;
        $this->positionFlatValueDetRepo = new PositionFlatValueRepo($adapter);
        $this->positionMonthlyValueRepo = new PositionMonthlyValueRepo($adapter);
        $this->ruleRepo = new RulesRepository($adapter);
        $this->sspvmRepo = new \Payroll\Repository\SSPayValueModifiedRepo($adapter);
        $monthlyValueList = $this->getMonthlyValues();
        $flatValuesList = $this->getFlatValues();

        $this->formattedMonthlyvalueList = [];
        foreach ($monthlyValueList as $monthlyValue) {
            $this->formattedMonthlyvalueList[$monthlyValue['MTH_ID']] = "[M:{$this->sanitizeString($monthlyValue['MTH_EDESC'])}]";
        }
        $this->formattedFlatValueList = [];
        foreach ($flatValuesList as $flatValue) {
            $this->formattedFlatValueList[$flatValue['FLAT_ID']] = "[F:{$this->sanitizeString($flatValue['FLAT_EDESC'])}]";
        }

        $this->formattedVariableList = [];
        foreach (self::VARIABLES as $variable) {
            $this->formattedVariableList[$variable] = "[V:{$variable}]";
        }

        $this->formattedSystemRuleList = [];
        foreach (self::SYSTEM_RULE as $systemRule) {
            $this->formattedSystemRuleList[$systemRule] = "[S:{$systemRule}]";
        }
    }

    public function generate($employeeId, $monthId, $sheetNo) {
        $this->employeeId = $employeeId;
        $this->monthId = $monthId;
        $this->sheetNo = $sheetNo;
        $payList = $this->ruleRepo->fetchAllTypeWise($sheetNo);
        $systemRuleProcessor = new SystemRuleProcessor($this->adapter, $employeeId, null, $monthId, null);
        
        $file = Helper::UPLOAD_DIR . "/PAYROLL_LOG.txt";
        file_put_contents($file,"Generate Start for employeeId=".$employeeId." monthId=".$monthId." sheetNo=".$sheetNo);

        $ruleValueMap = [];
        $ruleTaxValueMap = [];
        $counter = 0;
        foreach ($payList as $ruleDetail) {
            $ruleId = $ruleDetail[Rules::PAY_ID];
            $formula = $ruleDetail[Rules::FORMULA];
            
            // to override formula start
            $salaryTypeId=$ruleDetail['SALARY_TYPE_ID'];
            $salaryTypeFlag=$ruleDetail['TYPE_FLAG'];
            $salaryTypeFormula=$ruleDetail['TYPE_FORMULA'];
            if ($salaryTypeId != 1  && ( $salaryTypeFlag!==null OR $salaryTypeFlag == 'Y')) {
                $formula = $salaryTypeFormula;
            }else if($salaryTypeId != 1 && ($salaryTypeFlag!==null OR $salaryTypeFlag != 'Y')){
                $formula = 0;
            }
            // to override formula end
            $q = ['MONTH_ID' => $this->monthId, 'PAY_ID' => $ruleId, 'EMPLOYEE_ID' => $this->employeeId];
            $ruleValue = $this->sspvmRepo->fetch($q);
            if ($ruleValue == null) {
                $refRules = $this->ruleRepo->fetchReferencingRules($ruleId);

                foreach ($this->formattedMonthlyvalueList as $monthlyKey => $monthlyValue) {
                    $formula = $this->convertMonthlyToValue($formula, $monthlyKey, $monthlyValue);
                }

                foreach ($this->formattedFlatValueList as $flatKey => $flatValue) {
                    $formula = $this->convertFlatToValue($formula, $flatKey, $flatValue);
                }

                foreach ($this->formattedVariableList as $key => $variable) {
                    $formula = $this->convertVariableToValue($formula, $key, $variable);
                }

                foreach ($this->formattedSystemRuleList as $key => $systemRule) {
                    $formula = $this->convertSystemRuleToValue($formula, $key, $systemRule, $ruleId);
                }
                //added by prabin to remoeve extra params PARS and PARAe start
                $formula=$this->deleteAllBetweenString("PARS", "PARE", $formula);
                //added by prabin to remoeve extra params PARS and PARAe end
//                    print_r($formula);
                $processedformula = $this->convertReferencingRuleToValue($formula, $refRules);
                
                //print_r($processedformula);
                
                $current = file_get_contents($file);
                file_put_contents($file, $current."\r\nstartRuleId=".$ruleId." ".$processedformula);
                $ruleValue = eval("return {$processedformula} ;");
            }
            $rule = ["ruleValue" => $ruleValue, "rule" => $ruleDetail];
            array_push($this->ruleDetailList, $rule);
            $ruleValueMap[$ruleId] = $ruleValue;
            $ruleTaxValueMap[$ruleId] = $systemRuleProcessor->getTaxValue($rule);
            $counter++;
        }

        return ["ruleValueKV" => $ruleValueMap, "ruleTaxValueKV" => $ruleTaxValueMap];
    }

    private function getMonthlyValues() {
        $monthlyValueRepo = new MonthlyValueRepository($this->adapter);
        $monthlyValueList = $monthlyValueRepo->fetchAll();
        return Helper::extractDbData($monthlyValueList);
    }

    private function getFlatValues() {
        $flatValueRepo = new FlatValueRepository($this->adapter);
        $flatValueList = $flatValueRepo->fetchAll();
        return Helper::extractDbData($flatValueList);
    }

    private function getReferencingRules($payId = null) {
        $referencingruleList = $this->repository->fetchReferencingRules($payId);
        return Helper::extractDbData($referencingruleList);
    }

    private function sanitizeString($input) {
        $processed = str_replace(" ", "_", $input);
        return strtoupper($processed);
    }

    private function convertFlatToValue($rule, $key, $constant) {
        if (strpos($rule, $constant) !== false) {
            $flatVal = $this->positionFlatValueDetRepo->fetchValue(['EMPLOYEE_ID' => $this->employeeId, 'MONTH_ID' => $this->monthId, 'FLAT_ID' => $key]);
            return str_replace($constant, $flatVal, $rule);
        } else {
            return $rule;
        }
    }

    private function convertMonthlyToValue($rule, $key, $constant) {
        if (strpos($rule, $constant) !== false) {
            $monthlyVal = $this->positionMonthlyValueRepo->fetchValue(['EMPLOYEE_ID' => $this->employeeId, 'MONTH_ID' => $this->monthId, 'MTH_ID' => $key]);
            return str_replace($constant, $monthlyVal, $rule);
        } else {
            return $rule;
        }
    }

    private function convertVariableToValue($rule, $key, $variable) {
        if (strpos($rule, $variable) !== false) {
            $variableProcessor = new VariableProcessor($this->adapter, $this->employeeId, $this->monthId, $this->sheetNo);
            $processedVariable = $variableProcessor->processVariable($key);
            return str_replace($variable, is_string($processedVariable) ? "'{$processedVariable}'" : $processedVariable, $rule);
        } else {
            return $rule;
        }
    }

    private function convertSystemRuleToValue($rule, $key, $variable, $ruleId) {
        if (strpos($rule, $variable) !== false) {
            $systemRuleProcessor = new SystemRuleProcessor($this->adapter, $this->employeeId, $this->ruleDetailList, $this->monthId, $ruleId);
            $processedSystemRule = $systemRuleProcessor->processSystemRule($key);
            return str_replace($variable, is_string($processedSystemRule) ? "'{$processedSystemRule}'" : $processedSystemRule, $rule);
        } else {
            return $rule;
        }
    }

    private function convertReferencingRuleToValue($rule, $refRules) {
        foreach ($refRules as $refRule) {
            $payEdesc = "[R:{$this->sanitizeString($refRule['PAY_EDESC'])}]";
            $payId = $refRule['PAY_ID'];
            if (strpos($rule, $payEdesc) !== false) {
                $value = $this->getReferencingRuleValue($payId);
                $rule = str_replace($payEdesc, is_string($value) ? "'{$value}'" : $value, $rule);
            }
        }
        return isset($rule) ? $rule : 0;
    }

    private function getReferencingRuleValue($payId) {
        $payValue = 0;
        foreach ($this->ruleDetailList as $ruleDetail) {
            if ($ruleDetail['rule']['PAY_ID'] == $payId) {
                $payValue = $ruleDetail['ruleValue'];
            }
        }
        return $payValue;
    }

    // added by prabin start
    private function deleteAllBetweenString($beginning, $end, $string) {
        $beginningPos = strpos($string, $beginning);
        $endPos = strpos($string, $end);
        if ($beginningPos === false || $endPos === false) {
            return $string;
        }
        $textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);
        return $this->deleteAllBetweenString($beginning, $end, str_replace($textToDelete, '', $string)); // recursion to ensure all occurrences are replaced
    }
    // added by prabin end
    
}
