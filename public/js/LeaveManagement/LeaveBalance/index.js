(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        var $table = $("#table");
        var $search = $('#search');
        var columns = [
            {field: "EMPLOYEE_CODE", title: "Code", width: 150, locked: true},
            {field: "FULL_NAME", title: "Employee", width: 150, locked: true},
        ];
        var map = {
            'EMPLOYEE_CODE': 'Code',
            'EMPLOYEE_ID': 'Id', 
            'FULL_NAME': 'Name'
        };

        var leaveList = document.leaves;
        for (var i in leaveList) {
            columns.push({
                title: leaveList[i]['LEAVE_ENAME'],
                columns: [
                    {
                        title: 'Previous',
                        field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'PREVIOUS_YEAR_BAL',
                        width: 100
                    },
                    {
                        title: 'Total',
                        field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'TOTAL',
                        width: 100
                    },
                    {
                        title: 'Taken',
                        field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'TAKEN',
                        width: 100
                    },
                    {
                        title: 'Balance',
                        field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'BALANCE',
                        width: 100
                    }
                ]
            });
            map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'TOTAL'] = leaveList[i]['LEAVE_ENAME'] + '(Total)';
            map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'TAKEN'] = leaveList[i]['LEAVE_ENAME'] + '(Taken)';
            map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'BALANCE'] = leaveList[i]['LEAVE_ENAME'] + '(Balance)';
        }

        app.initializeKendoGrid($table, columns);
        app.searchTable($table, ['EMPLOYEE_ID', 'EMPLOYEE_CODE']);

        $search.on('click', function () {
            var q = document.searchManager.getSearchValues();
            App.blockUI({target: "#hris-page-content"});
            app.pullDataById(document.pullLeaveBalanceDetailLink, q).then(function (success) {
                App.unblockUI("#hris-page-content");
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
