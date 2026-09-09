document.addEventListener('DOMContentLoaded', () => {
  //phone validation
  const phonePattern = /^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/;
  const phoneInputs = document.querySelectorAll('input[type="tel"]');

  const authButton = document.querySelector('.btn-auth-step-1');

  const updateAuthButton = () => {
    if (!authButton) {
      return;
    }
    const allValid =
      phoneInputs.length > 0 &&
      Array.from(phoneInputs).every((item) => phonePattern.test(item.value));

    if (allValid) {
      authButton.removeAttribute('disabled');
    } else {
      authButton.setAttribute('disabled', '');
    }
  };

  phoneInputs.forEach((input) => {
    const parentBlock = input.closest('.form__block') || input.parentElement;

    const validatePhone = () => {
      if (input.value !== '' && !phonePattern.test(input.value)) {
        parentBlock.classList.add('form__block_error');
      } else {
        parentBlock.classList.remove('form__block_error');
      }
      updateAuthButton();
    };

    input.addEventListener('input', validatePhone);
    input.addEventListener('blur', validatePhone);
  });

  updateAuthButton();

  //auth steps switch
  const step1Block = document.querySelector('.auth-page__step-1');
  const step2Block = document.querySelector('.auth-page__step-2');

  //code resend timer
  const timerElement = document.querySelector('.timer-code');
  const step2Button = document.querySelector('.btn-auth-step-2');
  let codeTimer = null;

  const secondsPlural = (n) => {
    const mod10 = n % 10;
    const mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) {
      return 'секунда';
    }
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
      return 'секунды';
    }
    return 'секунд';
  };

  const startCodeTimer = () => {
    if (!timerElement) {
      return;
    }
    if (codeTimer) {
      clearInterval(codeTimer);
    }
    let seconds = 60;
    timerElement.textContent = `через ${seconds} ${secondsPlural(seconds)}`;
    codeTimer = setInterval(() => {
      seconds -= 1;
      if (seconds <= 0) {
        clearInterval(codeTimer);
        codeTimer = null;
        timerElement.textContent = '';
        step2Button?.removeAttribute('disabled');
      } else {
        timerElement.textContent = `через ${seconds} ${secondsPlural(seconds)}`;
      }
    }, 1000);
  };

  authButton?.addEventListener('click', () => {
    step1Block?.classList.add('hide');
    step2Block?.classList.remove('hide');
    startCodeTimer();
  });

  //auth links switch
  const authLinks = document.querySelectorAll('.auth-page__link');

  authLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      step1Block?.classList.add('hide');
      step2Block?.classList.remove('hide');
    });
  });

  //code input validation
  const codeInputs = document.querySelectorAll('.form__block-code input');

  codeInputs.forEach((codeInput) => {
    // const codeBlock = codeInput.closest('.form__block-code');

    const validateCode = () => {
      if (codeInput.value === '') {
        codeInput.classList.add('error');
      } else {
        codeInput.classList.remove('error');
      }
    };

    codeInput.addEventListener('input', validateCode);
    codeInput.addEventListener('blur', validateCode);
  });
});

document.addEventListener('DOMContentLoaded', () => {
  Fancybox.bind('[data-fancybox]', {
    // Your custom options
  });

  //cart block
  const cartButtons = document.querySelectorAll('.catalog-item__button');
  cartButtons.forEach((button) => {
    const parentBlock = button.closest('.catalog-item__desc');
    const counterBlock = parentBlock.querySelector('.catalog-item__counter');
    button.addEventListener('click', function (e) {
      counterBlock.classList.remove('hide');
      button.classList.add('hide');
    });
  });

  //favorite block
  const favoriteButtons = document.querySelectorAll('.catalog-item__favorit');
  favoriteButtons.forEach((button) => {
    button.addEventListener('click', function (e) {
      if (!button.classList.contains('active')) {
        button.classList.add('active');
      } else {
        button.classList.remove('active');
      }
    });
  });
  
  //collapse block
  const collapseButtons = document.querySelectorAll('.catalog-detail__description-shop-name');
  collapseButtons.forEach((button) => {
    const parentBlock = button.closest('.catalog-detail__description-shop');
    button.addEventListener('click', function (e) {
      if (!parentBlock.classList.contains('show-collapse')) {
        parentBlock.classList.add('show-collapse');
      } else {
        parentBlock.classList.remove('show-collapse');
      }
    });
  });

  //collapse block
  const collapseButtonsMenu = document.querySelectorAll(
    '.catalog-menu__block-title',
  );
  collapseButtonsMenu.forEach((button) => {
    const parentBlock = button.closest('.catalog-menu__block');
    button.addEventListener('click', function (e) {
      if (!parentBlock.classList.contains('show')) {
        parentBlock.classList.add('show');
      } else {
        parentBlock.classList.remove('show');
      }
    });
  });

  //collapse block filter
  const collapseButtonsFilter = document.querySelectorAll(
    '.catalog-filter__block-title',
  );
  collapseButtonsFilter.forEach((button) => {
    const parentBlock = button.closest('.catalog-filter__block');
    button.addEventListener('click', function (e) {
      if (!parentBlock.classList.contains('show')) {
        parentBlock.classList.add('show');
      } else {
        parentBlock.classList.remove('show');
      }
    });
  });

  const optionsButtonsFilter = document.querySelectorAll(
    '.catalog-filter__block-option',
  );
  optionsButtonsFilter.forEach((button) => {
    button.addEventListener('click', function (e) {
      if (!button.classList.contains('active')) {
        button.classList.add('active');
      } else {
        button.classList.remove('active');
      }
    });
  });

  new SlimSelect({
    select: '#sort-select',
    settings: {
      showSearch: false,
      // contentWidth: '>245px',
    },
  });
  //range slider
  let stepsSlider = document.getElementById( 'steps-slider' );
  if ( stepsSlider !== null && stepsSlider !== undefined ) {
    let input0 = document.getElementById( 'input-with-keypress-0' );
    let input1 = document.getElementById( 'input-with-keypress-1' );
    let inputs = [ input0, input1 ];
    let format = {
      to: function ( value ) {
        return Number( Math.round( value ) );
      },
      from: function ( value ) {
        return Number( Math.round( value ) );
      },
    };
    noUiSlider.create( stepsSlider, {
      start: [ 0, 1800 ],
      connect: true,
      tooltips: [ true, true ],
      format: format,
      range: {
        min: 0,
        max: 1800,
      },
    } );

    stepsSlider.noUiSlider.on( 'update', function ( values, handle ) {
      inputs[ handle ].value = values[ handle ];
    } );
  }

  //range slider mobile
  let stepsSlider2 = document.getElementById( 'steps-slider2' );
  if ( stepsSlider2 !== null && stepsSlider2 !== undefined ) {
    let input2 = document.getElementById( 'input-with-keypress-2' );
    let input3 = document.getElementById( 'input-with-keypress-3' );
    let inputs2 = [ input2, input3 ];
    let format2 = {
      to: function ( value ) {
        return Number( Math.round( value ) );
      },
      from: function ( value ) {
        return Number( Math.round( value ) );
      },
    };
    noUiSlider.create( stepsSlider2, {
      start: [ 0, 1800 ],
      connect: true,
      tooltips: [ true, true ],
      format: format2,
      range: {
        min: 0,
        max: 1800,
      },
    } );

    stepsSlider2.noUiSlider.on( 'update', function ( values, handle ) {
      inputs2[ handle ].value = values[ handle ];
    } );
  }

  //mask phone
  let telInputs = document.querySelectorAll('input[type="tel"]');
  if (telInputs) {
    let im = new Inputmask('+7 (999) 999-99-99');
    im.mask(telInputs);
  }

  //header-catalog-menu
  const categories = document.querySelectorAll('[data-category]');
  categories.forEach(category => {
    const categoryValue = category.dataset.category;
    const subcategory = document.querySelector(`[data-subcategory="${categoryValue}"]`);
    if (subcategory) {
      category.addEventListener('mouseenter', function() {
        subcategory.style.display = 'block';
      });
      category.addEventListener('mouseleave', function() {
        subcategory.style.display = 'none';
      });
    }
  } );
  const headerCatalogButton = document.querySelector( '.header-search__catalog-button' );
  const body = document.querySelector( 'body' );
  headerCatalogButton?.addEventListener('mouseenter', function() {
    body.classList.add('header-catalog-menu-show');
  } );
  const headerCatalogMenu = document.querySelector( '.header-catalog-menu' );
   headerCatalogMenu?.addEventListener('mouseleave', function() {
    body.classList.remove('header-catalog-menu-show');
  } );

  //toggle filter mobile
  const toggleFilter = document.querySelector('.btn_mobile-filter');
  const filterBlock = document.querySelector('.catalog-filter_mobile');
  toggleFilter?.addEventListener('click', function (e) {
    filterBlock.classList.add('open-filter');
  } );
  const closeFilter = document.querySelector('.catalog-filter__close');
  closeFilter?.addEventListener('click', function (e) {
    filterBlock.classList.remove('open-filter');
  } );

  //toggle filter blocks
  const toggleFilterButtons = document.querySelectorAll(
    '.catalog-filter__block-more',
  );
  toggleFilterButtons.forEach((button) => {
    const parentBlock = button.closest('.catalog-filter__block-params');
    button.addEventListener('click', function (e) {
      if (!parentBlock.classList.contains('full-options')) {
        parentBlock.classList.add('full-options');
        button.innerHTML = 'Скрыть';
      } else {
        parentBlock.classList.remove('full-options');
        button.innerHTML = 'Еще';
      }
    });
  });


  //counter
  document.querySelectorAll('.counter__plus').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = btn.parentElement.querySelector('.counter__input');
      const val = Number(input.value);
      input.value = !isNaN(val) ? val + 1 : 1;
    });
  });
  document.querySelectorAll('.counter__minus').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = btn.parentElement.querySelector('.counter__input');
      const val = Number(input.value);
      input.value = !isNaN(val) && val > 1 ? val - 1 : 1;
    });
  });

  const swiperMenuSlider = new Swiper('.header-menu-slider', {
    slidesPerView: 'auto',
    spaceBetween: 20,
    navigation: {
      nextEl: '.header-menu-slider-next',
      prevEl: '.header-menu-slider-prev',
    },
    loop: false,
  });

  const swiperMainSlider = new Swiper('.main-slider', {
    slidesPerView: 3,
    spaceBetween: 16,
    navigation: {
      nextEl: '.main-slider-next',
      prevEl: '.main-slider-prev',
    },
    loop: true,
    pagination: {
      el: '.main-slider-pagination',
      clickable: true,
    },
    breakpoints: {
      320: {
        slidesPerView: 1.2,
        spaceBetween: 16,
      },
      480: {
        slidesPerView: 2,
        spaceBetween: 16,
      },
      640: {
        slidesPerView: 3,
        spaceBetween: 16,
      },
    },
  });

  const swiperSectionPopularSlider = new Swiper('.section-popular-slider', {
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

  const swiperSectionProductsSlider01 = new Swiper(
    '.section-production-slider01',
    {
      slidesPerView: 'auto',
      spaceBetween: 16,
      navigation: {
        nextEl: '.section-production-slider-next01',
        prevEl: '.section-production-slider-prev01',
      },
      loop: true,
      pagination: {
        el: '.section-production-slider-pagination01',
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

  const swiperSectionProductsSlider02 = new Swiper(
    '.section-production-slider02',
    {
      slidesPerView: 'auto',
      spaceBetween: 16,
      navigation: {
        nextEl: '.section-production-slider-next02',
        prevEl: '.section-production-slider-prev02',
      },
      loop: true,
      pagination: {
        el: '.section-production-slider-pagination02',
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

  const swiperSectionProductsSlider03 = new Swiper(
    '.section-production-slider03',
    {
      slidesPerView: 'auto',
      spaceBetween: 16,
      navigation: {
        nextEl: '.section-production-slider-next03',
        prevEl: '.section-production-slider-prev03',
      },
      loop: true,
      pagination: {
        el: '.section-production-slider-pagination03',
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

  const swiperSectionProductsSlider04 = new Swiper(
    '.section-production-slider04',
    {
      slidesPerView: 'auto',
      spaceBetween: 16,
      navigation: {
        nextEl: '.section-production-slider-next04',
        prevEl: '.section-production-slider-prev04',
      },
      loop: true,
      pagination: {
        el: '.section-production-slider-pagination04',
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
  const swiperSectionBrandsSlider = new Swiper('.section-main-brands-slider', {
    slidesPerView: 5.2,
    spaceBetween: 10,
    navigation: {
      nextEl: '.section-main-brands-slider-next',
      prevEl: '.section-main-brands-slider-prev',
    },
    loop: true,
    breakpoints: {
      // when window width is >= 320px
      320: {
        slidesPerView: 2.6,
        spaceBetween: 8,
      },
      // when window width is >= 480px
      480: {
        slidesPerView: 3.2,
        spaceBetween: 10,
      },
      // when window width is >= 640px
      640: {
        slidesPerView: 4,
        spaceBetween: 10,
      },
      1024: {
        slidesPerView: 5.2,
        spaceBetween: 10,
      },
    },
  });

  // Initialize the Thumbs Swiper
  const galleryThumbs = new Swiper('.product-slider-thumbs', {
   
    slidesPerView: 5,
    spaceBetween: 8,
     loop: true,

      freeMode: true,
      watchSlidesProgress: true,
    // Configure as needed
  });

  // Initialize the Main Swiper
  const galleryMain = new Swiper('.product-slider', {
    direction: 'horizontal',
    thumbs: {
      swiper: galleryThumbs, // Link thumbs instance
    },
    pagination: {
        el: '.product-slider-pagination',
        clickable: true,
      },
    
  });
} );

// При фокусе на input - добавляем класс в body
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.header-search__input');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            document.body.classList.add('search-active');
        });
        
        input.addEventListener('blur', function() {
            document.body.classList.remove('search-active');
        });
    });
});




jQuery( document ).ready( function ( $ ) {

  //mobile menu
  $(document).mouseup(function (e) {
    const container = $('.popup-menu');
    if (!container.is(e.target) && container.has(e.target).length === 0) {
      $('body').removeClass('open-menu');
    }
  });

  $('.hamburger').click(function () {
    if ($('.hamburger').hasClass('is-active')) {
      $('.hamburger').removeClass('is-active');
      $('body').removeClass('open-menu');
    } else {
      $('.hamburger').addClass('is-active');
      $('body').addClass('open-menu');
    }
  });

  //TABS
  lightTabs('.tabs')

});
 // Popups
  let popupCurrent;
  let popupsList = document.querySelectorAll('.popup-outer-box');
  let popupTimer = null;

  document.querySelectorAll('.js-popup-open').forEach(function (element) {
    element.addEventListener('click', function (e) {
      document.querySelector('.popup-outer-box').classList.remove('active');
      document.body.classList.add('popup-open');
      if (popupTimer) {
        clearTimeout(popupTimer);
        popupTimer = null;
      }

      for (i = 0; i < popupsList.length; i++) {
        popupsList[i].classList.remove('active');
      }

      popupCurrent = this.getAttribute('data-popup');
      const popupElement = document.querySelector(
        `.popup-outer-box[id="${popupCurrent}"]`,
      );
      console.log(popupCurrent, popupElement);
      popupElement.classList.add('active');

      const timerValue = this.getAttribute('data-popup-timer');
      if (timerValue) {
        const timerMs = parseInt(timerValue);
        if (!isNaN(timerMs) && timerMs > 0) {
          popupTimer = setTimeout(function () {
            document.body.classList.remove('popup-open');
            document.body.classList.remove('popup-open-scroll');
            popupElement.classList.remove('active');
            popupTimer = null;
          }, timerMs);
        }
      }

      e.preventDefault();
      e.stopPropagation();
      return false;
    });
  });

  document.querySelectorAll('.js-popup-close').forEach(function (element) {
    element.addEventListener('click', function (event) {
      if (popupTimer) {
        clearTimeout(popupTimer);
        popupTimer = null;
      }

      document.body.classList.remove('popup-open');
      for (i = 0; i < popupsList.length; i++) {
        popupsList[i].classList.remove('active');
      }
      event.preventDefault();
      event.stopPropagation();
    });
  });