(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
//        app.startEndDatePicker("fromDate", "toDate", function (fromDate, toDate) {
             app.startEndDatePickerWithNepali('nepaliStartDate1', 'fromDate', 'nepaliEndDate1', 'toDate', function (fromDate, toDate) {
            if (fromDate <= toDate) {
                var oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
                var diffDays = Math.abs((fromDate.getTime() - toDate.getTime()) / (oneDay));
                var newValue = diffDays + 1;
                //dateDiff = newValue;
                $("#duration").val(newValue);
            }
        });
        app.setLoadingOnSubmit("workOnDayoff-form");
    });
})(window.jQuery, window.app);

