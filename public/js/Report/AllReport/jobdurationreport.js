(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
       
        var $employeeTable = $('#employeeTable');
        var $search = $('#search');
		
		var columns = [
			{field: "EMPLOYEE_CODE", title: "Code", locked: true, width: 80},
			{field: "FULL_NAME", title: "Full Name", locked: true, width: 110},
			{field: "FUNCTIONAL_TYPE_EDESC", title: "Functional Type", width: 120},
			{field: "DEPARTMENT_NAME", title: "Department", width: 100},
			{field: "DESIGNATION_TITLE", title: "Designation", width: 100},
			{field: "POSITION_NAME", title: "Position", width: 100},
			{field: "DOB", title: "BirthDate",  width: 100, format: "{0:dd-MM-yyyy}", type: "dateTime"},
			{field: "AGE", title: "Age", width: 130},
			{field: "DOJ", title: "JoinDate", width: 100, format: "{0:dd-MM-yyyy}", type: "dateTime"},
			{field: "SERVICE_DURATION", title: "Service Duration", width: 130},
			{field: "SERVICE_TYPE_NAME", title: "Service Type", width: 100},
			{field: "SALARY", title: "Salary", width: 100},
			{field: "ALLOWANCE", title: "Allowance", width: 100},
            {field: "F_ALLOWANCE", title: "Food Allowance", width: 100},
			{field: "GROSS", title: "Gross", width: 100}
		];
		
		function generateReport(reportData, columns_list) {
            $("#employeeTable").kendoGrid({
                toolbar: ["excel", "pdf"],
                excel: {
                    fileName: "Job Duration Report.xlsx",
                    filterable: true,
					allPages: true
                },
                dataSource: {
                    data: reportData,
                    schema: {
                        model: {
                            fields: {
                                DOJ: {
								  type: "date",
								  parse: function (e) {
									return new Date(e)
								  }
								},
								DOB: {
								  type: "date",
								  parse: function (e) {
									return new Date(e)
								  }
								},
                                SALARY: { type: "number"},
                                ALLOWANCE: { type: "number"},
                                GROSS: { type: "number"},
                                EMPLOYEE_CODE: { type: "number"}
                            }
                        }
                    },
                    pageSize: 20
                },
                height: 550,
                scrollable: true,
                sortable: true,
                groupable: true,
                filterable: true,
                pageable: {
                    input: true,
                    numeric: false
                },
                columns: columns_list
            });
        }      

        //app.initializeKendoGrid($employeeTable, columns, null, null, null, 'Job Duration Report.xlsx');

        app.searchTable('employeeTable', ['EMPLOYEE_CODE', 'FULL_NAME'], false);
  
        var map = {
            'EMPLOYEE_CODE': 'Employee Code',
            'FULL_NAME': 'Employee',
			'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
            'DEPARTMENT_NAME': 'Department',
            'DESIGNATION_TITLE': 'Designation',
			 'POSITION_NAME': 'Position',
            'DOB': 'BirthDate',
			 'AGE': 'Age',
            'DOJ': 'JoinDate',
            'SERVICE_DURATION': 'Service Duration',
            'SERVICE_TYPE_NAME': 'Service Type',
            'SALARY': 'Basic',
            'ALLOWANCE': 'Allowance',
            'F_ALLOWANCE': 'Allowance',
            'GROSS': 'Gross'
        }; 

        var exportColumnParameters = [];
        for(var key in map){
            exportColumnParameters.push({'VALUES' : key, 'COLUMNS' : map[key]});
        }
        var $exparams = $('#exparamsId');
        app.populateSelect($exparams, exportColumnParameters, 'VALUES', 'COLUMNS');
        $exparams.val(Object.keys(map));

        $('#excelExport').on('click', function () {
            var fc = app.filterExportColumns($("#exparamsId").val(), map);
            app.excelExport($employeeTable, fc, 'Job Duration Report.xlsx');
        });
        $('#pdfExport').on('click', function () {
            var fc = app.filterExportColumns($("#exparamsId").val(), map);
            app.exportToPDF($employeeTable, fc, 'Job Duration Report.pdf');
        });
       
        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            app.serverRequest(document.pullEmployeeListForEmployeeTableLink, data).then(function (response) {
                if (response.success) {
                    $employeeTable.empty();
                    var columnParameters = $exparams.val();
                    var columns_bk = [];
                    for(let i = 0; i < columns.length; i++){
                        for(let j = 0; j < columnParameters.length; j++){
                            if(columns[i].field == columnParameters[j]){
                                columns_bk.push(columns[i]);
                            }
                        }
                    }
					$employeeTable.empty();
					generateReport(response.data, columns_bk);
                    //app.initializeKendoGrid($employeeTable, columns_bk, null, null, null, 'Job Duration Report.xlsx');
                    //app.renderKendoGrid($employeeTable, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });  
        });
    }); 
})(window.jQuery, window.app);