jQuery.namespace = function () {
    var a = arguments,
        o = null,
        i, j, d;
    for (i = 0; i < a.length; i = i + 1) {
        d = a[i].split(".");
        o = window;
        for (j = 0; j < d.length; j = j + 1) {
            o[d[j]] = o[d[j]] || {};
            o = o[d[j]];
        }
    }
    return o;
};
jQuery.namespace('b4b');
jQuery.namespace('b4b.cfg');


$(document).ready(function () {

    $('.ajax-form').submit(function (event) {
        event.preventDefault();
        var formData = {};
        $(this).find('input[type=text],input[type=hidden]').each(function (i, el) {
            formData[$(el).attr('name')] = $(el).val();
        });

        sendAjaxPost($(this).attr('method'), $(this).attr('action'), formData);
    });

    $('.option-item-active').change(function () {
        if ($(this).is(':checked')) {
            $(this).parent().addClass('active');
        } else {
            $(this).parent().removeClass('active');
        }
    });
});


function sendAjaxPost(type, action, formData) {
    $.ajax({
        type: type,
        url: action,
        data: formData,
        dataType: 'json',
        encode: true
    }).fail(function (xhr) {
        var response = $.parseJSON(xhr.responseText);
        var error = '';
        if (xhr.status == 422 && response) {
            $.each(response, function (key, value) {
                error += value + '\n';
            });
            alert(error);
        } else {
            alert('Errore generico');
        }
    })
        .done(function () {
            alert('Salvato con successo!');
            location.reload();
        });
}
