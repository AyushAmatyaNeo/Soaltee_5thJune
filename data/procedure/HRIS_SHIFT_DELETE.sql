CREATE OR REPLACE PROCEDURE HRIS_SHIFT_DELETE(
    P_ID HRIS_EMPLOYEE_SHIFT_ASSIGN.ID%TYPE )
AS
  V_OLD_START_DATE HRIS_EMPLOYEE_SHIFT_ASSIGN.START_DATE%TYPE;
  V_EMPLOYEE_ID HRIS_EMPLOYEE_SHIFT_ASSIGN.EMPLOYEE_ID%TYPE;
BEGIN
  SELECT START_DATE,
    EMPLOYEE_ID
  INTO V_OLD_START_DATE,
    V_EMPLOYEE_ID
  FROM HRIS_EMPLOYEE_SHIFT_ASSIGN
  WHERE ID =P_ID;
  --
  DELETE FROM HRIS_EMPLOYEE_SHIFT_ASSIGN WHERE ID =P_ID;
  --
  IF(TRUNC(V_OLD_START_DATE) <= TRUNC(SYSDATE))THEN
    HRIS_REATTENDANCE(TRUNC(V_OLD_START_DATE),V_EMPLOYEE_ID);
    --    DECLARE
    --      jobno NUMERIC;
    --    BEGIN
    --      dbms_job.submit(jobno, 'BEGIN HRIS_REATTENDANCE('''||TRUNC(V_OLD_START_DATE)||''','||V_EMPLOYEE_ID||'); END;', SYSTIMESTAMP + INTERVAL '10' SECOND, NULL);
    --      COMMIT;
    --    END;
  END IF;
END;