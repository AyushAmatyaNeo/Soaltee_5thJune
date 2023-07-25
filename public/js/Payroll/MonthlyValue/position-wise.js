(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        var months = document.months;
        var fiscalYears = document.fiscalYears;
        var monthlyValues = document.monthlyValues;
        var positions = document.positions;

        var $monthId = $("#monthId");
        var $fiscalYearId = $("#fiscalYearId");

        var $assignMonthlyValueBtn = $('#assignMonthlyValueBtn');

        var $grid = $('#monthlyValueDetailGrid');
        var $header = $('#monthlyValuesDetailHeader');
        var $table = $('#monthlyValueDetailTable');
        var $footer = $('#monthlyValueDetailFooter');


        app.populateSelect($fiscalYearId, fiscalYears, "FISCAL_YEAR_ID", "FISCAL_YEAR_NAME", "Select Fiscal Year");
        app.populateSelect($monthId, [], "MONTH_ID", "MONTH_EDESC", "Select Month");

        $fiscalYearId.on('change', function () {
            var value = $(this).val();
            var filteredMonths = [];
            if (value != -1) {
                var filteredMonths = months.filter(function (item) {
                    return item['FISCAL_YEAR_ID'] == value;
                });
            }
            app.populateSelect($monthId, filteredMonths, "MONTH_ID", "MONTH_EDESC", "Select Month");
        });

        var pullData = function (monthId, fn) {
            app.pullDataById(document.getPositionMonthlyLink, {monthId: monthId}).then(function (response) {
                fn(response.data);
            }, function (error) {

            });
        };

        $monthId.on('change', function () {
            var value = $(this).val();
            if (value == -1) {
                return;
            }
            pullData(value, function (data) {
                initTable(monthlyValues, positions, data);
            });

        });

        var findMonthlyValue = function (serverData, positionId, mthId) {
            var result = serverData.filter(function (item) {
                return item['POSITION_ID'] == positionId;
            });

            if (result.length > 0) {
                return result[0][mthId];
            } else {
                return null;
            }
        };

        var initTable = function (monthlyValues, positions, data) {
            $header.html('');
            $header.append($('<th>', {text: 'Level'}));
            $header.append($('<th>', {text: 'Name'}));
            $.each(monthlyValues, function (index, item) {
                $header.append($('<th>', {id: item['MTH_ID'], text: item['MTH_EDESC']}));
            });
            $header.append($('<th>', {text: ''}));

            $grid.html('');
            $.each(positions, function (index, item) {
                var $tr = $('<tr>');

                $tr.append($('<td>', {text: item['LEVEL_NO']}));
                $tr.append($('<td>', {text: item['POSITION_NAME']}))

                $.each(monthlyValues, function (k, v) {
                    var $td = $('<td>');
                    $td.append($('<input>', {type: 'number', row: item['POSITION_ID'], col: v['MTH_ID'], value: findMonthlyValue(data, item['POSITION_ID'], v['MTH_ID']), class: 'form-control'}));
                    $tr.append($td);
                });
                var $td = $('<td>');
                $td.append($('<input>', {type: 'number', row: item['POSITION_ID'], class: 'group form-control'}));
                $tr.append($td);

                $grid.append($tr);
            });

            $footer.html('');
            var $tr = $('<tr>');

            $tr.append($('<td>', {text: ''}));
            $tr.append($('<td>', {text: ''}))

            $.each(monthlyValues, function (k, v) {
                var $td = $('<td>');
                $td.append($('<input>', {type: 'number', col: v['MTH_ID'], class: 'group form-control'}));
                $tr.append($td);
            });
            var $td = $('<td>');
            $td.append($('<input>', {type: 'number', class: 'group form-control'}));
            $tr.append($td);

            $footer.append($tr);
            $table.bootstrapTable({height: 400});
        };

        $table.on('change', '.group', function () {
            var $this = $(this);
            var value = $this.val();
            var row = $this.attr('row');
            if (typeof row !== typeof undefined && row !== false) {
                $('input[row=' + row + ']').val(value);
            }
            var col = $this.attr('col');
            if (typeof col !== typeof undefined && col !== false) {
                $('input[col=' + col + ']').val(value);
            }

            if ((typeof row === "undefined" || row === false) && (typeof col === "undefined" || col === false)) {
                $.each($this.parent().parent().children(), function (key, item) {
                    var $td = $(item);
                    var $input = $td.find('input');
                    if ($input.attr('col')) {
                        $input.val(value);
                        $input.trigger('change');
                    }
                });
            }


        });

        $assignMonthlyValueBtn.on('click', function () {
            var fiscalYearId = $fiscalYearId.val();
            var monthId = $monthId.val();

            var promiseList = [];
            App.blockUI({target: "#hris-page-content"});
            $.each($grid.find('input'), function (key, item) {
                var $item = $(item);
                var rowValue = $item.attr('row');
                var colValue = $item.attr('col');
                var value = $item.val();
                if (typeof rowValue !== "undefined" && rowValue != null && rowValue != "" && typeof colValue !== "undefined" && colValue != null && colValue != "" && typeof value !== "undefined" && value != null && value != "") {
                    promiseList.push(app.pullDataById(document.setPositionMonthlyValueLink, {
                        monthId: monthId,
                        fiscalYearId: fiscalYearId,
                        positionId: rowValue,
                        mthId: colValue,
                        assignedValue: value
                    }));
                }

            });

            Promise.all(promiseList).then(function (response) {
                App.unblockUI("#hris-page-content");
                app.showMessage("Monthly Value assigned successfully!!!");
            }, function (error) {
                App.unblockUI("#hris-page-content");
            });



        });



    });
})(window.jQuery, window.app);
