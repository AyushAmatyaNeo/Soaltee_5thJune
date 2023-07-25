--Adjustments after empower migration
begin
update Hris_Employee_Loan_Request set Print_Flag = null where loan_request_id = 5048;
update Hris_Employee_Loan_Request set Requested_Amount = Requested_Amount+2500 where Loan_Request_Id = 4997;
update Hris_Loan_Payment_Detail set voucher_no = 'INT/02279/76-77' where Loan_Request_Id = 3644 and from_date = '01-Sep-19';
update Hris_Loan_Payment_Detail set paid_flag = 'N' where Loan_Request_Id = 4315 and from_date = '01-DEC-19';
update Hris_Loan_Payment_Detail set paid_flag = 'N' where Loan_Request_Id = 4147 and from_date = '01-DEC-19';
INSERT INTO HRIS_LOAN_CASH_PAYMENT (
    id,
    loan_id,
    employee_id,
    payment_date,
    payment_amount,
    interest,
    receipt_no,
    remarks,
    created_dt,
    created_by,
    modified_by,
    modified_dt,
    deleted_dt,
    deleted_by,
    new_loan_req_id,
    type
) VALUES (
    1,
    1,
    1000957,
    TO_DATE('12-Feb-20','DD-MON-RR'),
    120000,
    0,
    NULL,
    'Loan Settled By FNL',
    TO_DATE('12-Feb-20','DD-MON-RR'),
    NULL,
    NULL,
    TO_DATE('12-Feb-20','DD-MON-RR'),
    NULL,
    NULL,
    NULL,
    'PRN'
);
end;