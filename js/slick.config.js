(function ($, Drupal, window, document) {

    Drupal.behaviors.THEMESlickConfig = {
        attach: function (context, settings) {
            var asNavSelector = null;
            if($('body').hasClass('page-node-type-property')) {
                asNavSelector = '.field--name-cottage-images-control ul';
            }

            $(".field--name-cottage-images ul").slick({
                infinite: true,
                speed: 1000,
                autoplay: true,
                autoplaySpeed: Math.random()*10000 + 2000,
                slidesToShow: 1,
                slidesToScroll: 1,
                prevArrow: '<button type="button" class="slick-prev"></button>',
                nextArrow: '<button type="button" class="slick-next"></button>',
                mobileFirst: true,
                centerMode: false,
                variableWidth: false,
                focusOnSelect: true,
                arrows: true,
                asNavFor: asNavSelector
            });

            $('.field--name-cottage-images-control ul').slick({
                focusOnSelect: true,
                slidesToShow: 5,
                slidesToScroll: 1,
                asNavFor: '.field--name-cottage-images ul',
                arrows: false,
                vertical: true,
                centerMode: true,
                verticalSwiping: true,
                responsive: [
                    {
                        breakpoint: 1400,
                        settings: {
                            centerMode: false,
                            verticalSwiping: false,
                            vertical: false,
                            slidesToShow: 3
                        }
                    },
                ]
            });

        }
    };

} (jQuery, Drupal, this, this.document));