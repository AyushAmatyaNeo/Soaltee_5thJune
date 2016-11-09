<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10/20/16
 * Time: 4:32 PM
 */

namespace Payroll\Controller;


use Application\Helper\EntityHelper;
use Application\Repository\RepositoryInterface;
use Payroll\Model\FlatValue as FlatValueModel;
use Payroll\Model\FlatValueDetail;
use Payroll\Model\MonthlyValue as MonthlyValueModel;
use Payroll\Model\MonthlyValueDetail;
use Payroll\Model\RulesDetail;
use Payroll\Repository\FlatValueDetailRepo;
use Payroll\Repository\MonthlyValueDetailRepo;
use Payroll\Repository\RulesDetailRepo;
use PHPExcel;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;


class Generate extends AbstractActionController
{

    private $adapter;
    private $flatValueDetRepo;
    private $monthlyValueDetRepo;
    private $form;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->flatValueDetRepo = new FlatValueDetailRepo($adapter);
        $this->monthlyValueDetRepo = new MonthlyValueDetailRepo($adapter);
    }

    public function indexAction()
    {
        $monthlyValues = EntityHelper::getTableKVList($this->adapter, MonthlyValueModel::TABLE_NAME, MonthlyValueModel::MTH_ID, [MonthlyValueModel::MTH_EDESC]);
        $flatValues = EntityHelper::getTableKVList($this->adapter, FlatValueModel::TABLE_NAME, FlatValueModel::FLAT_ID, [FlatValueModel::FLAT_EDESC]);

        $this->sanitizeStringArray($monthlyValues);
        $this->sanitizeStringArray($flatValues);

        print "<pre>";

        $ruleDetailRepo = new RulesDetailRepo($this->adapter);
        $rule = $ruleDetailRepo->fetchById(1)->{RulesDetail::MNENONIC_NAME};
        foreach ($monthlyValues as $key => $monthlyValue) {
            $rule = $this->convertConstantToValue($rule, $key, $monthlyValue, $this->monthlyValueDetRepo);
        }

        foreach ($flatValues as $key => $flatValue) {
            $rule = $this->convertConstantToValue($rule, $key, $flatValue, $this->flatValueDetRepo);
        }
//        $cell=new \PHPExcel_Cell();
//        $cell->setValue("343");
//
//        $objPHPExcel = new PHPExcel();
//        echo $objPHPExcel->getCalculationEngine()->calculate()
//        echo eval("echo " . $rule . ";");

       eval($rule);
       exit;
    }

    private function sanitizeStringArray(array &$stringArray)
    {
        foreach ($stringArray as &$string) {
            $string = str_replace(" ", "_", $string);
        }
    }

    private function convertConstantToValue($rule, $key, $constant, RepositoryInterface $repository)
    {
        if (strpos($rule, $constant) !== false) {
            return str_replace($constant, $this->generateValue($key, $repository), $rule);
        } else {
            return $rule;
        }
    }

    private function generateValue($constant, RepositoryInterface $repository)
    {
        if ($repository instanceof MonthlyValueDetailRepo) {
            return $repository->fetchById([1, $constant])[MonthlyValueDetail::MTH_VALUE];
        } else if ($repository instanceof FlatValueDetailRepo) {
            return $repository->fetchById([1, $constant])[FlatValueDetail::FLAT_VALUE];
        }

    }
}
