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
    328,
    'Attendnace Report',
    5,
    NULL,
    'managerReport',
    'E',
    TRUNC(SYSDATE),
    NULL,
    'fa fa-pencil',
    'index',
    19,
    NULL,
    NULL,
    'Y'
  ) ; 

ALTER TABLE HRIS_EMPLOYEES
ADD IS_CEO CHAR (1 BYTE) CHECK (IS_CEO IN ('Y','N'));

ALTER TABLE HRIS_EMPLOYEES
ADD IS_HR CHAR (1 BYTE) CHECK (IS_HR IN ('Y','N'));


ALTER TABLE HRIS_LEAVE_MASTER_SETUP ADD ASSIGN_ON_EMPLOYEE_SETUP CHAR(1 BYTE) DEFAULT 'Y'NOT NULL CHECK (ASSIGN_ON_EMPLOYEE_SETUP IN ('Y','N'));
ALTER TABLE HRIS_LEAVE_MASTER_SETUP ADD IS_PRODATA_BASIS CHAR(1 BYTE) DEFAULT 'Y'NOT NULL CHECK (IS_PRODATA_BASIS IN ('Y','N'));

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
    'EMP',
    325,
    'Edit My Profile',
    6,
    NULL,
    'employee',
    'E',
    to_date('13-JUL-17','DD-MON-RR'),
    NULL,
    'fa fa-wrench',
    'edit',
    44,
    NULL,
    NULL,
    'N'
  );
INSERT INTO HRIS_ROLE_PERMISSIONS
  (MENU_ID,ROLE_ID,STATUS
  )
SELECT (325),ROLE_ID, ('E') FROM HRIS_ROLES;
