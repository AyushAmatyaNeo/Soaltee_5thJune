<?php

namespace Loan\Controller;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Loan\Repository\LoanStatusRepository;
use ManagerService\Repository\LoanApproveRepository;
use SelfService\Form\LoanRequestForm;
use SelfService\Model\LoanRequest;
use Setup\Model\Loan;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Element\Select;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
  
class LoanStatus extends AbstractActionController {

    private $adapter;
    private $loanApproveRepository;
    private $loanStatusRepository;
    private $form;
    private $employeeId;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->loanApproveRepository = new LoanApproveRepository($adapter);
        $this->loanStatusRepository = new LoanStatusRepository($adapter);
        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function initializeForm() {
        $builder = new AnnotationBuilder();
        $form = new LoanRequestForm();
        $this->form = $builder->createForm($form);
    }

    public function indexAction() {
        $loanFormElement = new Select();
        $loanFormElement->setName("loan");
        $loans = EntityHelper::getTableKVListWithSortOption($this->adapter, Loan::TABLE_NAME, Loan::LOAN_ID, [Loan::LOAN_NAME], [Loan::STATUS => 'E'], Loan::LOAN_NAME, "ASC", NULL, FALSE, TRUE);
        $loans1 = [-1 => "All Loans"] + $loans;
        $loanFormElement->setValueOptions($loans1);
        $loanFormElement->setAttributes(["id" => "loanId", "class" => "form-control"]);
        $loanFormElement->setLabel("Loan Type");

        $loanStatus = [
            '-1' => 'All Status',
            'RQ' => 'Pending',
            'RC' => 'Recommended',
            'AP' => 'Approved',
            'R' => 'Rejected',
            'C' => 'Cancelled'
        ];
        $loanStatusFormElement = new Select();
        $loanStatusFormElement->setName("loanStatus");
        $loanStatusFormElement->setValueOptions($loanStatus);
        $loanStatusFormElement->setAttributes(["id" => "loanRequestStatusId", "class" => "form-control"]);
        $loanStatusFormElement->setLabel("Status");

        return Helper::addFlashMessagesToArray($this, [
                    'loans' => $loanFormElement,
                    'loanStatus' => $loanStatusFormElement,
                    'searchValues' => EntityHelper::getSearchData($this->adapter)
        ]);
    }

    public function viewAction() {
        $this->initializeForm();

        $id = (int) $this->params()->fromRoute('id');

        if ($id === 0) {
            return $this->redirect()->toRoute("loanStatus");
        }
        $loanRequest = new LoanRequest();
        $request = $this->getRequest();

        $detail = $this->loanApproveRepository->fetchById($id);
        $status = $detail['STATUS'];
        $employeeId = $detail['EMPLOYEE_ID'];


        $recommApprove = $detail['RECOMMENDER_ID'] == $detail['APPROVER_ID'] ? 1 : 0;
        $employeeName = $detail['FULL_NAME'];
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];

        if (!$request->isPost()) {
            $loanRequest->exchangeArrayFromDB($detail);
            $this->form->bind($loanRequest);
        } else {
            $getData = $request->getPost();
            $reason = $getData->approvedRemarks;
            $action = $getData->submit;

            $loanRequest->approvedDate = Helper::getcurrentExpressionDate();
            if ($action == "Reject") {
                $loanRequest->status = "R";
                $this->flashmessenger()->addMessage("Loan Request Rejected!!!");
            } else if ($action == "Approve") {
                $loanRequest->status = "AP";
                $this->flashmessenger()->addMessage("Loan Request Approved");
                $this->loanApproveRepository->addToDetails($id);
            }
            $loanRequest->approvedBy = $this->employeeId;
            $loanRequest->approvedRemarks = $reason;
            $this->loanApproveRepository->edit($loanRequest, $id);
 
            return $this->redirect()->toRoute("loanStatus");
        }
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'employeeId' => $employeeId,
                    'employeeName' => $employeeName,
                    'requestedDt' => $detail['REQUESTED_DATE'],
                    'recommender' => $authRecommender,
                    'approver' => $authApprover,
                    'approvedDT' => $detail['APPROVED_DATE'],
                    'status' => $status,
                    'loans' => EntityHelper::getTableKVListWithSortOption($this->adapter, Loan::TABLE_NAME, Loan::LOAN_ID, [Loan::LOAN_NAME], [Loan::STATUS => "E"], Loan::LOAN_ID, "ASC", NULL, FALSE, TRUE),
                    'customRenderer' => Helper::renderCustomView(),
                    'recommApprove' => $recommApprove
        ]);
    }
 
    public function editAction(){
        $id = (int) $this->params()->fromRoute('id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = (int) $this->params()->fromRoute('id');
            $data = $this->loanStatusRepository->editList($id);
            $data = Helper::extractDbData($data);
            
            return new JsonModel([
                "success" => "true",
                "data" => $data
            ]);
        }

        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchData($this->adapter)
        ]);
    }
 
    public function skipAction(){
        $id = (int) $this->params()->fromRoute('id');
        $loanStatusRepository = new LoanStatusRepository($this->adapter);
        
        $requestId = Helper::extractDbData($loanStatusRepository->getLoanRequestId($id));
        
        $loanStatusRepository->skipMonth($requestId[0]['LOAN_REQUEST_ID'], $id);
 
        return $this->redirect()->toRoute('loanStatus', array(
            'controller' => 'LoanStatus',
            'action' =>  'edit',
            'id' => $requestId[0]['LOAN_REQUEST_ID']
        ));
    }

 
    public function pullLoanRequestStatusListAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $loanStatusRepository = new LoanStatusRepository($this->adapter);
            $result = $loanStatusRepository->getLoanRequestList($data);
            $recordList = Helper::extractDbData($result);
            // $request_ids = array();
            // foreach($recordList as $record){
            //     array_push($request_ids, $record['LOAN_REQUEST_ID']);
            // }
            
            // $additionalDetails =  Helper::extractDbData($loanStatusRepository->getLoanRequestDetails($request_ids));
            return new JsonModel([
                "success" => "true",
                "data" => $recordList
                //'additionalDetails' => $additionalDetails
            ]);

        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

}
