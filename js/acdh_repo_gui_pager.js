jQuery(function($) {
    "use strict";
    var actionPage = '';
        
    if(window.location.href.indexOf("/oeaw_detail/") > -1) {
        actionPage = 'detail_view';
    }

    if(window.location.href.indexOf("/discover/root/") > -1) {
        actionPage = 'root';
    }
    
    var params = getUrlParams(actionPage);

    
    
    $(document).ready(function() {
        
        console.log('pager js ben');
        
        
    });
    
    function getUrlParams(actionPage = 'detail_view'){
        var urlPage;
        var urlLimit;
        var urlOrder;
        
        if(actionPage = 'root'){
            let searchParams = window.location.pathname.substring(window.location.pathname.indexOf("/discover/root/") + 15);
            //split the url to get the order limit and page values
            var searchValues = searchParams.split('/');
            if(searchValues[0]){
                urlOrder = searchValues[0];
            }
            if(searchValues[1]){
                urlLimit = searchValues[1];
            }
            if(searchValues[2]){
                urlPage = searchValues[2];
            }
        } else {
            let searchParams = new URLSearchParams(window.location.href);
            urlPage = searchParams.get('page');
            urlLimit= searchParams.get('limit');
            urlOrder= searchParams.get('order');
        }
        
        var obj = {urlPage: urlPage, urlLimit: urlLimit, urlOrder: urlOrder};
        return obj;
        
    } 
   
    
    $(document ).delegate( "#prev-btn", "click", function(e) {
        let newPageNumber = $(this).data('pagination');
        createNewUrl(newPageNumber, params.urlLimit, params.urlOrder, actionPage);
    });
    
    $(document ).delegate( "#next-btn", "click", function(e) { 
        let newPageNumber = $(this).data('pagination');
        createNewUrl(newPageNumber, params.urlLimit, params.urlOrder, actionPage);
    });
            
    
    /**
    * create and change the new URL after click events
    * 
    * @type Arguments
    */
    function createNewUrl(page, limit, orderBy, actionPage = ' detail_view'){
        var newurl = '';
        if (history.pushState) {
            if(actionPage == ' root') {
                console.log("a rootban creatnewurl");
               newurl = window.location.protocol + "//" + window.location.host + '/browser/discover/root/' + orderBy + '/' + limit + '/' + page; 
               console.log(newurl);
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
   
   
    
});

