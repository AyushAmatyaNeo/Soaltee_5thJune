(function ($, app) {
    'use strict';
    $(document).ready(function () {
        console.log(document.overtimeRequestList);
        $("#overtimeTable").kendoGrid({
            excel: {
                fileName: "OvertimeRequestList.xlsx",
                filterable: true,
                allPages: true
            },
            dataSource: {
                data: document.overtimeRequestList,
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
            dataBound: gridDataBound,
//            rowTemplate: kendo.template($("#rowTemplate").html()),
            columns: [
                {title: "Overtime Date",
                            columns: [{
                                    field: "OVERTIME_DATE",
                                    title: "English",
                                    template: "<span>#: (OVERTIME_DATE == null) ? '-' : OVERTIME_DATE #</span>"},
                                {field: "OVERTIME_DATE_N",
                                    title: "Nepali",
                                    template: "<span>#: (OVERTIME_DATE_N == null) ? '-' : OVERTIME_DATE_N #</span>"}]},
                {field: "DETAILS", title: "Overtime (From - To)", template: `<ul id="branchList">  
        #  ln=DETAILS.length #
        #for(var i=0; i<ln; i++) { #
        <li>
        #=i+1 #) #=DETAILS[i].START_TIME # - #=DETAILS[i].END_TIME #
        </li>
        #}#
        </ul>`},
                {field: "TOTAL_HOUR", title: "Total Hour"},
                {title: "Requested Date",
                            columns: [{
                                    field: "REQUESTED_DATE",
                                    title: "English",
                                    template: "<span>#: (REQUESTED_DATE == null) ? '-' : REQUESTED_DATE #</span>"},
                                {field: "REQUESTED_DATE_N",
                                    title: "Nepali",
                                    template: "<span>#: (REQUESTED_DATE_N == null) ? '-' : REQUESTED_DATE_N #</span>"}]},
                {field: "STATUS", title: "Status"},
                {field: ["OVERTIME_ID", "ALLOW_TO_EDIT"], title: "Action", template: `<span><a class="btn-edit" href="` + document.viewLink + `/#: OVERTIME_ID #" style="height:17px;" title="view detail">
                            <i class="fa fa-search-plus"></i>
                            </a>
                            #if(ALLOW_TO_EDIT == 1){#       
                            <a class="confirmation btn-delete" href="` + document.deleteLink + `/#: OVERTIME_ID #" id="bs_#:OVERTIME_ID #" style="height:17px;">
                            <i class="fa fa-trash-o"></i>
                            </a> #}#
                            </span>`
                }
            ]
        });
        
        app.searchTable('overtimeTable',['OVERTIME_DATE','OVERTIME_DATE_N','TOTAL_HOUR','REQUESTED_DATE','REQUESTED_DATE_N','STATUS']);
        
        app.pdfExport(
                        'overtimeTable',
                        {
                            'OVERTIME_DATE': ' Overtime Date(AD)',
                            'OVERTIME_DATE_N': ' Overtime Date(BS)',
                            'TOTAL_HOUR': 'Total Hour',
                            'REQUESTED_DATE': 'Request Date(AD)',
                            'REQUESTED_DATE_N': 'Request Date(BS)',
                            'STATUS': 'Status',
                            'DESCRIPTION': 'Description',
                            'REMARKS': 'Remarks',
                            'RECOMMENDER_NAME': 'Recommender',
                            'APPROVER_NAME': 'Approver',
                            'RECOMMENDED_REMARKS': 'Recommended Remarks',
                            'RECOMMENDED_DATE': 'Recommended Date',
                            'APPROVED_REMARKS': 'Approved Remarks',
                            'APPROVED_DATE': 'Approved Date'
                        }
                );
        
        
        function gridDataBound(e) {
            var grid = e.sender;
            if (grid.dataSource.total() == 0) {
                var colCount = grid.columns.length;
                $(e.sender.wrapper)
                        .find('tbody')
                        .append('<tr class="kendo-data-row"><td colspan="' + colCount + '" class="no-data">There is no data to show in the grid.</td></tr>');
            }
        }
        ;
        $("#export").click(function (e) {
            var rows = [{
                    cells: [
                        {value: "Overtime Date(AD)"},
                        {value: "Overtime Date(BS)"},
                        {value: "Time (From - To)"},
                        {value: "Total Hour"},
                        {value: "Requested Date(AD)"},
                        {value: "Requested Date(BS)"},
                        {value: "Status"},
                        {value: "Description"},
                        {value: "Remarks"},
                        {value: "Recommender"},
                        {value: "Approver"},
                        {value: "Remarks By Recommender"},
                        {value: "Recommended Date"},
                        {value: "Remarks By Approver"},
                        {value: "Approved Date"},
                    ]
                }];
            var dataSource = $("#overtimeTable").data("kendoGrid").dataSource;
            var filteredDataSource = new kendo.data.DataSource({
                data: dataSource.data(),
                filter: dataSource.filter()
            });

            filteredDataSource.read();
            var data = filteredDataSource.view();

            for (var i = 0; i < data.length; i++) {
                var dataItem = data[i];
                var details = [];
                for (var j = 0; j < dataItem.DETAILS.length; j++) {
                    details.push(dataItem.DETAILS[j].START_TIME+"-"+dataItem.DETAILS[j].END_TIME);
                }
                var details1 = details.toString();
                rows.push({
                    cells: [
                        {value: dataItem.OVERTIME_DATE},
                        {value: dataItem.OVERTIME_DATE_N},
                        {value: details1},
                        {value: dataItem.TOTAL_HOUR},
                        {value: dataItem.REQUESTED_DATE},
                        {value: dataItem.REQUESTED_DATE_N},
                        {value: dataItem.STATUS},
                        {value: dataItem.DESCRIPTION},
                        {value: dataItem.REMARKS},
                        {value: dataItem.RECOMMENDER_NAME},
                        {value: dataItem.APPROVER_NAME},
                        {value: dataItem.RECOMMENDED_REMARKS},
                        {value: dataItem.RECOMMENDED_DATE},
                        {value: dataItem.APPROVED_REMARKS},
                        {value: dataItem.APPROVED_DATE}
                    ]
                });
            }
            excelExport(rows);
            e.preventDefault();
        });

        function excelExport(rows) {
            var workbook = new kendo.ooxml.Workbook({
                sheets: [
                    {
                        columns: [
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true}
                        ],
                        title: "Overtime Request",
                        rows: rows
                    }
                ]
            });
            kendo.saveAs({dataURI: workbook.toDataURL(), fileName: "OvertimeRequestList.xlsx"});
        }
    });
})(window.jQuery, window.app);