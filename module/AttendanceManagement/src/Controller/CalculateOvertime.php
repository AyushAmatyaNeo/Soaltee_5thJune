<?php

namespace AttendanceManagement\Controller;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use AttendanceManagement\Form\AttendanceByHrForm;
use AttendanceManagement\Model\AttendanceDetail as AttendanceByHrModel;
use AttendanceManagement\Repository\AttendanceDetailRepository;
use AttendanceManagement\Repository\AttendanceRepository;
use DateTime;
use Exception;
use SelfService\Repository\OvertimeDetailRepository;
use SelfService\Repository\OvertimeRepository;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Element\Select;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class CalculateOvertime extends AbstractActionController {

    private $adapter;
    private $repository;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->repository = new AttendanceDetailRepository($adapter);
    }

    public function indexAction() {
        $statusFormElement = new Select();
        $statusFormElement->setName("status");
        $status = array(
            "All" => "All Status",
            "P" => "Present Only",
            "A" => "Absent Only",
            "H" => "On Holiday",
            "L" => "On Leave"
        );
        $statusFormElement->setValueOptions($status);
        $statusFormElement->setValue("P");
        $statusFormElement->setAttributes(["id" => "statusId", "class" => "form-control reset-field"]);
        $statusFormElement->setLabel("Status");

        $employeeTypeFormElement = new Select();
        $employeeTypeFormElement->setName("employeeType");
        $employeeType = array(
            '-1' => "All Employee Type",
            "C" => "Contract",
            "R" => "Regular"
        );
        $employeeTypeFormElement->setValueOptions($employeeType);
        $employeeTypeFormElement->setAttributes(["id" => "employeeTypeId", "class" => "form-control"]);
        $employeeTypeFormElement->setLabel("Employee Type");

        return Helper::addFlashMessagesToArray($this, [
                    'status' => $statusFormElement,
                    'employeeType' => $employeeTypeFormElement,
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
        ]);
    }

    public function initializeForm() {
        $builder = new AnnotationBuilder();
        $attendanceByHr = new AttendanceByHrForm();
        $this->form = $builder->createForm($attendanceByHr);
    }

    public function viewAction() {
        $this->initializeForm();
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute("calculateOvertime");
        }
        $attendanceByHrModel = new AttendanceByHrModel();
        $overtimeRepo = new OvertimeRepository($this->adapter);

        $detail = $this->repository->fetchById($id);
        $attendanceByHrModel->exchangeArrayFromDB($detail);
        $this->form->bind($attendanceByHrModel);
        $overtime = $overtimeRepo->getAllByEmployeeId($detail['EMPLOYEE_ID'], $detail['ATTENDANCE_DT'], 'AP')->current();
        $overtimeDetailRepo = new OvertimeDetailRepository($this->adapter);
        $overtimeDetailResult = $overtimeDetailRepo->fetchByOvertimeId($overtime['OVERTIME_ID']);
        $overtimeDetails = [];
        foreach ($overtimeDetailResult as $overtimeDetailRow) {
            array_push($overtimeDetails, $overtimeDetailRow);
        }

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'overtimeDetails' => $overtimeDetails,
                    'overtimeInHour' => $overtime['TOTAL_HOUR'],
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", NULL, FALSE, TRUE)
                        ]
        );
    }

    public function calculateAction() {
        $request = $this->getRequest();
        $postData = $request->getPost()->getArrayCopy();
        $fromDate = $postData['fromDate'];
        $toDate = $postData['toDate'];
        $begin = new DateTime($fromDate);
        $end = new DateTime($toDate);
        try {
            $overtimeRepo = new OvertimeRepository($this->adapter);
            for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
                $overtimeAutoCalc = $overtimeRepo->executeProcedure($i->format("d-M-Y"));
            }
            $this->flashmessenger()->addMessage("Calculation of Overtime Successfully Completed!!");
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage("Calculation of Overtime Failed!!");
            $this->flashmessenger()->addMessage($e->getMessage());
        }
        $this->redirect()->toRoute("calculateOvertime");
    }

    public function pullAttendanceWidOvertimeListAction() {
        throw new Exception("Need Rework on this Page.");
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $attendanceDetailRepository = new AttendanceDetailRepository($this->adapter);
            $overtimeRepo = new OvertimeRepository($this->adapter);
            $overtimeDetailRepo = new OvertimeDetailRepository($this->adapter);
            $employeeId = $data['employeeId'];
            $companyId = $data['companyId'];
            $branchId = $data['branchId'];
            $departmentId = $data['departmentId'];
            $positionId = $data['positionId'];
            $designationId = $data['designationId'];
            $serviceTypeId = $data['serviceTypeId'];
            $serviceEventTypeId = $data['serviceEventTypeId'];
            $fromDate = $data['fromDate'];
            $toDate = $data['toDate'];
            $status = $data['status'];
            $employeeTypeId = $data['employeeTypeId'];
            $overtimeOnly = (int) $data['overtimeOnly'];
            $result = $attendanceDetailRepository->filterRecord($employeeId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $fromDate, $toDate, $status, $companyId, $employeeTypeId, true);
            $list = [];
            foreach ($result as $row) {
                if ($status == 'L') {
                    $row['STATUS'] = "On Leave[" . $row['LEAVE_ENAME'] . "]";
                } else if ($status == 'H') {
                    $row['STATUS'] = "On Holiday[" . $row['HOLIDAY_ENAME'] . "]";
                } else if ($status == 'A') {
                    $row['STATUS'] = "Absent";
                } else if ($status == 'P') {
                    $row['STATUS'] = "Present";
                } else {
                    if ($row['LEAVE_ENAME'] != null) {
                        $row['STATUS'] = "On Leave[" . $row['LEAVE_ENAME'] . "]";
                    } else if ($row['HOLIDAY_ENAME'] != null) {
                        $row['STATUS'] = "On Holiday[" . $row['HOLIDAY_ENAME'] . "]";
                    } else if ($row['HOLIDAY_ENAME'] == null && $row['LEAVE_ENAME'] == null && $row['IN_TIME'] == null) {
                        $row['STATUS'] = "Absent";
                    } else if ($row['IN_TIME'] != null) {
                        $row['STATUS'] = "Present";
                    }
                }
                $overtimeDetailResult = $overtimeDetailRepo->fetchByOvertimeId($row['ID']);
                $overtimeDetails = [];
                foreach ($overtimeDetailResult as $overtimeDetailRow) {
                    array_push($overtimeDetails, $overtimeDetailRow);
                }
                $middleName = ($row['MIDDLE_NAME'] != null) ? " " . $row['MIDDLE_NAME'] . " " : " ";
                $row['EMPLOYEE_NAME'] = $row['FIRST_NAME'] . $middleName . $row['LAST_NAME'];
                $row['DETAILS'] = $overtimeDetails;
                if ($overtimeOnly == 1 && $row['OVERTIME_ID'] != null) {
                    array_push($list, $row);
                } else if ($overtimeOnly == 0) {
                    array_push($list, $row);
                }
            }

            return new JsonModel(['success' => true, 'data' => $list, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pullInOutTimeAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $attendanceDt = $data['attendanceDt'];
            $employeeId = $data['employeeId'];

            $attendanceRepository = new AttendanceRepository($this->adapter);
            $result = $attendanceRepository->fetchInOutTimeList($employeeId, $attendanceDt);
            $list = [];
            foreach ($result as $row) {
                array_push($list, $row);
            }

            return new JsonModel(['success' => true, 'data' => $list, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

}
