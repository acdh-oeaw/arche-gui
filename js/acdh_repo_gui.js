 jQuery(function($) {
    
    $( document ).ready(function() {
        console.log('ready');
    });
    
    $( "#submit-search-vcr").click(function(e) {
        e.preventDefault();
        console.log('clicked');
        
        let test = window.location.href;
        test = test.substring(test.indexOf("/search/") + 8);
        console.log(test);
        
        var params = new window.URLSearchParams(window.location.search);
console.log(params);
        let clarinUrl = $('#search-clarinurl').val();
        let searchString = "type=acdh:Collection";
        let url = "/browser/search_vcr/"+searchString;
        $.get(url).success(function (data) {
                

              
        }).error(function (data) {
                console.log('error' + data);
        });
        
    });

    
});

