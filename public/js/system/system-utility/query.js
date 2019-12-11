(function ($, app) {
    'use strict';
    $(document).ready(function () {

        var $table = $('#table');
        var $search = $('#search');
        var $reset = $('#reset');

        var columns = [];

        var map = {};

        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'Query result.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'Query result.pdf');
        });
        
        $search.on('click', function () {

            var query_input = $('#query_input').val();
            var data = {
                query: query_input
            };
            var resData;
            app.serverRequest(document.executeQueryLink, data).then(function (response) {
                if (response.success) {
                    resData = response.data;
                    $table.empty();
                    columns = [];
                    map = {};
                    for (var x in response.data[0]) {
                        columns.push({field: x, title: x, width: 130});
                        map[x] = x;
                    }
                    
                    app.initializeKendoGrid($table, columns);
                    app.renderKendoGrid($table, resData);

                } else {
                    app.showMessage(response.message, 'error');
                }
            }, function (error) {
//                app.showMessage(error, 'error');
            });
        });

        $reset.on('click', function () {
            $('#query_input').val('');
        });
    });
})(window.jQuery, window.app);

