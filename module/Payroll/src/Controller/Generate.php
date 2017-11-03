<?php

namespace Payroll\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Repository\MonthRepository;
use Exception;
use Payroll\Controller\SalarySheet as SalarySheetController;
use Payroll\Model\Rules;
use Payroll\Model\SalarySheet;
use Payroll\Repository\PayrollRepository;
use Payroll\Repository\SalarySheetRepo;
use Setup\Model\HrEmployees;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class Generate extends AbstractActionController {

    private $adapter;
    private $payrollRepo;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->payrollRepo = new PayrollRepository($this->adapter);
    }

    public function indexAction() {
        $rules = EntityHelper::getTableKVListWithSortOption($this->adapter, Rules::TABLE_NAME, Rules::PAY_ID, [Rules::PAY_EDESC], [Rules::STATUS => 'E'], Rules::PRIORITY_INDEX, Select::ORDER_ASCENDING, null, false, true);
        $fiscalYears = $this->payrollRepo->fetchFiscalYears();

        return Helper::addFlashMessagesToArray($this, [
                    'rules' => $rules,
                    'fiscalYears' => $fiscalYears,
                    'searchValues' => EntityHelper::getSearchData($this->adapter)
        ]);
    }

    public function generateMonthlySheetAction() {
        try {
            $request = $this->getRequest();
            if (!($request->isPost())) {
                throw new Exception("The request should be of type post");
            }
            $data = $request->getPost();

            $monthId = $data['month'];
            $regenerateFlag = ($data['regenerateFlag'] == "true") ? 1 : 0;

            $monthRepo = new MonthRepository($this->adapter);
            $monthDetail = $monthRepo->fetchByMonthId($monthId);

            $salarySheetController = new SalarySheetController($this->adapter);
            $payrollRepo = new PayrollRepository($this->adapter);
            $employeeList = $payrollRepo->fetchEmployeeList();

            if ($regenerateFlag) {
                $salarySheetController->deleteSalarySheetDetail($monthId);
                $salarySheetController->deleteSalarySheet($monthId);
            }
            if (!($salarySheetController->checkIfGenerated($monthId))) {
                $salarySheetDetails = [];
                foreach ($employeeList as $employee) {
                    $generateMonthlySheet = new PayrollGenerator($this->adapter, $monthId);
                    $result = $generateMonthlySheet->generate($employee['EMPLOYEE_ID']);
                    $salarySheetDetails[$employee['EMPLOYEE_ID']] = $result;
                }
                $addSalarySheetRes = $salarySheetController->addSalarySheet($monthId);
                if ($addSalarySheetRes == null) {
                    throw new Exception('Salary Sheet is null');
                }
                $salarySheetController->addSalarySheetDetail($monthId, $salarySheetDetails, $addSalarySheetRes[SalarySheet::SHEET_NO]);
            }
            $results = $salarySheetController->viewSalarySheet($monthId, $employeeList);

            return new CustomViewModel(['success' => true, 'data' => $results, 'error' => '']);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]]);
        }
    }

    public function pullPayRollGeneratedMonthsAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $employeeId = null;
            $joinDate = null;
            if (isset($data['employeeId'])) {
                $employeeId = $data['employeeId'];
            }
            if ($employeeId != null) {
                $result = EntityHelper::getTableKVList($this->adapter, HrEmployees::TABLE_NAME, null, [HrEmployees::JOIN_DATE], [HrEmployees::EMPLOYEE_ID => $employeeId], null, null);
                if (sizeof($result) > 0) {
                    $joinDate = $result[0];
                }
            }
            $salarySheetRepo = new SalarySheetRepo($this->adapter);
            $generatedSalarySheets = Helper::extractDbData($salarySheetRepo->joinWithMonth(null, $joinDate));

            return new JsonModel(['success' => true, 'data' => $generatedSalarySheets, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

}
