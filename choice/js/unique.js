$(function(){
	
	$('.mainSection .btn').on('click', function(ev){
		ev.preventDefault();
		$(this).parent().toggleClass('active').find('.block02').stop().slideToggle(500);
	});
	
});