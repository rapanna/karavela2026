jQuery( document ).ready(function() {
    $ = jQuery;
    if($('.term__btn').length){
        $( ".term__btn" ).each(function( index ) {
            $( this).click(function( event ) {
                event.preventDefault();
               $('#reservation_code' ).val($( this).data('code'));
             

                /*$('.single-trip__content').hide();
                $('.content--trip__reservation-form').fadeIn(500);
                $('html, body').animate({scrollTop: $('.title--h1').offset().top -100 }, 'slow');*/
                jQuery('.reservation-date').hide();
                jQuery('#trip-reservation-form').addClass("open");
                jQuery('#trip-reservation-form form').attr('action', window.location.href);
                jQuery(".content--trip__reservation-form__content form").css(
                    "height",
                    jQuery(".content--trip__reservation-form__content form").innerHeight() *
                      0.8 +
                    "px"
                  );
                
              });
        });

        jQuery("#trip-reservation-form, #trip-reservation-form button.close").on("click keyup", function(event) {
            if (
              event.target == this ||
              event.target.className == "close" ||
              event.keyCode == 27
            ) {
                jQuery('#trip-reservation-form').removeClass("open");
                //window.location.href = "https://karavela.cz";
               // jQuery(this).removeClass("open");
            }
          });
    }

    if($('.reservation-main-btn').length){
        $('.reservation-main-btn').click(function( event ) {
            event.preventDefault();
            jQuery('.reservation-date').show();
            $('#reservation_code' ).val('');
            jQuery('#trip-reservation-form').addClass("open");
            jQuery('#trip-reservation-form form').attr('action', window.location.href);
            jQuery(".content--trip__reservation-form__content form").css(
                "height",
                jQuery(".content--trip__reservation-form__content form").innerHeight() *
                  0.8 +
                "px"
              );
        });

        jQuery("#trip-reservation-form, #trip-reservation-form button.close").on("click keyup", function(event) {
            if (
              event.target == this ||
              event.target.className == "close" ||
              event.keyCode == 27
            ) {
              jQuery('#trip-reservation-form').removeClass("open");
              //window.location.href = "https://karavela.cz";
              // jQuery(this).removeClass("open");
            }
          });
    }

    // Kontaktní formulář „Zavolejte mi": povinný aspoň jeden z dvojice telefon/e-mail.
    if($('#trip-contact-form .k26-contact-form').length){
        var $contactForm = $('#trip-contact-form .k26-contact-form');
        var $phone = $contactForm.find('#reservation_phone_c');
        var $email = $contactForm.find('#reservation_email_c');

        var clearOneOf = function(){
            $phone[0].setCustomValidity('');
            $email[0].setCustomValidity('');
        };
        $phone.add($email).on('input', clearOneOf);

        $contactForm.on('submit', function( event ){
            if ( $.trim( $phone.val() ) === '' && $.trim( $email.val() ) === '' ) {
                var msg = 'Vyplňte prosím alespoň jeden kontakt – telefon, nebo e-mail.';
                $phone[0].setCustomValidity(msg);
                $phone[0].reportValidity();
                event.preventDefault();
            } else {
                clearOneOf();
            }
        });
    }

    if($('.more_terms_btn').length){
        $('.more_terms_btn').click(function( event ) {
            event.preventDefault();
            $.post(
                karavela_ajax, 
                {
                    'action': 'morecontent',
                    'offset':   karavela_offset
                }, 
                function(response) {
                    
                    var TripsData = jQuery.parseJSON(response);
                    console.log(TripsData);

                    $.each( TripsData, function( key, value ) {
                        if(key != "count"){
                            var trip_content;
                            trip_content = '<div class="col-sm-4 col-md-3">';
                            trip_content = trip_content+'<div class="box">';
                            trip_content = trip_content+'<a href="'+value.permalink+'" class="box-link">';
                           
                            trip_content = trip_content+'<img src="'+value.thumbnail+'" style="max-width:100%;" class="box-link__img" />';
                            trip_content = trip_content+'<h2 class="box-link__title">'+value.title+'</h2>';
                            trip_content = trip_content+'<div class="box-link-date">';
                            trip_content = trip_content+'<span class="box-link-date__term">'+value.start_date+' - '+value.end_date+'</span>';
                            trip_content = trip_content+'<span class="box-link-date__long">'+value.day_count+' dní</span>';
                            trip_content = trip_content+'</div>';

                            trip_content = trip_content+'<div class="box-link-cost">';
                            trip_content = trip_content+'<span class="box-link-cost__default">Cena: '+value.price+' Kč </span>';

                            if(value.price_fm){
                                trip_content = trip_content+'<span class="box-link-cost__fm">Cena FM: '+value.price_fm+' Kč</span>';
                            }

                            trip_content = trip_content+'</div>';

                            trip_content = trip_content+'</a>';
                            trip_content = trip_content+'</div>';
                            trip_content = trip_content+'</div>';
                            console.log(trip_content);
                            $( trip_content).insertBefore( $( '.recommended-trips .col-sm-12' ) ).hide().fadeIn();
                            //$('.recommended-trips .col-sm-12').prepend(trip_content);

                        }
                    });

                    if(TripsData.count < 12){
                        $('.more_terms_btn').fadeOut();
                    }

                    karavela_offset = parseInt(karavela_offset)+1;
                    console.log(karavela_offset);
                }
            );
        });
    }

    if($('.btn-archive').length){
        $('.btn-archive').click(function( event ) {
            event.preventDefault();
            $.post(
                karavela_ajax, 
                {
                    'action': 'loadedcontent',
                    'offset':  $('.btn-archive').data('count'),
                    'post_type':   $('.btn-archive').data('posttype'),
                    'posts':   $('.btn-archive').data('posts')
                }, 
                function(response) {
                    
                    var TripsData = jQuery.parseJSON(response);

                    $.each( TripsData, function( key, value ) {
                        if(key != "count"){
                            var trip_content;
                            trip_content = '<div class="col-sm-4 col-md-3">';
                            trip_content = trip_content+'<div class="box">';
                            trip_content = trip_content+'<a href="'+value.permalink+'" class="box-link">';
                           
                            trip_content = trip_content+'<img src="'+value.thumbnail+'" style="max-width:100%;" class="box-link__img" />';
                            trip_content = trip_content+'<h2 class="box-link__title">'+value.title+'</h2>';
                            trip_content = trip_content+'<div class="box-link-date">';
                            trip_content = trip_content+'<span class="box-link-date__term">'+value.start_date+' - '+value.end_date+'</span>';
                            trip_content = trip_content+'<span class="box-link-date__long">'+value.day_count+' dní</span>';
                            trip_content = trip_content+'</div>';

                            trip_content = trip_content+'<div class="box-link-cost">';
                            trip_content = trip_content+'<span class="box-link-cost__default">Cena: '+value.price+' Kč </span>';

                            if(value.price_fm){
                                trip_content = trip_content+'<span class="box-link-cost__fm">Cena FM: '+value.price_fm+' Kč</span>';
                            }

                            trip_content = trip_content+'</div>';

                            trip_content = trip_content+'</a>';
                            trip_content = trip_content+'</div>';
                            trip_content = trip_content+'</div>';
                            //console.log(trip_content);
                            //$( '.trips-content' ).append(trip_content );
                            $(trip_content ).hide().appendTo( '.trips-content').fadeIn();
                            //$( trip_content).append( $( '.trips-content' ) ).hide().fadeIn();
                            //$('.recommended-trips .col-sm-12').prepend(trip_content);

                        }
                    });
                    console.log(TripsData.count);
                    if(TripsData.count < $('.btn-archive').data('posts')){
                        $('.btn-archive').fadeOut();
                    }
                    console.log(parseInt($('.btn-archive').data('count'))+20);
                   // $('.btn-archive').attr('data-count', parseInt($('.btn-archive').data('count'))+20);
                    $('.btn-archive').data('count', parseInt($('.btn-archive').data('count'))+20); 

                    if( $('.pagination').length){
                        $('.pagination').find('.current').removeClass('current').next().addClass('current');

                    }
                }
            );
        });
    }
});

// ── Archiv zájezdů: postupné odkrývání „Zobraz další zájezdy" ─────────────────
(function(){
    var btn = document.querySelector('.k26-load-more');
    if ( ! btn ) return;
    var batch = parseInt( btn.dataset.batch, 10 ) || 12, stagger = 90, busy = false;

    btn.addEventListener('click', function(){
        if ( busy ) return;
        var hidden = document.querySelectorAll('.k26-trip-hidden');
        if ( ! hidden.length ) return;
        busy = true;
        var toShow = Math.min( batch, hidden.length );

        // Postupné odkrývání s lehkým náběhem – aby bylo jasné, co se děje
        for ( var i = 0; i < toShow; i++ ) {
            (function( card, delay ){
                setTimeout(function(){
                    card.classList.add( 'k26-revealing' );      // výchozí stav (opacity 0)
                    card.classList.remove( 'k26-trip-hidden' );  // zobraz
                    requestAnimationFrame(function(){
                        requestAnimationFrame(function(){
                            card.classList.remove( 'k26-revealing' ); // plynulý náběh
                        });
                    });
                }, delay);
            })( hidden[i], i * stagger );
        }

        // Po dokončení dávky uvolni tlačítko a případně ho schovej
        setTimeout(function(){
            busy = false;
            if ( document.querySelectorAll( '.k26-trip-hidden' ).length === 0 ) {
                btn.style.display = 'none';
            }
        }, toShow * stagger + 200);
    });
})();