<?php

namespace AttendanceManagement\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use AttendanceManagement\Model\ShiftSetup;
use AttendanceManagement\Repository\RoasterRepo;
use Exception;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class Roaster extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->repository = new RoasterRepo($this->adapter);
    }

    public function indexAction() {
        return $this->stickFlashMessagesTo([
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'shifts' => EntityHelper::getTableList($this->adapter, ShiftSetup::TABLE_NAME, [ShiftSetup::SHIFT_ID, ShiftSetup::SHIFT_ENAME], [ShiftSetup::STATUS => EntityHelper::STATUS_ENABLED]),
                    'acl' => $this->acl,
                    'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function getRoasterListAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $result = $this->repository->getRosterDetailList($data['q']);
            return new JsonModel(['success' => true, 'data' => $result, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function assignRoasterAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

//            print_r($data['data']);
//            die();

            foreach ($data['data'] as $item) {
                $this->repository->merge($item['EMPLOYEE_ID'], $item['FOR_DATE'], $item['SHIFT_ID']);
            }
            return new JsonModel(['success' => true, 'data' => null, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'error' => $e->getMessage()]);
        }
    }

    public function getShiftDetailsAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $result = $this->repository->getshiftDetail($data);
            return new JsonModel(['success' => true, 'data' => $result, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'error' => $e->getMessage()]);
        }
    }

}
