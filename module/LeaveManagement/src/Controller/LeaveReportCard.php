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
