INSERT INTO HRIS_LOAN_CASH_PAYMENT
(
ID,
LOAN_ID,
EMPLOYEE_ID,
REMARKS,
PAYMENT_AMOUNT,
CREATED_DT,
MODIFIED_DT,
payment_date,
type
)
SELECT 
1,
(select loan_id from hris_loan_master_setup 
where acc_code_emp = emp.acc_code),
ltrim(SUBSTR(SUB_CODE, 2), '0') AS EMPLOYEE_ID,
particulars,
cr_amount,
created_date,
modify_date,
modify_date,
'PRN'
from FA_VOUCHER_SUB_DETAIL@EMPNEW emp
where lower(particulars) not like '%salary sheet%'
and lower(particulars) like '%interest%'
and acc_code in (select acc_code from hris_loan_master_setup);