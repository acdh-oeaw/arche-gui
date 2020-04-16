 jQuery(function($) {
    
    $(document ).delegate( ".res-act-button-expertview", "click", function(e) {
        if ($(this).hasClass('basic')) {
            $('.single-res-overview-basic').hide();
            $('.single-res-overview-expert').fadeIn(200);
            $(this).removeClass('basic');
            $(this).addClass('expert');
            $(this).children('span').text(Drupal.t('Switch to Basic-View'));
            //we need to destroy and reinit the expert view datatable, because it has an html data
            $('#expertTable').DataTable().destroy();
            $('#expertTable').DataTable();
        } else {
            $('.single-res-overview-expert').hide();
            $('.single-res-overview-basic').fadeIn(200);
            $(this).removeClass('expert');
            $(this).addClass('basic');
            $(this).children('span').text(Drupal.t('Switch to Expert-View'));
        }
    });
});

