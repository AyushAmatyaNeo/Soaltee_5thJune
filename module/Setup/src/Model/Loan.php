<?php
namespace Setup\Model;

use Application\Model\Model;

class Loan extends Model{
    const TABLE_NAME = "HRIS_LOAN_MASTER_SETUP";
    const LOAN_ID = "LOAN_ID";
    const LOAN_CODE = "LOAN_CODE";
    const LOAN_NAME = "LOAN_NAME";
    const MIN_AMOUNT = "MIN_AMOUNT";
    const MAX_AMOUNT = "MAX_AMOUNT";
    const INTEREST_RATE = "INTEREST_RATE";
    const REPAYMENT_AMOUNT = "REPAYMENT_AMOUNT";
    const REPAYMENT_PERIOD = "REPAYMENT_PERIOD";
    const REMARKS = "REMARKS";
    const CREATED_DATE = "CREATED_DATE";
    const CREATED_BY = "CREATED_BY";
    const MODIFIED_DATE = "MODIFIED_DATE";
    const MODIFIED_BY = "MODIFIED_BY";
    const STATUS = "STATUS";
    const COMPANY_ID = "COMPANY_ID";
    const PAY_ID_INT = "PAY_ID_INT";
    const PAY_ID_AMT = "PAY_ID_AMT";
    const IS_RATE_FLEXIBLE = "IS_RATE_FLEXIBLE";
    
    public $loanId;
    public $payIdInt;
    public $payIdAmt;
    public $isRateFlexible;
    public $loanCode;
    public $loanName;
    public $minAmount;
    public $maxAmount;
    public $interestRate;
    public $repaymentAmount;
    public $repaymentPeriod;
    public $remarks;
    public $createdDate;
    public $createdBy;
    public $modifiedDate;
    public $modifiedBy;
    public $status;
    public $companyId;
    
    public $mappings =[
        'loanId'=>self::LOAN_ID,
        'payIdInt'=>self::PAY_ID_INT,
        'payIdAmt'=>self::PAY_ID_AMT,
        'isRateFlexible'=>self::IS_RATE_FLEXIBLE,
        'loanCode'=>self::LOAN_CODE,
        'loanName'=>self::LOAN_NAME,
        'minAmount'=>self::MIN_AMOUNT,
        'maxAmount'=>self::MAX_AMOUNT,
        'interestRate'=>self::INTEREST_RATE,
        'repaymentAmount'=>self::REPAYMENT_AMOUNT,
        'repaymentPeriod'=>self::REPAYMENT_PERIOD,
        'status'=>self::STATUS,
        'remarks'=>self::REMARKS,
        'createdDate'=>self::CREATED_DATE,
        'createdBy'=>self::CREATED_BY,
        'modifiedDate'=>self::MODIFIED_DATE,
        'modifiedBy'=>self::MODIFIED_BY,
        'companyId' => self::COMPANY_ID
    ];
}