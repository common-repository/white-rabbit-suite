jQuery(function ($) {
    $("#options").submit(function (e) {
        $("#loader").show();
        $('#import').attr('disabled', true);
        setTimeout(function () {
            window.location.reload(1);
        }, 30000);
        return true;
    });


    if ($("#refresh").length) {
        setTimeout(function () {
            window.location.reload(1);
        }, 30000);
    }

})
;


