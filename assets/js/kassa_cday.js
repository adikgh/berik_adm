// Курьерлердің күндік есебін сақтау (Касса беті)
$(document).ready(function() {

    function cday_send(act, data, btn) {
        if (btn.hasClass('cd_wait')) return
        btn.addClass('cd_wait')
        $.ajax({
            url: '/kassa/cday_get.php?' + act,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function (r) {
                if (r && r.ok) location.reload()
                else { btn.removeClass('cd_wait'); alert((r && r.msg) || 'Қате') }
            },
            error: function () { btn.removeClass('cd_wait'); alert('Байланыс қатесі') }
        })
    }

    // Kaspi қалдығы теріс болса қызыл (kassa.js санды жаңартқаннан кейін)
    $('html').on('input', '.k_cour .btype_rask, .k_cour .btype_cash', function () {
        var k = $(this).closest('tr').children('.btype_kaspi')
        k.toggleClass('k_neg', parseInt(k.text(), 10) < 0)
    })

    // бір курьерді сақтау
    $('html').on('click', '.cd_save', function () {
        var b = $(this)
        if (!confirm(b.attr('data-name') + ': бүгінгі есепті қабылдап, сақтайсыз ба?')) return
        cday_send('save', { courier_id: b.attr('data-user-id'), day: b.attr('data-day'), cashbox_id: b.attr('data-id') }, b)
    })

    // барлығын сақтау
    $('html').on('click', '.cd_save_all', function () {
        var b = $(this)
        if (!confirm('Барлық курьердің есебін қабылдап, сақтайсыз ба?')) return
        cday_send('save', { all: 1, day: b.attr('data-day'), cashbox_id: b.attr('data-id') }, b)
    })

    // сақтауды болдырмау
    $('html').on('click', '.cd_undo', function () {
        var b = $(this)
        if (!confirm(b.attr('data-name') + ': сақталған есепті ашып, қайта түзетесіз бе?')) return
        cday_send('undo', { courier_id: b.attr('data-user-id'), day: b.attr('data-day'), cashbox_id: b.attr('data-id') }, b)
    })

})
