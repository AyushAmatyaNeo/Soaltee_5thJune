(function ($, app) {
    "use strict";
    $(document).ready(function () {
    	let $flatValue = $("#flatValue");
    	let $monthlyValue = $("#monthlyValue");
    	let $employeeIdBased = $("#employeeIdBased");
    	let $employeeCodeBased = $("#employeeCodeBased");
    	let $fiscalYearId = $("#fiscalYearId");
    	let $monthId = $("#monthId");
    	let $payHeads = $("#payHeads");
    	let $table = $("#table");
    	var excelData;
    	let typeFlag = 1;
        let basedOnFlag = 1;
    	$("select").select2();
    	$(".months").hide();
    	app.populateSelect($payHeads, document.flatValues, "FLAT_ID", "FLAT_EDESC", "Select One");
    	app.populateSelect($fiscalYearId, document.fiscalYears, "FISCAL_YEAR_ID", "FISCAL_YEAR_NAME", "Select Fiscal Year");
    	$("#flatValue").click(function(){
    		app.populateSelect($payHeads, document.flatValues, "FLAT_ID", "FLAT_EDESC", "Select One");
    		$(".months").hide();
    		typeFlag = 1;
    	});
    	$("#monthlyValue").click(function(){
    		app.populateSelect($payHeads, document.monthlyValues, "MTH_ID", "MTH_EDESC", "Select One");
    		$(".months").show();
    		typeFlag = 2;
    	});

        $("#employeeIdBased").click(function(){ basedOnFlag = 1; });
        $("#employeeCodeBased").click(function(){ basedOnFlag = 2; });

    	$fiscalYearId.change(function(){
    		var selectedYearMonthList = document.months.filter(function (item) {
                return item['FISCAL_YEAR_ID'] == $fiscalYearId.val();
            });
            app.populateSelect($monthId, selectedYearMonthList, 'MONTH_ID', 'MONTH_EDESC', 'Months');
    	});
    	var columns = [];
    	columns.push({field: "ID", title: "ID", width: 80});
    	columns.push({field: "NAME", title: "NAME", width: 120});
    	columns.push({field: "AMOUNT", title: "AMOUNT", width: 120});
    	app.initializeKendoGrid($table, columns);

    	$("#submit").on('click', function(){
            var valueType = $payHeads.val();
            var fileUploadedFlag = document.getElementById("excelImport").files.length;
            var fiscalYearId = $fiscalYearId.val();
            if(valueType == null || fileUploadedFlag == 0 || fiscalYearId == -1){
                app.showMessage('One or more input missing', 'warning');
                return;
            }
            if(typeFlag == 1){
            	app.serverRequest(document.updateFlatValuesLink, {data : excelData, fiscalYearId: $fiscalYearId.val(), flatValueId: valueType, basedOn: basedOnFlag}).then(function(){
	                app.showMessage('Operation successfull', 'success');
	            }, function (error) {
	                console.log(error);
	            });
            }
            if(typeFlag == 2){
            	if($monthId.val() == -1){
	                app.showMessage('Month not selected', 'warning');
	                return;
	            }
            	app.serverRequest(document.updateMonthlyValuesLink, {data : excelData, fiscalYearId: $fiscalYearId.val(), monthId: $monthId.val(), monthlyValueId: valueType, basedOn: basedOnFlag}).then(function(){
	                app.showMessage('Operation successfull', 'success');
	            }, function (error) {
	                console.log(error);
	            });
            }
    	});

      	$("#excelImport").change(function(evt){
            var selectedFile = evt.target.files[0];
            var reader = new FileReader();
            reader.onload = function(event) {
              var data = event.target.result;
              var workbook = XLSX.read(data, {
                  type: 'binary'
              });
              workbook.SheetNames.forEach(function(sheetName) {
                  var XL_row_object = XLSX.utils.sheet_to_row_object_array(workbook.Sheets[sheetName]);
                  var json_object = JSON.stringify(XL_row_object);
                  excelData = JSON.parse(json_object);
                  app.renderKendoGrid($table, excelData);
                });
            }
            reader.onerror = function(event) {
              console.error("File could not be read! Code " + event.target.error.code);
            };
            reader.readAsBinaryString(selectedFile);
      	});
    });
})(window.jQuery, window.app);