 jQuery(function($) {
    "use strict";
 
    /** Handle the child button click **/
    $(document ).delegate( ".getRepoChildView", "click", function(e) {
        $(".loader-div").show();
        e.preventDefault();
        let searchParams = new URLSearchParams(window.location.href);
        
        //get the uuid
        var uuid = getIDFromUrl(window.location.href);
        uuid = uuid.replace('id.acdh.oeaw.ac.at/uuid/', '');
        var urlPage = searchParams.get('page');
        var urlLimit = searchParams.get('limit');
        var urlOrder = searchParams.get('order');
                
        if(!urlPage && !urlLimit && !urlOrder) {
            urlPage = 1;
            urlLimit = 10;
            urlOrder = 'titleasc';
        }
        getChildData(uuid, urlLimit, urlPage, urlOrder); 
        
    });
    /** Handle the child button click  END **/
    
    $(document ).delegate( ".hideRepoChildView", "click", function(e) {
        e.preventDefault();
        $('.res-act-button.hideChildView').hide();
        $('#getRepoChildView').show();
        $('#child-div-content').hide();
    });
    
    //if the url already contains the aparameters then we just get and load the data
    if(window.location.href.indexOf("/oeaw_detail/") > -1) {
        
        if(window.location.href.indexOf("&page=") > -1) {
            
            $(".loader-div").show();
            
            let searchParams = new URLSearchParams(window.location.href);
            
            var urlPage = searchParams.get('page');
            var urlLimit = searchParams.get('limit');
            var urlOrder = searchParams.get('order');
            
            $('.res-act-button.hideChildView').css('display', 'table');
            //get the uuid
            var uuid = getIDFromUrl(window.location.href);
            uuid = uuid.replace('id.acdh.oeaw.ac.at/uuid/', '');
            getChildData(uuid, urlLimit, urlPage, urlOrder);
        }
    }
    
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
        return res;
    }
    
    
    /**
    * Do the API request to get the actual child data
    * 
    * @param {type} insideUri
    * @param {type} limit
    * @param {type} page
    * @param {type} orderby
    * @returns {undefined}
    */
   function getChildData(insideUri, limit, page, orderby) {
       $(".loader-div").show();
       $.ajax({
           url: '/browser/repo_child_api/'+insideUri+'/'+limit+'/'+page+'/'+orderby,
           data: {'ajaxCall':true},
           async: true,
           success: function(result){
               //empty the data div, to display the new informations
               $('#child-div-content').show();
               $('#child-div-content').html(result);
               $('#limit-sel').val(limit);
               $('#actualPageSpan').val(page);
               $('#orderby').val(orderby);
               createNewUrl(page, limit, orderby);
               $('.getRepoChildView').hide();
               $(".loader-div").hide();
               $('#resPerPageButton').html(limit);
               $('.res-act-button.hideChildView').css('display', 'table');
               return false;
           },
           error: function(error){
               $('#child-div-content').html('<div>There is no data...</div>');
               $(".loader-div").hide();
               return false;
           }
       });
   }
   
   /**
    * create and change the new URL after click events
    * 
    * @type Arguments
    */
    function createNewUrl(page, limit, orderBy, actionPage = ' detail_view'){
        var newurl = '';
        if (history.pushState) {
            if(actionPage == ' root') {
               newurl = window.location.protocol + "//" + window.location.host + '/browser/discover/root/' + orderBy + '/' + limit + '/' + page; 
            }else {
                var path = window.location.pathname;
                var newUrlLimit = "&limit="+limit;
                var newUrlPage = "&page="+page;
                var newUrlOrder = "&order="+orderBy;
                var cleanPath = "";
                if(path.indexOf('&') != -1){
                    cleanPath = path.substring(0, path.indexOf('&'));
                } else {
                    cleanPath = path;
                }
                newurl = window.location.protocol + "//" + window.location.host + cleanPath + newUrlPage + newUrlLimit + newUrlOrder; 
           } 
               
           
           window.history.pushState({path:newurl},'',newurl);
       }
    }
    
    function createNewUrlForInsideClick(id) {
        var newurl = window.location.protocol + "//" + window.location.host + '/browser/oeaw_detail/'+id;
        window.history.pushState({path:newurl},'',newurl);
    }
   
    $(document).ready(function() {
        /**
         * If we are inside the oeaw_detail view, then we will just update the mainpagecontent div
         */
        if(window.location.href.indexOf("browser/oeaw_detail/") >= 0 ){
            
            $(document ).delegate( "a", "click", function(e) {
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
                        },
                        error: function(message) {
                            $('#block-mainpagecontent').html("Resource does not exists!");
                        }
                    });
                    $("#loader-div").hide();
                    e.preventDefault();
                }
                else {
                   window.open(url, '_blank'); 
                }
                $("#loader-div").hide();
            });
        }
   });
            
});

