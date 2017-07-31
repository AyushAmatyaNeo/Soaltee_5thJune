(function ($, app) {
    'use strict';
    $(document).ready(function () {

        $("img.lazy").lazyload({
//            effect: "fadeIn",
            threshold: 5000
        });

        // Init Data Tables
        var table = $('#sample_1');

        /* Table tools samples: https://www.datatables.net/release-datatables/extras/TableTools/ */

        /* Set tabletools buttons and button container */

        $.extend(true, $.fn.DataTable.TableTools.classes, {
            "container": "btn-group tabletools-dropdown-on-portlet",
            "buttons": {
                "normal": "btn btn-sm default",
                "disabled": "btn btn-sm default disabled"
            },
            "collection": {
                "container": "DTTT_dropdown dropdown-menu tabletools-dropdown-menu"
            }
        });

        var oTable = table.dataTable({

            // Internationalisation. For more info refer to http://datatables.net/manual/i18n
            "language": {
                "aria": {
                    "sortAscending": ": activate to sort column ascending",
                    "sortDescending": ": activate to sort column descending"
                },
                "emptyTable": "No data available in table",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "No entries found",
                "infoFiltered": "(filtered1 from _MAX_ total entries)",
                "lengthMenu": "Show _MENU_ entries",
                "search": "Search:",
                "zeroRecords": "No matching records found"
            },

            // Or you can use remote translation file
            //"language": {
            //   url: '//cdn.datatables.net/plug-ins/3cfcc339e89/i18n/Portuguese.json'
            //},

            "order": [
                [1, 'asc']
            ],

            "lengthMenu": [
                [5, 15, 20, -1],
                [5, 15, 20, "All"] // change per page values here
            ],
            // set the initial value
            "pageLength": 10,

            "dom": "<'row' <'col-md-12'T>><'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12'f>r><'table-scrollable't><'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12'p>>", // horizobtal scrollable datatable

            // Uncomment below line("dom" parameter) to fix the dropdown overflow issue in the datatable cells. The default datatable layout
            // setup uses scrollable div(table-scrollable) with overflow:auto to enable vertical scroll(see: assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js).
            // So when dropdowns used the scrollable div should be removed.
            //"dom": "<'row' <'col-md-12'T>><'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12'f>r>t<'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12'p>>",

            "tableTools": {
                "sSwfPath": "../assets/global/plugins/datatables/extensions/TableTools/swf/copy_csv_xls_pdf.swf",
                "aButtons": [{
                        "sExtends": "pdf",
                        "sButtonText": "PDF"
                    }, {
                        "sExtends": "csv",
                        "sButtonText": "CSV"
                    }, {
                        "sExtends": "xls",
                        "sButtonText": "Excel"
                    }, {
                        "sExtends": "print",
                        "sButtonText": "Print",
                        "sInfo": 'Please press "CTR+P" to print or "ESC" to quit',
                        "sMessage": "Generated by DataTables"
                    }]
            }
        });

        var tableWrapper = $('#sample_1_wrapper'); // datatable creates the table wrapper by adding with id {your_table_jd}_wrapper

        tableWrapper.find('.dataTables_length select').select2(); // initialize select2 dropdown

        oTable.on('click', 'tr', function () {
            if ($(this).hasClass('selected')) {
                $(this).removeClass('selected');
                $('.hrm-dashboard-employee-list .fonticon').removeClass('active').addClass('fonticon-disabled');
            } else {
                table.$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
                $('.hrm-dashboard-employee-list .fonticon').addClass('active').removeClass('fonticon-disabled');
            }
        });

        $('.ln-hrd-emp-lst').on('click', function (e) {
            var $this = $(this);
            var $selectedTr = oTable.find('tbody tr.selected');
            var employeeId = $selectedTr.data('empid');
            console.log(employeeId);
            var employeeName = $selectedTr.find('td:eq(1)').text();
            switch (true) {
                case $this.is('.ln-hrd-emp-payroll') :
                    alert('Generate the pay roll of' + ' ' + employeeName);
                    break;
                case $this.is('.ln-hrd-emp-recruitment') :
                    alert('Functionality will be provided as requested');
                    break;
                case $this.is('.ln-hrd-emp-training') :
                    window.location = document.trainingUrl;
//                    alert('Assgin the training of' + ' ' + employeeName);
                    break;
                case $this.is('.ln-hrd-emp-travel') :
                    window.location = document.travelUrl;
//                    alert('Approve the travel of' + ' ' + employeeName)
                    break;
                case $this.is('.ln-hrd-emp-leave') :
                    window.location = document.leaveUrl;
//                    alert('Approve the leave of selected' + ' ' + employeeName);
                    break;
                case $this.is('.ln-hrd-emp-payslip') :
                    alert('Generate the pay slip of' + ' ' + employeeName);
                    break;
            }
        });
    });

    /***** Charts *****/
    var deptAttnCategories = [];
    var departmentAttendanceData = {
        'present': [],
        'absent': []
    };
    for (var dept in document.deptattn) {
        deptAttnCategories.push(dept);
        departmentAttendanceData['present'].push(Number(document.deptattn[dept].PRESENT));
        departmentAttendanceData['absent'].push(Number(document.deptattn[dept].ABSENT));
    }
    Highcharts.chart('chart-department-attendance', {
        chart: {
            type: 'column',
            options3d: {
                enabled: true,
                alpha: 0,
                beta: -1,
                viewDistance: 25,
                depth: 40
            }
        },
        title: {
            text: 'Today\'s Attendance',
            style: {
                color: '#63AB6A',
                fontSize: '15px'
            }
        },
        xAxis: {
            categories: deptAttnCategories
        },
        yAxis: {
            min: 0,
            title: {
                text: 'No. of Employees'
            },
            stackLabels: {
                enabled: true,
                style: {
                    fontWeight: 'bold',
                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                }
            }
        },
        legend: {
            verticalAlign: 'top',
            align: 'right',
            // x: -30,
            // y: 0,
            symbolPadding: 5,
            symbolWidth: 10,
            itemDistance: 10,
            floating: true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
            borderColor: '#CCC',
            borderWidth: 1,
            shadow: false
        },
        tooltip: {
            headerFormat: '<b>{point.x}</b><br/>',
            pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: {
                    enabled: true,
                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white'
                }
            }
        },
        series: [{
                name: "Present",
                data: departmentAttendanceData.present
            },
            {
                name: "Absent",
                data: departmentAttendanceData.absent
            }]
    });

    var departmentHeadCountData = [];
    for (var x in document.odept) {
        departmentHeadCountData.push([document.odept[x], document.odepthc[x]]);
    }
    Highcharts.chart('chart-department-headcount', {
        chart: {
            type: 'column',
            options3d: {
                enabled: true,
                alpha: 0,
                beta: -1,
                depth: 50,
                viewDistance: 25
            }
        },
        title: {
            text: 'Employees By Department',
            style: {
                color: '#63AB6A',
                fontSize: '15px'
            }
        },
        subtitle: {
            //text: 'Department Wise Employee Head Count'
        },
        xAxis: {
            type: 'category',
            labels: {
                rotation: -50,
                style: {
                    fontSize: '12px'
                }
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: 'No. Of Employees'
            }
        },
        legend: {
            enabled: false
        },
        tooltip: {
            pointFormat: 'Employees: <b>{point.y}</b>'
        },
        series: [{
                name: 'Head Count',
                data: departmentHeadCountData,
                dataLabels: {
                    enabled: true,
                    rotation: 0,
                    color: '#544b4b',
                    x: 5,
                    align: 'right',
                    crop: false,
                    style: {
                        fontSize: '12px',
                        textOutline: 0
                    }
                }
            }]
    });

    var locationHeadCountData = [];
    for (var x in document.brln) {
        locationHeadCountData.push([document.brln[x], document.brlnhc[x]]);
    }
    Highcharts.chart('chart-location-headcount', {
        chart: {
            type: 'pie',
            options3d: {
                enabled: true,
                alpha: 45
            }
        },
        title: {
            text: 'Employees By Branch',
            style: {
                color: '#63AB6A',
                fontSize: '15px'
            }
        },
        subtitle: {
            //text: 'Branch Wise Employees Head Count'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: false,
                },
                innerSize: 60,
                depth: 45,
                size: 150
            }
        },
        legend: {
            layout: 'vertical',
            floating: true,
            align: 'left',
            verticalAlign: 'top',
            symbolPadding: 10,
            symbolWidth: 10,
            y: 20
        },
        series: [{
                name: 'Head Count',
                data: locationHeadCountData,
                showInLegend: true
            }]
    });

    var genderHeadCountData = [];
    for (var x in document.xndr) {
        genderHeadCountData.push([document.xndr[x], document.xndrhc[x]]);
    }
    Highcharts.chart('chart-gender-headcount', {
        chart: {
            type: 'pie',
            options3d: {
                enabled: true,
                alpha: 45
            }
        },
        title: {
            text: 'Employees By Gender',
            style: {
                color: '#63AB6A',
                fontSize: '15px'
            }
        },
        subtitle: {
            //text: 'Gender Wise Employees Head Count'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: false,
                },
                innerSize: 70,
                depth: 45
            }
        },
        legend: {
            layout: 'horizontal',
            floating: true,
            align: 'right',
            verticalAlign: 'top',
            symbolPadding: 10,
            symbolWidth: 10,
            y: 45
        },
        series: [{
                name: 'Head Count',
                data: genderHeadCountData,
                showInLegend: true
            }]
    });

    /*************** BIRTHDAY TAB CLICK EVENT ***************/
    $('.task-list').slimScroll({
        height: '200px'
    });
    $('.tab-pane-birthday').slimScroll({
        height: '300px'
    });
    $('.upcomingholidays').slimScroll({
        height: '218px'
    });

    $('.ln-nav-tab-birthday').on('click', function (e) {
        e.preventDefault();
        $('.ln-birthday').removeClass('active');
        $('.ln-birthday a').attr('aria-expanded', 'false');
        $(this).attr('aria-expanded', 'true');
        $(this).parent('li').addClass('active');
        if ($(this).is('#ln-birthday-today')) {
            $('#tab-birthday-upcoming').hide().removeClass('active');
            $('#tab-birthday-today').show().addClass('active');
        } else {
            $('#tab-birthday-today').hide().removeClass('active');
            $('#tab-birthday-upcoming').show().addClass('active');
        }
    });

    $('#task-save').on('click', function (e) {
        var taskDate = $.trim($('#inp-task-dt').val());
        var taskName = $.trim($('#inp-task-name').val());
        var slEmp = $('#lst-task-emp option:selected');
        if (taskDate && taskName && slEmp) {
            var taskLi =
                    '<li>' +
                    '<div class="task-list-content clearfix">' +
                    '<h4>' + taskName + '</h4>' +
                    '<div class="positon">' + slEmp.data('dsg') + '</div>' +
                    '<div class="name">' + slEmp.text() + '</div>' +
                    '</div>' +
                    '</li>';

            if ($('.task-list').length) {
                $('.task-list ul').append(taskLi);
            } else {
                $('.notask').remove();
                $('.portlet-inner-task-wrapper').append('<div class="task-list"><ul>' + taskLi + '</ul></div>')
            }
        }
        $('#inp-task-dt').val('');
        $('#inp-task-name').val('');
    });

    ComponentsPickers.init();

    window.app.pullDataById(document.getAdminDashboardUrl, {
        action: 'getAdminDashboardUrl',
    }).then(function (success) {
        var dashboardData = success.data;
        $('#employeePresentDays').text(dashboardData['PRESENT_DAY']);  //present 
        $('#employeeLeaveDays').text(dashboardData['LEAVE']);  //on leave
        $('#employeeTrainingDays').text(dashboardData['TRAINING']);  //on training
        $('#employeeTravelDays').text(dashboardData['TOUR']);  // on tour
        $('#employeeWOHDays').text(dashboardData['WOH']);  // on woh
        $('#employeeLateInDays').text(dashboardData['LATE_IN']);  //  late in
        $('#employeeEarlyOutDays').text(dashboardData['EARLY_OUT']);  //  early out
        $('#employeeMissPunch').text(dashboardData['MISSED_PUNCH']);  //  miss punch


        $('#employeeFullName').text(dashboardData['FULL_NAME']);   //full name
        if (dashboardData['EMAIL_OFFICIAL'] != null) {
            $('#employeeOfficialEmail').text(dashboardData['EMAIL_OFFICIAL']);  //  email
        } else {
            $('#employeeOfficialEmail').next('br').remove();
        }
        if (dashboardData['DESIGNATION_TITLE'] != null) {
            $('#employeeDesignationTitle').text(dashboardData['DESIGNATION_TITLE']);  //  designation Title
        } else {
            $('#employeeDesignationTitle').next('br').remove();
        }
        if (dashboardData['FILE_PATH'] != null) {
            $('#employeeImage').attr("src", document.basePath + '/uploads/' + dashboardData['FILE_PATH']);
        }


        var year = ' ';
        var month = ' ';
        var days = ' ';
        if (dashboardData['SERVICE_YEARS'] != 0) {
            if (dashboardData['SERVICE_YEARS'] == 1) {
                year = dashboardData['SERVICE_YEARS'] + ' Year ';
            } else {
                year = dashboardData['SERVICE_YEARS'] + ' Years ';
            }
        }
        if (dashboardData['SERVICE_MONTHS'] != 0) {
            if (dashboardData['SERVICE_MONTHS'] == 1) {
                month = dashboardData['SERVICE_MONTHS'] + ' Month ';
            } else {
                month = dashboardData['SERVICE_MONTHS'] + ' Months ';
            }
        }
        if (dashboardData['SERVICE_DAYS'] != 0) {
            if (dashboardData['SERVICE_DAYS'] == 0) {
                days = dashboardData['SERVICE_DAYS'] + ' Day';
            } else {
                days = dashboardData['SERVICE_DAYS'] + ' Days';
            }
        }

        var empServiceDate = "At work for : " + year + month + days;
        $('#employeeServiceDate').text(empServiceDate);  //  service

    }, function (failure) {
        console.log(failure);
    });



})(window.jQuery, window.app);
