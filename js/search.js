jQuery(function ($) {
    
    "use strict";
    $(document).ready(function () {
        var currentURL = window.location.toString();
        var breadcrumbSearchInfo = "";
        breadcrumbSearchInfo = checkSelectedSearchValues();

        if (breadcrumbSearchInfo) {
            breadcrumbSearchInfo = '<a href="' + currentURL + '">' + Drupal.t("Searched for") + breadcrumbSearchInfo + '</a>';
            $('#searchInfo').html('/ ' + breadcrumbSearchInfo);
        }
    });

    //Check if we can append selected query to filters
    function checkSelectedSearchValues() {
        var breadcrumbSearchInfo = "";
        breadcrumbSearchInfo = checkSelectedTypes();
        breadcrumbSearchInfo += checkSelectedCategories();
        var selectedPayload = getParameterByName('payload', window.location.toString());
        if (selectedPayload === 'true') {
            $('input.payloadSearch').prop('checked', true);
        }

        breadcrumbSearchInfo += checkSelectedYears();

        //Metavalue field
        var metaValueField = getParameterByName('words');
        if (metaValueField) {
            $("input[name='metavalue']").val(metaValueField);
            breadcrumbSearchInfo += ' ' + Drupal.t('containing') + ': "' + metaValueField + '"';
        }
        return breadcrumbSearchInfo;
    }

    function checkSelectedCategories() {
        var returnBreadcrumb = "";
        var selectedCategory = getParameterByName('category', window.location.toString());
        if (selectedCategory) {
            if (selectedCategory.includes(" or ")) {
                selectedCategory = selectedCategory.split(" or ");
                selectedCategory.forEach(function (category) {
                    $('input[value="' + category + '"]').prop('checked', true);
                });
                var categoryString = selectedCategory.join(" or ");
                returnBreadcrumb += ' ' + Drupal.t('category') + ': "' + categoryString + '"';
            } else {
                $('input[value="' + selectedCategory + '"]').prop('checked', true);
                returnBreadcrumb += ' ' + Drupal.t('type') + ': "' + selectedCategory + '"';
            }
        }
        return returnBreadcrumb;
    }

    function checkSelectedYears() {
        var returnBreadcrumb = "";
        //Year of resource field
        var selectedYears = getParameterByName('years', window.location.toString());
        if (selectedYears) {
            if (selectedYears.includes(" or ")) {
                selectedYears = selectedYears.split(" or ");
                selectedYears.forEach(function (year) {
                    $('input[value="' + year + '"]').prop('checked', true);
                });
                var yearsString = selectedYears.join(" or ");
                returnBreadcrumb += ' from years ' + yearsString;
            } else {
                $('input[value="' + selectedYears + '"]').prop('checked', true);
                returnBreadcrumb += ' from year ' + selectedYears;
            }
        }
        return returnBreadcrumb;
    }

    function checkSelectedTypes() {
        var returnBreadcrumb = "";
        var selectedTypes = getParameterByName('type', window.location.toString());
        if (selectedTypes) {
            if (selectedTypes.includes(" or ")) {
                selectedTypes = selectedTypes.split(" or ");
                selectedTypes.forEach(function (type) {
                    $('input[value="' + type + '"]').prop('checked', true);
                });
                var typesString = selectedTypes.join(" or ");
                returnBreadcrumb += ' ' + Drupal.t('types') + ': "' + typesString + '"';
            } else {
                $('input[value="' + selectedTypes + '"]').prop('checked', true);
                returnBreadcrumb += ' ' + Drupal.t('type') + ': "' + selectedTypes + '"';
            }
        }
        return returnBreadcrumb;
    }

    //Getting the params from url
    function getParameterByName(name, url) {

        if (!url) {
            url = window.location.href;
        }
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[/&]" + name + "(=([^&#]*)|&|#|$)"),
                results = regex.exec(url);
        if (!results)
            return null;
        if (!results[2])
            return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
        
    }

   

    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }

    function getCookie(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    var searchFilterVisibility = getCookie("searchFilterVisibility");
    var porFilterVisibility = getCookie("porFilterVisibility");
    var torFilterVisibility = getCookie("torFilterVisibility");
    var corFilterVisibility = getCookie("corFilterVisibility");
    var yorFilterVisibility = getCookie("yorFilterVisibility");
    var actionsFilterVisibility = getCookie("actionsFilterVisibility");
    var dorFilterVisibility = getCookie("dorFilterVisibility");

    searchBoxFilters();

    function searchBoxFilters() {

        if (searchFilterVisibility === 'hidden') {
            $('.sks-form > h3').addClass('closed');
            $('.sks-form > form .form-item-metavalue').hide();
            $('.sks-form > form > .form-actions').hide();
        }

        if (torFilterVisibility === 'hidden') {
            $('#edit-searchbox-types--wrapper > legend > .fieldset-legend').addClass('closed');
            $('#edit-searchbox-types--wrapper > legend').next('.fieldset-wrapper').hide();
        }

        if (porFilterVisibility === 'hidden') {
            $('#edit-payloadsearch--wrapper > legend > .fieldset-legend').addClass('closed');
            $('#edit-payloadsearch--wrapper > legend').next('.fieldset-wrapper').hide();
        }

        if (corFilterVisibility === 'hidden') {
            $('#edit-searchbox-category--wrapper > legend > .fieldset-legend').addClass('closed');
            $('#edit-searchbox-category--wrapper > legend').next('.fieldset-wrapper').hide();
        }

        if (yorFilterVisibility === 'hidden') {
            $('#edit-datebox-years--wrapper > legend > .fieldset-legend').addClass('closed');
            $('#edit-datebox-years--wrapper > legend').next('.fieldset-wrapper').hide();
        }

        if (actionsFilterVisibility === 'hidden') {
            $('.actions-on-results > legend > .fieldset-legend').addClass('closed');
            $('.actions-on-results > legend').next('.fieldset-wrapper').hide();
        }

        if (dorFilterVisibility === 'hidden') {
            $('.extra-filter-heading').addClass('closed');
            $('.extra-filter-heading').next().hide();
            $('.extra-filter-heading').next().next().hide();
        } else if (dorFilterVisibility === 'visible') {
            $('.extra-filter-heading').removeClass('closed');
            $('.extra-filter-heading').next().show();
            $('.extra-filter-heading').next().next().show();
        }
        //Show the search block after comforming the user cookies
        $('.sks-form').fadeIn(100);
    }



    /********************** EVENTS *************************************/

    //Complex search-form behaviour
    //front page search
    $("#sks-form-front").submit(function (event) {
        let metaValueField = $("input[name='metavalue']").val().replace(/[^a-z0-9öüäéáűúőóüöí:./\s]/gi, '').replace(/[_]/g, '-').replace(/[\s]/g, '+');
        if(!metaValueField) {
            window.location.href = '/browser/discover/root';
        } else {
            window.location.href = '/browser/search/words='+metaValueField+'&payload=false&order=titleasc&limit=10&page=1';
        }
        event.preventDefault();
    });
    //main search block
    $(".sks-form > form").submit(function (event) {
        searchMethod();
        event.preventDefault();
    });

    /**
     * This method contains the search functionality
     * @returns {undefined}
     */
    function searchMethod() {
        var resultsPerPageSetting = getCookie("resultsPerPage");
        if (!resultsPerPageSetting) {
            resultsPerPageSetting = 10;
        }
        var resultsOrderSetting = getCookie("resultsOrder");

        if (!resultsOrderSetting) {
            resultsOrderSetting = 'titleasc';
        }
        var urlParams = "";
        //Metavalue field
        var metaValueField = $("input[name='metavalue']").val().replace(/[^a-z0-9öüäéáűúőóüöí:./\s]/gi, '').replace(/[\s]/g, '+');
        if (metaValueField) {
            //metaValueField = metaValueField.replace(/\s/g, '+');
            if (metaValueField.includes('type=') || metaValueField.includes('words=') || metaValueField.includes('mindate=')
                    || metaValueField.includes('maxdate=') || metaValueField.includes('payload=')) {
                urlParams += metaValueField;
                window.location.href = '/browser/search/' + urlParams + '&limit=' + resultsPerPageSetting + '&page=1';
            } else {
                urlParams += 'words=' + metaValueField;
            }
        }
        //ToR field
        var selectedTypes = [];
        $('.searchbox_types input:checked').each(function () {
            selectedTypes.push($(this).attr('value'));
        });
        if (selectedTypes.length > 0) {
            if (urlParams) {
                urlParams += '&';
            }
            urlParams += 'type=' + selectedTypes.join('+or+');
        }

        var selectedCategories = [];
        $('.searchbox_category input:checked').each(function () {
            selectedCategories.push($(this).attr('value'));
        });
        if (selectedCategories.length > 0) {
            if (urlParams) {
                urlParams += '&';
            }
            urlParams += 'category=' + selectedCategories.join('+or+');
        }

        //Year of resource field
        var selectedYears = [];
        $('.datebox_years input:checked').each(function () {
            selectedYears.push($(this).attr('value'));
        });
        if (selectedYears.length > 0) {
            if (urlParams) {
                urlParams += '&';
            }
            urlParams += 'years=' + selectedYears.join('+or+');
        }


        //add the payload checkbox value  

        var payload = $('.payloadSearch:checkbox:checked').length > 0;
        urlParams += '&payload=' + payload;

        if (!urlParams) {
            urlParams = "root";
        }

        window.location.href = '/browser/search/' + urlParams + '&order=' + resultsOrderSetting + '&limit=' + resultsPerPageSetting + '&page=1';
    }

    //Show apply-search button on ToR select
    $('.searchbox_types').change(function () {
        $('.sks-form > form > .form-actions').fadeIn(300);
    });

    $('.searchbox_category').change(function () {
        $('.sks-form > form > .form-actions').fadeIn(300);
    });

    $('.payloadSearch').change(function () {
        $('.sks-form > form > .form-actions').fadeIn(300);
    });

    //Show apply-search button on ToR select
    $('.datebox_years').change(function () {
        $('.sks-form > form > .form-actions').fadeIn(300);
    });

    //Show apply-search button on search text keyup
    $(".sks-form > form input").keyup(function (e) {
        $('.sks-form > form > .form-actions').fadeIn(300);
    });


    $('.sks-form :input').on('change input', function () {
        $('#vcr-submit-form').css('margin-top', '65px');
    });

    //Toggle Search filter
    $('.sks-form > h3').click(function () {
        if ($(this).hasClass('closed')) {
            $(this).removeClass('closed');
            $('.sks-form > form .form-item-metavalue').fadeIn(200);
            setCookie("searchFilterVisibility", 'visible', 180);
        } else {
            $(this).addClass('closed');
            $('.sks-form > form .form-item-metavalue').fadeOut(200);
            setCookie("searchFilterVisibility", 'hidden', 180);
        }
    });

    //Toggle ToR filter
    $('.searchbox_types > legend > .fieldset-legend').click(function () {
        if ($(this).hasClass('closed')) {
            $(this).removeClass('closed');
            $(this).parent().next('.fieldset-wrapper').fadeIn(200);
            setCookie("torFilterVisibility", 'visible', 180);
        } else {
            $(this).addClass('closed');
            $(this).parent().next('.fieldset-wrapper').fadeOut(200);
            setCookie("torFilterVisibility", 'hidden', 180);
        }
    });

    //Toggle payload filter
    $('.payloadSearch  > legend > .fieldset-legend').click(function () {
        if ($(this).hasClass('closed')) {
            $(this).removeClass('closed');
            $(this).parent().next('.fieldset-wrapper').fadeIn(200);
            setCookie("porFilterVisibility", 'visible', 180);
        } else {
            $(this).addClass('closed');
            $(this).parent().next('.fieldset-wrapper').fadeOut(200);
            setCookie("porFilterVisibility", 'hidden', 180);
        }
    });


    //Toggle CoR filter
    $('.searchbox_category  > legend > .fieldset-legend').click(function () {
        if ($(this).hasClass('closed')) {
            $(this).removeClass('closed');
            $(this).parent().next('.fieldset-wrapper').fadeIn(200);
            setCookie("corFilterVisibility", 'visible', 180);
        } else {
            $(this).addClass('closed');
            $(this).parent().next('.fieldset-wrapper').fadeOut(200);
            setCookie("corFilterVisibility", 'hidden', 180);
        }
    });

    //Toggle year of resource filter
    $('.datebox_years > legend > .fieldset-legend').click(function () {
        if ($(this).hasClass('closed')) {
            $(this).removeClass('closed');
            $(this).parent().next('.fieldset-wrapper').fadeIn(200);
            setCookie("yorFilterVisibility", 'visible', 180);
        } else {
            $(this).addClass('closed');
            $(this).parent().next('.fieldset-wrapper').fadeOut(200);
            setCookie("yorFilterVisibility", 'hidden', 180);
        }
    });

    //Toggle actions filter
    $('.actions-on-results > legend > .fieldset-legend').click(function () {
        if ($(this).hasClass('closed')) {
            $(this).removeClass('closed');
            $(this).parent().next('.fieldset-wrapper').fadeIn(200);
            setCookie("actionsFilterVisibility", 'visible', 180);
        } else {
            $(this).addClass('closed');
            $(this).parent().next('.fieldset-wrapper').fadeOut(200);
            setCookie("actionsFilterVisibility", 'hidden', 180);
        }
    });


    //Toggle DoP filter
    $('.extra-filter-heading').click(function () {
        if ($(this).hasClass('closed')) {
            $(this).removeClass('closed');
            $(this).next().fadeIn(200);
            $(this).next().next().fadeIn(200);
            setCookie("dorFilterVisibility", 'visible', 180);
        } else {
            $(this).addClass('closed');
            $(this).next().fadeOut(200);
            $(this).next().next().fadeOut(200);
            setCookie("dorFilterVisibility", 'hidden', 180);
        }
    });

    $("input[type=text].date-filter").keyup(function (e) {
        //Show apply-search button on date keyup
        $('.sks-form > form > .form-actions').fadeIn(300);
        var textSoFar = $(this).val();
        if (e.keyCode != 191) {
            if (e.keyCode != 8) {
                if (textSoFar.length == 2 || textSoFar.length == 5) {
                    $(this).val(textSoFar + "/");
                }
                //to handle copy & paste of 8 digit
                else if (e.keyCode == 86 && textSoFar.length == 8) {
                    $(this).val(textSoFar.substr(0, 2) + "/" + textSoFar.substr(2, 2) + "/" + textSoFar.substr(4, 4));
                }
            } else {
                //backspace would skip the slashes and just remove the numbers
                if (textSoFar.length == 5) {
                    $(this).val(textSoFar.substring(0, 4));
                } else if (textSoFar.length == 2) {
                    $(this).val(textSoFar.substring(0, 1));
                }
            }
        } else {
            //remove slashes to avoid 12//01/2014
            $(this).val(textSoFar.substring(0, textSoFar.length - 1));
        }

    });



});