(function ($,app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $payIdInt = $("#payIdInt");
        var $payIdAmt = $("#payIdAmt");
        var inputFieldId = "form-loanName";
        var formId = "loan-form";
        var tableName =  "HRIS_LOAN_MASTER_SETUP";
        var columnName = "LOAN_NAME";
        var checkColumnName = "LOAN_ID";
        var selfId = $("#loanID").val();
        if (typeof(selfId) == "undefined"){
            selfId=0;
        }
        window.app.checkUniqueConstraints(inputFieldId,formId,tableName,columnName,checkColumnName,selfId, function () {
            App.blockUI({target: "#hris-page-content"});
        });
        window.app.checkUniqueConstraints("form-loanCode",formId,tableName,"LOAN_CODE",checkColumnName,selfId);
        
        var pay_codes = document.pay_codes;
        app.populateSelect($payIdInt, pay_codes, 'PAY_ID', 'PAY_EDESC');
        app.populateSelect($payIdAmt, pay_codes, 'PAY_ID', 'PAY_EDESC');
        if(document.selected_pay_codes != null){
            document.getElementById("payIdInt").value = document.selected_pay_codes[0].PAY_ID_INT;
            document.getElementById("payIdAmt").value = document.selected_pay_codes[0].PAY_ID_AMT;
        }
        if(document.is_rate_flexible != null){
            $('#isRateFlexible option[value="'+document.is_rate_flexible+'"]').prop('selected', true).change();
        }
    });
})(window.jQuery,window.app);

angular.module('hris',[])
        .controller('loanRestrictionController',function($scope,$http){
            $scope.view = function(){
                $scope.salaryRangeFrom = 8000;
            }
        });

