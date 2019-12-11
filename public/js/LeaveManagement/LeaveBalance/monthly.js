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
            'EMPLOYEE_ID': 'Id',
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Name'
        };

        var leaveList = document.leaves;
        for (var i in leaveList) {
            columns.push({
                title: leaveList[i]['LEAVE_ENAME'],
                columns: [
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

        app.initializeKendoGrid($table, columns, null, null, null, 'Monthly Leave Balance Report.xlsx');
        app.searchTable($table, ['EMPLOYEE_ID', 'EMPLOYEE_CODE']);
        var months = null;
        var $year = $('#leaveYear');
        var $month = $('#leaveMonth');
        app.setLeaveMonth($year, $month, function (yearList, monthList, currentMonth) {
            months = monthList;
        });

        var onSearch = function (value) {
            if (value == null) {
                return;
            }
            var selectedMonthList = months.filter(function (item) {
                return item['MONTH_ID'] === value;
            });
            if (selectedMonthList.length <= 0) {
                return;
            }
            var q = document.searchManager.getSearchValues();
            q['leaveYearMonthNo'] = selectedMonthList[0]['LEAVE_YEAR_MONTH_NO'];
            
            console.log(q);
            
            app.pullDataById('', q).then(function (success) {
                app.renderKendoGrid($table, success.data);
            }, function (failure) {
            });

        };
        $month.on('change', function () {
            var value = $(this).val();
            onSearch(value);

        });

        $search.on('click', function () {
            onSearch($month.val());
        });

        $('#excelExport').on("click", function () {
            app.excelExport($table, map, "Employee Leave Balance Report.xlsx");
        });
        $('#pdfExport').on("click", function () {
            app.exportToPDF($table, map, "Employee Leave Balance Report.pdf", 'A2');
        });

    });
})(window.jQuery, window.app);
