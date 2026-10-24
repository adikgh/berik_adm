// start jquery
$(document).ready(function() {




    
    $('html').on('input', '.btype_rask', function () {
        btn = $(this)
        btype_start = Number(btn.parent().siblings('.btype_start').attr('data-start'))
        btype_cash = Number(btn.parent().siblings('td').children('.btype_cash').attr('data-val'))
        btype_rask = Number(btn.attr('data-val'))

        $(this).parent().siblings('.btype_kaspi').html((btype_start - btype_cash - btype_rask) + ' тг')

        $.ajax({
            url: "/kassa/get.php?expenses",
            type: "POST",
            dataType: "html",
            data: ({ 
                id: btn.attr('data-id'),
                user_id: btn.attr('data-user-id'),
                expenses: btype_rask,
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
        btype_rask = Number(btn.parent().siblings('td').children('.btype_rask').attr('data-val'))
        btype_cash = Number(btn.attr('data-val'))

        $(this).parent().siblings('.btype_kaspi').html((btype_start - btype_cash - btype_rask) + ' тг')

        $.ajax({
            url: "/kassa/get.php?cash",
            type: "POST",
            dataType: "html",
            data: ({ 
                id: btn.attr('data-id'),
                user_id: btn.attr('data-user-id'),
                cash: btype_cash,
            }),
            success: function(data){ 
                // if (data == 'yes') location.reload();
                console.log(data);
            },
            beforeSend: function(){ },
            error: function(data){ }
        })
    })



        // cashbox_pay
	$('.read_one').click(function(){
		$('.read_block').addClass('pop_bl_act');
		$('#html').addClass('ovr_h');

        $('.read_pay2').attr('data-id', $(this).attr('data-id'))      
        $('.read_pay2').attr('data-user-id', $(this).attr('data-user-id'))      
	})
	$('.read_back').click(function(){
		$('.read_block').removeClass('pop_bl_act');
		$('#html').removeClass('ovr_h');

        $('.read_pay2').attr('data-id', '')      
        $('.read_pay2').attr('data-user-id', '')
	})
    $('html').on('click', '.read_pay2', function () {
        sum = Number($('.btype_kaspi').attr('data-val'))

        btn = $(this)
        $.ajax({
            url: "/kassa/get.php?kaspi",
            type: "POST",
            dataType: "html",
            data: ({ 
                id: $('.read_pay2').attr('data-id'),
                user_id: $('.read_pay2').attr('data-user-id'),
                kaspi: sum,
            }),
            success: function(data){ 
                if (data == 'yes') location.reload();
                console.log(data);
            },
            beforeSend: function(){ },
            error: function(data){ }
        })
    })




   // 
	$('.on_sort_branch').on('change', function () {
      var val = $(this).children('option:selected').attr('data-id');
      const url = new URL(window.location);
      url.searchParams.set('branch', val); 
      history.pushState(null, null, url);
      location.reload();
	})




















}) // end jquery