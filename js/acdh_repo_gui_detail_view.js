 jQuery(function($) {
    "use strict";
 
    
    /** Handle the child button click  END **/
    
    $(document ).delegate( ".hideRepoChildView", "click", function(e) {
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
                $(".loader-div").show();
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
                            $(".loader-div").hide();
                        },
                        error: function(message) {
                            $('#block-mainpagecontent').html("Resource does not exists!");
                            $(".loader-div").hide();
                        }
                    });
                    e.preventDefault();
                } else {
                   window.open(url, '_blank'); 
                   $(".loader-div").hide();
                }
            });
        }
   });
   
   
   /**
     * Get the uuid from the url
     * 
     * @param {type} str
     * @returns {String}
     */
    function getIDFromUrl(str) {
        var reg = /^\d+$/;
	var res = "";
        if(str.indexOf('/oeaw_detail/') >= 0) {
            var n = str.indexOf("/oeaw_detail/");
            res = str.substring(n+13, str.length);
            
            if(res.indexOf('&') >= 0) {
                res = res.substring(0, res.indexOf('&'));
            }
            if(res.indexOf('?') >= 0) {
                res = res.substring(0, res.indexOf('?'));
            }
        }
        res = res.replace('id.acdh.oeaw.ac.at/uuid/', '');
        return res;
    }
    
    $(document ).delegate( ".res-act-button-treeview", "click", function(e) {
        if ($(this).hasClass('basic')) {
            $('.children-overview-basic').hide();
            $('.child-ajax-pagination').hide();
            $('.children-overview-tree').fadeIn(200);
            $(this).removeClass('basic');
            $(this).addClass('tree');
            $(this).children('span').text(Drupal.t('Switch to List-View'));
            
            let numberOfChildElements = $('#numberOfItems').val();
            
            if(numberOfChildElements > 10000) {
                $('#collectionBrowser').html("<h3>Error: </h3><p>"+  Drupal.t("This Resource has more than 10.000 child elements! Please use the download collection script!")+"</p>");
                return false;
            }
            //get the data
            var url = $('#insideUri').val();
            if(url){

                $('#collectionBrowser')
                .jstree({
                    core : {
                        'check_callback': false,
                        data : {
                            "url" : '/browser/get_collection_data/'+url,
                            "dataType" : "json"
                        },
                        themes : { stripes : true },
                        error : function (jqXHR, textStatus, errorThrown) { 
                            $('#collectionBrowser').html("<h3>Error: </h3><p>" + jqXHR.reason +"</p>");
                        } 
                    },
                    search: {
                        case_sensitive: false,
                        show_only_matches: true
                    },
                    plugins : [ 'search' ]
                });

                $('#collectionBrowser')
                //handle the node clicking to download the file
                .bind("click.jstree", function (node, data) {
                    if(node.originalEvent.target.id) {
                        
                        var node = $('#collectionBrowser').jstree(true).get_node(node.originalEvent.target.id);
                        if(node.original.encodedUri){
                            window.location.href = "/browser/oeaw_detail/"+node.original.uri;
                        }
                    }
                });
            }
        } else {
            $('.children-overview-tree').hide();
            $('.child-ajax-pagination').fadeIn(200);
            $('.children-overview-basic').fadeIn(200);
            $(this).removeClass('tree');
            $(this).addClass('basic');
            $(this).children('span').text(Drupal.t('Switch to Tree-View'));
        }
    });
            
});

