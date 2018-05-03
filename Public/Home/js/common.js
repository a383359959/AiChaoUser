$(function(){
	$('.back').bind('click',function(){
		history.go(-1);
	});
	
	/*
	*	配送时间
	*/
	/*
	$('.peisongshijian').bind('click',function(){
		var css = $('.pssj').css('bottom');
		if(css == '0px'){
			$('.pssj').animate({'bottom' : '-165px'});
		}else{
			$('.pssj').animate({'bottom' : '0px'});
		}
	});
	$('.pssj h1 a').bind('click',function(){
		$('.pssj').animate({'bottom' : '-165px'});
	});
	$('.pssj li a').bind('click',function(){
		$('.peisongshijian p').text($(this).text());
		$('input[name="pei_time"]').val($(this).text());
		$('.pssj').animate({'bottom' : '-165px'});
	});
	*/
	
	/*
	*	配送方式
	*/
	/*
	$('.peisongfangshi').bind('click',function(){
		var css = $('.psfs').css('bottom');
		if(css == '0px'){
			$('.psfs').animate({'bottom' : '-165px'});
		}else{
			$('.psfs').animate({'bottom' : '0px'});
		}
	});
	$('.psfs h1 a').bind('click',function(){
		$('.psfs').animate({'bottom' : '-165px'});
	});
	$('.psfs li a').bind('click',function(){
		var text = $(this).text();
		if(text == '兼职配送'){
			$('.dashangjine').show();
			$('.dashang_price').show();
		}else{
			$('.dashangjine').hide();
			$('.dashang_price').hide();
		}
		$('.peisongfangshi p').text($(this).text());
		$('input[name="pei_type"]').val($(this).text());
		$('.psfs').animate({'bottom' : '-165px'});
	});
	$('.dashangjine input').bind('keyup',function(){
		var value = $(this).val();
		if(value > 0){
			$('.dashang_price').show();
			$('.dashang_price_main').text('￥' + value);
		}
		var len = $('.checkout_goods tr').length;
		var index = 0;
		var count = 0;
		$('.checkout_goods tr').each(function(){
			index++;
			if(index != len){
				var price = $(this).find('td').eq(2).text().replace('￥','');
				price = Number(price);
				count += price;
			}
		});
		$('.checkout_goods tr').eq(len - 1).find('td').eq(2).text('￥' + count);
		$('input[name="dashang_price"]').val(value);
	});*/
});