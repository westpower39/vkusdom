$(document).ready(function(){
	
	if($("#FormSort [name='sort']").length){
		new SlimSelect({
			select: "#FormSort [name='sort']",
			settings: {
				showSearch: false,
			},
			events: {
			    afterChange: (newVal) => {
			    	$("#FormSort").submit();
			    }
			}
		});
	}
	
	
});