(function ($, Drupal, window, document) {

    Drupal.behaviors.THEMESlickConfig = {
        attach: function (context, settings) {

            $(".js-slick-single-item-center").slick({
                infinite: true,
                speed: 1000,
                autoplay: true,
                autoplaySpeed: Math.random()*10000 + 2000,
                slidesToShow: 1,
                prevArrow: '<button type="button" class="slick-prev"></button>',
                nextArrow: '<button type="button" class="slick-next"></button>'
                // responsive: [
                //     {
                //         breakpoint: 1600,
                //         settings: {
                //             centerPadding: '300px',
                //         }
                //     },
                //     {
                //         breakpoint: 960,
                //         settings: {
                //             adaptiveHeight: true,
                //             centerPadding: '100px',
                //         }
                //     },
                // ]
            });

        }
    };

} (jQuery, Drupal, this, this.document));