(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, false);
        app.searchTable('wsReport', ['COMPANY_NAME', 'DEPARTMENT_NAME', 'FULL_NAME'], true);
//        app.pdfExport(
//                'wsReport',
//                {
//                    'COMPANY_NAME': 'Company',
//                    'DEPARTMENT_NAME': 'Department',
//                    'EMPLOYEE_ID': 'ID',
//                    'EMPLOYEE_CODE': 'Code',
//                    'FULL_NAME': 'Name',
//                    'PRESENT': 'Present',
//                    'ABSENT': 'Absent',
//                    'DAYOFF': 'Dayoff',
//                    'HOLIDAY': 'Holiday',
//                    'LEAVE': 'Leave',
//                    'PAID_LEAVE': 'Paid Leave',
//                    'UNPAID_LEAVE': 'Unpaid Leave',
//                    'OVERTIME_HOUR': 'Overtime Hour',
//                    'TRAVEL': 'Travel',
//                    'TRAINING': 'Training',
//                    'WORK_ON_HOLIDAY': 'Work on Holiday',
//                    'WORK_ON_DAYOFF': 'Work on Dayoff',
//                }
//        );

        var $wsReport = $('#wsReport');
        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate');
        var $nepaliFromDate = $('#nepaliFromDate');
        var $nepaliToDate = $('#nepaliToDate');
        var $search = $('#search');
        app.initializeKendoGrid($wsReport, [
            {field: "EMPLOYEE_CODE", title: "Code", width: 70, locked: true},
            {field: "FULL_NAME", title: "Name", width: 100, locked: true},
            {field: "DEPARTMENT_NAME", title: "Department", width: 100, locked: true},
            {field: "PRESENT", title: "Present", width: 70},
            {field: "ABSENT", title: "Absent", width: 70},
            {field: "DAYOFF", title: "Dayoff", width: 70},
            {
                title: "Leave",
                columns: [{
                        field: "PAID_LEAVE",
                        title: "PL",
                        width: 70
                    }, {
                        field: "UNPAID_LEAVE",
                        title: "UL",
                        width: 70
                    }]
            },
            {field: "HOLIDAY", title: "Holiday", width: 70},
            {field: "OVERTIME_HOUR", title: "Total OT", width: 70},
            {field: "TRAVEL", title: "Travel", width: 70},
            {field: "TRAINING", title: "Training", width: 70},
            {field: "WORK_ON_HOLIDAY", title: "WOH", width: 70},
            {field: "WORK_ON_DAYOFF", title: "WOD", width: 70},
            {field: "TOTAL_WORKED_HOUR", title: "Total Hour", width: 70},
        ],  null, null, null, 'Employee working summary report.xlsx');
        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            data['fromDate'] = $fromDate.val();
            data['toDate'] = $toDate.val();
            
            if(data['fromDate'] == '' || data['toDate'] == ''){
                app.showMessage( 'please select both dates', 'error');
                return; 
            }
            app.serverRequest(document.wsBetnDate, data).then(function (response) {
                if (response.success) {
                    app.renderKendoGrid($wsReport, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });


    });
})(window.jQuery, window.app);