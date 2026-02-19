$(document).ready(function () {

    $('#datatable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 50,
        "fnRowCallback": function (nRow, aData, iDisplayIndex) {
            var a = $(nRow).find('li.delete a').first();
            if(a.length < 1){ return; }
            var $events = jQuery._data(a, "events");
            //console.log($events);
            if (typeof $events !== "undefined") return;
            $(nRow).find('li.delete a').click(function (event) {
                event.preventDefault();
                event.stopPropagation();
                var target = ( $(event.target).is('a') ? $(event.target).first() : $(event.target).parent('a').first() );
                if (confirm('Sei sicuro di voler cancellare questo record? ' + target.attr('href'))) {
                    var target = ( $(event.target).is('a') ? $(event.target).first() : $(event.target).parent('a').first() );
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val()
                        }
                    });
                    $.ajax({
                        url: target.attr('href'),
                        type: 'POST'
                    }).done(function (response) {

                        if (response.success) {
                            target.parents('tr').remove();
                            $('.btn-group.open').removeClass('open');
                            alert('Fatto!');
                        } else {
                            alert('Qualcosa e\' andato storto');
                        }
                    }).fail(function (response, textStatus) {
                        alert('Qualcosa e\' andato storto');
                    });
                }

                return false;

            });
        }
    });
});
