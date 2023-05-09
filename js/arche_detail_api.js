jQuery(function ($) {


    const Cite = require('citation-js');

    "use strict";
    let acdhid = $('#acdhid').val();

    fetchOverview();

    /** CTRL PRess check for the tree view  #19924 **/
    var cntrlIsPressed = false;


    $(document).ready(function () {

    });



    /*
     * Fetch the overview data
     */
    function fetchOverview() {
        $.ajax({
            url: '/browser/api/gui/overview/' + acdhid + '/en',
            type: "GET",
            success: function (data, status) {
                $('#detail-overview-api-div').html(data);
                checkAudioPlayer();
                showCiteBlock();
                getInverseData();
                getRPRData();
                fetchBreadcrumb();
                fetchChild();
                showVersions();
                showVersionsAlert();
                gethasActorData();
                $('#arche-loader-image-div').hide();
            },
            error: function (xhr, status, error) {
                $(".arche_detail_view_main").html('<div class="messages messages--error">' + Drupal.t('Resource does not exists!') + '</div>');
                console.log('error overview');
                console.log(error);
                $('#arche-loader-image-div').hide();
            }
        });
    }

    /**
     * Display child data
     * @returns {undefined}
     */
    function fetchChild() {
        $('#child-div-content').show();
        var childTable = $('.child-table').DataTable({
            "paging": true,
            "searching": true,
            "pageLength": 10,
            "processing": true,
            'language': {
                "processing": "<img src='/browser/modules/contrib/arche-gui/images/arche_logo_flip_47px.gif' />"
            },
            "serverSide": true,
            "serverMethod": "post",
            "ajax": {
                'url': "/browser/api/child/" + acdhid + "/en",
                complete: function (response) {
                    var order = 0;
                    if (response.responseJSON.order == "desc") {
                        order = 1;
                    }
                    let rootType = response.responseJSON.rootType;
                    if( rootType === "https://vocabs.acdh.oeaw.ac.at/schema#Collection" || rootType === "https://vocabs.acdh.oeaw.ac.at/schema#TopCollection") {
                        $('.res-act-button-treeview').show();
                    }
                    console.log(response.responseJSON);
                    $('#sortBy').val(response.responseJSON.orderby + '-' + order);
                    $('#childTitle').html('<h3>' + response.responseJSON.childTitle + '</h3>');
                },
                error: function (xhr, status, error) {
                    //$(".loader-versions-div").hide();
                    console.log('error');
                    console.log(error);
                }
            },
            'columns': [
                {data: 'title', render: function (data, type, row, meta) {
                        var shortcut = row.type;
                        shortcut = shortcut.replace('https://vocabs.acdh.oeaw.ac.at/schema#', 'acdh:');
                        var text = '<div class="col-block col-lg-12">';
                        //title
                        text += '<div class="res-property">';
                        text += '<span class="res-title">' + getAccessResIcon(row.accessres) + '&nbsp;';
                        text += '<a href="/browser/detail/' + row.id + '">' + row.title + '</a></span></div>';
                        //type
                        text += '<div class="res-property">';
                        text += '<i class="material-icons">&#xE54E;</i>';
                        text += '<span class="res-prop-label">' + Drupal.t("Type") + ': </span>';
                        text += '<span class="res-rdfType"><a id="archeHref" href="/browser/search/type=' + shortcut + '&payload=false">' + shortcut + '</a></span>';
                        text += '</div>';

                        //avdate

                        text += '</div>';
                        return  text;
                    }


                },
                {data: 'image', width: "20%", render: function (data, type, row, meta) {
                        let acdhid = row.acdhid.replace('https://', '');
                        return '<div class="dt-single-res-thumb text-center" style="min-width: 120px;">\n\
                            <center><a href="https://arche-thumbnails.acdh.oeaw.ac.at/' + acdhid + '?width=600" data-lightbox="detail-titleimage-' + row.id + '">\n\
                                <img class="img-fluid bg-white" src="https://arche-thumbnails.acdh.oeaw.ac.at/' + acdhid + '?width=75">\n\
                            </a></center>\n\
                            </div>';
                    }
                },
                {data: 'property', visible: false},
                {data: 'type', visible: false},
                {data: 'accessres', visible: false},
                {data: 'acdhid', visible: false},
                {data: 'sumcount', visible: false}

            ],
            fnDrawCallback: function () {
                $(".child-table thead").remove();
            }
        });

        $("#sortBy").change(function () {
            var colIdx = $('#sortBy :selected').val();
            let id = colIdx.substring(0, 1);
            let order = colIdx.substring(2, 3);
            orderVal = 'asc';
            if (order > 0) {
                orderVal = 'desc';
            }

            childTable.order([id, orderVal]).draw();
        });
    }

    /**
     * Display breadcrumb
     * @returns {undefined}
     */
    function fetchBreadcrumb() {
        console.log('breadcrumb');
        $.ajax({
            url: '/browser/api/breadcrumb/' + acdhid,
            type: "GET",
            success: function (data, status) {
                $('#breadcrumb-content-div').html(data);
            },
            error: function (xhr, status, error) {
                //console.log('no breadcrumb');
            }
        });
    }


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

    /**
     * Display RPR data table
     * @returns {undefined}
     */
    function getRPRData() {
        if (acdhid !== undefined) {
            $('table.rprTable').DataTable({
                "ajax": {
                    "url": "/browser/api/getRPR/" + acdhid + "/en",
                    "data": function (d) {
                        d.limit = d.draw;
                    },
                    error: function (xhr, error, code)
                    {
                        const resp = xhr.responseJSON;
                        $('#rprTableDiv').html();
                        $('#rprTableDiv').html("<div class='messages messages--warning'>The resource has no Related Publication and Resource data.</div>");
                    }
                },
                "deferRender": true,
                "errMode": 'throw'
            });
        }
    }

    /**
     * Display Actos data table
     * @returns {undefined}
     */
    function gethasActorData() {
        if ($('#acdhid').val()) {
            $('#values-by-property-and-id-table').DataTable({
                "paging": true,
                "searching": true,
                "pageLength": 10,
                "processing": true,
                'language': {
                    "processing": "<img src='/browser/modules/contrib/arche-gui/images/arche_logo_flip_47px.gif' />"
                },
                "serverSide": true,
                "serverMethod": "post",
                "fixedColumns": true,
                "ajax": "/browser/api/getHasActors/" + $('#acdhid').val() + "/en",
                'columns': [
                    {data: 'title'},
                    {data: 'property'}
                ]
            });
        }
    }

    /**
     * Generate inverse data table
     * @returns {undefined}
     */
    function getInverseData() {
        //genereate the data
        if (acdhid) {
            $('table.inverseTable').DataTable({
                "ajax": {
                    "url": "/browser/api/getInverseData/" + acdhid,
                    "data": function (d) {
                        d.limit = d.draw;
                    }
                },
                "deferRender": true
            });
        }
    }

    /**
     * Display the versions in the left side block
     * @returns {undefined}
     */
    function showVersions() {
        $(".loader-versions-div").show();
        var acdhid = $('#acdhid').val();
        $.ajax({
            url: '/browser/api/versions_list/' + acdhid + '/en',
            type: "GET",
            success: function (data, status) {
                $(".loader-versions-div").hide();
                $('.versions-block-div').html(data);

            },
            error: function (message) {
                $(".loader-versions-div").hide();
                console.log('versions error');
                console.log(message);
            }
        });
    }

    /**
     * Display alert message on the top of the overview to inform the user about
     * a new version is existing
     * @returns {undefined}
     */
    function showVersionsAlert() {
        var acdhid = $('#acdhid').val();
        $.ajax({
            url: '/browser/api/versions_alert/' + acdhid + '/en',
            type: "GET",
            success: function (data, status) {
                if (data != null) {
                    $(".versions-detail-block").html(data);
                }
            },
            error: function (message) {
            }
        });
    }

    ///////////// CITE ////////////////////
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

    /**
     * Display Cite error message
     * @param {type} errorText
     * @returns {undefined}
     */
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

    /**
     * CITE TAB EVENTS
     * @param {type} obj
     * @param {type} selected
     * @returns {undefined}
     */
    function handleCiteTabEvents(obj, selected) {
        $('#' + selected).removeClass('selected');
        let figId = selected.replace('cite-tab-', 'highlight-');
        $('#' + figId).removeClass('selected').addClass('hidden');

        var id = obj.attr('id');
        $('#' + id).addClass('selected');
        let contentId = id.replace('cite-tab-', 'highlight-');
        $('#' + contentId).removeClass('hidden').addClass('selected');
    }


    /**
     * Generate the accessrec icon based on the resource value
     * @param {type} accessres
     * @returns {String}
     */
    function getAccessResIcon(accessres) {

        if (accessres == 'public' || accessres == 'öffentlich') {
            return '<span class="green-dot" title="public"></span>';
        } else if (accessres == 'academic' || accessres == 'akademisch') {
            return  '<span class="orange-dot" title="academic"></span>';
        } else if (accessres == 'restricted' || accessres == 'eingeschränkt') {
            return '<span class="red-dot" title="restricted"></span>';
        }

        return '';
    }

    //////////////// DELEGATE //////////////////////////////

    /**
     * EXPERT VIEW 
     */
    $(document).delegate(".res-act-button-expertview", "click", function (e) {
        if ($(this).hasClass('basic')) {
            $('.single-res-overview-basic').hide();
            $('.single-res-overview-expert').fadeIn(200);
            $(this).removeClass('basic');
            $(this).addClass('expert');
            $(this).children('span').text(Drupal.t('Switch to Basic-View'));

        } else {
            $('.single-res-overview-expert').hide();
            $('.single-res-overview-basic').fadeIn(200);
            $(this).removeClass('expert');
            $(this).addClass('basic');
            $(this).children('span').text(Drupal.t('Switch to Expert-View'));
        }
    });

    /**
     * Child view treeview
     */
    $(document).delegate(".res-act-button-treeview", "click", function (e) {

        if ($(this).hasClass('basic')) {
            $('#child-view-table').hide();
            $('.children-overview-tree').fadeIn(200);
            $(this).removeClass('basic');
            $(this).addClass('tree');
            $(this).children('span').text(Drupal.t('Switch to List-View'));

            //get the data
            var url = $('#acdhid').val();

            if (url) {

                $('#child-tree').jstree({
                    'core': {
                        'data': {
                            'url': function (node) {
                                var acdhid = $('#acdhid').val();

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
                                "url": '/browser/api/get_collection_data_lazy/' + $('#acdhid').val() + '/' + drupalSettings.language,
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
                            if (cntrlIsPressed)
                            {
                                window.open("/browser/detail/" + node.original.uri, '_blank');
                            } else {
                                window.location.href = "/browser/detail/" + node.original.uri;
                            }
                        }
                    }
                });

            }
        } else {
            $('.children-overview-tree').hide();
            $('#child-view-table').fadeIn(200);
            $(this).removeClass('tree');
            $(this).addClass('basic');
            $(this).children('span').text(Drupal.t('Switch to Tree-View'));
        }
    });

    /**
     * Versions treeview
     */
    $(document).delegate(".res-act-button-versions-treeview", "click", function (e) {
        if ($(this).hasClass('basic')) {
            $('.versions-list-view').hide();
            $('#versions-tree').fadeIn(200);
            $(this).removeClass('basic');
            $(this).addClass('tree');
            $(this).children('span').text(Drupal.t('Show as List'));

            //get the data
            var url = $('#acdhid').val();

            if (url) {

                $('#versions-tree').jstree({
                    'core': {
                        'data': {
                            'url': function (node) {
                                var acdhid = $('#acdhid').val();
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
                            console.log('versions tree error');
                            $('#versions-tree').html("<h3>Error: </h3><p>" + jqXHR.reason + "</p>");
                        }
                    }
                });


                $('#versions-tree').bind("click.jstree", function (node, data) {
                    if (node.originalEvent.target.id) {
                        var node = $('#versions-tree').jstree(true).get_node(node.originalEvent.target.id);
                        if (node.id) {
                            if (cntrlIsPressed)
                            {
                                window.open("/browser/detail/" + node.id, '_blank');
                            } else {
                                window.location.href = "/browser/detail/" + node.id;
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

    $(document).keydown(function (event) {
        if (event.which == "17")
            cntrlIsPressed = true;
    });

    $(document).keyup(function () {
        cntrlIsPressed = false;
    });
    /** CTRL PRess check for the tree view   #19924  END **/
    $(document).delegate(".cite-style", "click", function (e) {
        e.preventDefault();
        handleCiteTabEvents($(this), $("#cite-selector-div").find(".selected").attr('id'));
    });

});



