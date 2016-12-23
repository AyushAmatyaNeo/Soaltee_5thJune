<?php

namespace Setup\Controller;

/**
 * Master Setup for Designation
 * Designation controller.
 * Created By: Ukesh Gaiju
 * Edited By: Somkala Pachhai
 * Date: August 3, 2016, Friday
 * Last Modified By: Somkala Pachhai
 * Last Modified Date: August 10,2016, Wednesday
 */

use Application\Helper\Helper;
use Application\Helper\EntityHelper;
use Setup\Form\DesignationForm;
use Setup\Model\Designation;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Adapter\AdapterInterface;
use Setup\Repository\DesignationRepository;

class DesignationController extends AbstractActionController
{
    private $repository;
    private $form;
    private $adapter;

    function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->repository = new DesignationRepository($adapter);
    }

    public function initializeForm()
    {
        $designationForm = new DesignationForm();
        $builder = new AnnotationBuilder();
        if (!$this->form) {
            $this->form = $builder->createForm($designationForm);
        }
    }

    public function indexAction()
    {
        $designations = $this->repository->fetchAll();
        
        $designationList = [];
        
        foreach($designations as $designationRow){
            array_push($designationList, $designationRow);
        }
        
        return Helper::addFlashMessagesToArray($this, ["designations" => $designationList]);
    }

    public function addAction()
    {

        $this->initializeForm();
        $request = $this->getRequest();
        if ($request->isPost()) {

            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $designation = new Designation();
                $designation->exchangeArrayFromForm($this->form->getData());
                $designation->createdDt = Helper::getcurrentExpressionDate();
                $designation->designationId = ((int)Helper::getMaxId($this->adapter, "HR_DESIGNATIONS", "DESIGNATION_ID")) + 1;
                 $designation->status = 'E';
                $this->repository->add($designation);

                $this->flashmessenger()->addMessage("Designation Successfully added!!!");
                return $this->redirect()->toRoute("designation");
            }
        }
        $designationList = EntityHelper::getTableKVListWithSortOption($this->adapter, Designation::TABLE_NAME, Designation::DESIGNATION_ID, [Designation::DESIGNATION_TITLE], ["STATUS" => "E"],"DESIGNATION_TITLE","ASC");
        $designationList1 = [""=>"none"]+$designationList;
        return new ViewModel(Helper::addFlashMessagesToArray(
            $this,
            [
                'form' => $this->form,
                'customRender' => Helper::renderCustomView(),
                'designationList' => $designationList1,
                'messages' => $this->flashmessenger()->getMessages()
            ]
        )
        );

    }

    public function editAction()
    {

        $id = (int)$this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('designation');
        }
        $this->initializeForm();
        $request = $this->getRequest();
        $designation = new Designation();
        if (!$request->isPost()) {
            $designation->exchangeArrayFromDB($this->repository->fetchById($id)->getArrayCopy());
            $this->form->bind($designation);
        } else {

            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {

                $designation->exchangeArrayFromForm($this->form->getData());
                $designation->modifiedDt = Helper::getcurrentExpressionDate();
                $this->repository->edit($designation, $id);

                $this->flashmessenger()->addMessage("Designation Successfully Updated!!!");
                return $this->redirect()->toRoute("designation");
            }
        }
        $designationList = EntityHelper::getTableKVList($this->adapter, Designation::TABLE_NAME, Designation::DESIGNATION_ID, [Designation::DESIGNATION_TITLE], ["STATUS" => "E"]);
        $designationList1 = [""=>"none"]+$designationList;
        return new ViewModel(Helper::addFlashMessagesToArray(
            $this,
            [
                'form' => $this->form,
                'customRender' => Helper::renderCustomView(),
                'designationList' => $designationList1,
                'messages' => $this->flashmessenger()->getMessages(),
                'id' => $id
            ])
        );
    }

    public function deleteAction()
    {
        $id = (int)$this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('designation');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Designation Successfully Deleted!!!");
        return $this->redirect()->toRoute('designation');
    }
}

/* End of file DesignationController.php */
/* Location: ./Setup/src/Controller/DesignationController.php */
