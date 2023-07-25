BEGIN
  UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
  SET BALANCE     =TOTAL_DAYS
  WHERE LEAVE_ID IN
    (SELECT LEAVE_ID FROM HRIS_LEAVE_MASTER_SETUP WHERE IS_MONTHLY='Y'
    );
  FOR leave IN
  (SELECT R.EMPLOYEE_ID,
    R.LEAVE_ID,
    SUM(R.NO_OF_DAYS) AS TOTAL_NO_OF_DAYS
  FROM HRIS_EMPLOYEE_LEAVE_REQUEST R
  JOIN HRIS_LEAVE_MASTER_SETUP L
  ON (R.LEAVE_ID = L.LEAVE_ID),
    (SELECT *
    FROM HRIS_MONTH_CODE
    WHERE TRUNC(SYSDATE) BETWEEN FROM_DATE AND TO_DATE
    ) M
  WHERE R.STATUS   = 'AP'
  AND L.IS_MONTHLY = 'Y'
  AND R.START_DATE BETWEEN M.FROM_DATE AND M.TO_DATE
  GROUP BY R.EMPLOYEE_ID,
    R.LEAVE_ID
  )
  LOOP
    UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
    SET BALANCE       = TOTAL_DAYS - leave.TOTAL_NO_OF_DAYS
    WHERE EMPLOYEE_ID = leave.EMPLOYEE_ID
    AND LEAVE_ID      = leave.LEAVE_ID;
  END LOOP;
END;
