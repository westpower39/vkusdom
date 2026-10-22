$(document).ready(function(){
	
	$(".lk-rating input[name='RATING']").click(function(){
		var Data = $(this).parents("form").serializeArray();
		$(this).parents(".lk-rating__item").remove();
		
		
		$.ajax({
			type:"POST",
			data: Data,
			url: "/local/components/custom/product.rating/ajax.php"
		});
		
	});
	
	
});