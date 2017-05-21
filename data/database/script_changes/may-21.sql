ALTER TABLE HRIS_EMPLOYEE_HOLIDAY ADD CONSTRAINT  UNQ_EMP_HOLIDAY UNIQUE(EMPLOYEE_ID,HOLIDAY_ID);


DROP TRIGGER UPDATE_EMPLOYEE_SERVICE;

CREATE OR REPLACE PROCEDURE HRIS_UPDATE_EMPLOYEE_SERVICE(
    P_TO_COMPANY_ID NUMBER,
    P_TO_BRANCH_ID NUMBER,
    P_TO_DEPARTMENT_ID NUMBER,
    P_TO_DESIGNATION_ID NUMBER,
    P_TO_POSITION_ID NUMBER,
    P_TO_SERVICE_TYPE_ID NUMBER,
    P_SERVICE_EVENT_TYPE_ID NUMBER,
    P_EMPLOYEE_ID NUMBER,
    P_START_DATE DATE)
AS
BEGIN
  DECLARE
    IS_LATEST           NUMBER:=0;
  BEGIN
    SELECT COUNT(*)
    INTO IS_LATEST
    FROM
      (SELECT MAX(START_DATE) AS MAX_START_DATE
      FROM HRIS_JOB_HISTORY
      WHERE EMPLOYEE_ID=700055
      GROUP BY EMPLOYEE_ID
      ) H
    WHERE H.MAX_START_DATE=P_START_DATE ;
    
    
    
    IF (IS_LATEST            >0 ) THEN
      UPDATE HRIS_EMPLOYEES
      SET BRANCH_ID          =P_TO_BRANCH_ID,
        DEPARTMENT_ID        =P_TO_DEPARTMENT_ID,
        DESIGNATION_ID       =P_TO_DESIGNATION_ID,
        POSITION_ID          =P_TO_POSITION_ID,
        SERVICE_TYPE_ID      =P_TO_SERVICE_TYPE_ID,
        SERVICE_EVENT_TYPE_ID=P_SERVICE_EVENT_TYPE_ID,
        COMPANY_ID           =P_TO_COMPANY_ID
      WHERE EMPLOYEE_ID      =P_EMPLOYEE_ID;
    END IF;
    IF (P_SERVICE_EVENT_TYPE_ID=2) THEN
      UPDATE HRIS_EMPLOYEES
      SET APP_BRANCH_ID          =P_TO_BRANCH_ID,
        APP_DEPARTMENT_ID        =P_TO_DEPARTMENT_ID,
        APP_DESIGNATION_ID       =P_TO_DESIGNATION_ID,
        APP_POSITION_ID          =P_TO_POSITION_ID,
        APP_SERVICE_TYPE_ID      =P_TO_SERVICE_TYPE_ID,
        APP_SERVICE_EVENT_TYPE_ID=P_SERVICE_EVENT_TYPE_ID,
        RETIRED_FLAG             ='N'
      WHERE EMPLOYEE_ID          =P_EMPLOYEE_ID;
    END IF;
    IF (P_SERVICE_EVENT_TYPE_ID=1 OR P_SERVICE_EVENT_TYPE_ID=3 OR P_SERVICE_EVENT_TYPE_ID=4) THEN
      UPDATE HRIS_EMPLOYEES SET RETIRED_FLAG='N' WHERE EMPLOYEE_ID=P_EMPLOYEE_ID;
    END IF;
    IF (P_SERVICE_EVENT_TYPE_ID=5 OR P_SERVICE_EVENT_TYPE_ID=8 OR P_SERVICE_EVENT_TYPE_ID=14) THEN
      UPDATE HRIS_EMPLOYEES SET RETIRED_FLAG='Y' WHERE EMPLOYEE_ID=P_EMPLOYEE_ID;
    END IF;
  END;
END HRIS_UPDATE_EMPLOYEE_SERVICE;
/