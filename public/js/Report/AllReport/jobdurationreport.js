(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
       
        var $employeeTable = $('#employeeTable');
        var $search = $('#search');
    

        app.initializeKendoGrid($employeeTable, [
            {field: "EMPLOYEE_CODE", title: "Code", locked: true, width: 80},
            {field: "FULL_NAME", title: "Full Name", locked: true, width: 110},
            {field: "FUNCTIONAL_TYPE_EDESC", title: "Functional Type", width: 120},
            {field: "DEPARTMENT_NAME", title: "Department", width: 100},
            {field: "DESIGNATION_TITLE", title: "Designation", width: 100},
            {field: "POSITION_NAME", title: "Position", width: 100},
            {field: "DOB", title: "BirthDate",  width: 100},
            {field: "AGE", title: "Age", width: 130},
            {field: "DOJ", title: "JoinDate", width: 100},
            {field: "SERVICE_DURATION", title: "Service Duration", width: 130},
            {field: "SERVICE_TYPE_NAME", title: "Service Type", width: 100},
            {field: "BASIC", title: "Basic", width: 100},
            {field: "GRADE", title: "Grade", width: 100},
            {field: "ALLOWANCE", title: "Allowance", width: 100},
            {field: "GROSS", title: "Gross", width: 100},
            /*{field: "LEVEL_NO", title: "Level", width: 150},
            {field: "LOCATION_EDESC", title: "Location", width: 150},
            {field: "FUNCTIONAL_TYPE_EDESC", title: "Functional Type", width: 150},
            {field: "FUNCTIONAL_LEVEL_EDESC", title: "Functional Level", width: 150}*/
        ], null, null, null, 'Job Duration Report.xlsx');

        app.searchTable('employeeTable', ['EMPLOYEE_CODE', 'FULL_NAME'], false);
  
        var map = {
            'EMPLOYEE_ID': 'Employee Id',
            'EMPLOYEE_CODE': 'Employee Code',
            'FULL_NAME': 'Employee',
            'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
            'DEPARTMENT_NAME': 'Department',
            'POSITION_NAME': 'Position',
            'BIRTH_DATE_AD': 'BirthDate',
            'AGE': 'Age',
            'JOIN_DATE_AD': 'JoinDate',
            'SERVICE_DURATION': 'Service Duration',
            'SERVICE_TYPE_NAME': 'Service Type',
            'BASIC': 'Basic',
            'GRADE': 'Grade',
            'ALLOWANCE': 'Alowance',
            'GROSS': 'Gross',
        }; 

        $('#excelExport').on('click', function () {
            app.excelExport($employeeTable, map, 'Birthday Employee List.xlsx');        
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($employeeTable, map, 'Birthday Employee List.pdf');
        });
       
        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            app.serverRequest(document.pullEmployeeListForEmployeeTableLink, data).then(function (response) {
                if (response.success) {
                    console.log(response);
                    app.renderKendoGrid($employeeTable, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });  
        });
        
//        $("#reset").on("click", function () {
//            $(".form-control").val("");
//            document.searchManager.reset();
//        });
    }); 
})(window.jQuery, window.app);