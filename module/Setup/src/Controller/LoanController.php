<?php

namespace Setup\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\ACLHelper;
use Application\Helper\EntityHelper as EntityHelper2;
use Application\Helper\Helper;
use Exception;
use Setup\Form\LoanForm;
use Setup\Model\Company;
use Setup\Model\Designation;
use Setup\Model\Loan;
use Setup\Model\LoanRestriction;
use Setup\Model\Position;
use Setup\Model\ServiceType;
use Setup\Repository\LoanRepository;
use Setup\Repository\LoanRestrictionRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Element\Select;
use Zend\Mvc\Controller\AbstractActionController;

class LoanController extends AbstractActionController {

    private $form;
    private $adapter;
    private $repository;
    private $employeeId;
    private $loanRestrictionRepo;
    private $storageData;
    private $acl;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        $this->adapter = $adapter;
        $this->repository = new LoanRepository($adapter);
        $this->loanRestrictionRepo = new LoanRestrictionRepository($adapter);
        $this->storageData = $storage->read();
        $this->employeeId = $this->storageData['employee_id'];
        $this->acl = $this->storageData['acl'];
    }

    public function initializeForm() {
        $builder = new AnnotationBuilder();
        $form = new LoanForm();
        $this->form = $builder->createForm($form);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchActiveRecord();
                $loanList = Helper::extractDbData($result);
                return new CustomViewModel(['success' => true, 'data' => $loanList, 'error' => '']);
            } catch (Exception $e) {
                return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, ['acl' => $this->acl]);
    }

    public function addAction() {
        ACLHelper::checkFor(ACLHelper::ADD, $this->acl, $this);
        $this->initializeForm();
        $request = $this->getRequest();

        $designationFormElement = new Select();
        $designationFormElement->setName("designation");
        $designations = EntityHelper2::getTableKVListWithSortOption($this->adapter, Designation::TABLE_NAME, Designation::DESIGNATION_ID, [Designation::DESIGNATION_TITLE], [Designation::STATUS => 'E'], "DESIGNATION_TITLE", "ASC", null, false, true);
        $designationFormElement->setValueOptions($designations);
        $designationFormElement->setAttributes(["id" => "designationId", "class" => "form-control", "multiple" => "multiple"]);
        $designationFormElement->setLabel("Designation");

        $positionFormElement = new Select();
        $positionFormElement->setName("position");
        $positions = EntityHelper2::getTableKVListWithSortOption($this->adapter, Position::TABLE_NAME, Position::POSITION_ID, [Position::POSITION_NAME], [Position::STATUS => 'E'], "POSITION_NAME", "ASC", null, false, true);
        $positionFormElement->setValueOptions($positions);
        $positionFormElement->setAttributes(["id" => "positionId", "class" => "form-control", "multiple" => "multiple"]);
        $positionFormElement->setLabel("Position");
        
        $serviceTypeFormElement = new Select();
        $serviceTypeFormElement->setName("serviceType");
        $serviceTypes = EntityHelper2::getTableKVListWithSortOption($this->adapter, ServiceType::TABLE_NAME, ServiceType::SERVICE_TYPE_ID, [ServiceType::SERVICE_TYPE_NAME], [ServiceType::STATUS => 'E'], "SERVICE_TYPE_NAME", "ASC", null, false, true);
        $serviceTypeFormElement->setValueOptions($serviceTypes);
        $serviceTypeFormElement->setAttributes(["id" => "serviceTypeId", "class" => "form-control", "multiple" => "multiple"]);
        $serviceTypeFormElement->setLabel("Service Type");
        
        $payCodes = $this->repository->getPayCodesList();
     
        if ($request->isPost()) {
            $postRecord = $request->getPost();
            $this->form->setData($postRecord);
            $loanRestrictionList = array();

            $serviceType = $postRecord['serviceType'];
            if ($serviceType != "" || $serviceType != null) {
                $serviceTypeList = implode(",", $serviceType);
                $loanRestrictionList["serviceType"] = $serviceTypeList;
            } else {
                $loanRestrictionList["serviceType"] = "";
            }
            $designation = $postRecord['designation'];
            if ($designation != "" || $designation != null) {
                $designationList = implode(",", $designation);
                $loanRestrictionList["designation"] = $designationList;
            } else {
                $loanRestrictionList["designation"] = "";
            }
            $position = $postRecord['position'];
            if ($position != "" || $position != null) {
                $positionList = implode(",", $position);
                $loanRestrictionList["position"] = $positionList;
            } else {
                $loanRestrictionList["position"] = "";
            }
            $salaryRangeFrom = $postRecord['salaryRangeFrom'];
            $salaryRangeTo = $postRecord['salaryRangeTo'];
            if ($salaryRangeFrom != "" || $salaryRangeTo !== "") {
                $salaryRange = $salaryRangeFrom . "," . $salaryRangeTo;
                $loanRestrictionList["salaryRange"] = $salaryRange;
            } else {
                $loanRestrictionList["salaryRange"] = "";
            }
            $workingPeriodFrom = $postRecord['workingPeriodFrom'];
            $workingPeriodTo = $postRecord['workingPeriodTo'];
            if ($workingPeriodFrom != "" || $workingPeriodTo !== "") {
                $workingPeriod = $workingPeriodFrom . "," . $workingPeriodTo;
                $loanRestrictionList["workingPeriod"] = $workingPeriod;
            } else {
                $loanRestrictionList["workingPeriod"] = "";
            }

            if ($this->form->isValid()) {
                $loanModel = new Loan();
                $loanRestrictionModel = new LoanRestriction();
                $loanModel->exchangeArrayFromForm($this->form->getData());
                $loanModel->loanId = ((int) Helper::getMaxId($this->adapter, Loan::TABLE_NAME, Loan::LOAN_ID)) + 1;
                $loanModel->createdDate = Helper::getcurrentExpressionDate();
                $loanModel->status = 'E';
                $loanModel->isRateFlexible = $postRecord['isRateFlexible'];
                $loanModel->createdBy = $this->employeeId;
                $this->repository->add($loanModel);

                foreach ($loanRestrictionList as $loanRestrictionType => $loanRestrictionValue) {
                    $loanRestrictionModel->loanId = $loanModel->loanId;
                    $loanRestrictionModel->restrictionId = ((int) Helper::getMaxId($this->adapter, LoanRestriction::TABLE_NAME, LoanRestriction::RESTRICTION_ID)) + 1;
                    $loanRestrictionModel->restrictionType = $loanRestrictionType;
                    $loanRestrictionModel->value = $loanRestrictionValue;
                    $loanRestrictionModel->createdDate = Helper::getcurrentExpressionDate();
                    $loanRestrictionModel->status = 'E';
                    $loanRestrictionModel->createdBy = $this->employeeId;
                    $this->loanRestrictionRepo->add($loanRestrictionModel);
                }

                $this->flashmessenger()->addMessage("Loan Successfully added!!!");
                return $this->redirect()->toRoute('loan');
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'designation' => $designationFormElement,
                    'position' => $positionFormElement,
                    'serviceType' => $serviceTypeFormElement,
                    'pay_codes' => $payCodes,
                    'companies' => EntityHelper2::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, "ASC", null, false, true)
        ]);
    }

    public function editAction() {
        ACLHelper::checkFor(ACLHelper::UPDATE, $this->acl, $this);
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('loan');
        }

        $this->initializeForm();
        $request = $this->getRequest();

        $designationFormElement = new Select();
        $designationFormElement->setName("designation");
        $designations = EntityHelper2::getTableKVListWithSortOption($this->adapter, Designation::TABLE_NAME, Designation::DESIGNATION_ID, [Designation::DESIGNATION_TITLE], [Designation::STATUS => 'E'], "DESIGNATION_TITLE", "ASC", null, false, true);
        $designationFormElement->setValueOptions($designations);
        $designationFormElement->setAttributes(["id" => "designationId", "class" => "form-control", "multiple" => "multiple"]);
        $designationFormElement->setLabel("Designation");

        $payCodes = $this->repository->getPayCodesList();
        $selectedPayCodes = $this->repository->getSelectedPayCodes($id);

        $positionFormElement = new Select();
        $positionFormElement->setName("position");
        $positions = EntityHelper2::getTableKVListWithSortOption($this->adapter, Position::TABLE_NAME, Position::POSITION_ID, [Position::POSITION_NAME], [Position::STATUS => 'E'], "POSITION_NAME", "ASC", null, false, true);
        $positionFormElement->setValueOptions($positions);
        $positionFormElement->setAttributes(["id" => "positionId", "class" => "form-control", "multiple" => "multiple"]);
        $positionFormElement->setLabel("Position");

        $serviceTypeFormElement = new Select();
        $serviceTypeFormElement->setName("serviceType");
        $serviceTypes = EntityHelper2::getTableKVListWithSortOption($this->adapter, ServiceType::TABLE_NAME, ServiceType::SERVICE_TYPE_ID, [ServiceType::SERVICE_TYPE_NAME], [ServiceType::STATUS => 'E'], "SERVICE_TYPE_NAME", "ASC", null, false, true);
        $serviceTypeFormElement->setValueOptions($serviceTypes);
        $serviceTypeFormElement->setAttributes(["id" => "serviceTypeId", "class" => "form-control", "multiple" => "multiple"]);
        $serviceTypeFormElement->setLabel("Service Type");

        $loanModel = new Loan();
        $loanRestrictionModel = new LoanRestriction();
        if (!$request->isPost()) {
            $loanModel->exchangeArrayFromDB($this->repository->fetchById($id)->getArrayCopy());
            $this->form->bind($loanModel);

            $loanRestrictionDetail = $this->loanRestrictionRepo->getByLoanId($id);
            $serviceTypeRestriction = explode(",", $loanRestrictionDetail['serviceType']);
            $designationRestriction = explode(",", $loanRestrictionDetail['designation']);
            $positionRestriction = explode(",", $loanRestrictionDetail['position']);
            $salaryRange = explode(",", $loanRestrictionDetail['salaryRange']);
            $workingPeriod = explode(",", $loanRestrictionDetail['workingPeriod']);
        } else {
            $postRecord = $request->getPost();
            $this->form->setData($postRecord);

            $serviceType = $postRecord['serviceType'];
            if ($serviceType != "" || $serviceType != null) {
                $serviceTypeList = implode(",", $serviceType);
                $loanRestrictionList["serviceType"] = $serviceTypeList;
            } else {
                $loanRestrictionList["serviceType"] = "";
            }
            $designation = $postRecord['designation'];
            if ($designation != "" || $designation != null) {
                $designationList = implode(",", $designation);
                $loanRestrictionList["designation"] = $designationList;
            } else {
                $loanRestrictionList["designation"] = "";
            }
            $position = $postRecord['position'];
            if ($position != "" || $position != null) {
                $positionList = implode(",", $position);
                $loanRestrictionList["position"] = $positionList;
            } else {
                $loanRestrictionList["position"] = "";
            }
            $salaryRangeFrom = $postRecord['salaryRangeFrom'];
            $salaryRangeTo = $postRecord['salaryRangeTo'];
            if ($salaryRangeFrom != "" || $salaryRangeTo !== "") {
                $salaryRange = $salaryRangeFrom . "," . $salaryRangeTo;
                $loanRestrictionList["salaryRange"] = $salaryRange;
            } else {
                $loanRestrictionList["salaryRange"] = "";
            }

            $workingPeriodFrom = $postRecord['workingPeriodFrom'];
            $workingPeriodTo = $postRecord['workingPeriodTo'];
            if ($workingPeriodFrom != "" || $workingPeriodTo !== "") {
                $workingPeriod = $workingPeriodFrom . "," . $workingPeriodTo;
                $loanRestrictionList["workingPeriod"] = $workingPeriod;
            } else {
                $loanRestrictionList["workingPeriod"] = "";
            }

            if ($this->form->isValid()) {
                $loanModel->exchangeArrayFromForm($this->form->getData());
                $loanModel->modifiedDate = Helper::getcurrentExpressionDate();
                $loanModel->modifiedBy = $this->employeeId;
                $loanModel->isRateFlexible = $postRecord['isRateFlexible'];
                $this->repository->edit($loanModel, $id);

                foreach ($loanRestrictionList as $loanRestrictionType => $loanRestrictionValue) {
                    $loanRestrictionModel->restrictionType = $loanRestrictionType;
                    $loanRestrictionModel->value = $loanRestrictionValue;
                    $loanRestrictionModel->modifiedDate = Helper::getcurrentExpressionDate();
                    $loanRestrictionModel->modifiedBy = $this->employeeId;
                    $this->loanRestrictionRepo->edit($loanRestrictionModel, $id);
                }

                $this->flashmessenger()->addMessage("Loan Successfully Updated!!!");
                return $this->redirect()->toRoute("loan");
            }
        }
        return Helper::addFlashMessagesToArray(
                        $this, [
                    'form' => $this->form,
                    'id' => $id,
                    'designation' => $designationFormElement,
                    'position' => $positionFormElement,
                    'serviceType' => $serviceTypeFormElement,
                    'salaryRange' => $salaryRange,
                    'workingPeriod' => $workingPeriod,
                    'isRateFexible' => $this->repository->getRateFlexibleFlag($id),
                    'pay_codes' => $payCodes,
                    'selectedPayCodes' => $selectedPayCodes,
                    'serviceTypeRestriction' => $serviceTypeRestriction,
                    'designationRestriction' => $designationRestriction,
                    'positionRestriction' => $positionRestriction,
                    'companies' => EntityHelper2::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, "ASC", null, false, true)
                        ]
        );
    }

    public function deleteAction() {
        if (!ACLHelper::checkFor(ACLHelper::DELETE, $this->acl, $this)) {
            return;
        };
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('loan');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Loan Successfully Deleted!!!");
        return $this->redirect()->toRoute('loan');
    }

}
