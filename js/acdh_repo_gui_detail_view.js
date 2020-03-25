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
        console.log(uuid);
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
    * create and change the new URL after click events
    * 
    * @type Arguments
    */
    function createNewUrl(page, limit, orderBy){
       if (history.pushState) {
           var path = window.location.pathname;
           var newUrlLimit = "&limit="+limit;
           var newUrlPage = "&page="+page;
           var newUrlOrder = "&order="+orderBy;
           var cleanPath = "";
           if(path.indexOf('&') != -1){
               cleanPath = path.substring(0, path.indexOf('&'));
           }else {
               cleanPath = path;
           }
           var newurl = window.location.protocol + "//" + window.location.host + cleanPath + newUrlPage + newUrlLimit + newUrlOrder;
           window.history.pushState({path:newurl},'',newurl);
       }
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
       console.log('/browser/repo_child_api/'+insideUri+'/'+limit+'/'+page+'/'+orderby);
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
   
   
   //************************* PAGINATION ****************************************//
   if(window.location.href.indexOf("/oeaw_detail/") > -1) {
       $(document ).delegate( "#prev-btn", "click", function(e) {            
       console.log("prev btn");
   });
   
   $(document ).delegate( "#next-btn", "click", function(e) {            
       console.log("next btn");
        e.preventDefault();
/*
  $(".loader-div").show();
        var urlParams = generateUrlParams();
        var urlPage = urlParams['urlPage'];
        var urlLimit = urlParams['urlLimit'];
        var urlOrder = urlParams['urlOrder'];
        var maxPageLimit = urlParams['maxPageLimit'];
        var insideUri = urlParams['insideUri'];

        if ($(this).hasClass('disabled')){
            $(".loader-div").hide();
            return false;
        }

        if(maxPageLimit == parseInt(urlPage) + 1){
            urlPage = parseInt(urlPage) + 1;
            $( "#next-btn" ).addClass('disabled');
            getData(insideUri, urlLimit, urlPage, urlOrder);
        }else if (maxPageLimit == urlPage) {
            $( "#next-btn" ).addClass('disabled');
            $(".loader-div").hide();
            return false;
        }else {
            $( "#next-btn" ).removeClass('disabled');                
            urlPage = parseInt(urlPage) + 1;
            getData(insideUri, urlLimit, urlPage, urlOrder);
        }
        $('#actualPageSpan').html(urlPage);
        createNewUrl(urlPage, urlLimit, urlOrder);
        $(".loader-div").delay(2000).fadeOut("fast");
        $(".loader-div").hide();
        //to skip the jump to top function
        return false;
        * */  
 
    });
    
       
   }
   
  
            
});

