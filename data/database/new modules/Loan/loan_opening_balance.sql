insert into Hris_Loan_Opening_Balance(
ACC_CODE,
EMPLOYEE_ID,
OPENING_DATE,
PARTICULARS,
TRANSACTION_TYPE,
OPENING_AMOUNT,
CURRENCY_CODE,
EXCHANGE_RATE,
CREATED_BY,
CREATED_DATE,
MODIFY_DATE
) 
select
acc_code,
ltrim(sub_code, 'E0') as employee_id,
opening_date,
particulars,
TRANSACTION_TYPE,
OPENING_AMOUNT,
CURRENCY_CODE,
EXCHANGE_RATE,
CREATED_BY,
CREATED_DATE,
MODIFY_DATE
from Fa_Sub_Opening_Balance_Setup@EMPNEW;


update Hris_Loan_Opening_Balance set loan_id = (select loan_id from Hris_Loan_Master_Setup
where acc_code_emp = Hris_Loan_Opening_Balance.acc_code);