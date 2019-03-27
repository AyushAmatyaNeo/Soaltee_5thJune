(function ($, app) {
    'use strict';
    $(document).ready(function () {
        if (document.employeeId == document.selfEmployeeId) {
            app.lockField(true, [
			'employeeCode',
			'genderId',
			'countryId',
			'religionId',
			'emailOfficial',
			'MobileNo',
			'bloodGroupId',
			'birthdate', 'firstName', 'middleName', 'lastName', 'nameNepali', 'nepaliBirthDate', 'companyId', 'idCardNo', 'idThumbId', 'idLbrf', 'tab1', 'tab2', 'tab3', 'tab4', 'tab5', 'tab7','tab1', 'tab8']);
        }
    });
})(window.jQuery, window.app);