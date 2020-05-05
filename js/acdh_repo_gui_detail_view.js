 jQuery(function($) {
    "use strict";
 
    
    /** Handle the child button click  END **/
    
    $(document ).delegate( ".hideRepoChildView", "click", function(e) {
        console.log('hideRepoChildView');
        e.preventDefault();
        $('.res-act-button.hideChildView').hide();
        $('#getRepoChildView').show();
        $('#child-div-content').hide();
        $(".loader-div").hide();
    });
    
    
    function createNewUrlForInsideClick(id) {
        var newurl = window.location.protocol + "//" + window.location.host + '/browser/oeaw_detail/'+id;
        window.history.pushState({path:newurl},'',newurl);
    }
    
   
    $(document).ready(function() {
        /**
         * If we are inside the oeaw_detail view, then we will just update the mainpagecontent div
         */
        if(window.location.href.indexOf("browser/oeaw_detail/") >= 0 ){
            
            $(document ).delegate( "a#archeHref", "click", function(e) {
                var reloadTable = false;
                $("#loader-div").show();
                var url = $(this).attr('href');
                //if the url is arche url
                if(url && url.indexOf("/browser/oeaw_detail/") >= 0 || url && url.indexOf("/browser//oeaw_detail/") >= 0 ) {
                    $('html, body').animate({scrollTop: '0px'}, 0);
                    url = url.substring(url.indexOf("/browser/"));
                    $(".loader-div").show();
                    var id = url;
                    id = id.replace("/browser/oeaw_detail/", "");
                    id = id.replace("/browser//oeaw_detail/", "");
                    url = url+"&ajax=1";
                    $.ajax({
                        url: url,
                        type: "POST",
                        success: function(data, status) {
                            //change url
                            createNewUrlForInsideClick(id);
                            $('#block-mainpagecontent').html(data);
                            reloadTable = true;
                        },
                        error: function(message) {
                            $('#block-mainpagecontent').html("Resource does not exists!.");
                        }
                    });
                    //
                    $("#loader-div").hide();
                    e.preventDefault();
                }
                else {
                   window.open(url, '_blank'); 
                }
                $("#loader-div").hide();
                if(reloadTable) {
                    
                }
                
            });
        }
   });
            
});

