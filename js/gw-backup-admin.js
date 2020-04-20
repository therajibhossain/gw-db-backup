/*document on ready*/
jQuery(document).ready(function ($) {
    /*on change event on admin menu tablinks*/
    var tablinks = $('.wpb_tablinks');
    if (tablinks.length > 0) {
        tablinks.click(function () {
            var i, id = $(this).attr("id"), tabcontent = $(".tabcontent");
            if (tabcontent.length > 0) {
                for (i = 0; i < tabcontent.length; i++) {
                    var content = tabcontent[i];
                    content.style.display = (content.id == id) ? 'block' : 'none';
                }
            }
            tablinks.removeClass('active');
            $(this).addClass('active');
        });
    }

    /*submitting settings form*/
    var $form = $('form.ajax');
    if ($form.length > 0) {
        $form.on('submit', function (e) {
            var submit = $('#submit');
            submit.spin();

            e.preventDefault();
            $.ajax({
                action: 'wpb_update_setting',
                method: "POST",
                dataType: 'json',
                data: {
                    action: 'wpb_update_setting',
                    formData: $form.serialize(),
                    wpb_section: $(this).attr('id'),
                },
                url: ajaxurl,

                success: function (result) {
                    var notice = '.wpb-notice-', response = result.response == 1 ? 'success' : 'error',
                        notice_class = notice + response;

                    $(notice_class).css('display', 'block');
                    $(notice_class + ' p').text(result.message);
                    submit.reset();
                },
                error: function (error) {
                    $(".wpb-notice-error").css("display", "block");
                    console.log(error);
                    submit.reset();
                }
            });
        });
    }
    /*on submit spinner*/
    $.fn.extend({
        spin: function () {
            this.data("original-html", this.html());
            var i = '<i class="fa fa-spinner fa-pulse fa-3x fa-fw" style="font-size:16px; margin-right:10px"></i> <span class="text-danger">Processing ...</span>';
            this.html(i);
            this.attr("disabled", "disabled");
        },
        reset: function () {
            this.html(this.data("original-html"));
            this.attr("disabled", null);
        }
    });
});