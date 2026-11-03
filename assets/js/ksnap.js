// Касса: сақталған күндік есепті қайта сақтау
$(document).ready(function () {
    $('html').on('click', '.ks_resave', function () {
        var b = $(this)
        if (b.hasClass('cd_wait')) return
        ask('Осы күннің касса есебін тапсырыстардан қайта есептеп, сақтайсыз ба?', function () {
            b.addClass('cd_wait')
            $.ajax({
                url: '/kassa/ksnap_get.php?save',
                type: 'POST',
                dataType: 'json',
                data: { day: b.attr('data-day') },
                success: function (r) {
                    if (r && r.ok) { mess('Сақталды', 'ok'); ajxReload(b) }
                    else { b.removeClass('cd_wait'); mess((r && r.msg) || 'Қате', 'err') }
                },
                error: function () { b.removeClass('cd_wait'); mess('Байланыс қатесі', 'err') }
            })
        }, { ok: 'Қайта сақтау', no: 'Болдырмау' })
    })
})
