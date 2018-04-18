<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use LeaveManagement\Form\LeaveApplyForm;
use LeaveManagement\Model\LeaveApply;
use LeaveManagement\Model\LeaveMaster;
use ManagerService\Repository\LeaveApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Model\LeaveSubstitute;
use SelfService\Repository\LeaveRequestRepository;
use SelfService\Repository\LeaveSubstituteRepository;
use Setup\Model\HrEmployees;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class LeaveRequest extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LeaveRequestRepository::class);
        $this->initializeForm(LeaveApplyForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $rawList = $this->repository->getfilterRecords($data);
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        $leaveList = EntityHelper::getTableKVListWithSortOption($this->adapter, LeaveMaster::TABLE_NAME, LeaveMaster::LEAVE_ID, [LeaveMaster::LEAVE_ENAME], [LeaveMaster::STATUS => 'E'], LeaveMaster::LEAVE_ENAME, "ASC", null, true);
        $leaveSE = $this->getSelectElement(['name' => 'leave', 'id' => 'leaveId', 'class' => 'form-control', 'label' => 'Leave Type'], $leaveList);
        $leaveStatusFE = $this->getStatusSelectElement(['name' => 'leaveStatus', 'id' => 'leaveRequestStatusId', 'class' => 'form-control', 'label' => 'Leave Request Status']);

        return Helper::addFlashMessagesToArray($this, [
                    'leaves' => $leaveSE,
                    'leaveStatus' => $leaveStatusFE,
                    'employeeId' => $this->employeeId,
        ]);
    }

    public function addAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);
            $leaveSubstitute = $postData->leaveSubstitute;
            if ($this->form->isValid()) {
                $leaveRequest = new LeaveApply();
                $leaveRequest->exchangeArrayFromForm($this->form->getData());

                $leaveRequest->id = (int) Helper::getMaxId($this->adapter, LeaveApply::TABLE_NAME, LeaveApply::ID) + 1;
                $leaveRequest->employeeId = $this->employeeId;
                $leaveRequest->startDate = Helper::getExpressionDate($leaveRequest->startDate);
                $leaveRequest->endDate = Helper::getExpressionDate($leaveRequest->endDate);
                $leaveRequest->requestedDt = Helper::getcurrentExpressionDate();
                $leaveRequest->status = "RQ";

                $this->repository->add($leaveRequest);
                $this->flashmessenger()->addMessage("Leave Request Successfully added!!!");

                if ($leaveSubstitute !== null && $leaveSubstitute !== "") {
                    $leaveSubstituteModel = new LeaveSubstitute();
                    $leaveSubstituteRepo = new LeaveSubstituteRepository($this->adapter);


                    $leaveSubstituteModel->leaveRequestId = $leaveRequest->id;
                    $leaveSubstituteModel->employeeId = $leaveSubstitute;
                    $leaveSubstituteModel->createdBy = $this->employeeId;
                    $leaveSubstituteModel->createdDate = Helper::getcurrentExpressionDate();
                    $leaveSubstituteModel->status = 'E';

                    $leaveSubstituteRepo->add($leaveSubstituteModel);
                    try {
                        HeadNotification::pushNotification(NotificationEvents::LEAVE_SUBSTITUTE_APPLIED, $leaveRequest, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                } else {
                    try {
                        HeadNotification::pushNotification(NotificationEvents::LEAVE_APPLIED, $leaveRequest, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                }
                return $this->redirect()->toRoute("leaverequest");
            }
        }

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employeeId' => $this->employeeId,
                    'leave' => $this->repository->getLeaveList($this->employeeId),
                    'customRenderer' => Helper::renderCustomView(),
                    'employeeList' => EntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"], HrEmployees::FIRST_NAME, "ASC", " ", false, true)
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('leaverequest');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Leave Request Successfully Cancelled!!!");
        return $this->redirect()->toRoute('leaverequest');
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute("leaveapprove");
        }
        $leaveApproveRepository = new LeaveApproveRepository($this->adapter);

        $detail = $leaveApproveRepository->fetchById($id);


        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];

        //to get the previous balance of selected leave from assigned leave detail
        $result = $leaveApproveRepository->assignedLeaveDetail($detail['LEAVE_ID'], $detail['EMPLOYEE_ID']);
        $preBalance = $result['BALANCE'];

        $leaveApply = new LeaveApply();
        $leaveApply->exchangeArrayFromDB($detail);
        $this->form->bind($leaveApply);

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'employeeName' => $detail['FULL_NAME'],
                    'requestedDt' => $detail['REQUESTED_DT'],
                    'availableDays' => $preBalance,
                    'status' => $detail['STATUS'],
                    'recommender' => $authRecommender,
                    'approver' => $authApprover,
                    'remarksDtl' => $detail['REMARKS'],
                    'totalDays' => $result['TOTAL_DAYS'],
                    'recommendedBy' => $detail['RECOMMENDED_BY'],
                    'employeeId' => $this->employeeId,
                    'allowHalfDay' => $detail['ALLOW_HALFDAY'],
                    'leave' => $this->repository->getLeaveList($detail['EMPLOYEE_ID']),
                    'customRenderer' => Helper::renderCustomView(),
                    'subEmployeeId' => $detail['SUB_EMPLOYEE_ID'],
                    'subRemarks' => $detail['SUB_REMARKS'],
                    'subApprovedFlag' => $detail['SUB_APPROVED_FLAG'],
                    'employeeList' => EntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"], HrEmployees::FIRST_NAME, "ASC", " ", false, true),
                    'gp' => $detail['GRACE_PERIOD']
        ]);
    }

    public function pullLeaveDetailWidEmployeeIdAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();

                $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
                $employeeId = $postedData['employeeId'];
                $leaveList = $leaveRequestRepository->getLeaveList($employeeId);

                $leaveRow = [];
                foreach ($leaveList as $key => $value) {
                    array_push($leaveRow, ["id" => $key, "name" => $value]);
                }
                return new CustomViewModel(['success' => true, 'data' => $leaveRow, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function pullLeaveDetailAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
                $leaveId = $postedData['leaveId'];
                $employeeId = $postedData['employeeId'];
                $startDate = $postedData['startDate'];
                $leaveDetail = $leaveRequestRepository->getLeaveDetail($employeeId, $leaveId, $startDate);

                return new CustomViewModel(['success' => true, 'data' => $leaveDetail, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function fetchAvailableDaysAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
                $availableDays = $leaveRequestRepository->fetchAvailableDays(Helper::getExpressionDate($postedData['startDate'])->getExpression(), Helper::getExpressionDate($postedData['endDate'])->getExpression(), $postedData['employeeId'], $postedData['halfDay'], $postedData['leaveId']);
                return new CustomViewModel(['success' => true, 'data' => $availableDays, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function validateLeaveRequestAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
                $error = $leaveRequestRepository->validateLeaveRequest(Helper::getExpressionDate($postedData['startDate'])->getExpression(), Helper::getExpressionDate($postedData['endDate'])->getExpression(), $postedData['employeeId']);
                return new CustomViewModel(['success' => true, 'data' => $error, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

}
