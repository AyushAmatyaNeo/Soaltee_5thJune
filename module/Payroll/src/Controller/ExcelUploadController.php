<?php

namespace Payroll\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Payroll\Model\FlatValue as FlatValueModel;
use Application\Model\FiscalYear;
use Payroll\Model\MonthlyValue as MonthlyValueModel;
use Application\Model\Months;
use Zend\Db\Adapter\AdapterInterface;
use Payroll\Repository\FlatValueDetailRepo;
use Payroll\Repository\MonthlyValueDetailRepo;
use Zend\Authentication\Storage\StorageInterface;
use Zend\View\Model\JsonModel;
use Payroll\Repository\ExcelUploadRepository;

class ExcelUploadController extends HrisController {

    public $adapter;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->adapter = $adapter;
        $this->initializeRepository(ExcelUploadRepository::class);
    }

    public function indexAction() {
        $flatValues = EntityHelper::getTableList($this->adapter, FlatValueModel::TABLE_NAME, [FlatValueModel::FLAT_ID, FlatValueModel::FLAT_EDESC], [FlatValueModel::STATUS => EntityHelper::STATUS_ENABLED, FlatValueModel::ASSIGN_TYPE => 'E']);
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        $monthlyValues = EntityHelper::getTableList($this->adapter, MonthlyValueModel::TABLE_NAME, [MonthlyValueModel::MTH_ID, MonthlyValueModel::MTH_EDESC]);
        $months = EntityHelper::getTableList($this->adapter, Months::TABLE_NAME, [Months::MONTH_ID, Months::MONTH_EDESC, Months::FISCAL_YEAR_ID],null,'','FISCAL_YEAR_MONTH_NO');
        return $this->stickFlashMessagesTo([
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'flatValues' => $flatValues,
                    'fiscalYears' => $fiscalYears,
                    'monthlyValues' => $monthlyValues,
                    'months' => $months,
                    'acl' => $this->acl,
        ]);
    }

    public function updateFlatValuesAction(){
        $excelData = $_POST['data'];
        $fiscalYearId = $_POST['fiscalYearId'];
        $flatId = $_POST['flatValueId'];
        $basedOn = $_POST['basedOn'];
        $detailRepo = new FlatValueDetailRepo($this->adapter);
        foreach($flatId as $fid){
            foreach ($excelData as $data) {
                if($basedOn == 2){ $data['ID'] = EntityHelper::getEmployeeIdFromCode($this->adapter, $data['ID']); }
                if($data['ID'] == null || $data['ID'] == ''){ continue; }
                $item['employeeId'] = $data['ID'];
                $item['value'] = $data['AMOUNT'];
                $item['flatId'] = $fid;
                $detailRepo->postBulkFlatValuesDetail($item, $fiscalYearId);
            }
        }
        return new JsonModel(['success' => true, 'error' => '']);
    }

    public function updateMonthlyValuesAction(){
        $excelData = $_POST['data'];
        $monthId = $_POST['monthId'];
        $fiscalYearId = $_POST['fiscalYearId'];
        $monthlyValueId = $_POST['monthlyValueId'];
        $basedOn = $_POST['basedOn'];
        $detailRepo = new MonthlyValueDetailRepo($this->adapter);
        foreach($monthlyValueId as $mid){
            foreach ($excelData as $data) {
                if($basedOn == 2){ $data['ID'] = EntityHelper::getEmployeeIdFromCode($this->adapter, $data['ID']); }
                if($data['ID'] == null || $data['ID'] == ''){ continue; }
                $item['employeeId'] = $data['ID'];
                $item['mthValue'] = $data['AMOUNT'];
                $item['mthId'] = $mid;
                $item['fiscalYearId'] = $fiscalYearId;
                $item['monthId'] = $monthId;
                $detailRepo->postMonthlyValuesDetail($item);
            }
        }
        return new JsonModel(['success' => true, 'error' => '']);
    }

    public function updateEmployeeSalaryAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $excelData = $_POST['data'];
            $basedOn = $_POST['basedOn'];
            foreach ($excelData as $data) {
                if($basedOn == 2){ $data['ID'] = EntityHelper::getEmployeeIdFromCode($this->adapter, $data['ID']); }
                if($data['ID'] == null || $data['ID'] == ''){ continue; }
                $this->repository->updateEmployeeSalary($data['ID'], $data['AMOUNT']);
            }
            return new JsonModel(['success' => true, 'error' => '']);
        }
        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl
        ]);
    }
}