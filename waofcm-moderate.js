jQuery(function ($) {

    $('body').addClass('js');

    $('.' + waofcmmoderatejs.pluginprefix + '-report').click(function () {

        if (confirm(waofcmmoderatejs.confirm_report)) {
            var element = $(this);
            var cid = $(this).data('cid');
            var nonce = $(this).data('nonce');

            $(element).addClass('waofcm-moderate-loading');
            $(element).text(waofcmmoderatejs.reporting_string + '…');

            $.ajax({
                type: 'POST',
                url: waofcmmoderatejs.adminurl,
                data: {
                    action: waofcmmoderatejs.pluginprefix + '_flag',
                    id: cid,
                    _ajax_nonce: nonce
                },
                success: function (response_data) {
                    $(element).removeClass('waofcm-moderate-loading');

                    var msg = $(document.createElement('span'))
                        .addClass(waofcmmoderatejs.pluginprefix + '-report ' + waofcmmoderatejs.pluginprefix + '-success')
                        .text(response_data);
                    $(element).replaceWith(msg);
                },
                dataType: 'html'
            });

        }

        return false;

    });

});