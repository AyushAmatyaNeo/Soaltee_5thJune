(function ($, app) {
    'use strict';
    $(document).ready(function () {

        var inputFieldId = "form-roleName";
        var formId = "rolesetup-form";
        var tableName = "HRIS_ROLES";
        var columnName = "ROLE_NAME";
        var checkColumnName = "ROLE_ID";
        var selfId = $("#roleId").val();
        var $controlOption = $("#controlOption");

        var $selectOptions = $("#selectOptions");
        $selectOptions.select2();

        var controlValue = $('input[name=control]:checked').val();
        $('input[name=control]').change(function () {
            changeControl();
        });

        function changeControl() {
            var controlValue = $('input[name=control]:checked').val();

            var populateValues = [];
            $.each(document.selectedControlValues, function (k, v) {
                if (v.CONTROL == controlValue) {
                    populateValues.push(v.VAL);
                }
            });

            if (controlValue === 'C') {
                $controlOption.text("Select Companies");
                app.populateSelect($selectOptions, document.searchValues['company'], 'COMPANY_ID', 'COMPANY_NAME', '---', '');
            } else if (controlValue === 'B') {
                $controlOption.text("Select Branches");
                $selectOptions.empty();
                app.populateSelect($selectOptions, document.searchValues['branch'], 'BRANCH_ID', 'BRANCH_NAME', '---', '');
            } else if (controlValue === "DP") {
                $controlOption.text("Select Departments");
                $selectOptions.empty();
                app.populateSelect($selectOptions, document.searchValues['department'], 'DEPARTMENT_ID', 'DEPARTMENT_NAME', '---', '');
            } else if (controlValue === "DS") {
                $controlOption.text("Select Designations");
                $selectOptions.empty();
                app.populateSelect($selectOptions, document.searchValues['designation'], 'DESIGNATION_ID', 'DESIGNATION_TITLE', '---', '');
            } else if (controlValue === "P") {
                $controlOption.text("Select Positions");
                $selectOptions.empty();
                app.populateSelect($selectOptions, document.searchValues['position'], 'POSITION_ID', 'POSITION_NAME', '---', '');
            } else {
                $controlOption.empty();
                $selectOptions.empty();
            }

            $selectOptions.val(populateValues).trigger('change.select2');
        }


        changeControl();






        if (typeof selfId === "undefined") {
            selfId = 0;
        }
        app.checkUniqueConstraints(inputFieldId, formId, tableName, columnName, checkColumnName, selfId, function () {
            App.blockUI({target: "#hris-page-content"});
        });
    });
})(window.jQuery, window.app);
