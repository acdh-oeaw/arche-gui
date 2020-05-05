jQuery(function($) {
    "use strict";
    var actionPage = '';
    
    if(window.location.href.indexOf("/oeaw_detail/") > -1) {
        actionPage = 'detail_view';
    }

    if(window.location.href.indexOf("/discover/") > -1) {
        if(window.location.href.indexOf("/discover/root") > -1) {
            actionPage = 'root_main';
            if(window.location.href.indexOf("/discover/root/") > -1) {
                actionPage = 'root';
            }
        }else{
            actionPage = 'search';
        }
    }
    
    //var params = getUrlParams(actionPage);
    
    $(document).ready(function() { 
        addExtraSortingForViews();
        var params = getUrlParams(actionPage);
        
        //we already have an url
        if(window.location.href.indexOf("/oeaw_detail/") > -1) {
            if(window.location.href.indexOf("&page=") > -1) {
                //get the uuid/repoid
                var repoid = getIDFromUrl(window.location.href);
                getChildData(repoid, params.urlLimit, params.urlPage, params.urlOrder, function(result){
                    if(params.urlOrder != null && params.urlLimit != null) {
                        updateGui(params.urlOrder, params.urlLimit)
                    }
                });
            }
        }
        
        if(params.urlOrder != null && params.urlLimit != null) {
            updateGui(params.urlOrder, params.urlLimit)
        }
        
    });
    
    //add some extra sorting fields
    function addExtraSortingForViews() {
        var currentURL = window.location.toString();
        //extend the search dropdown for the persons
        if (currentURL.indexOf("type=Person/") >= 0 || currentURL.indexOf("type=Person&") >= 0) {
            $('#sortByDropdown').append('<a class="dropdown-item" data-value="lastnameasc" href="#">Last Name (ASC)</a>');
            $('#sortByDropdown').append('<a class="dropdown-item" data-value="lastnamedesc" href="#">Last Name (DESC)</a>');            
            $('.sortByDropdownBottom').append('<a class="dropdown-item" data-value="lastnameasc" href="#">Last Name (ASC)</a>');
            $('.sortByDropdownBottom').append('<a class="dropdown-item" data-value="lastnamedesc" href="#">Last Name (DESC)</a>');
        }
        //extend the collection with 
        if (currentURL.indexOf("type=Collection/") >= 0 || currentURL.indexOf("type=Collection&") >= 0) {
            $('#sortByDropdown').append('<a class="dropdown-item" data-value="dateasc" href="#">Date (ASC)</a>');
            $('#sortByDropdown').append('<a class="dropdown-item" data-value="datedesc" href="#">Date (DESC)</a>');
            $('.sortByDropdownBottom').append('<a class="dropdown-item" data-value="dateasc" href="#">Date (ASC)</a>');
            $('.sortByDropdownBottom').append('<a class="dropdown-item" data-value="datedesc" href="#">Date (DESC)</a>');
        }

        if (currentURL.indexOf("discover/root") >= 0) {
            $('#sortByDropdown').append('<a class="dropdown-item" data-value="dateasc" href="#">Date (ASC)</a>');
            $('#sortByDropdown').append('<a class="dropdown-item" data-value="datedesc" href="#">Date (DESC)</a>');
            $('.sortByDropdownBottom').append('<a class="dropdown-item" data-value="dateasc" href="#">Date (ASC)</a>');
            $('.sortByDropdownBottom').append('<a class="dropdown-item" data-value="datedesc" href="#">Date (DESC)</a>');
        }
    }
    
    // get the actual url params like limit/order/offset based on the view
    function getUrlParams(actionPage = 'detail_view'){
        var urlPage;
        var urlLimit;
        var urlOrder;
        var searchStr;
        
        if(actionPage == 'root' || actionPage == 'root_main'){
            
            var searchParams = '';
            
            if(actionPage == 'root'){
                searchParams = window.location.pathname.substring(window.location.pathname.indexOf("/discover/root/") + 15);
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
                urlPage = 1;
                urlLimit = 10;
                urlOrder = 'datedesc';
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
            //detail view child paging
            let searchParams = new URLSearchParams(window.location.href);
            urlPage = searchParams.get('page');
            urlLimit= searchParams.get('limit');
            urlOrder= searchParams.get('order');
        }
        if(urlOrder){
            urlOrder = urlOrder.replace('#','');
        }
        if(urlLimit && $.isNumeric(urlLimit) === false){
            urlLimit = urlLimit.replace('#','');
        }
        if(urlPage && $.isNumeric(urlPage) === false){
            urlPage = urlPage.replace('#','');
        }
        
        let orderTexts = {
            titleasc: 'Title (ASC)', titledesc: 'Title (DESC)', 
            dateasc:  'Date (ASC)', datedesc: 'Date (DESC)'
        };
        
        //change the gui values
        $('.sortByButton').html(orderTexts[urlOrder]);
        $('.resPerPageButton').html(urlLimit);
        var obj = {urlPage: urlPage, urlLimit: urlLimit, urlOrder: urlOrder, searchStr: searchStr};
        return obj;
    }
    
    //update the gui elements
    function updateGui(order, limit) {
        let orderTexts = {
            titleasc: 'Title (ASC)', titledesc: 'Title (DESC)', 
            dateasc:  'Date (ASC)', datedesc: 'Date (DESC)'
        };
        
        //change the gui values
        $('.sortByButton').html(orderTexts[order]);
        $('.resPerPageButton').html(limit);
    }
    
    //the pager buttons
    $(document ).delegate( "#first-btn", "click", function(e) {
        let newPageNumber = $(this).data('pagination');
        let params = getUrlParams(actionPage);
        var uuid = getIDFromUrl(window.location.href);
        getChildData(uuid, params.urlLimit, newPageNumber, params.urlOrder, function(result){
            createNewUrl(newPageNumber, params.urlLimit, params.urlOrder, actionPage); 
            if( params.urlOrder != null && params.urlLimit != null) {
                updateGui( params.urlOrder, params.urlLimit)
            }
        });
    });
    
    $(document ).delegate( "#last-btn", "click", function(e) {
        let newPageNumber = $(this).data('pagination');
        let params = getUrlParams(actionPage);
        var uuid = getIDFromUrl(window.location.href);
        getChildData(uuid, params.urlLimit, newPageNumber, params.urlOrder, function(result){
            createNewUrl(newPageNumber, params.urlLimit, params.urlOrder, actionPage); 
            if( params.urlOrder != null && params.urlLimit != null) {
                updateGui( params.urlOrder, params.urlLimit)
            }
        });
        //createNewUrl(newPageNumber, params.urlLimit, params.urlOrder, actionPage, params.searchStr);
    });
    
    $(document ).delegate( "#prev-btn", "click", function(e) {
        let newPageNumber = $(this).data('pagination');
        let params = getUrlParams(actionPage);
        var uuid = getIDFromUrl(window.location.href);
        getChildData(uuid, params.urlLimit, newPageNumber, params.urlOrder, function(result){
            createNewUrl(newPageNumber, params.urlLimit, params.urlOrder, actionPage); 
            if( params.urlOrder != null && params.urlLimit != null) {
                updateGui( params.urlOrder, params.urlLimit)
            }
        });
    });
    
    $(document ).delegate( "#next-btn", "click", function(e) { 
        let newPageNumber = $(this).data('pagination');
        let params = getUrlParams(actionPage);
        var uuid = getIDFromUrl(window.location.href);
        getChildData(uuid, params.urlLimit, newPageNumber, params.urlOrder, function(result){
            createNewUrl(newPageNumber, params.urlLimit, params.urlOrder, actionPage); 
            if( params.urlOrder != null && params.urlLimit != null) {
                updateGui( params.urlOrder, params.urlLimit)
            }
        });
    });
    
    //Results info-bar pagination selectors on click
    $(document).delegate( '#resPerPageButton > a', "click", function(event) {
        let newLimit = $(this).html();
        let params = getUrlParams(actionPage);
        createNewUrl(1, newLimit, params.urlOrder, actionPage, params.searchStr);
    
    });
    $(document).delegate( '#sortByDropdown > a', "click", function(event) {
        let newOrder = $(this).data('value');
        let params = getUrlParams(actionPage);
        createNewUrl(params.urlPage, params.urlLimit, newOrder, actionPage, params.searchStr);
    });
    
  
    function getCleanPath() {
        let path = window.location.pathname;
        var cleanPath = "";
        if(path.indexOf('&') != -1){
            cleanPath = path.substring(0, path.indexOf('&'));
        } else {
            cleanPath = path;
        }
        return cleanPath;
    }
    /**
    * create and change the new URL after click events
    * 
    * @type Arguments
    */
    function createNewUrl(page, limit, orderBy, actionPage = ' detail_view', searchStr = ''){
        var newurl = '';
        //if (history.pushState) {
        if(actionPage == 'root' || actionPage == 'root_main') {
           newurl = window.location.protocol + "//" + window.location.host + '/browser/discover/root/' + orderBy + '/' + limit + '/' + page; 
           window.history.pushState({path:newurl},'',newurl);
           window.location = newurl;
        } else if(actionPage == 'search') {
            newurl = window.location.protocol + "//" + window.location.host + '/browser/discover/'+ searchStr +'/' + orderBy + '/' + limit + '/' + page; 
            window.history.pushState({path:newurl},'',newurl);
            window.location = newurl;
        } else {
            //child view
            let newUrlLimit = "&limit="+limit;
            let newUrlPage = "&page="+page;
            let newUrlOrder = "&order="+orderBy;
            let cleanPath = getCleanPath();
            newurl = window.location.protocol + "//" + window.location.host + cleanPath + newUrlPage + newUrlLimit + newUrlOrder; 
            window.history.pushState({path:newurl},'',newurl);
            
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
        res = res.replace('id.acdh.oeaw.ac.at/uuid/', '');
        return res;
    }
    
    /** Handle the child button click **/
    $(document ).delegate( ".getRepoChildView", "click", function(e) {
        //$(".loader-div").show();
        e.preventDefault();
        let searchParams = new URLSearchParams(window.location.href);
        //get the uuid
        var uuid = getIDFromUrl(window.location.href);
        var urlPage = searchParams.get('page');
        var urlLimit = searchParams.get('limit');
        var urlOrder = searchParams.get('order');
        if(!urlPage && !urlLimit && !urlOrder) {
            urlPage = 1;
            urlLimit = 10;
            urlOrder = 'titleasc';
        }
            
        getChildData(uuid, urlLimit, urlPage, urlOrder, function(result){
            createNewUrl(urlPage, urlLimit, urlOrder); 
            if(urlOrder != null && urlLimit != null) {
                updateGui(urlOrder, urlLimit)
            }
        });
            
        //$(".loader-div").hide();
        return false;
        
    });
    
    /**
    * Do the API request to get the actual child data
    * 
    * @param {type} insideUri
    * @param {type} limit
    * @param {type} page
    * @param {type} orderby
    * @returns {undefined}
    */
    function getChildData(insideUri, limit, page, orderby, callbackFunction) {
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
                $('.getRepoChildView').hide();
                $('#resPerPageButton').html(limit);
                $('.res-act-button.hideChildView').css('display', 'table');
                if(typeof callbackFunction == 'function'){
                    callbackFunction.call(this, result);
                }
                $(".loader-div").hide();
                return true;
            },
            error: function(error){
                $('#child-div-content').html('<div>There is no data...</div>');
                $(".loader-div").hide();
                return false;
            }
        });
    }
   
   
    
});

