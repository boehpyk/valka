$(document).ready(function() {

    $('.top-slider').slick({
        dots: true,
        arrows: false,
        infinite: true
    });

    $('#photo-slider').slick({
        dots: false,
        infinite: true,
        centerMode: true,
        variableWidth: true
    });

    $('.reviews__slider').slick({
        dots: false,
        infinite: true,
        slidesToShow: 1,
    });


    $('.data-fancybox').fancybox();

    $( "#photo-slider .photo__slide" ).on( "click", function() {
        $(this).find('.data-fancybox').triggerHandler('click');
    });


    // $(".photo__slide").fancybox();
});
