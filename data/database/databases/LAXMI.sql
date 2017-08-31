ALTER TABLE HRIS_APPRAISAL_ASSIGN
ADD IS_EXECUTIVE CHAR(1 BYTE) NULL;

ALTER TABLE HRIS_APPRAISAL_ASSIGN
ADD CONSTRAINT CHECK_IS_EXECUTIVE CHECK (IS_EXECUTIVE IN ('Y','N'));
  
INSERT
INTO HRIS_MENUS
  (
    MENU_CODE,
    MENU_ID,
    MENU_NAME,
    PARENT_MENU,
    MENU_DESCRIPTION,
    ROUTE,
    STATUS,
    CREATED_DT,
    MODIFIED_DT,
    ICON_CLASS,
    ACTION,
    MENU_INDEX,
    CREATED_BY,
    MODIFIED_BY,
    IS_VISIBLE
  )
  VALUES
  (
  NULL,
    (select max(menu_id+1) from HRIS_MENUS),
    'Subordinate Review',
    6,
    NULL,
    'subordinatesReview',
    'E',
      TRUNC(SYSDATE),
    NULL,
    'fa fa-pencil',
    'index',
    20,
    NULL,
    NULL,
    'Y'
    );



CREATE TABLE BIRTHDAY_MESSAGES(
BIRTHDAY_ID         NUMBER(7,0) PRIMARY KEY,
FROM_EMPLOYEE       NUMBER(7,0) NOT NULL,
BIRTHDAY_EMPLOYEE   NUMBER(7,0) NOT NULL,
BIRTHDAY_MESSAGE             VARCHAR(500) NOT NULL,
CREATED_DATE DATE NOT NULL,
MODIFIED_DATE DATE NULL,
STATUS CHAR(1 BYTE) NOT NULL CHECK (STATUS IN ('E','D'))
);


ALTER TABLE BIRTHDAY_MESSAGES
ADD CONSTRAINT FK_BIRTH_FROM_EMP FOREIGN KEY(FROM_EMPLOYEE) REFERENCES
HRIS_EMPLOYEES(EMPLOYEE_ID);

ALTER TABLE BIRTHDAY_MESSAGES
ADD CONSTRAINT FK_BIRTH_DAY_EMP FOREIGN KEY(BIRTHDAY_EMPLOYEE) REFERENCES
HRIS_EMPLOYEES(EMPLOYEE_ID);


ALTER TABLE HRIS_USERS
MODIFY PASSWORD VARCHAR2(64);

ALTER TABLE HRIS_USERS
ADD IS_LOCKED CHAR(1 BYTE) DEFAULT ('Y') NOT NULL;