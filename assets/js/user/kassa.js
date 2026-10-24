// start jquery
$(document).ready(function() {

    $('html').on('input', '.btype_rask', function () {
        btn = $(this)
        btype_start = Number(btn.parent().siblings('.btype_start').attr('data-start'))
        sum = Number(btn.attr('data-val'))
        $(this).parent().siblings('.btype_kaspi').html((btype_start - sum) + ' тг')
        btn.parent().siblings('.btype_start').attr('data-rask', sum)

        $.ajax({
            url: "/kassa/get.php?expenses",
            type: "POST",
            dataType: "html",
            data: ({ 
                id: btn.attr('data-id'),
                user_id: btn.attr('data-user-id'),
                expenses: sum,
            }),
            success: function(data){ 
                // if (data == 'yes') location.reload();
                console.log(data);
            },
            beforeSend: function(){ },
            error: function(data){ }
        })
    })
    
    $('html').on('input', '.btype_cash', function () {
        btn = $(this)
        btype_start = Number(btn.parent().siblings('.btype_start').attr('data-start'))
        btype_rask = Number(btn.parent().siblings('.btype_start').attr('data-rask'))
        sum = Number(btn.attr('data-val'))

        $(this).parent().siblings('.btype_kaspi').html((btype_start - sum - btype_rask) + ' тг')

        $.ajax({
            url: "/kassa/get.php?cash",
            type: "POST",
            dataType: "html",
            data: ({ 
                id: btn.attr('data-id'),
                user_id: btn.attr('data-user-id'),
                cash: sum,
            }),
            success: function(data){ 
                // if (data == 'yes') location.reload();
                console.log(data);
            },
            beforeSend: function(){ },
            error: function(data){ }
        })
    })

}) // end jquery