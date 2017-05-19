
ALTER TABLE HRIS_ROLE_PERMISSIONS DROP CONSTRAINT ROLE_PERM_PK;

ALTER TABLE HRIS_ROLE_PERMISSIONS ADD CONSTRAINT ROLE_MENU_UNQ UNIQUE(MENU_ID,ROLE_ID);


ALTER TABLE HRIS_JOB_HISTORY ADD FROM_COMPANY_ID NUMBER(7,0) ;

ALTER TABLE HRIS_JOB_HISTORY ADD TO_COMPANY_ID NUMBER(7,0) ;

ALTER TABLE HRIS_JOB_HISTORY ADD CONSTRAINT JOB_HIST_COMP_FK_1 FOREIGN KEY(FROM_COMPANY_ID) REFERENCES HRIS_COMPANY(COMPANY_ID);

ALTER TABLE HRIS_JOB_HISTORY ADD CONSTRAINT JOB_HIST_COMP_FK_2 FOREIGN KEY(TO_COMPANY_ID) REFERENCES HRIS_COMPANY(COMPANY_ID);