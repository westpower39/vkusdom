document.addEventListener('DOMContentLoaded', () => {
	new Swiper('.section-popular-slider', {
	    slidesPerView: 'auto',
	    spaceBetween: 16,
	    navigation: {
	      nextEl: '.section-popular-slider-next',
	      prevEl: '.section-popular-slider-prev',
	    },
	    loop: true,
	    pagination: {
	      el: '.section-popular-slider-pagination',
	      clickable: true,
	    },
	  });  
});