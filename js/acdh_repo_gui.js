 jQuery(function($) {
    
    
    $(document ).delegate( ".res-act-button-expertview", "click", function(e) {
//$('.res-act-button-expertview').click(function() {
    if ($(this).hasClass('basic')) {
        $('.single-res-overview-basic').hide();
        $('.single-res-overview-expert').fadeIn(200);
        $(this).removeClass('basic');
        $(this).addClass('expert');
        $(this).children('span').text(Drupal.t('Switch to Basic-View'));
    } else {
        $('.single-res-overview-expert').hide();
        $('.single-res-overview-basic').fadeIn(200);
        $(this).removeClass('expert');
        $(this).addClass('basic');
        $(this).children('span').text(Drupal.t('Switch to Expert-View'));
    }
});


    
            
});

