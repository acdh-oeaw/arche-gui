jQuery(function($) {
    "use strict";
    var actionPage = '';
        
    if(window.location.href.indexOf("/oeaw_detail/") > -1) {
        actionPage = 'detail_view';
    }

    if(window.location.href.indexOf("/discover/") > -1) {
        if(window.location.href.indexOf("/discover/root/") > -1) {
            actionPage = 'root';
        }else{
            actionPage = 'search';
        }
    }
    
    var params = getUrlParams(actionPage);
    
    $(document).ready(function() {
    });
    
    function getUrlParams(actionPage = 'detail_view'){
        var urlPage;
        var urlLimit;
        var urlOrder;
        var searchStr;
        
        if(actionPage == 'root'){
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
        } else if(actionPage == 'search'){
            let searchParams = window.location.pathname.substring(window.location.pathname.indexOf("/discover/") + 10);
            
            //split the url to get the order limit and page values
            var searchValues = searchParams.split('/');
            
            if(searchValues[0]){
                searchStr = searchValues[0];
            }
            if(searchValues[1]){
                urlOrder = searchValues[1];
            }
            if(searchValues[2]){
                urlLimit = searchValues[2];
            }
            if(searchValues[3]){
                urlPage = searchValues[3];
            }
            
        } else {
            
            let searchParams = new URLSearchParams(window.location.href);
            urlPage = searchParams.get('page');
            urlLimit= searchParams.get('limit');
            urlOrder= searchParams.get('order');
        }
        var obj = {urlPage: urlPage, urlLimit: urlLimit, urlOrder: urlOrder, searchStr: searchStr};
        return obj;
        
    } 
   
    
    $(document ).delegate( "#prev-btn", "click", function(e) {
        let newPageNumber = $(this).data('pagination');
        createNewUrl(newPageNumber, params.urlLimit, params.urlOrder, actionPage, params.searchStr);
    });
    
    $(document ).delegate( "#next-btn", "click", function(e) { 
        let newPageNumber = $(this).data('pagination');
        createNewUrl(newPageNumber, params.urlLimit, params.urlOrder, actionPage, params.searchStr);
    });
            
    
    /**
    * create and change the new URL after click events
    * 
    * @type Arguments
    */
    function createNewUrl(page, limit, orderBy, actionPage = ' detail_view', searchStr = ''){
        var newurl = '';
        //if (history.pushState) {
            if(actionPage == 'root') {
               newurl = window.location.protocol + "//" + window.location.host + '/browser/discover/root/' + orderBy + '/' + limit + '/' + page; 
               console.log(newurl);
            } else if(actionPage = 'search') {
                newurl = window.location.protocol + "//" + window.location.host + '/browser/discover/'+ searchStr +'/' + orderBy + '/' + limit + '/' + page; 
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
       //}
   }
   
   
    
});

