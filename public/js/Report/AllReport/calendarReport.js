(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.populateSelect($('#cal_emp'), document.empList, 'EMPLOYEE_ID', 'FULL_NAME',null,null,document.selfEmployeeId);
        
    });
})(window.jQuery, window.app);