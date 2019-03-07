<?php

namespace WorkOnDayoff\Controller;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use SelfService\Form\WorkOnDayoffForm;
use SelfService\Model\WorkOnDayoff;
use SelfService\Repository\WorkOnDayoffRepository;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;

class WorkOnDayoffApply extends AbstractActionController {

    private $form;
    private $adapter;
    private $workOnDayoffRepository;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->workOnDayoffRepository = new WorkOnDayoffRepository($adapter);
    }

    public function initializeForm() {
        $builder = new AnnotationBuilder();
        $form = new WorkOnDayoffForm();
        $this->form = $builder->createForm($form);
    }

    public function indexAction() {
        return $this->redirect()->toRoute("workOnDayoffStatus");
    }

    public function addAction() {
        $this->initializeForm();
        $request = $this->getRequest();

        $model = new WorkOnDayoff();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->id = ((int) Helper::getMaxId($this->adapter, WorkOnDayoff::TABLE_NAME, WorkOnDayoff::ID)) + 1;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                $this->workOnDayoffRepository->add($model);
                $this->flashmessenger()->addMessage("Work on Day-off Request Successfully added!!!");

                return $this->redirect()->toRoute("workOnDayoffStatus");
            }
        }

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE","FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", "-", false, true),
        ]);
    }

}
