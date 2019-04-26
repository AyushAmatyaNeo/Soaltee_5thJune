(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('Select').select2();

        var monthList = null;
        var $fiscalYear = $('#fiscalYearId');
        var $month = $('#monthId');
        var $reportType = $('#reportType');
        var $otVariable = $('#otVariable');
        var $extraFields = $('#extraFields');
        var $table = $('#table');
        var map = {};
         var exportType = {
            "ACCOUNT_NO": "STRING",
        };

        var extraFieldsList = [
            {ID: "DESIGNATION_TITLE", VALUE: "Designation"},
            {ID: "DEPARTMENT_NAME", VALUE: "Department"},
            {ID: "FUNCTIONAL_TYPE_EDESC", VALUE: "Functional Type"},
            {ID: "ACCOUNT_NO", VALUE: "Account No"},
            {ID: "BIRTH_DATE", VALUE: "Birth Date"},
            {ID: "JOIN_DATE", VALUE: "Join Date"}
        ];





        app.setFiscalMonth($fiscalYear, $month, function (years, months, currentMonth) {
            monthList = months;
        });

//        console.log(document.datas.otVariables);

        app.populateSelect($otVariable, document.datas.otVariables, 'VARIANCE_ID', 'VARIANCE_NAME', '---', '');
        app.populateSelect($extraFields, extraFieldsList, 'ID', 'VALUE', '---', '');


        var initKendoGrid = function (defaultColumns, otVariables, extraVariable) {
            $table.empty();
//            console.log(defaultColumns);
            map = {
                'EMPLOYEE_CODE': 'Employee Code',
                'FULL_NAME': 'Employee',
                'POSITION_NAME': 'Position',
                'SERVICE_TYPE_NAME': 'Service',
            }

            var columns = [
                {field: "EMPLOYEE_CODE", title: "Code", width: 80, locked: true},
                {field: "FULL_NAME", title: "Employee", width: 120, locked: true},
                {field: "POSITION_NAME", title: "Position", width: 120, locked: true},
                {field: "SERVICE_TYPE_NAME", title: "Service", width: 120, locked: true},
            ];

            $.each(extraVariable, function (index, value) {
                for (var i in extraFieldsList) {
                    if (extraFieldsList[i]['ID'] == value) {
                        columns.push({
                            field: value,
                            title: extraFieldsList[i]['VALUE'],
                            width: 100
                        });
                        map[value] = extraFieldsList[i]['VALUE'];
                    }
                }
//                columns.push({
//                    field: value['VARIANCE'],
//                    title: value['VARIANCE_NAME'],
//                    width: 100
//                });
            });

            $.each(defaultColumns, function (index, value) {
                columns.push({
                    field: value['VARIANCE'],
                    title: value['VARIANCE_NAME'],
                    width: 100
                });
                map[value['VARIANCE']] = value['VARIANCE_NAME'];
            });

            $.each(otVariables, function (index, value) {
                for (var i in document.datas.otVariables) {
                    if (document.datas.otVariables[i]['VARIANCE_ID'] == value) {
                        columns.push({
                            field: 'V' + value,
                            title: document.datas.otVariables[i]['VARIANCE_NAME'],
                            width: 100
                        });
                        map['V' + value] = document.datas.otVariables[i]['VARIANCE_NAME'];
                    }
                }
            });

            console.log(map);
            app.initializeKendoGrid($table, columns);




        }






        $('#searchEmployeesBtn').on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['fiscalId'] = $fiscalYear.val();
            q['monthId'] = $month.val();
            q['extVar'] = $otVariable.val();
            q['extField'] = $extraFields.val();
            q['reportType'] = $reportType.val();
            console.log(q);

            app.serverRequest(document.pullGradeBasicLink, q).then(function (response) {
                if (response.success) {
//                    console.log(response);
                    initKendoGrid(response.columns, $otVariable.val(), $extraFields.val());
                    app.renderKendoGrid($table, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });


        });



        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'GradeBasicReport.xlsx',exportType);
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'GradeBasicReport.pdf');
        });






    });
})(window.jQuery, window.app);


