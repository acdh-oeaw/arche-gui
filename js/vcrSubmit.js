jQuery(function ($) {


    $("#submit-search-vcr").click(function (e) {

        e.preventDefault();
        $('#submit-search-vcr').hide();
        $('#vcr-data-loading').show();
        setInterval(blink_text('vcr-data-loading'), 10000);

        let metavalue = vcrMeta();
        let url = "/browser/api/search_vcr/" + metavalue;
        $.ajax({
            type: "GET",
            url: url,
            success: function (data) { 
                
                buildAndSubmitVcrForm($('#search-clarinurl').val(), data);
                
                $('#vcr-data-loading').hide();
                $('#vcr-search-result-text').append('VCR Data Submitted');
                setTimeout(
                    function ()
                    {
                        
                        //$('#dynamicVcr').submit(function() { e.preventDefault();});
                        $('#vcr-search-result-text').hide();
                        $('#submit-search-vcr').show();
                    }, 2000);
            },
            error: function(data) {
                $('#vcr-search-result-text').append('Error: ' + data.responseText);
            }
        });
       

    });

    function blink_text(text_id) {
        $('#' + text_id).fadeOut(500);
        $('#' + text_id).fadeIn(500);
    }

    function buildAndSubmitVcrForm(clarinUrl, data) {
        
        $('<form action="' + clarinUrl + '" method="POST" target="_blank" id="dynamicVcr" class="dynamicVcrForm">\n\
        <input type="hidden" name="name" value="ArcheCollection"/>\n\</form>').appendTo('#vcr-search-form');
        let obj = JSON.parse(data);
        $.each(obj, function (key, value) {
            $('<input type="hidden" id="vcrResourceUri" name="resourceUri"/>').val(JSON.stringify(value)).appendTo('#dynamicVcr');
        });
        
        $('#dynamicVcr').submit();
        
    }
    
      /**
     * Get the search string values for the vcr data
     * @returns string
     */
    function vcrMeta() {
        let url = window.location.href;
     
        if(url.includes('/browser/discover/root')) {
           return "type=acdh:TopCollection";
        }else if (url.includes('/search/')) {
            url = url.substring(url.indexOf("/search/") + 8);
            return url.split('&payload', 8)[0];
        } else {
            alert('This is not a search page!');
        }
    }
    
    
    
    
});

