(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.datePickerWithNepali('fromDate', 'nepaliFromDate');
        app.datePickerWithNepali('toDate', 'nepaliToDate');
       
        var $employeeTable = $('#employeeTable');
        var $search = $('#search');
  

        app.initializeKendoGrid($employeeTable, [
            {field: "EMPLOYEE_CODE", title: "Code", locked: true, width: 70},
            {field: "FULL_NAME", title: "Full Name", locked: true, width: 120},
            {field: "MOBILE_NO", title: "Mobile No", locked: true, width: 100},
            {title: "Birth Date", locked: true, columns: [
                    {field: "BIRTH_DATE_AD", title: "AD", width: 80},
                    {field: "BIRTH_DATE_BS", title: "BS", width: 80}
                ]},
            {title: "Join Date", locked: true, columns: [
                    {field: "JOIN_DATE_AD", title: "AD", width: 80},
                    {field: "JOIN_DATE_BS", title: "BS", width: 80}
                ]},
//            {field: "COMPANY_NAME", title: "Company", width: 150},
//            {field: "BRANCH_NAME", title: "Branch", width: 150},
            {field: "FUNCTIONAL_TYPE_EDESC", title: "Functional Type", width: 120},
            {field: "DEPARTMENT_NAME", title: "Department", width: 120},
            {field: "DESIGNATION_TITLE", title: "Designation", width: 120},
            {field: "POSITION_NAME", title: "Position", width: 120},
            /*{field: "LEVEL_NO", title: "Level", width: 150},
            {field: "LOCATION_EDESC", title: "Location", width: 150},
            {field: "FUNCTIONAL_TYPE_EDESC", title: "Functional Type", width: 150},
            {field: "FUNCTIONAL_LEVEL_EDESC", title: "Functional Level", width: 150}*/
        ], null, null, null, 'Birthday Report.xlsx');

        app.searchTable('employeeTable', ['EMPLOYEE_CODE', 'FULL_NAME', 'MOBILE_NO', 'BIRTH_DATE', 'COMPANY_NAME', 'BRANCH_NAME', 'DEPARTMENT_NAME', 'DESIGNATION_TITLE'], false);
  
        var map = {
            'EMPLOYEE_ID': 'Employee Id',
            'EMPLOYEE_CODE': 'Employee Code',
            'TITLE': 'Title',
            'FULL_NAME': 'Employee',
            'GENDER_NAME': 'Gender',
            'BIRTH_DATE_AD': 'Birth Date(AD)',
            'BIRTH_DATE_BS': 'Birth Date(BS)',
            'JOIN_DATE_AD': 'Join Date(AD)',
            'JOIN_DATE_BS': 'Join Date(BS)',
            /*'COUNTRY_NAME': 'Country',
            'RELIGION_NAME': 'Religion',
            'BLOOD_GROUP_CODE': 'Blood Group',
            'MOBILE_NO': 'Mobile No',
            'TELEPHONE_NO': 'Telephone No',
            'SOCIAL_ACTIVITY': 'Social Activity',
            'EXTENSION_NO': 'Extension No',
            'EMAIL_OFFICIAL': 'Official Email',
            'EMAIL_PERSONAL': 'Personal Email',
            'SOCIAL_NETWORK': 'Social Network',*/
            'COMPANY_NAME': 'Company',
            'BRANCH_NAME': 'Branch',
            'DEPARTMENT_NAME': 'Department',
            'DESIGNATION_TITLE': 'Designation',
            /*'POSITION_NAME': 'Position',
            'LEVEL_NO': 'Level',
            'SERVICE_TYPE_NAME': 'Service Type',
            'EMPLOYEE_TYPE': 'Employee',
            'LOCATION_EDESC': 'Location',
            'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
            'FUNCTIONAL_LEVEL_NO': 'Functional Level No',
            'FUNCTIONAL_LEVEL_EDESC': 'Functional Level',
            'ADDR_PERM_HOUSE_NO': 'Permanent House No',
            'ADDR_PERM_WARD_NO': 'Permanent Ward No',
            'ADDR_PERM_STREET_ADDRESS': 'Permanent Street Address',
            'ADDR_PERM_COUNTRY_NAME': 'Permanent Country',
            'ADDR_PERM_ZONE_NAME': 'Permanent Zone',
            'ADDR_PERM_DISTRICT_NAME': 'Permanent District',
            'VDC_MUNICIPALITY_NAME_PERM': 'Permanent VDC/Municipality',
            'ADDR_TEMP_HOUSE_NO': 'Temporary House No',
            'ADDR_TEMP_WARD_NO': 'Temporary Ward No',
            'ADDR_TEMP_STREET_ADDRESS': 'Temporary Street Address',
            'ADDR_TEMP_COUNTRY_NAME': 'Temporary Country',
            'ADDR_TEMP_ZONE_NAME': 'Temporary Zone',
            'ADDR_TEMP_DISTRICT_NAME': 'Temporary District',
            'VDC_MUNICIPALITY_NAME_TEMP': 'Temporary VDC/Municipality',
            'EMRG_CONTACT_NAME': 'Emergency Contact Name',
            'EMERG_CONTACT_RELATIONSHIP': 'Emergency Contact Relationship',
            'EMERG_CONTACT_ADDRESS': 'Emergency Contact Address',
            'EMERG_CONTACT_NO': 'Emergency Contact No',
            'ID_ACCOUNT_NO': 'Account No',
            'BANK_ACCOUNT': 'BANK',*/
            'ID_THUMB_ID': 'THUMB ID'
        }; 

        $('#excelExport').on('click', function () {
            app.excelExport($employeeTable, map, 'Birthday Employee List.xlsx');        
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($employeeTable, map, 'Birthday Employee List.pdf');
        });
       
        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            var fromDate = $('#fromDate').val();
            var toDate = $('#toDate').val();
            data.fromDate = fromDate;
            data.toDate = toDate;
            
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