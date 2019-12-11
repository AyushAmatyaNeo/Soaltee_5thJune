(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        var $tableContainer = $("#reportTable");

        var $year = $('#fiscalYear');
        var $month = $('#fiscalMonth');

        app.setFiscalMonth($year, $month);

        app.searchTable('reportTable', ['code', 'employee'], false);

        var extractDetailData = function (rawData, branchId) {
            var data = {};
            var column = {};
            for (var i in rawData) {
                if (typeof data[rawData[i].EMPLOYEE_ID] !== 'undefined') {
                    data[rawData[i].EMPLOYEE_ID].DAYS['C' + rawData[i].DAY_COUNT] =
                            JSON.stringify({
                                IS_ABSENT: rawData[i].IS_ABSENT,
                                IS_PRESENT: rawData[i].IS_PRESENT,
                                ON_LEAVE: rawData[i].ON_LEAVE,
                                IS_DAYOFF: rawData[i].IS_DAYOFF,
                                IN_TIME: rawData[i].IN_TIME,
                                OUT_TIME: rawData[i].OUT_TIME,
                                TOTAL_HOUR: rawData[i].TOTAL_HOUR,
                                EMPLOYEE_CODE: rawData[i].EMPLOYEE_CODE
                            });
                    data[rawData[i].EMPLOYEE_ID].TOTAL.IS_ABSENT = data[rawData[i].EMPLOYEE_ID].TOTAL.IS_ABSENT + parseFloat(rawData[i].IS_ABSENT);
                    data[rawData[i].EMPLOYEE_ID].TOTAL.IS_PRESENT = data[rawData[i].EMPLOYEE_ID].TOTAL.IS_PRESENT + parseFloat(rawData[i].IS_PRESENT);
                    data[rawData[i].EMPLOYEE_ID].TOTAL.ON_LEAVE = data[rawData[i].EMPLOYEE_ID].TOTAL.ON_LEAVE + parseFloat(rawData[i].ON_LEAVE);
                    data[rawData[i].EMPLOYEE_ID].TOTAL.IS_DAYOFF = data[rawData[i].EMPLOYEE_ID].TOTAL.IS_DAYOFF + parseFloat(rawData[i].IS_DAYOFF);
                } else {
                    data[rawData[i].EMPLOYEE_ID] = {
                        EMPLOYEE_CODE: rawData[i].EMPLOYEE_CODE,
                        EMPLOYEE_ID: rawData[i].EMPLOYEE_ID,
                        FULL_NAME: rawData[i].FULL_NAME,
                        DAYS: {},
                        TOTAL: {
                            IS_ABSENT: parseFloat(rawData[i].IS_ABSENT),
                            IS_PRESENT: parseFloat(rawData[i].IS_PRESENT),
                            ON_LEAVE: parseFloat(rawData[i].ON_LEAVE),
                            IS_DAYOFF: parseFloat(rawData[i].IS_DAYOFF)
                        }
                    };
                    data[rawData[i].EMPLOYEE_ID].DAYS['C' + rawData[i].DAY_COUNT] =
                            JSON.stringify({
                                IS_ABSENT: rawData[i].IS_ABSENT,
                                IS_PRESENT: rawData[i].IS_PRESENT,
                                ON_LEAVE: rawData[i].ON_LEAVE,
                                IS_DAYOFF: rawData[i].IS_DAYOFF,
                                IN_TIME: rawData[i].IN_TIME,
                                OUT_TIME: rawData[i].OUT_TIME,
                                TOTAL_HOUR: rawData[i].TOTAL_HOUR,
                                EMPLOYEE_CODE: rawData[i].EMPLOYEE_CODE

                            });

                }
//                if (typeof column['C' + rawData[i].DAY_COUNT] === 'undefined') {
//                    var temp = 'C' + rawData[i].DAY_COUNT;
//                    column[temp] = {
//                        field: temp,
//                        title: "" + rawData[i].DAY_COUNT,
//                        template: '<span data="#: ' + temp + ' #" class="daily-attendance"></span>'
//                    }
//
//                }
            }
            var returnData = {rows: [], cols: []};

            returnData.cols.push({
                field: 'EMPLOYEE_CODE',
                title: 'Code',
                template: '<span><b>#=code#</b></span>'
            });

            returnData.cols.push({
                field: 'Employee',
                title: 'EMPLOYEES',
                template: '<span><b>#=employee#</b></span>'
            });

            returnData.cols.push({
                title: 'Time',
                width: 400,
                template: '<span style="text-align: center"><i>In_time</br>Out_time</br>Total_hour</i></span>'
            });

            for (var i = 1; i < 33; i++) {
                var temp = 'C' + i;
                returnData.cols.push({
                    field: temp,
                    title: "" + i,
                    template: '<span " data="#: ' + temp + ' #" class="daily-attendance"></span>'
                });
            }
//            returnData.cols.push({
//                field: 'total',
//                title: 'Total',
//                template: '<div data="#: total #" class="btn-group widget-btn-list total-attendance">' +
//                        '<a class="btn widget-btn custom-btn-present totalbtn"></a>' +
//                        '<a class="btn widget-btn custom-btn-absent totalbtn"></a>' +
//                        '<a class="btn widget-btn custom-btn-leave totalbtn"></a>' +
//                        '</div>'});

            for (var k in data) {
                var row = data[k].DAYS;
                for (var i = 1; i < 33; i++) {
                    if (typeof row['C' + i] === 'undefined') {
                        row['C' + i] = null;
                    }
                }
                row['employee'] = data[k].FULL_NAME;
                row['code'] = (data[k].EMPLOYEE_CODE == null) ? '' : data[k].EMPLOYEE_CODE;
                returnData.rows.push(row);
//                row['total'] = JSON.stringify(data[k].TOTAL);
            }
            return returnData;
        };
        var firstTime = true;
        var initializeReport = function (monthId, branchId) {
            if (firstTime) {
                App.blockUI({target: "#hris-page-content"});

            } else {
                App.blockUI({target: "#reportTable"});

            }
            app.pullDataById(document.wsBranchWiseDailyReport, {branchId: branchId, monthId: monthId}).then(function (response) {
                if (firstTime) {
                    App.unblockUI("#hris-page-content");
                    firstTime = false;
                } else {
                    App.unblockUI("#reportTable");
                }
                console.log('branchWiseEmployeeMonthlyR', response);
                var extractedDetailData = extractDetailData(response.data, branchId);
                console.log('extractedDetailData', extractedDetailData);
                $tableContainer.kendoGrid({
                    dataSource: {
                        data: extractedDetailData.rows,
                        pageSize: 500
                    },
                    dataBound: function (e) {
                        var grid = e.sender;
                        if (grid.dataSource.total() === 0) {
                            var colCount = grid.columns.length;
                            $(e.sender.wrapper)
                                    .find('tbody')
                                    .append('<tr class="kendo-data-row"><td colspan="' + colCount + '" class="no-data">There is no data to show in the grid.</td></tr>');
                        }

                        $('.daily-attendance').each(function (k, group) {
                            var $group = $(group);

                            var data = $group.attr('data');
                            if (data == 'null') {

                            } else {
                                data = JSON.parse(data);
                                if (data.IS_PRESENT == 1 || data.IN_TIME != null) {
                                    $group.html(((data.IN_TIME == null) ? '-' : data.IN_TIME) + '</br>' + ((data.OUT_TIME == null) ? '-' : data.OUT_TIME) + '</br>' + ((data.TOTAL_HOUR == null) ? '-' : data.TOTAL_HOUR));
                                    $group.parent().addClass('bg-success text-center');
                                } else {
                                    if (data.IS_ABSENT == 1) {
                                        $group.html('A');
                                        $group.parent().addClass('bg-red1 textcolor1 text-center');

                                    } else {
                                        if (data.ON_LEAVE == 1) {
                                            $group.html('L');
                                            $group.parent().addClass('bg-blue1 textcolor2 text-center');

                                        } else {
                                            $group.html('H');
                                            $group.parent().addClass('bg-white1 textcolor3 text-center');
                                        }

                                    }

                                }
                            }

                        });

                    },
                    scrollable: false,
                    sortable: false,
                    pageable: false,
                    columns: extractedDetailData.cols
                });

            }, function (error) {
                if (firstTime) {
                    App.unblockUI("#hris-page-content");
                    firstTime = false;
                } else {
                    App.unblockUI("#reportTable");
                }
                console.log('departmentWiseEmployeeMonthlyE', error);
            });
        };

        $('select').select2();
        var $monthList = $('#monthList');
        var $branchList = $('#branchList');
        var $generateReport = $('#generateReport');

        var populateList = function ($element, list, id, value, defaultMessage, selectedId) {
            $element.html('');
            $element.append($("<option></option>").val(-1).text(defaultMessage));
            for (var i in list) {
                if (typeof selectedId !== 'undefined' && selectedId != null && selectedId == list[i][id]) {
                    $element.append($("<option selected='selected'></option>").val(list[i][id]).text(list[i][value]));
                } else {
                    $element.append($("<option></option>").val(list[i][id]).text(list[i][value]));
                }
            }
        }

        var comBraDepList = document.comBraDepList;
        var monthList = document.monthList;
        var monthId = document.monthId;
        var branchId = document.branchId;

        populateList($monthList, monthList, 'MONTH_ID', 'MONTH_EDESC', "Select Month", monthId);
        populateList($branchList, comBraDepList['BRANCH_LIST'], 'BRANCH_ID', 'BRANCH_NAME', "SELECT BRANCH");


        $generateReport.on('click', function () {
            var branchId = $branchList.val();
            var monthId = $('#fiscalMonth').val();

            console.log(branchId);
            console.log(monthId);
            if (branchId == -1 || monthId == '') {
                app.errorMessage("No Branch Selected", "Notification");
            } else {
                initializeReport(monthId, branchId);
            }
        });



        if (monthId != 0 && branchId != 0) {
//            initializeReport(monthId, branchId);
//            $monthList.val(monthId);
//            $branchList.val(branchId);
        }

        $('#excelExport').on('click', function () {
            $tableContainer.table2excel({
                exclude: ".noExl",
                name: "Branch wise daily In Out",
                filename: "Branch wise daily In Out"
            });
        });

        $('#exportPdf').on('click', function () {
            kendo.drawing.drawDOM($tableContainer).then(function (group) {
                kendo.drawing.pdf.saveAs(group, "Branch wise daily In Out.pdf");
            });
        });

        $("#printAsPDF").click(function (e) {
            var divToPrint = document.getElementById('reportTable');

            var newWin = window.open('', 'Print-Window');

            newWin.document.open();
            newWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</body><style>table {border-collapse: collapse;}table, th, td {border: 1px solid black;text-align: center;}</style></html>');
            newWin.document.close();

            setTimeout(function () {
                newWin.close();
            }, 1000);
        });


    });
})(window.jQuery, window.app);