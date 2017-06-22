CREATE OR REPLACE PROCEDURE HRIS_ATTENDANCE_ITNEPAL(
    P_ATTENDANCE_DT DATE )
AS
  V_SHIFT_ID HRIS_SHIFTS.SHIFT_ID%TYPE;
  V_START_TIME HRIS_SHIFTS.START_TIME%TYPE ;
  V_END_TIME HRIS_SHIFTS.END_TIME%TYPE ;
  V_GRACE_START_TIME HRIS_SHIFTS.GRACE_START_TIME%TYPE;
  V_GRACE_END_TIME HRIS_SHIFTS.GRACE_END_TIME%TYPE ;
  V_LATE_IN HRIS_SHIFTS.LATE_IN%TYPE;
  V_EARLY_OUT HRIS_SHIFTS.EARLY_OUT%TYPE;
  V_LATE_START_TIME TIMESTAMP;
  V_EARLY_END_TIME  TIMESTAMP;
  V_IN_TIME HRIS_ATTENDANCE_DETAIL.IN_TIME%TYPE;
  V_OUT_TIME HRIS_ATTENDANCE_DETAIL.OUT_TIME%TYPE;
  V_LATE_STATUS HRIS_ATTENDANCE_DETAIL.LATE_STATUS%TYPE:='N';
  V_FORCE_ABSENT        NUMBER                                :=0;
  V_FROM_DATE           DATE;
  V_TO_DATE             DATE;
  V_HAS_ATTENDANCE_DATA NUMBER:=0;
  V_EMPLOYEE_ID         NUMBER;
  V_ADJUSTED_START_TIME HRIS_SHIFT_ADJUSTMENT.START_TIME%TYPE:=NULL;
  V_ADJUSTED_END_TIME HRIS_SHIFT_ADJUSTMENT.END_TIME%TYPE    :=NULL;
  CURSOR CUR_EMPLOYEE
  IS
    SELECT EMPLOYEE_ID
    FROM HRIS_ATTENDANCE_DETAIL
    WHERE ATTENDANCE_DT =TRUNC(P_ATTENDANCE_DT);
BEGIN
  BEGIN
    SELECT FROM_DATE,
      TO_DATE
    INTO V_FROM_DATE,
      V_TO_DATE
    FROM HRIS_MONTH_CODE
    WHERE TRUNC(P_ATTENDANCE_DT) BETWEEN TRUNC(FROM_DATE) AND TRUNC(TO_DATE);
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    RAISE_APPLICATION_ERROR(-20344, 'NO MONTH_CODE FOUND FOR THE DATE');
  END;
  OPEN CUR_EMPLOYEE;
  LOOP
    FETCH CUR_EMPLOYEE INTO V_EMPLOYEE_ID;
    EXIT
  WHEN CUR_EMPLOYEE%NOTFOUND;
    BEGIN
      BEGIN
        SELECT IN_TIME ,
          OUT_TIME,
          SHIFT_ID
        INTO V_IN_TIME,
          V_OUT_TIME,
          V_SHIFT_ID
        FROM HRIS_ATTENDANCE_DETAIL
        WHERE EMPLOYEE_ID = V_EMPLOYEE_ID
        AND ATTENDANCE_DT =TRUNC(P_ATTENDANCE_DT);
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20344,'NO DATA FOUND FOR EMPLOYEE => '|| V_EMPLOYEE_ID || 'ON DATE => '||TRUNC(P_ATTENDANCE_DT));
      END;
      BEGIN
        SELECT S.START_TIME ,
          S.END_TIME ,
          S.GRACE_START_TIME,
          S.GRACE_END_TIME ,
          S.LATE_IN,
          S.EARLY_OUT,
          S.START_TIME+(.000694*NVL(S.LATE_IN,0)),
          S.END_TIME  -(.000694*NVL(S.EARLY_OUT,0))
        INTO V_START_TIME,
          V_END_TIME,
          V_GRACE_START_TIME,
          V_GRACE_END_TIME,
          V_LATE_IN,
          V_EARLY_OUT,
          V_LATE_START_TIME,
          V_EARLY_END_TIME
        FROM HRIS_SHIFTS S
        WHERE S.SHIFT_ID=V_SHIFT_ID
        AND (TRUNC(P_ATTENDANCE_DT) BETWEEN TRUNC(S.START_DATE) AND TRUNC(S.END_DATE))
        AND S.CURRENT_SHIFT = 'Y'
        AND S.STATUS        = 'E' ;
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20344, 'SHIFT WITH SHIFT_ID => '|| V_SHIFT_ID ||' NOT FOUND.');
      END;
      IF V_IN_TIME IS NULL THEN
        CONTINUE;
      END IF;
      --   CHECK FOR ADJUSTED SHIFT
      BEGIN
        SELECT SA.START_TIME,
          SA.END_TIME
        INTO V_ADJUSTED_START_TIME,
          V_ADJUSTED_END_TIME
        FROM HRIS_SHIFT_ADJUSTMENT SA
        JOIN HRIS_EMPLOYEE_SHIFT_ADJUSTMENT ESA
        ON (SA.ADJUSTMENT_ID=ESA.ADJUSTMENT_ID)
        WHERE (TRUNC(P_ATTENDANCE_DT) BETWEEN TRUNC(SA.ADJUSTMENT_START_DATE) AND TRUNC(SA.ADJUSTMENT_END_DATE) )
        AND ESA.EMPLOYEE_ID =V_EMPLOYEE_ID;
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
        DBMS_OUTPUT.PUT_LINE('NO ADJUSTMENT FOUND FOR EMPLOYEE =>'|| V_EMPLOYEE_ID || 'ON THE DATE'||P_ATTENDANCE_DT);
      END;
      IF(V_ADJUSTED_START_TIME IS NOT NULL) THEN
        V_START_TIME           :=V_ADJUSTED_START_TIME;
        V_LATE_START_TIME      := V_START_TIME+(.000694*NVL(V_LATE_IN+1,0));
      END IF;
      IF(V_ADJUSTED_END_TIME IS NOT NULL) THEN
        V_END_TIME           :=V_ADJUSTED_END_TIME;
        V_EARLY_END_TIME     := V_END_TIME -(.000694*NVL(V_EARLY_OUT+1,0));
      END IF;
      --      END FOR CHECK FOR ADJUSTED_SHIFT
      IF (V_LATE_START_TIME-TRUNC(V_LATE_START_TIME))<(V_IN_TIME-TRUNC(V_IN_TIME)) THEN
        V_LATE_STATUS                               :='L';
      END IF;
      IF (V_EARLY_END_TIME-TRUNC(V_EARLY_END_TIME))>(V_OUT_TIME-TRUNC(V_OUT_TIME)) THEN
        V_LATE_STATUS                             :='E';
      END IF;
      IF ((V_LATE_START_TIME-TRUNC(V_LATE_START_TIME))<(V_IN_TIME-TRUNC(V_IN_TIME))) AND ((V_EARLY_END_TIME-TRUNC(V_EARLY_END_TIME))>(V_OUT_TIME-TRUNC(V_OUT_TIME))) THEN
        V_LATE_STATUS                                :='B';
      END IF;
      IF V_LATE_STATUS != 'N' THEN
        UPDATE HRIS_ATTENDANCE_DETAIL
        SET LATE_STATUS   =V_LATE_STATUS
        WHERE EMPLOYEE_ID = V_EMPLOYEE_ID
        AND ATTENDANCE_DT =TRUNC(P_ATTENDANCE_DT);
      END IF;
      V_LATE_STATUS:='N';
    END;
  END LOOP;
  CLOSE CUR_EMPLOYEE;
END;