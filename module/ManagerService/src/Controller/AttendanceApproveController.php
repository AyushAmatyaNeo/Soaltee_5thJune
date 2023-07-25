<?php

namespace ManagerService\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use AttendanceManagement\Repository\AttendanceStatusRepository;
use Exception;
use ManagerService\Repository\AttendanceApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\AttendanceRequestForm;
use SelfService\Model\AttendanceRequestModel;
use SelfService\Repository\AttendanceRequestRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Element\Select;
use Zend\View\Model\JsonModel;
use AttendanceManagement\Repository\RoasterRepo;
use AttendanceManagement\Model\ShiftSetup;
use Report\Repository\ReportRepository;

class AttendanceApproveController extends HrisController {

    protected $roasterRepo;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(AttendanceApproveRepository::class);
        $this->initializeForm(AttendanceRequestForm::class);
        $this->roasterRepo =  new RoasterRepo($this->adapter);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $rawList = $this->repository->getAllRequest($this->employeeId);
                $list = iterator_to_array($rawList, false);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([]);
    }

    private function makeDecision($id, $role, $approve, $remarks = null, $enableFlashNotification = false) {
        $notificationEvent = null;
        $message = null;
        $model = new AttendanceRequestModel();
        $model->id = $id;
        switch ($role) {
            case 2:
                $model->recommendedRemarks = $remarks;
                $model->recommendedDate = Helper::getcurrentExpressionDate();
                $model->recommendedBy = $this->employeeId;
                $model->status = $approve ? "RC" : "R";
                $message = $approve ? "Attendance Request Recommended" : "Attendance Request Rejected";
                $notificationEvent = $approve ? NotificationEvents::ATTENDANCE_RECOMMEND_ACCEPTED : NotificationEvents::ATTENDANCE_RECOMMEND_REJECTED;
                break;
            case 4:
                $model->recommendedDate = Helper::getcurrentExpressionDate();
                $model->recommendedBy = $this->employeeId;
            case 3:
                $model->approvedRemarks = $remarks;
                $model->approvedDate = Helper::getcurrentExpressionDate();
                $model->approvedBy = $this->employeeId;
                $model->status = $approve ? "AP" : "R";
                $message = $approve ? "Attendance Request Approved" : "Attendance Request Rejected";
                $notificationEvent = $approve ? NotificationEvents::ATTENDANCE_APPROVE_ACCEPTED : NotificationEvents::ATTENDANCE_APPROVE_REJECTED;
                break;
        }
        $this->repository->edit($model, $id);
        if ($enableFlashNotification) {
            $this->flashmessenger()->addMessage($message);
        }
        try {
            HeadNotification::pushNotification($notificationEvent, $model, $this->adapter, $this);
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage($e->getMessage());
        }
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');
        $role = $this->params()->fromRoute('role');


        if ($id === 0) {
            return $this->redirect()->toRoute("attedanceapprove");
        }
        $attendanceRequestRepository = new AttendanceRequestRepository($this->adapter);


        $request = $this->getRequest();
        $model = new AttendanceRequestModel();
        $detail = $attendanceRequestRepository->fetchByIdWithEmployeeId($id, $this->employeeId);

//        if ($this->employeeId != $detail['RECOMMENDER_ID'] && $this->employeeId != $detail['APPROVER_ID']) {
//            return $this->redirect()->toRoute("attedanceapprove");
//        }

        $employeeId = $detail['EMPLOYEE_ID'];
        $employeeName = $detail['FULL_NAME'];

        $approvedDT = $detail['APPROVED_DT'];

        $requestedEmployeeID = $detail['EMPLOYEE_ID'];
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];
        $recommenderId = $detail['RECOMMENDED_BY'] == null ? $detail['RECOMMENDER_ID'] : $detail['RECOMMENDED_BY'];
        if ($request->isPost()) {
            $postedData = (array) $request->getPost();
            $action = $postedData['submit'];
            $this->makeDecision($id, $role, $action == 'Approve', $postedData[$role == 2 ? 'recommendedRemarks' : 'approvedRemarks'], true);
            return $this->redirect()->toRoute("attedanceapprove");
        }
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);


        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'status' => $detail['STATUS'],
                    'employeeName' => $employeeName,
                    'employeeId' => $this->employeeId,
                    'approver' => $authApprover,
                    'requestedDt' => $detail['REQUESTED_DT'],
                    'role' => $role,
                    'recommender' => $authRecommender,
                    'approver' => $authApprover,
                    'recommendedBy' => $recommenderId,
                    'approvedDT' => $approvedDT,
                    'requestedEmployeeId' => $requestedEmployeeID,
        ]);
    }

    public function statusAction() {
        $attendanceStatus = [
            '-1' => 'All',
            'RQ' => 'Pending',
            'RC' => 'Recommended',
            'AP' => 'Approved',
            'R' => 'Rejected'
        ];
        $attendanceStatusFormElement = new Select();
        $attendanceStatusFormElement->setName("attendanceStatus");
        $attendanceStatusFormElement->setValueOptions($attendanceStatus);
        $attendanceStatusFormElement->setAttributes(["id" => "attendanceRequestStatusId", "class" => "form-control reset-field"]);
        $attendanceStatusFormElement->setLabel("Status");

        return Helper::addFlashMessagesToArray($this, [
                    'attendanceStatus' => $attendanceStatusFormElement,
                    'approverId' => $this->employeeId,
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
        ]);
    }

    public function batchApproveRejectAction() {
        $request = $this->getRequest();
        try {
            $postData = $request->getPost();
            $this->makeDecision($postData['id'], $postData['role'], $postData['btnAction'] == "btnApprove");
            return new JsonModel(['success' => true, 'data' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function pullAttendanceRequestStatusListAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $attendanceStatusRepository = new AttendanceStatusRepository($this->adapter);
            if (key_exists('approverId', $data)) {
                $approverId = $data['approverId'];
            } else {
                $approverId = null;
            }
            $result = $attendanceStatusRepository->getFilteredRecord($data, $approverId);
            $recordList = Helper::extractDbData($result);
            return new JsonModel([
                "success" => "true",
                "data" => $recordList,
                "num" => count($recordList)
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function roasterAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                foreach ($data['data'] as $item) {
                    $this->roasterRepo->merge($item['EMPLOYEE_ID'], $item['FOR_DATE'], $item['SHIFT_ID']);
                }
                return new JsonModel(['success' => true, 'data' => null, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'error' => $e->getMessage()]);
            }
        }

        $searchValues = EntityHelper::getSearchData($this->adapter);
        $employees = EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId, 1);
        $searchValues['employee'] = $employees;

        return $this->stickFlashMessagesTo([
                    'searchValues' => $searchValues,
                    'shifts' => EntityHelper::getTableList($this->adapter, ShiftSetup::TABLE_NAME, [ShiftSetup::SHIFT_ID, ShiftSetup::SHIFT_ENAME], [ShiftSetup::STATUS => EntityHelper::STATUS_ENABLED]),
                    'acl' => $this->acl,
                    'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function getRoasterListAction() {
        try {           
            $request = $this->getRequest();
            $data = $request->getPost();
            if(empty($data['q']['employeeId'])){
                $temp = EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId, 2);
                $fromDate = $data['q']['fromDate'];
                $toDate = $data['q']['toDate'];
                $data['q'] = ['employeeId' => $temp, 'fromDate' => $fromDate, 'toDate' => $toDate];
            }
            $result = $this->roasterRepo->getRosterDetailList($data['q']);
            return new JsonModel(['success' => true, 'data' => $result, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getShiftDetailsAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $result = $this->roasterRepo->getshiftDetail($data);
            return new JsonModel(['success' => true, 'data' => $result, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'error' => $e->getMessage()]);
        }
    }

    public function weeklyRosterAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $request = $this->getRequest();
                $data = $request->getPost();
                if(empty($data['q']['employeeId'])){
                    $temp = EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId, 2);
                    $data['q'] = ['employeeId' => $temp];
                }
                $result = $this->roasterRepo->getWeeklyRosterDetailList($data['q']);
                return new JsonModel($result);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        
        $data['pvmReadLink'] = $this->url()->fromRoute('attedanceapprove', ['action' => 'weeklyRoster']);
        $data['pvmUpdateLink'] = $this->url()->fromRoute('attedanceapprove', ['action' => 'assignWeeklyRoster']);
        
        $shfitList=EntityHelper::getTableList($this->adapter, ShiftSetup::TABLE_NAME, [ShiftSetup::SHIFT_ID, ShiftSetup::SHIFT_ENAME], [ShiftSetup::STATUS => EntityHelper::STATUS_ENABLED]);
        asort($shfitList);
        array_unshift($shfitList,array('SHIFT_ID' => -1,'SHIFT_ENAME' => 'select shift'));

        $searchValues = EntityHelper::getSearchData($this->adapter);
        $employees = EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId, 1);
        $searchValues['employee'] = $employees;
        
        return $this->stickFlashMessagesTo([
                    'searchValues' => $searchValues,
                    'shifts' => $shfitList,
                    'acl' => $this->acl,
                    'employeeDetail' => $this->storageData['employee_detail'],
                    'data' => json_encode($data)
        ]);
    }

    public function getWeeklyShiftDetailsAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $result = $this->repository->getWeeklyShiftDetail($data);
            return new JsonModel(['success' => true, 'data' => $result, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'error' => $e->getMessage()]);
        }
    }

    public function assignWeeklyRosterAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $modelData=json_decode($data['models']);
                $arryData=$modelData[0];
                
                $selectedEmp=$arryData->EMPLOYEE_ID;
                $sun=($arryData->SUNARR->SHIFT_ID==$arryData->SUN)?$arryData->SUN:$arryData->SUNARR->SHIFT_ID;
                $mon=($arryData->MONARR->SHIFT_ID==$arryData->MON)?$arryData->MON:$arryData->MONARR->SHIFT_ID;
                $tue=($arryData->TUEARR->SHIFT_ID==$arryData->TUE)?$arryData->TUE:$arryData->TUEARR->SHIFT_ID;
                $wed=($arryData->WEDARR->SHIFT_ID==$arryData->WED)?$arryData->WED:$arryData->WEDARR->SHIFT_ID;
                $thu=($arryData->THUARR->SHIFT_ID==$arryData->THU)?$arryData->THU:$arryData->THUARR->SHIFT_ID;
                $fri=($arryData->FRIARR->SHIFT_ID==$arryData->FRI)?$arryData->FRI:$arryData->FRIARR->SHIFT_ID;
                $sat=($arryData->SATARR->SHIFT_ID==$arryData->SAT)?$arryData->SAT:$arryData->SATARR->SHIFT_ID;
                $sql="
                    BEGIN
                    hris_weekly_ros_assign(
                    {$selectedEmp},
                    {$sun},
                    {$mon},
                    {$tue},
                    {$wed},
                    {$thu},
                    {$fri},
                    {$sat}
                    );
                    END;
                    ";
                EntityHelper::rawQueryResult($this->adapter, $sql);
                return new JsonModel($modelData);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'error' => $e->getMessage()]);
            }
        }
    }

    public function rosterReportAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $from_date = date("d-M-y", strtotime($postedData['fromDate']));
                $to_date = date("d-M-y", strtotime($postedData['toDate']));

                $begin = new \DateTime($from_date);
                $end = new \DateTime($to_date);
                $end->modify('+1 day');

                $interval = \DateInterval::createFromDateString('1 day');
                $period = new \DatePeriod($begin, $interval, $end);

                $dates = array();

                foreach ($period as $dt) {
                    array_push($dates, $dt->format("d-M-y"));
                }
                $reportRepo = new ReportRepository($this->adapter);
                if(empty($postedData['employeeId'])){
                    $temp = EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId, 2);
                    $fromDate = $postedData['fromDate'];
                    $toDate = $postedData['toDate'];
                    $postedData = ['employeeId' => $temp, 'fromDate' => $fromDate, 'toDate' => $toDate];
                }
                $data = $reportRepo->fetchRosterReport($postedData, $dates);
                $rawData = $reportRepo->getDefaultShift();
                $defShift = $rawData[0]['SHIFT_ENAME'];
    
                for($i = 0; $i < count($data); $i++){
                    foreach($data[$i] as $key => $value){
                        if($value == null || $value == ''){
                            $data[$i][$key] = $defShift;
                        }
                    }
                }
                return new JsonModel(['success' => true, 'data' => $data, 'dates' => $dates, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'dates' => $dates, 'error' => $e->getMessage()]);
            }
        }
        $searchValues = EntityHelper::getSearchData($this->adapter);
        $employees = EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId, 1);
        $searchValues['employee'] = $employees;
        return [
            'searchValues' => $searchValues,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }
}
