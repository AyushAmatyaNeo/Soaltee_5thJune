ALTER TABLE HRIS_APPRAISAL_STATUS
ADD (
REVIEW_PERIOD     VARCHAR2(255 BYTE),
PREVIOUS_REVIEW_PERIOD    VARCHAR2(255 BYTE),
PREVIOUS_RATING         VARCHAR2(255 BYTE)
);

ALTER TABLE HRIS_APPRAISAL_KPI MODIFY SUCCESS_CRITERIA VARCHAR2(4000 BYTE) NULL

ALTER TABLE HRIS_APPRAISAL_SETUP 
ADD HR_FEEDBACK_ENABLE CHAR(1 BYTE) CHECK (HR_FEEDBACK_ENABLE IN ('Y','N'));

ALTER TABLE HRIS_APPRAISAL_STATUS
ADD HR_FEEDBACK VARCHAR2(255 BYTE)

ALTER TABLE HRIS_APPRAISAL_ASSIGN
ADD SUPER_REVIEWER_ID NUMBER(7,0);

ALTER TABLE HRIS_APPRAISAL_ASSIGN ADD CONSTRAINT FK_APP_ASN_EMP_EMP_ID FOREIGN KEY(SUPER_REVIEWER_ID) REFERENCES
HRIS_EMPLOYEES(EMPLOYEE_ID);

INSERT
INTO HRIS_APPRAISAL_STAGE
  (
    STAGE_ID,
    STAGE_CODE,
    STAGE_EDESC,
    STAGE_NDESC,
    START_DATE,
    END_DATE,
    ORDER_NO,
    COMPANY_ID,
    BRANCH_ID,
    CREATED_BY,
    CREATED_DATE,
    MODIFIED_BY,
    MODIFIED_DATE,
    CHECKED,
    APPROVED_BY,
    APPROVED_DATE,
    APPROVED,
    STATUS,
    REMARKS
  )
  VALUES
  (
    8,
    'SK008',
    'Super Reviewer Comment',
    NULL,
    NULL,
    NULL,
    4,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'N',
    NULL,
    NULL,
    'N',
    'E',
    NULL
  );

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
    323,
    'Appraisal Final Review',
    5,
    NULL,
    'appraisal-final-review',
    'E',
      TRUNC(SYSDATE),
    NULL,
    'fa fa-pencil',
    'index',
    18,
    NULL,
    NULL,
    'Y'
    );
    
    
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
    324,
    'view',
    323,
    NULL,
    'appraisal-final-review',
    'E',
      TRUNC(SYSDATE),
    NULL,
    'fa fa-pencil',
    'view',
    1,
    NULL,
    NULL,
    'N'
    );