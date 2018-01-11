create or replace PROCEDURE HRIS_TRAVEL_REQUEST_PROC(
    P_TRAVEL_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.TRAVEL_ID%TYPE,
    P_LINK_TO_SYNERGY CHAR := 'N')
AS
  V_FROM_DATE HRIS_EMPLOYEE_TRAVEL_REQUEST.FROM_DATE%TYPE;
  V_EMPLOYEE_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.EMPLOYEE_ID%TYPE;
  V_STATUS HRIS_EMPLOYEE_TRAVEL_REQUEST.STATUS%TYPE;
  V_REQUESTED_AMOUNT HRIS_EMPLOYEE_TRAVEL_REQUEST.REQUESTED_AMOUNT%TYPE;
  V_SETTLEMENT_AMOUNT FLOAT;
  V_REQUEST_TYPE HRIS_EMPLOYEE_TRAVEL_REQUEST.REQUESTED_TYPE%TYPE;
  --
  V_LINK_TRAVEL_TO_SYNERGY HRIS_PREFERENCES.VALUE%TYPE;
  V_FORM_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_DR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_CR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_EXCESS_CR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_LESS_DR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  --
  V_COMPANY_CODE VARCHAR2(255 BYTE):='07';
  V_BRANCH_CODE  VARCHAR2(255 BYTE):=-'07.01';
  V_CREATED_BY   VARCHAR2(255 BYTE):='ADMIN';
  V_VOUCHER_NO   VARCHAR2(255 BYTE);
BEGIN
  BEGIN
    SELECT FROM_DATE ,
      EMPLOYEE_ID,
      STATUS,
      REQUESTED_AMOUNT,
      REQUESTED_TYPE
    INTO V_FROM_DATE,
      V_EMPLOYEE_ID,
      V_STATUS,
      V_REQUESTED_AMOUNT,
      V_REQUEST_TYPE
    FROM HRIS_EMPLOYEE_TRAVEL_REQUEST
    WHERE TRAVEL_ID =P_TRAVEL_ID;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    DBMS_OUTPUT.PUT('NO DATA FOUND FOR ID =>'|| P_TRAVEL_ID);
    RETURN;
  END;
  --
  IF V_STATUS IN ('AP','C') AND V_REQUEST_TYPE = 'ep' AND V_FROM_DATE <TRUNC(SYSDATE) THEN
    HRIS_REATTENDANCE(V_FROM_DATE,V_EMPLOYEE_ID);
  END IF;
  --
  IF(LOWER(V_REQUEST_TYPE) ='ep') THEN
    SELECT SUM(TOTAL_AMOUNT)
    INTO V_SETTLEMENT_AMOUNT
    FROM HRIS_EMP_TRAVEL_EXPENSE_DTL
    WHERE TRAVEL_ID = P_TRAVEL_ID;
  END IF;
  --
  SELECT *
  INTO V_LINK_TRAVEL_TO_SYNERGY,
    V_FORM_CODE,
    V_DR_ACC_CODE,
    V_CR_ACC_CODE,
    V_EXCESS_CR_ACC_CODE,
    V_LESS_DR_ACC_CODE
  FROM
    (SELECT * FROM HRIS_PREFERENCES
    ) PIVOT ( MAX(VALUE) FOR KEY IN ('LINK_TRAVEL_TO_SYNERGY' AS LINK_TRAVEL_TO_SYNERGY,'FORM_CODE' AS FORM_CODE, 'DR_ACC_CODE' AS DR_ACC_CODE, 'CR_ACC_CODE' AS CR_ACC_CODE,'EXCESS_CR_ACC_CODE' AS EXCESS_CR_ACC_CODE,'LESS_DR_ACC_CODE' AS LESS_DR_ACC_CODE) );
  --
  IF V_LINK_TRAVEL_TO_SYNERGY = 'Y' AND P_LINK_TO_SYNERGY = 'Y' THEN
    SELECT FN_NEW_VOUCHER_NO(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),'FA_DOUBLE_VOUCHER')
    INTO V_VOUCHER_NO
    FROM DUAL;
    IF LOWER(V_REQUEST_TYPE) = 'ad' THEN
      HRIS_TRAVEL_ADVANCE(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),V_BRANCH_CODE,V_CREATED_BY,TRUNC(SYSDATE),V_DR_ACC_CODE,V_CR_ACC_CODE,'TEST',V_REQUESTED_AMOUNT,'E'||V_EMPLOYEE_ID,V_VOUCHER_NO);
    END IF;
    IF (LOWER(V_REQUEST_TYPE) ='ep') AND (V_REQUESTED_AMOUNT = V_SETTLEMENT_AMOUNT) THEN
      HRIS_TRAVEL_SETTLEMENT_EQUAL(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),V_BRANCH_CODE,V_CREATED_BY,TRUNC(SYSDATE),V_DR_ACC_CODE,V_CR_ACC_CODE,'TEST',V_SETTLEMENT_AMOUNT,'E'||V_EMPLOYEE_ID,V_VOUCHER_NO);
    END IF;
    IF (LOWER(V_REQUEST_TYPE) ='ep') AND (V_REQUESTED_AMOUNT < V_SETTLEMENT_AMOUNT) THEN
      HRIS_TRAVEL_SETTLEMENT_EXCESS(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),V_BRANCH_CODE,V_CREATED_BY,TRUNC(SYSDATE),V_DR_ACC_CODE,V_CR_ACC_CODE,V_EXCESS_CR_ACC_CODE,V_SETTLEMENT_AMOUNT,V_SETTLEMENT_AMOUNT-V_REQUESTED_AMOUNT,'TEST','E'||V_EMPLOYEE_ID,V_VOUCHER_NO);
    END IF;
    IF (LOWER(V_REQUEST_TYPE) ='ep') AND (V_REQUESTED_AMOUNT > V_SETTLEMENT_AMOUNT) THEN
      HRIS_TRAVEL_SETTLEMENT_LESS(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),V_BRANCH_CODE,V_CREATED_BY,TRUNC(SYSDATE),V_DR_ACC_CODE,V_LESS_DR_ACC_CODE,V_CR_ACC_CODE,V_SETTLEMENT_AMOUNT,V_REQUESTED_AMOUNT-V_SETTLEMENT_AMOUNT,'TEST','E'||V_EMPLOYEE_ID,V_VOUCHER_NO);
    END IF;
    UPDATE HRIS_EMPLOYEE_TRAVEL_REQUEST
    SET VOUCHER_NO  = V_VOUCHER_NO
    WHERE TRAVEL_ID = P_TRAVEL_ID;
  END IF;
  --
END;