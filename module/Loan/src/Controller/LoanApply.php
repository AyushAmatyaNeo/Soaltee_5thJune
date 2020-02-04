<?php
namespace Loan\Controller;

use Application\Controller\HrisController;
use Zend\Db\Adapter\AdapterInterface;
use Application\Helper\Helper;
use Application\Helper\EntityHelper;
use Zend\Form\Annotation\AnnotationBuilder;
use SelfService\Form\LoanRequestForm;
use Loan\Form\LoanClosing AS LoanClosingForm;
use Setup\Model\HrEmployees;
use Zend\Authentication\Storage\StorageInterface;
use Loan\Model\LoanClosing AS LoanClosingModel;
use SelfService\Repository\LoanRequestRepository;
use Loan\Repository\LoanClosingRepository;
use SelfService\Model\LoanRequest as LoanRequestModel;
use Setup\Model\Loan;
use ManagerService\Repository\LoanApproveRepository;

class LoanApply extends HrisController{
    protected $form;
    protected $loanClosingForm;
    protected $adapter;
    protected $loanRequesteRepository;
    protected $loanClosingRepository;
    
    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->loanRequesteRepository = new LoanRequestRepository($adapter);
        $this->loanClosingRepository = new LoanClosingRepository($adapter);
    }
    public function initializeLoanForm(){
        $builder = new AnnotationBuilder();
        $form = new LoanRequestForm();
        $this->form = $builder->createForm($form);
    }
    public function initializeClosingForm(){
        $builder = new AnnotationBuilder();
        $loanClosingForm = new LoanClosingForm();
        $this->loanClosingForm = $builder->createForm($loanClosingForm);
    }
    
    public function indexAction() {
       return $this->redirect()->toRoute("loanStatus");
    }

    public function addAction() {
        $this->initializeLoanForm();
        $request = $this->getRequest();
        $model = new LoanRequestModel();  

        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->loanRequestId = ((int) Helper::getMaxId($this->adapter, LoanRequestModel::TABLE_NAME, LoanRequestModel::LOAN_REQUEST_ID)) + 1;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                $model->deductOnSalary = 'Y';
                $this->loanRequesteRepository->add($model);
                $this->flashmessenger()->addMessage("Loan Request Successfully added!!!");
                return $this->redirect()->toRoute("loanStatus");
            }
        }
        $rateDetails = $this->loanRequesteRepository->getLoanDetails();

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'rateDetails' => Helper::extractDbData($rateDetails),
                    'employees'=> EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"],["STATUS"=>'E','RETIRED_FLAG'=>'N'],"FIRST_NAME","ASC"," ",FALSE,TRUE, $this->employeeId),
                    'loans' => EntityHelper::getTableKVListWithSortOption($this->adapter, Loan::TABLE_NAME, Loan::LOAN_ID, [Loan::LOAN_NAME], [Loan::STATUS => "E"], Loan::LOAN_ID, "ASC",NULL,FALSE,TRUE)
        ]);
    }

    public function issueNewLoanAsRequired($emp_id, $paymentAmount, $old_loan_req_id, $repaymentMonths){
        $loanAmount = $this->loanClosingRepository->getRemainingAmount($old_loan_req_id, $paymentAmount);
        $loanAmount = Helper::extractDbData($loanAmount)[0]['REMAINING_AMOUNT'];
        $oldLoanId = $this->loanClosingRepository->getOldLoanId($old_loan_req_id);
        $oldLoanId = Helper::extractDbData($oldLoanId)[0]['LOAN_ID'];
        
        $model = new LoanRequestModel();
        $model->loanRequestId = ((int) Helper::getMaxId($this->adapter, LoanRequestModel::TABLE_NAME, LoanRequestModel::LOAN_REQUEST_ID)) + 1;
        $model->requestedDate = Helper::getcurrentExpressionDate();
        $model->loanDate = Helper::getcurrentExpressionDate();
        $model->status = 'RQ';
        $model->interestRate = Helper::extractDbData($this->loanClosingRepository->getRateByLoanReqId($old_loan_req_id))[0]['INTEREST_RATE'];
        $model->employeeId = $emp_id;
        $model->repaymentMonths = $repaymentMonths;
        $model->requestedAmount = $loanAmount;
        $model->reason = '';
        $model->loanId = $oldLoanId;
        $model->deductOnSalary = 'Y';
        $this->loanRequesteRepository->add($model);
        $newLoanReqId = ((int) Helper::getMaxId($this->adapter, LoanRequestModel::TABLE_NAME, LoanRequestModel::LOAN_REQUEST_ID));
        $model = new LoanClosingModel();
        $loanApproveRepository = new LoanApproveRepository($this->adapter);
        $loanRequest = new LoanRequestModel();
        $model->newLoanReqId = $newLoanReqId;
        $this->loanClosingRepository->edit($model, $old_loan_req_id);
        $loanRequest->status = "AP";
        $loanApproveRepository->addToDetails($newLoanReqId);
        $loanRequest->approvedBy = $this->employeeId;
        $loanRequest->approvedRemarks = '';
        $loanApproveRepository->edit($loanRequest, $newLoanReqId);
        $this->flashmessenger()->addMessage("Loan Successfully closed with this payment 
        and new Loan has been issued and approved!!!");
        return $this->redirect()->toRoute("loanStatus");
    }

    public function loanClosingAction() {
        $this->initializeClosingForm();
        $request = $this->getRequest();
        $model = new LoanClosingModel();
        $id = (int) $this->params()->fromRoute('id');
        if ($request->isPost()) {
            $this->loanClosingForm->setData($request->getPost());
            if ($this->loanClosingForm->isValid()) {
                $model->exchangeArrayFromForm($this->loanClosingForm->getData());
                $model->id = ((int) Helper::getMaxId($this->adapter, LoanClosingModel::TABLE_NAME, LoanClosingModel::ID)) + 1;
                //$model->paymentDate = Helper::getcurrentExpressionDate();
                $model->loanReqId = (int) $this->params()->fromRoute('id');
                $this->loanClosingRepository->add($model);
                $this->loanClosingRepository->updateLoanStatus($model->loanReqId);

                $emp_id = $this->loanClosingRepository->getEmployeeByLoanRequestId($id);
                $emp_id = Helper::extractDbData($emp_id)[0]['EMPLOYEE_ID'];
                if(!empty($_POST['repaymentMonths'])){
                    $this->issueNewLoanAsRequired($emp_id, $model->paymentAmount, $id, $_POST['repaymentMonths']);
                }
                else{
                    $this->flashmessenger()->addMessage("Loan Successfully closed with this payment!!!");
                }
                return $this->redirect()->toRoute("loanStatus");
            }
        }  
        $emp_id = $this->loanClosingRepository->getEmployeeByLoanRequestId($id);
        $emp_id = Helper::extractDbData($emp_id)[0]['EMPLOYEE_ID'];
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->loanClosingForm,
            'id' => $id,
            'rate' => Helper::extractDbData($this->loanClosingRepository->getRateByLoanReqId($id))[0]['INTEREST_RATE'],
            'employee'=> EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"],["EMPLOYEE_ID"=>$emp_id,"STATUS"=>'E','RETIRED_FLAG'=>'N'],"FIRST_NAME","ASC"," ",FALSE,TRUE),
            'unpaidAmount'=>Helper::extractDbData($this->loanClosingRepository->getUnpaidAmount($id))[0]['UNPAID_AMOUNT']
        ]);
    }

    public function rectifyAction() {
        $this->initializeClosingForm();
        $request = $this->getRequest();
        $id = (int) $this->params()->fromRoute('id');
        $model = new LoanClosingModel();
        $paymentId = Helper::extractDbData($this->loanClosingRepository->getPaymentId($id))[0]["ID"];
        if ($request->isPost()) {
            $data = $request->getPost();
            //echo '<pre>'; print_r($data); die;
            $this->loanClosingRepository->rectify($paymentId, $data);
            $this->flashmessenger()->addMessage("Amount has been rectified successfully!!!");
            return $this->redirect()->toRoute("loanStatus");
        }  
        $detail = $this->loanClosingRepository->fetchById($paymentId);
        $model->exchangeArrayFromDB($detail);
        $this->loanClosingForm->bind($model);
        $emp_id = $this->loanClosingRepository->getEmployeeByLoanRequestId($id);
        $emp_id = Helper::extractDbData($emp_id)[0]['EMPLOYEE_ID'];
        
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->loanClosingForm,
            'id' => $id,
            'rate' => Helper::extractDbData($this->loanClosingRepository->getRateByLoanReqId($id))[0]['INTEREST_RATE'],
            'employee'=> EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"],["EMPLOYEE_ID"=>$emp_id,"STATUS"=>'E','RETIRED_FLAG'=>'N'],"FIRST_NAME","ASC"," ",FALSE,TRUE),
            'unpaidAmount'=>Helper::extractDbData($this->loanClosingRepository->getUnpaidAmount($id))[0]['UNPAID_AMOUNT']
        ]);
    }
}