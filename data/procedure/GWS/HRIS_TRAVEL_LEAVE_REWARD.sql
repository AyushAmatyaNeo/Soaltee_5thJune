create or replace PROCEDURE hris_travel_leave_reward ( p_travel_id NUMBER ) AS

v_travel_duration NUMBER;
v_substitute_leave_id hris_leave_master_setup.leave_id%TYPE;
v_balance hris_employee_leave_assign.balance%TYPE;
v_employee_id hris_employee_work_holiday.employee_id%TYPE;
p_employee_id hris_employees.employee_id%TYPE;
v_functional_type_id HRIS_EMPLOYEES.FUNCTIONAL_TYPE_ID%TYPE;
v_increment_day NUMBER := 0;

BEGIN
SELECT
employee_id,
approved_by,
( TO_DATE - from_date ) + 1
INTO
v_employee_id,p_employee_id,v_travel_duration
FROM
hris_employee_travel_request
WHERE
travel_id = p_travel_id;

SELECT
FUNCTIONAL_TYPE_ID
INTO
v_functional_type_id
FROM
hris_employees
WHERE 
employee_id=v_employee_id;

IF
( v_travel_duration >= 15 and v_functional_type_id=1 )
THEN
dbms_output.put_line('LEAVE ADDITION OF TRAVEL PROJECT LEAVE');
SELECT
leave_id
INTO
v_substitute_leave_id
FROM
hris_leave_master_setup
WHERE
is_project = 'Y'
AND
ROWNUM = 1;

BEGIN
SELECT
balance
INTO
v_balance
FROM
hris_employee_leave_assign
WHERE
employee_id = v_employee_id
AND
leave_id = v_substitute_leave_id;

EXCEPTION
WHEN no_data_found THEN
INSERT INTO hris_employee_leave_assign (
employee_id,
leave_id,
previous_year_bal,
total_days,
balance,
created_dt,
created_by
) VALUES (
v_employee_id,
v_substitute_leave_id,
0,
0,
0,
trunc(SYSDATE),
p_employee_id
);

END;

SELECT
trunc(v_travel_duration / 15)
INTO
v_increment_day
FROM
dual;

INSERT INTO hris_employee_leave_addition (
employee_id,
leave_id,
no_of_days,
remarks,
created_date,
wod_id,
woh_id,
TRAINING_ID,
TRAVEL_ID
) VALUES (
v_employee_id,
v_substitute_leave_id,
v_increment_day,
'TRAVEL REWARD',
trunc(SYSDATE),
NULL,
NULL,
NULL,
p_travel_id
);

END IF;

END;