jQuery(function ($) {
    "use strict";
    /** Handle the child button click  END **/

    $(document).delegate(".hideRepoChildView", "click", function (e) {
        e.preventDefault();
        $('.res-act-button.hideChildView').hide();
        $('#getRepoChildView').show();
        $('#child-div-content').hide();
        $(".loader-div").hide();
    });

    function createNewUrlForInsideClick(id) {
        var newurl = window.location.protocol + "//" + window.location.host + '/browser/oeaw_detail/' + id;
        window.history.pushState({path: newurl}, '', newurl);
    }

    /**
     * Change the website title
     * @returns void
     */
    function changeTitle() {
        var title = $('#arche-dv-main .res-property span.res-title').text();
        if (title) {
            document.title = 'ARCHE - ' + title.trim();
        }
    }

    function createCiteTab(type, first) {
        var selected = 'selected';
        if (!first) {
            selected = '';
        }
        var html = "<div class='cite-style " + selected + "' id='cite-tab-" + type.toLowerCase() + "'>" + type.toUpperCase() + "</a></div>";
        //<a href='javascript://' id='cite-tooltip-" + type.toLowerCase() + "' data-toggle='cite-tooltip-" + type.toLowerCase() + "'  data-placement='right' data-html='true' data-trigger='focusv title='" + type.toLowerCase() + "'><i class='material-icons'>&#xE88F;</i>
        $('#cite-selector-div').append(html);
    }

    function createCiteContent(data, type, first) {
        var selected = 'selected';
        if (!first) {
            selected = 'hidden';
        }
        var html = "<span class='cite-content " + selected + "' id='highlight-" + type.toLowerCase() + "'>" + data + "</span>";
        $('#cite-content-figure').append(html);
    }

    /**
     * Show the CITE block
     * @returns {undefined}
     */
    function showCiteBlock() {

        var url = $('#biblaTexUrl').val();
        if (url) {

            $.get(url).success(function (data) {
                $('#cite-content-div').addClass('show');
                $('#cite-content-div').removeClass('hidden');
                $('#cite-loader').addClass('hidden');

                const Cite = require('citation-js');
                //console.log(data);
                let cite = new Cite(data);
                //APA
                var opt = {
                    format: 'string'
                }
                opt.type = 'html';
                opt.style = 'citation-apa';
                opt.lang = 'en-US';

                createCiteTab('apa', true);
                //$('#highlight-apa').html(cite.get(opt));
                createCiteContent(cite.get(opt), 'apa', true);

                //Vancouver
                var opt = {
                    format: 'string'
                }
                opt.type = 'html';
                opt.style = 'citation-vancouver';
                opt.lang = 'en-US';

                createCiteTab('vancouver', false);
                createCiteContent(cite.get(opt), 'vancouver', false);

                //Vancouver
                var opt = {
                    format: 'string'
                }
                opt.type = 'html';
                opt.style = 'citation-harvard1';
                opt.lang = 'en-US';

                createCiteTab('harvard', false);
                createCiteContent(cite.get(opt), 'harvard', false);
                
                createCiteTab('BiblaTex', false);
                createCiteContent(data, 'BiblaTex', false);


            }).error(function (data) {
                $('#cite-content-div').addClass('show');
                $('#cite-content-div').removeClass('hidden');
                $('#cite-loader').addClass('hidden');
                //stop spinner
                $('#cite-content-div').append('Error, please reload the page');
            });
        }
    }

    function handleCiteTabEvents(obj, selected) {
        $('#' + selected).removeClass('selected');
        let figId = selected.replace('cite-tab-', 'highlight-');
        $('#' + figId).removeClass('selected').addClass('hidden');

        var id = obj.attr('id');
        $('#' + id).addClass('selected');
        let contentId = id.replace('cite-tab-', 'highlight-');
        $('#' + contentId).removeClass('hidden').addClass('selected');

    }



    $(document).ready(function () {
        /** add hasTitle value for the document title in every detail view **/
        changeTitle();
        
        //CITE Block
        showCiteBlock();

        $(document).delegate(".cite-style", "click", function (e) {
            e.preventDefault();
            handleCiteTabEvents($(this), $("#cite-selector-div").find(".selected").attr('id'));
        });

        /**
         * If we are inside the oeaw_detail view, then we will just update the mainpagecontent div
         */
        if (window.location.href.indexOf("browser/oeaw_detail/") >= 0) {

            $(document).delegate("a#archeHref", "click", function (e) {
                var reloadTable = false;
                $(".loader-div").show();
                var url = $(this).attr('href');
                //if the url is arche url
                if (url && url.indexOf("/browser/oeaw_detail/") >= 0 || url && url.indexOf("/browser//oeaw_detail/") >= 0) {
                    $('html, body').animate({scrollTop: '0px'}, 0);
                    url = url.substring(url.indexOf("/browser/"));
                    $(".loader-div").show();
                    var id = url;
                    id = id.replace("/browser/oeaw_detail/", "");
                    id = id.replace("/browser//oeaw_detail/", "");
                    url = url + "&ajax=1";
                    $.ajax({
                        url: url,
                        type: "POST",
                        success: function (data, status) {
                            //change url
                            createNewUrlForInsideClick(id);
                            $('#block-mainpagecontent').html(data);
                            reloadTable = true;
                            $(".loader-div").hide();
                        },
                        error: function (message) {
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
        if (str.indexOf('/oeaw_detail/') >= 0) {
            var n = str.indexOf("/oeaw_detail/");
            res = str.substring(n + 13, str.length);
            if (res.indexOf('&') >= 0) {
                res = res.substring(0, res.indexOf('&'));
            }
            if (res.indexOf('?') >= 0) {
                res = res.substring(0, res.indexOf('?'));
            }
        }
        res = res.replace('id.acdh.oeaw.ac.at/uuid/', '');
        return res;
    }

    $(document).delegate(".res-act-button-treeview", "click", function (e) {
        if ($(this).hasClass('basic')) {
            $('.children-overview-basic').hide();
            $('.child-ajax-pagination').hide();
            $('.children-overview-tree').fadeIn(200);
            $(this).removeClass('basic');
            $(this).addClass('tree');
            $(this).children('span').text(Drupal.t('Switch to List-View'));
            let numberOfChildElements = $('#numberOfItems').val();
            if (numberOfChildElements > 10000) {
                $('#collectionBrowser').html("<h3>Error: </h3><p>" + Drupal.t("This Resource has more than 10.000 child elements! Please use the download collection script!") + "</p>");
                return false;
            }
            //get the data
            var url = $('#insideUri').val();
            if (url) {

                $('#collectionBrowser')
                        .jstree({
                            core: {
                                'check_callback': false,
                                data: {
                                    "url": '/browser/get_collection_data/' + url,
                                    "dataType": "json"
                                },
                                themes: {stripes: true},
                                error: function (jqXHR, textStatus, errorThrown) {
                                    $('#collectionBrowser').html("<h3>Error: </h3><p>" + jqXHR.reason + "</p>");
                                }
                            },
                            search: {
                                case_sensitive: false,
                                show_only_matches: true
                            },
                            plugins: ['search']
                        });
                $('#collectionBrowser')
                        //handle the node clicking to download the file
                        .bind("click.jstree", function (node, data) {
                            if (node.originalEvent.target.id) {

                                var node = $('#collectionBrowser').jstree(true).get_node(node.originalEvent.target.id);
                                if (node.original.encodedUri) {
                                    window.location.href = "/browser/oeaw_detail/" + node.original.uri;
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

