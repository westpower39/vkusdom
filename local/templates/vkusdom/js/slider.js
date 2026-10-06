var Slide = {
	Init: function(){
		
		$("[data-slider]").each(function(){
			var Id = $(this).attr("data-slider");
			
			if(Id != ""){
				new Swiper(
				    '.section-production-slider'+Id,
				    {
				      slidesPerView: 'auto',
				      spaceBetween: 16,
				      navigation: {
				        nextEl: '.section-production-slider-next'+Id,
				        prevEl: '.section-production-slider-prev'+Id,
				      },
				      loop: true,
				      pagination: {
				        el: '.section-production-slider-pagination'+Id,
				        clickable: true,
				      },
				      breakpoints: {
				        320: {
				          spaceBetween: 8,
				        },
				        768: {
				          spaceBetween: 16,
				        },
				      },
				    },
				);
				$(this).attr("data-slider","");
			}	
		});
		
	}
}

$(document).ready(function(){
	Slide.Init();
	
})