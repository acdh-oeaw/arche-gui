

jQuery(function ($) {
    const Cite = require('citation-js');

    "use strict";
    /** Handle the child button click  END **/

    /** CTRL PRess check for the tree view  #19924 **/
    var cntrlIsPressed = false;

    $(document).keydown(function (event) {
        if (event.which == "17")
            cntrlIsPressed = true;
    });

    $(document).keyup(function () {
        cntrlIsPressed = false;
    });
    /** CTRL PRess check for the tree view   #19924  END **/


    $(document).delegate(".hideRepoChildView", "click", function (e) {
        e.preventDefault();
        $('.res-act-button.hideChildView').hide();
        $('#getRepoChildView').show();
        $('#child-div-content').hide();
        $(".loader-div").hide();
    });

    $(document).delegate("#getClarinVCR", "click", function (e) {
        e.preventDefault();
        $('#vcr-div > form').submit();
    });

    //check the audio player in the detail view
    function checkAudioPlayer() {
        var audio = document.getElementById('arche-audio-player');
        if (audio) {
            audio.addEventListener('error', function (e) {
                var noSourcesLoaded = (this.networkState === HTMLMediaElement.NETWORK_NO_SOURCE);
                if (noSourcesLoaded) {
                    console.log("could not load audio source");
                    $('#arche-audio-player').hide();
                    $('.arche-audio-player-container').html(Drupal.t('Could not load audio source!')).addClass('messages messages--error');
                }

            }, true);
        }
    }

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

    /**
     * Generate the cite tab header
     * @param string type
     * @param string first
     * @param string typeid -> id for the handle event
     * @returns string
     */
    function createCiteTab(type, first, typeid) {
        var selected = 'selected';
        if (!first) {
            selected = '';
        }
        var html = "<div class='cite-style " + selected + "' id='cite-tab-" + typeid.toLowerCase() + "'>" + type.toUpperCase() + "</a></div>";
        //<a href='javascript://' id='cite-tooltip-" + type.toLowerCase() + "' data-toggle='cite-tooltip-" + type.toLowerCase() + "'  data-placement='right' data-html='true' data-trigger='focusv title='" + type.toLowerCase() + "'><i class='material-icons'>&#xE88F;</i>
        $('#cite-selector-div').append(html);
    }

    /**
     * Generate the cite block content
     * @param string data
     * @param string typeid -> id for the handle event
     * @param string first
     * @returns string
     */
    function createCiteContent(data, typeid, first) {
        var selected = 'selected';
        if (!first) {
            selected = 'hidden';
        }

        var html = "<span class='cite-content " + selected + "' id='highlight-" + typeid.toLowerCase() + "'>" + data + "</span>";
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

                try {
                    let cite = new Cite(data);

                    var apa_loaded = true;

                    let templateName = 'apa-6th';
                    var template = "";
                    url_csl_content("/browser/modules/contrib/arche-gui/csl/apa-6th-edition.csl").success(function (data) {

                        template = data;
                        Cite.CSL.register.addTemplate(templateName, template);

                        var opt = {
                            format: 'string'
                        };
                        opt.type = 'html';
                        opt.style = 'citation-' + templateName;
                        opt.lang = 'en-US';
                        createCiteTab('apa 6th', true, 'apa-6th');
                        createCiteContent(cite.get(opt), 'apa-6th', true);
                        apa_loaded = false;
                    }).then(function (d) {

                        //harvard
                        var opt = {
                            format: 'string'
                        };
                        opt.type = 'html';
                        opt.style = 'citation-harvard1';
                        opt.lang = 'en-US';

                        createCiteTab('harvard', apa_loaded, 'harvard');
                        createCiteContent(cite.get(opt), 'harvard', apa_loaded);

                        //Vancouver
                        var opt = {
                            format: 'string'
                        };
                        opt.type = 'html';
                        opt.style = 'citation-vancouver';
                        opt.lang = 'en-US';

                        createCiteTab('vancouver', false, 'vancouver');
                        createCiteContent(cite.get(opt), 'vancouver', false);

                        createCiteTab('BiblaTex', false, 'biblatex');
                        createCiteContent(data, 'BiblaTex', false);
                    });
                } catch (error) {
                    createCiteErrorResponse(error);
                    return false;
                }

            }).error(function (data) {
                createCiteErrorResponse("The Resource does not have CITE data.");
            });
        }
    }

    function createCiteErrorResponse(errorText) {
        $('#cite-content-div').addClass('show');
        $('#cite-content-div').removeClass('hidden');
        $('#cite-loader').addClass('hidden');
        $('#cite-selector-div').hide();
        $('#cite-content-figure').hide();
        $('.bd-clipboard').hide();
        //stop spinner
        $('#cite-content-div').append('<div class="messages messages--warning">' + Drupal.t(errorText) + '</>');
    }

    function url_csl_content(url) {
        return $.get(url);
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

        //check the audio player can load the audio file or not
        checkAudioPlayer();

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
                            $('#block-mainpagecontent').html(Drupal.t("Resource does not exists!"));
                            $(".loader-div").hide();
                        }
                    });
                    e.preventDefault();
                } else {
                    e.preventDefault();
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

            //get the data
            var url = $('#insideUri').val();

            if (url) {

                $('#child-tree').jstree({
                    'core': {
                        'data': {
                            'url': function (node) {
                                var acdhid = $('#insideUri').val();

                                if (node.id != "#") {
                                    acdhid = node.id;
                                }

                                return '/browser/api/get_collection_data_lazy/' + acdhid + '/' + drupalSettings.language;
                            },
                            'data': function (node) {
                                return {'id': node.id};
                            },
                            'success': function (nodes) {
                            }
                        },
                        themes: {stripes: true},
                        error: function (jqXHR, textStatus, errorThrown) {
                            $('#child-tree').html("<h3>Error: </h3><p>" + jqXHR.reason + "</p>");
                        },
                        search: {
                            "ajax": {
                                "url": '/browser/api/get_collection_data_lazy/' + $('#insideUri').val() + '/' + drupalSettings.language,
                                "data": function (str) {
                                    return {
                                        "operation": "search",
                                        "q": str
                                    };
                                }
                            },
                            case_sensitive: false
                        },
                        plugins: ['search']
                    }
                });
                // not ready yet
                $("#search-input").keyup(function () {
                    var searchString = $(this).val();
                    $('#child-tree').jstree('search', searchString);
                });

                $('#child-tree').bind("click.jstree", function (node, data) {
                    if (node.originalEvent.target.id) {
                        var node = $('#child-tree').jstree(true).get_node(node.originalEvent.target.id);
                        if (node.original.uri) {
                            if(cntrlIsPressed)
                            {
                                window.open( "/browser/oeaw_detail/" + node.original.uri, '_blank');
                            }else {
                                window.location.href = "/browser/oeaw_detail/" + node.original.uri;
                            }
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
    
    
    $(document).delegate(".res-act-button-versions-treeview", "click", function (e) {
        if ($(this).hasClass('basic')) {
            $('.versions-list-view').hide();
            $('#versions-tree').fadeIn(200);
            $(this).removeClass('basic');
            $(this).addClass('tree');
            $(this).children('span').text(Drupal.t('Show as List'));

            //get the data
            var url = $('#insideUri').val();

            if (url) {

                $('#versions-tree').jstree({
                    'core': {
                        'data': {
                            'url': function (node) {
                                var acdhid = $('#insideUri').val();
                                return '/browser/api/versions/' + acdhid + '/en';
                            },
                            'data': function (node) {
                                return {'id': node.id};
                            },
                            'success': function (nodes) {
                            }
                        },
                        themes: {stripes: true},
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log('error');
                            $('#versions-tree').html("<h3>Error: </h3><p>" + jqXHR.reason + "</p>");
                        }
                    }
                });
              

                $('#versions-tree').bind("click.jstree", function (node, data) {
                    if (node.originalEvent.target.id) {
                        var node = $('#versions-tree').jstree(true).get_node(node.originalEvent.target.id);
                        if (node.id) {
                            if(cntrlIsPressed)
                            {
                                window.open( "/browser/oeaw_detail/" + node.id, '_blank');
                            }else {
                                window.location.href = "/browser/oeaw_detail/" + node.id;
                            }
                        }
                    }
                });

            }
        } else {
            $('#versions-tree').hide();
            $('.versions-list-view').fadeIn(200);
            $(this).removeClass('tree');
            $(this).addClass('basic');
            $(this).children('span').text(Drupal.t('Show as Tree'));
        }
    });
    
    
});

