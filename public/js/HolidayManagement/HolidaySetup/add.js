/**
 * Created by ukesh on 9/12/16.
 */
(function ($,app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
    app.addDatePicker(
        $("#startDate"),
        $("#endDate")
    );

    });
})(window.jQuery,window.app);




