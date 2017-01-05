(function ($,app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        
        var inputFieldId = "form-advanceName";
        var formId = "advance-form";
        var tableName =  "HR_ADVANCE_MASTER_SETUP";
        var columnName = "ADVANCE_NAME";
        var checkColumnName = "ADVANCE_ID";
        var selfId = $("#advanceID").val();
        if (typeof(selfId) == "undefined"){
            selfId=0;
        }
        window.app.checkUniqueConstraints(inputFieldId,formId,tableName,columnName,checkColumnName,selfId);
        window.app.checkUniqueConstraints("form-advanceCode",formId,tableName,"ADVANCE_CODE",checkColumnName,selfId);
    });
})(window.jQuery,window.app);

