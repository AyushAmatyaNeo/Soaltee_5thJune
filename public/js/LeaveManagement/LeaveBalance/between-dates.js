(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $table = $("#table");
        var $search = $('#search');
        var map;
        
        var $leave = $('#leaveId');
        var leaveList = document.leaves;
        app.populateSelect($leave, document.leaves, 'LEAVE_ID', 'LEAVE_ENAME');


        function reinitializeKendo(optionalColumns) {
            console.log(optionalColumns);
            var columns = [
                {field: "EMPLOYEE_CODE", title: "Code", width: 80, locked: true},
                {field: "FULL_NAME", title: "Employee", width: 120, locked: true},
                {field: "DEPARTMENT_NAME", title: "Department", width: 120, locked: true},
                {field: "DESIGNATION_TITLE", title: "Designation", width: 120, locked: true},
                {field: "POSITION_NAME", title: "Position", width: 120, locked: true},
            ];
             map = {
                'EMPLOYEE_CODE': 'Code',
                'FULL_NAME': 'Name',
                'DEPARTMENT_NAME': 'Department',
                'DESIGNATION_TITLE': 'Designation',
                'POSITION_NAME': 'Position',
                'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
            };
            var columnsList;

            if (optionalColumns != null) {
                $.each(optionalColumns, function (i, val) {

                    columnsList = {
                        title: leaveList[i]['LEAVE_ENAME'],
                        columns: [
                            {
                                title: 'TAKEN',
                                field: 'L' + val + '_' + 'TAKEN',
                                width: 100
                            },
                            {
                                title: 'BALANCE',
                                field: 'L' + val + '_' + 'BALANCE',
                                width: 100
                            },
                        ]
                    };
                    map['L' + val + '_TAKEN'] = leaveList[i]['LEAVE_ENAME'] + 'TAKEN';
                    map['L' + val + '_BALANCE'] = leaveList[i]['LEAVE_ENAME'] + 'BALANCE';
                    columns.push(columnsList);

                });
            } else {

                for (var i in leaveList) {

                    columnsList = {
                        title: leaveList[i]['LEAVE_ENAME'],
                        columns: [
                            {
                                title: 'TAKEN',
                                field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'TAKEN',
                                width: 100
                            },
                            {
                                title: 'BALANCE',
                                field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'BALANCE',
                                width: 100
                            },
                        ]
                    };
                    map['L' + leaveList[i]['LEAVE_ID'] + '_TAKEN'] = leaveList[i]['LEAVE_ENAME'] + ' TAKEN';
                    map['L' + leaveList[i]['LEAVE_ID'] + '_BALANCE'] = leaveList[i]['LEAVE_ENAME'] + ' BALANCE';
                    columns.push(columnsList);

                }

            }
            app.initializeKendoGrid($table, columns);

        }
        app.searchTable($table, ['EMPLOYEE_CODE', 'FULL_NAME']);

        $search.on('click', function () {
            var fromDate = $('#fromDate').val();
            var toDate = $('#toDate').val();

            var leaveColumns = $leave.val();
            $table.empty();

            if (fromDate == -1 || fromDate == '') {
                app.errorMessage("Please select From Date", "Notification");
                return;
            }
            if (toDate == -1 || toDate == '') {
                return;
                app.errorMessage("Please select To Date", "Notification");
            }
            reinitializeKendo(leaveColumns);

            var q = document.searchManager.getSearchValues();
            q['fromDate'] = fromDate;
            q['toDate'] = toDate;
            App.blockUI({target: "#hris-page-content"});
            app.pullDataById(document.pullBalanceBetweenDates, q).then(function (success) {
                App.unblockUI("#hris-page-content");
                console.log(success.data);
                app.renderKendoGrid($table, success.data);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });

        $('#excelExport').on("click", function () {
            app.excelExport($table, map, "Employee Leave Balance Report.xlsx");
        });
        $('#pdfExport').on("click", function () {
            app.exportToPDF($table, map, "Employee Leave Balance Report.pdf", 'A2');
        });

    });
})(window.jQuery, window.app);
