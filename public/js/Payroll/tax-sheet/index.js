(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var months = null;
        var $year = $('#fiscalYearId');
        var $month = $('#monthId');
        var $employeeId = $('#employeeId');
        var $viewBtn = $('#viewBtn');
        var $taxSheetBody = $('#taxSheetBody');
        var $excelExport = $('#excelExport');
        var $pdfExport = $('#pdfExport');
        app.setFiscalMonth($year, $month, function (yearList, monthList, currentMonth) {
            months = monthList;
        });
        app.setEmployeeSearch($employeeId);
        var showTaxSheet = function ($data) {
            $taxSheetBody.html('');
            var additionData = {};
            var additionCounter = 0;
            var additionSum = 0
            var deductionData = {};
            var deductionCounter = 0;
            var deductionSum = 0;
            $.each($data, function (index, item) {
                switch (item['PAY_TYPE_FLAG']) {
                    case 'A':
                        additionData[additionCounter] = item;
                        additionSum = additionSum + parseFloat(item['VAL']);
                        additionCounter++;
                        break;
                    case 'D':
                        deductionData[deductionCounter] = item;
                        deductionSum = deductionSum + parseFloat(item['VAL']);
                        deductionCounter++;
                        break;
                }
            });
            var maxRows = (additionCounter > deductionCounter) ? additionCounter : deductionCounter;
            for (var i = 0; i < maxRows; i++) {
                var $row = $(`<tr>
                                <td>${(typeof additionData[i] !== 'undefined') ? additionData[i]['PAY_EDESC'] : ''}</td>
                                <td>${(typeof additionData[i] !== 'undefined') ? additionData[i]['VAL'] : ''}</td>
                                <td>${(typeof deductionData[i] !== 'undefined') ? deductionData[i]['PAY_EDESC'] : ''}</td>
                                <td>${(typeof deductionData[i] !== 'undefined') ? deductionData[i]['VAL'] : ''}</td>
                                </tr>`);
                $taxSheetBody.append($row);
            }
            $taxSheetBody.append($(`<tr>
                                <td>Total:</td>
                                <td>${additionSum}</td>
                                <td>Total:</td>
                                <td>${deductionSum}</td>
                                </tr>`));

        }
        $viewBtn.on('click', function () {
            var monthId = $month.val();
            var employeeId = $employeeId.val();
            app.serverRequest('', {monthId: monthId, employeeId: employeeId}).then(function (response) {
                showTaxSheet(response.data);
            }, function (error) {

            });
        });

        $pdfExport.on('click', function () {
            app.exportDomToPdf2($('#taxSheetView'));
        });

    });
})(window.jQuery, window.app);