(function ($) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#table');
        var actiontemplateConfig = {
            update: {
                'ALLOW_UPDATE': document.acl.ALLOW_UPDATE,
                'params': ["USER_ID"],
                'url': document.editLink
            },
            delete: {
                'ALLOW_DELETE': document.acl.ALLOW_DELETE,
                'params': ["USER_ID"],
                'url': document.deleteLink
            }
        };
        var columns = [
            {field: "ROLE_NAME", title: "Role", width: 150},
            {field: "FULL_NAME", title: "Employee Name", width: 150},
            {field: "USER_NAME", title: "User Name", width: 150},
            {field: ["USER_ID"], title: "Action", width: 120, template: app.genKendoActionTemplate(actiontemplateConfig)}
        ];
        var map = {
            'ROLE_NAME': 'Role',
            'FULL_NAME': 'Employee Name',
            'USER NAME': 'User Name',
        }
        app.initializeKendoGrid($table, columns, "User List.xlsx");

        app.searchTable($table, ['ROLE_NAME', 'FULL_NAME']);

        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'User List.xlsx');
        });
        $('#excelExportWithPassword').on('click', function () {
            map['PASSWORD'] = "Password";
            app.excelExport($table, map, 'User List.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'User List.pdf');
        });

        app.pullDataById("", {}).then(function (response) {
            app.renderKendoGrid($table, response.data);
        }, function (error) {

        });
    });
})(window.jQuery);