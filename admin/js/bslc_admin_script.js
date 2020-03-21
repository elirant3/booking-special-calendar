(function ($) {
    $(document).ready(function () {
        let bslc_hours = $('#bslc_hours').DataTable({
            ajax: {
                url: ajaxurl + "?action=get_bslcMeetingHours",
                dataSrc: 'data'
            },
            "processing": true,
            "serverSide": true,
        });

        $(document).on('click', '.bslc-houraction', function () {
            let that = $(this), id, status;
            id = that.data('id');
            status = that.data('trigger');

            if (!isNaN(id)) {
                if (id > 0) {
                    $.get(ajaxurl, 'trigger=' + status + '&hourid=' + id + '&action=bslc_hourAction&bslc_houraction=' + BSLC.bslc_houraction, function (data) {
                        if (data.success) {
                            iziToast.success({
                                message: data.success,
                            });

                            if (status === 'status') {
                                if (data.btn_text.toLowerCase() === 'enable') {
                                    that.removeClass('button-primary');
                                } else if (data.btn_text.toLowerCase() === 'disable') {
                                    that.addClass('button-primary');
                                }
                            } else if (status === 'delete') {
                                bslc_hours
                                    .row(that.parents('tr'))
                                    .remove()
                                    .draw();
                            } else if (status === 'closeOpen') {
                                if (data.btn_text.toLowerCase() === 'open') {
                                    that.removeClass('button-primary');
                                } else if (data.btn_text.toLowerCase() === 'close') {
                                    that.addClass('button-primary');
                                }
                            }

                            that.html(data.btn_text);
                        } else if (data.error) {
                            iziToast.error({
                                title: 'Error:',
                                message: data.error,
                            });
                        }
                    });
                }
            }
        });
    });
})(jQuery);
