(function ($, app) {
    'use strict';
    $(document).ready(function () {

        $('select').select2();
        $('#inTime').combodate({
            minuteStep: 1
        });

        app.datePickerWithNepali("attendanceDt", "nepaliDate");

        var $employeeId = $('#employeeId');
        app.floatingProfile.setDataFromRemote($employeeId.val());

        $employeeId.on("change", function (e) {
            app.floatingProfile.setDataFromRemote($(e.target).val());
        });
        app.setLoadingOnSubmit("attendanceByHr", function () {
            if ($('#inTime').val() == '') {
                app.showMessage('In time is not set.', 'error');
                return false;
            }
            return true;
        });
    });
})(window.jQuery, window.app);


