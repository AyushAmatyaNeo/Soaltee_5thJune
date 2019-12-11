<?php
namespace Loan\Model;

use Application\Model\Model;

class LoanClosing extends Model{
    const TABLE_NAME = "HRIS_LOAN_CASH_PAYMENT";
    const ID = "ID";
    const PAYMENT_DATE = "PAYMENT_DATE";
    const LOAN_REQ_ID = "LOAN_REQ_ID";
    const PAYMENT_AMOUNT = "PAYMENT_AMOUNT";
    const PAYMENT_ID = "PAYMENT_ID";
    const NEW_LOAN_REQ_ID = "NEW_LOAN_REQ_ID";
    const INTEREST = "INTEREST";
    const RECEIPT_NO = "RECEIPT_NO";
    const REMARKS = "REMARKS";
     
    public $id;
    public $paymentDate;
    public $loanReqId;
    public $paymentAmount;
    public $paymentId;
    public $newLoanReqId;
    public $interest;
    public $receiptNo;
    public $remarks;
     
    public $mappings = [
        'id'=> self::ID,
        'paymentDate'=> self::PAYMENT_DATE,
        'loanReqId'=> self::LOAN_REQ_ID,
        'paymentAmount'=> self::PAYMENT_AMOUNT,
        'paymentId'=>self::PAYMENT_ID,
        'newLoanReqId'=>self::NEW_LOAN_REQ_ID,
        'interest'=>self::INTEREST,
        'receiptNo'=>self::RECEIPT_NO,
        'remarks'=>self::REMARKS
    ];
}