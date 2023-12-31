create or replace PROCEDURE HRIS_MIG_POSITION
AS
V_POSITION_ID NUMBER;
V_STATUS CHAR(1 BYTE);
BEGIN

FOR V_POSITION_LIST IN (SELECT * FROM HR_GRADE_CODE ORDER BY GRADE_CODE)
LOOP


BEGIN
SELECT POSITION_ID INTO V_POSITION_ID FROM HRIS_POSITIONS WHERE POSITION_ID=V_POSITION_LIST.GRADE_CODE;
EXCEPTION
  WHEN NO_DATA_FOUND THEN
BEGIN
DBMS_OUTPUT.PUT_LINE('TO BE INSERTED');
IF(V_POSITION_LIST.DELETED_FLAG='N') THEN
V_STATUS:='E';
ELSE
V_STATUS:='D';
END IF;

INSERT INTO HRIS_POSITIONS
(POSITION_ID,POSITION_NAME,COMPANY_ID,REMARKS,CREATED_DT,STATUS)
VALUES
(V_POSITION_LIST.GRADE_CODE,
V_POSITION_LIST.GRADE_EDESC,
V_POSITION_LIST.COMPANY_CODE,
V_POSITION_LIST.EREMARKS,
TRUNC(SYSDATE),
V_STATUS
);


END;
END;



END LOOP;
END;