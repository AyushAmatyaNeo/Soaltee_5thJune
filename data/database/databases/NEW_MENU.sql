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
    'Leave Report',
    148,
    NULL,
    'allreport',
    'E',
      TRUNC(SYSDATE),
    NULL,
    'fa fa-pencil',
    'leaveReport',
    6,
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
    (select max(menu_id+1) from HRIS_MENUS),
    'Hire&Exit Report',
    148,
    NULL,
    'allreport',
    'E',
      TRUNC(SYSDATE),
    NULL,
    'fa fa-pencil',
    'HireAndFireReport',
    7,
    NULL,
    NULL,
    'Y'
    );




insert into hris_menus
(
menu_id,
MENU_NAME,
PARENT_MENU,
route,
status,
created_dt,
icon_class,
action,
menu_index,
is_visible
)values
(
(select max(menu_id)+1 from hris_menus),
'Branch Wise Daily',
148,
'allreport',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'branchWiseDaily',
(select max(MENU_INDEX)+1 from hris_menus where parent_menu=148),
'Y'
);

DELETE
FROM HRIS_ROLE_PERMISSIONS
WHERE MENU_ID IN
  (SELECT MENU_ID
  FROM HRIS_MENUS
  WHERE LOWER(ROUTE) LIKE LOWER('%trainingapply%')
  );
DELETE
FROM HRIS_MENUS
WHERE MENU_ID IN
  (SELECT MENU_ID
  FROM HRIS_MENUS
  WHERE LOWER(ROUTE) LIKE LOWER('%trainingapply%')
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
    'nto1',
    (SELECT MAX(menu_id)+1 FROM hris_menus
    ),
    'News Type',
    9,
    'News Type',
    'news-type',
    'E',
    TRUNC(SYSDATE),
    NULL,
    'fa fa-pencil',
    'index',
    15,NULL,
    NULL,
    'Y'
  );

INSERT
INTO hris_menus
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
    (select max(menu_id)+1 from hris_menus),
    'Report Only',
    4,
    NULL,
    'attendancebyhr',
    'E',
    to_date('07-MAY-18','DD-MON-RR'),
    to_date('07-MAY-18','DD-MON-RR'),
    'fa fa-pencil',
    'report',
    (select max(MENU_INDEX)+1 from hris_menus where parent_menu=4),
    NULL,
    NULL,
    'Y'
  );

UPDATE hris_menus SET route='salarySheet' WHERE route='generate';
UPDATE HRIS_MENUS
SET MENU_NAME          ='Salary Sheet'
WHERE lower(MENU_NAME) =lower('generate') ;



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
    (SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS
    ),
    'Functional Level',
    1,
    NULL,
    'functionalLevels',
    'E',
    to_date('22-JAN-18','DD-MON-RR'),
    NULL,
    'fa fa-square-o',
    'index',
    100,
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
    (SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS
    ),
    'Functional Type',
    1,
    NULL,
    'functionalTypes',
    'E',
    to_date('22-JAN-18','DD-MON-RR'),
    to_date('22-JAN-18','DD-MON-RR'),
    'fa fa-square-o',
    'index',
    99,
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
    (SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS
    ),
    'Location',
    1,
    NULL,
    'location',
    'E',
    to_date('22-JAN-18','DD-MON-RR'),
    NULL,
    'fa fa-square-o',
    'index',
    89,
    NULL,
    NULL,
    'Y'
  );

BEGIN
    HRIS_INSERT_MENU('Overtime Report','overtime-report','index',280,4,'fa fa-list-alt','Y');
END;
/

BEGIN
  HRIS_INSERT_MENU('Leave Balance(monthly)','leavebalance','monthly',2 ,7,'fa fa-list-alt','Y');
END;
/

BEGIN
    HRIS_INSERT_MENU('Report with Location','attendancebyhr','attendanceReportWithLocation',4,12,'fa fa-list-alt','Y');
END;
/


BEGIN
    HRIS_INSERT_MENU('System Setting','system-setting','index',9,99,'fa fa-square-o','Y');
END;
/

BEGIN
    HRIS_INSERT_MENU('Attendance Log','AttendanceDevice','attendanceLog',337,99,'fa fa-square-o','Y');
END;
/

BEGIN
  HRIS_INSERT_MENU('Not Settled Report','travelStatus','settlement-report','104',5,'fa fa-star-o','Y');
END;
/

BEGIN
  HRIS_INSERT_MENU('Monthly Val Assign(Position)','monthlyValue','position-wise',36,3,'fa fa-file-text-o','Y');
END;
/
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
    364,
    'Overtime Report',
    280,
    NULL,
    'overtime-report',
    'E',
    to_date('09-MAY-18','DD-MON-RR'),
    NULL,
    'fa fa-list-alt',
    'index',
    4,
    NULL,
    NULL,
    'Y'
  );


--new menus from new fisacl year



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
    (select max(menu_id)+1 from hris_menus),
    'Leave Sub Bypass',
    301,
    NULL,
    'leaveSubBypass',
    'E',
    trunc(sysdate),
    NULL,
    'fa fa-list-alt',
    'index',
    8,
    NULL,
    NULL,
    'Y'
  );

-- do not insert it start
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
    (select max(menu_id)+1 from hris_menus),
    'Apply',
    (select menu_id from hris_menus where lower(menu_name) like 'training%' and parent_menu=302 and route='trainingStatus'),
    NULL,
    'trainingApply',
    'E',
    trunc(sysdate),
    NULL,
    'fa fa-pencil',
    'add',
    4,
    NULL,
    NULL,
    'Y'
  );
-- do not insert it end


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
    (select max(menu_id)+1 from hris_menus),
    'Leave Cancel To Approve',
    (select menu_id from hris_menus where lower(menu_name) like 'approval%'),
    NULL,
    'leaveapprove',
    'E',
    trunc(sysdate),
    NULL,
    'fa fa-pencil-square-o',
    'cancelList',
    1,
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
    (select max(menu_id)+1 from hris_menus),
    'view',
    (select menu_id from hris_menus where lower(menu_name) like 'leave cancel%'),
    NULL,
    'leaveapprove',
    'E',
    trunc(sysdate),
    NULL,
    'fa fa-pencil-square-o',
    'cancelView',
    1,
    NULL,
    NULL,
    'N'
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
    (select max(menu_id)+1 from hris_menus),
    'File Type',
    (select menu_id from hris_menus where lower(menu_name) like 'setup%' and parent_menu is null),
    NULL,
    'fileType',
    'E',
    trunc(sysdate),
    NULL,
    'fa fa-pencil-square-o',
    'index',
    (select max(menu_index)+1 from hris_menus where parent_menu=(select menu_id from hris_menus where lower(menu_name) like 'setup%'
and parent_menu is null)),
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
    (select max(menu_id)+1 from hris_menus),
    'ADD',
    (select menu_id from hris_menus where menu_name like 'File Type%'),
    NULL,
    'fileType',
    'E',
    trunc(sysdate),
    NULL,
    'fa fa-pencil-square-o',
    'add',
    1,
    NULL,
    NULL,
    'N'
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
    (select max(menu_id)+1 from hris_menus),
    'EDIT',
    (select menu_id from hris_menus where menu_name like 'File Type%'),
    NULL,
    'fileType',
    'E',
    trunc(sysdate),
    NULL,
    'fa fa-pencil-square-o',
    'edit',
    1,
    NULL,
    NULL,
    'N'
  );
