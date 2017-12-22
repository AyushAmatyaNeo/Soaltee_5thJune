BEGIN
  FOR mv IN
  (SELECT *
  FROM
    (SELECT EMPLOYEE_ID,
      FISCAL_YEAR_ID,
      MONTH_ID,
      ROW_NUMBER() OVER (PARTITION BY EMPLOYEE_ID ORDER BY EMPLOYEE_ID,FISCAL_YEAR_ID ) R
    FROM HRIS_FLAT_VALUE_DETAIL
    )
  WHERE R!=1
  )
  LOOP
  
  DELETE FROM HRIS_FLAT_VALUE_DETAIL WHERE EMPLOYEE_ID = mv.EMPLOYEE_ID AND FISCAL_YEAR_ID = mv.FISCAL_YEAR_ID AND MONTH_ID = mv.MONTH_ID;
  
  END LOOP;
END;


BEGIN
  FOR u IN
  (SELECT U.*
  FROM HRIS_USERS U
  JOIN HRIS_EMPLOYEES E
  ON (U.EMPLOYEE_ID  = E.EMPLOYEE_ID)
  WHERE U.USER_NAME IN
    (SELECT USER_NAME FROM HRIS_USERS GROUP BY USER_NAME HAVING COUNT(*) > 1
    )
  AND E.STATUS= 'D'
  ORDER BY USER_NAME
  )
  LOOP
    DELETE FROM HRIS_USERS WHERE USER_ID= u.USER_ID;
  END LOOP;
END;
--
SELECT USER_NAME FROM HRIS_USERS GROUP BY USER_NAME HAVING COUNT(*) > 1;


SELECT * FROM HRIS_USERS ORDER BY CREATED_DT DESC;

UPDATE HRIS_USERS SET ROLE_ID = 3 WHERE CREATED_DT = '14-AUG-17';


-- JGI RECEPTION SHIFT QUERIES

SELECT * FROM HRIS_EMPLOYEES WHERE LOWER(FIRST_NAME)  LIKE LOWER('%NAJINA%');
--
SELECT * FROM HRIS_EMPLOYEES WHERE LOWER(FIRST_NAME)  LIKE LOWER('%SUJA%');
--
SELECT * FROM HRIS_EMPLOYEES WHERE LOWER(FIRST_NAME)  LIKE LOWER('%SABIN%');
--
SELECT USER_NAME,FN_DECRYPT_PASSWORD(PASSWORD) FROM HRIS_USERS WHERE EMPLOYEE_ID=700342;

SELECT * FROM HRIS_SHIFTS;

--1,4,3

--700378 |SUJASMI | sujasmi_shrestha_maleku | 9849100048s
--700291 |NAJINA | najina_maharjan | rameshbishnu


DESC HRIS_EMPLOYEE_SHIFTS;


INSERT INTO HRIS_EMPLOYEE_SHIFTS VALUES(700378,4,'16-JUL-17','01-JAN-19');
--
SELECT * FROM HRIS_EMPLOYEE_SHIFTS;



DELETE  FROM HRIS_EMPLOYEE_SHIFTS;

DELETE FROM HRIS_ATTENDANCE_DETAIL WHERE EMPLOYEE_ID = 700291;

BEGIN
  HRIS_REATTENDANCE('16-JUL-17',700291);
END;
--

UPDATE HRIS_SHIFTS SET EARLY_OUT = 5 WHERE SHIFT_ID=3;

--


SELECT SHIFT_ID,LATE_IN,EARLY_OUT FROM HRIS_SHIFTS;

UPDATE HRIS_SHIFTS SET EARLY_OUT = EARLY_OUT/60 WHERE SHIFT_ID = 7;



-- SELECT AD.EMPLOYEE_ID,
  E.FULL_NAME                             AS EMPLOYEE_NAME,
  TO_CHAR(AD.ATTENDANCE_DT,'DD-MON-YYYY') AS ATTENDANCE_DATE,
  TO_CHAR(AD.IN_TIME,'HH:MI AM')          AS IN_TIME,
  TO_CHAR(AD.OUT_TIME,'HH:MI AM')         AS OUT_TIME,
  LATE_STATUS_DESC(AD.LATE_STATUS)        AS LATE_STATUS_DETAIL,
  AD.SHIFT_ID,
  S.SHIFT_ENAME AS SHIFT_NAME
FROM HRIS_ATTENDANCE_DETAIL AD
JOIN HRIS_EMPLOYEES E
ON (AD.EMPLOYEE_ID =E.EMPLOYEE_ID)
JOIN HRIS_SHIFTS S
ON (AD.SHIFT_ID      =S.SHIFT_ID)
WHERE AD.EMPLOYEE_ID = 700291
ORDER BY AD.ATTENDANCE_DT ASC;
--
SELECT DISTINCT ES.SHIFT_ID,
  S.SHIFT_ENAME                    AS SHIFT_NAME,
  TO_CHAR(S.START_TIME,'HH:MI AM') AS IN_TIME,
  TO_CHAR(S.END_TIME,'HH:MI AM')   AS OUT_TIME
FROM HRIS_EMPLOYEE_SHIFTS ES
JOIN HRIS_SHIFTS S
ON (ES.SHIFT_ID= S.SHIFT_ID);
--
SELECT HRIS_BEST_CASE_SHIFT(700291,TO_DATE('17-JUL-2017','DD-MON-YYYY'))
FROM DUAL;
--
SELECT (AD.OUT_TIME-TRUNC(AD.OUT_TIME))-(AD.IN_TIME-TRUNC(AD.IN_TIME))
FROM HRIS_ATTENDANCE_DETAIL AD
WHERE AD.EMPLOYEE_ID= 700291;

-- 

DECLARE
  V_COUNTER NUMBER;
BEGIN
  SELECT MAX(ID)
  INTO V_COUNTER
  FROM HRIS_EMPLOYEE_SHIFT_ASSIGN ;
  FOR employee IN
  (SELECT * FROM HRIS_EMPLOYEES WHERE STATUS ='E' AND COMPANY_ID =4
  )
  LOOP
    V_COUNTER:=V_COUNTER+1;
    INSERT
    INTO HRIS_EMPLOYEE_SHIFT_ASSIGN VALUES
      (
        employee.EMPLOYEE_ID,
        17,
        TRUNC(SYSDATE),
        NULL,
        NULL,
        'E',
        700284,
        NULL,
        '25-NOV-17',
        '30-APR-18',
        V_COUNTER
      );
  END LOOP;
END;
/

DECLARE
  V_MONTH_COUNTER        NUMBER(2,0);
  V_FISCAL_MONTH_COUNTER NUMBER(2,0);
BEGIN
  FOR fyear IN
  (SELECT DISTINCT FISCAL_YEAR_ID FROM HRIS_MONTH_CODE
  )
  LOOP
    V_MONTH_COUNTER       :=4;
    V_FISCAL_MONTH_COUNTER:=1;
    FOR months IN
    (SELECT     *
    FROM HRIS_MONTH_CODE
    WHERE FISCAL_YEAR_ID = fyear.FISCAL_YEAR_ID
    ORDER BY FROM_DATE
    )
    LOOP
      UPDATE HRIS_MONTH_CODE
      SET MONTH_NO           =V_MONTH_COUNTER,
        FISCAL_YEAR_MONTH_NO =V_FISCAL_MONTH_COUNTER
      WHERE MONTH_ID         =months.MONTH_ID;
      V_MONTH_COUNTER       :=V_MONTH_COUNTER       +1;
      V_FISCAL_MONTH_COUNTER:=V_FISCAL_MONTH_COUNTER+1;
      IF(V_MONTH_COUNTER     =13) THEN
        V_MONTH_COUNTER     :=1;
      END IF;
    END LOOP;
  END LOOP;
END;
/