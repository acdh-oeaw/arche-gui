jQuery(function ($) {

    $(document).ready(function () {
    });

    $("#submit-search-vcr").click(function (e) {

        e.preventDefault();
        $('#submit-search-vcr').hide();
        $('#vcr-data-loading').show();
        setInterval(blink_text('vcr-data-loading'), 10000);
        
        let metavalue = vcrMeta();
        let url = "/browser/search_vcr/" + metavalue;
        $.get(url).success(function (data) {
            buildAndSubmitVcrForm($('#search-clarinurl').val(), data);
            $('#vcr-data-loading').hide();
            $('#vcr-search-result-text').append('VCR Data Submitted');
        }).error(function (data) {
            console.log('error' + data);
            $('#vcr-search-result-text').append('Error: ' + data);
        });

    });

    function blink_text(text_id) {
        $('#' + text_id).fadeOut(500);
        $('#' + text_id).fadeIn(500);
    }

    /**
     * Get the search string values for the vcr data
     * @returns {unresolved}
     */
    function vcrMeta() {
        let test = window.location.href;
        test = test.substring(test.indexOf("/search/") + 8);
        return test.split('&payload', 8)[0];
    }


    function buildAndSubmitVcrForm(clarinUrl, data) {
        $('<form action="' + clarinUrl + '" method="POST" target="_blank">\n\
        <input type="hidden" name="name" value="ArcheCollection"/>\n\
        <input type="hidden" name="resourceUri" value=' + data + ' /></form>').appendTo('#vcr-search-form');//.submit();
    }


});

