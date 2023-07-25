(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate'); 
        var $table = $('#table');
        var $search = $('#search');

        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, false);
        app.getServerDate().then(function (response) {
            $fromDate.val(response.data.serverDate);
            $('#nepaliFromDate').val(nepaliDatePickerExt.fromEnglishToNepali(response.data.serverDate));
        });

        var columns = [
            {field: "EMPLOYEE_CODE", title: "Code", locked: true, width: 80},
            {field: "FULL_NAME", title: "Full Name", locked: true, width: 200},
            {field: "ATTENDANCE_DT", title: "Date", width: 200},
            {field: "IN_TIME", title: "In Time", width: 200},
            {field: "OUT_TIME", title: "Out Time", width: 200},
            {field: "TOTAL_WORKED_HOUR", title: "Total Hour", width: 200}
        ];

        function generateReport(reportData, columns_list) {
            $("#table").kendoGrid({
                toolbar: ["excel", "pdf"],
                excel: {
                    fileName: "In Out Report.xlsx",
                    filterable: true,
                    allPages: true
                },
                dataSource: {
                    data: reportData,
                    pageSize: 20,
                    group: {field: "FULL_NAME"},
                },
                height: 550,
                scrollable: true,
                sortable: true,
                groupable: true,
                filterable: true,
                pageable: {
                    refresh: true,
                    pageSizes: true,
                    buttonCount: 5
                },
                columns: columns_list
            });
        }      

        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            data['fromDate'] = $fromDate.val();
            data['toDate'] = $toDate.val();
            app.serverRequest(document.pullInOutTimeLink, data).then(function (response) {
                if (response.success) {
                    $table.empty();
                    generateReport(response.data, columns);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });  
        });

    });
})(window.jQuery, window.app);
