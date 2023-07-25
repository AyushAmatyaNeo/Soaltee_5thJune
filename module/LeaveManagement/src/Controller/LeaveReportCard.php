<?php

namespace LeaveManagement\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use LeaveManagement\Model\LeaveMaster;
use LeaveManagement\Repository\LeaveReportCardRepository;
use SelfService\Repository\LeaveRequestRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class LeaveReportCard extends HrisController {

    private $leaveRequestRepository;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LeaveReportCardRepository::class);
    }

    public function indexAction() {
        $leaveList = EntityHelper::getTableKVListWithSortOption($this->adapter, LeaveMaster::TABLE_NAME, LeaveMaster::LEAVE_ID, [LeaveMaster::LEAVE_ENAME], [LeaveMaster::STATUS => 'E'], LeaveMaster::LEAVE_ENAME, "ASC", NULL, ['-1' => 'All Leaves'], TRUE);
        $leaveSE = $this->getSelectElement(['name' => 'leave', 'id' => 'leaveId', 'class' => 'form-control reset-field', 'label' => 'Type'], $leaveList);
        $leaveSE->setAttribute('multiple', 'multiple');
        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'leaves' => $leaveSE,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }
  
    public function fetchReportCardAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $employee = $data['data']['employeeId'];
                $rawList = $this->repository->fetchLeaveReportCard($data);
                $list = Helper::extractDbData($rawList);
				
				//echo '<pre>'; print_r($list); die;
				
				if($list == null || count($list) == 0){
					$rawList = $this->repository->fetchIfEmpty($data);
					$list = Helper::extractDbData($rawList);
					$list[0]['FROM_DATE_AD'] = '-';
					$list[0]['FROM_DATE_BS'] = '-';
					$list[0]['TO_DATE_AD'] = '-';
					$list[0]['TO_DATE_BS'] = '-';
					$list[0]['HALF_DAY_DETAIL'] = '-';
					$list[0]['NO_OF_DAYS'] = '-';
					$list[0]['REMARKS'] = '-';
					$list[0]['STATUS'] = '-';
					$list[0]['RECOMMENDED_BY'] = '-';
					$list[0]['RECOMMENDED_DT'] = '-';
					$list[0]['LEAVE_ENAME'] = '-';
					$list[0]['LEAVE_CODE'] = '-';
					$list[0]['RECOMMENDED_BY_NAME'] = '-';
					$list[0]['APPROVED_BY_NAME'] = '-';
				}
				
                $rawLeaves = $this->repository->fetchLeaves($employee, $data['data']['leaveId']);
                $leaves = Helper::extractDbData($rawLeaves);
				
                return new JsonModel([
                    "success" => true,
                    "data" => $list,
                    "leaves" => $leaves,
                    "message" => null,
                ]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }
    }
}
