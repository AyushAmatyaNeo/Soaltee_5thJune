(function ($) {
    'use strict';
    $(document).ready(function () {    
       
        $("#roleTable").kendoGrid({
            excel: {
                fileName: "RoleList.xlsx",
                filterable: true,
                allPages: true
            },
            dataSource: {
                data: document.roles,
                pageSize: 20
            },
            height: 450,
            scrollable: true,
            sortable: true,
            filterable: true,
            pageable: {
                input: true,
                numeric: false
            },
            rowTemplate: kendo.template($("#rowTemplate").html()),
            columns: [
                {field: "SN", title: "S.N.",width:50},
                {field: "ROLE_NAME", title: "Role Name",width:200},
                {title: "Action",width:50}
            ]
        });  
        
        app.searchTable('roleTable',['ROLE_NAME']);
        
        app.pdfExport(
                'roleTable',
                {
                    'ROLE_NAME': 'Role'
                }
        );
        
        $("#export").click(function (e) {
            var grid = $("#roleTable").data("kendoGrid");
            grid.saveAsExcel();
        });
    });   
})(window.jQuery, window.app);
