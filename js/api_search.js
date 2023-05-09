jQuery(function ($) {


    var payload = "";
    var searchText = "";
    var entity = [];
    var category = [];
    var year = [];
    var apiParamsText = "";

    $(document).ready(function () {
        //searchTable();
        loadSearchBlock()
    });


    function getPayload() {
        payload = "false";
        //payloadSearch
        if ($('input:checkbox.payloadSearch:checked')) {
            payload = "true";
        }
        if (apiParamsText !== "") {
            apiParamsText += "&payload=" + payload;
        } else {
            apiParamsText += "payload=" + payload;
        }
    }

    function getSearchText() {
        apiParamsText = "words=" + $('#edit-metavalue').val().replace(/\s+/g, '+');
    }

    function getEntity() {
        var entityText = "";

        $('input:checkbox.searchbox_types').each(function () {
            if (this.checked) {
                entity.push(($(this).val()));
            }
        });
        if (entity.length > 0) {
            entityText = entity.join("+or+");
            if (apiParamsText !== "") {
                apiParamsText += "&type=" + entityText;
            } else {
                apiParamsText += "type=" + entityText;
            }
        }
    }

    function getCategory() {
        var categoryText = "";
        $('input:checkbox.searchbox_category').each(function () {
            if (this.checked) {
                category.push(($(this).val()));
            }
        });
        if (category.length > 0) {
            categoryText = category.join("+or+");
            if (apiParamsText !== "") {
                apiParamsText += "&category=" + categoryText;
            } else {
                apiParamsText += "category=" + categoryText;
            }
        }
    }

    function getYear() {
        var yearText = "";
        $('input:checkbox.datebox_years').each(function () {
            if (this.checked) {
                year.push(($(this).val()));
            }
        });

        if (year.length > 0) {
            yearText = year.join("+or+");
            if (apiParamsText !== "") {
                apiParamsText += "&years=" + yearText;
            } else {
                apiParamsText += "years=" + yearText;
            }
        }
    }

    function searchTable() {
        getSearchText();
        getEntity();
        getCategory();
        getYear();
        getPayload();

        var searchTable = $('.search-table').DataTable({
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
                'url': "/browser/api/search/" + apiParamsText,
                complete: function (response) {
                    var order = 0;
                    if (response.responseJSON.orderby == "desc") {
                        order = 1;
                    }
                    $('#sortBySearch').val(response.responseJSON.order + '-' + order);
                },
                error: function (error) {
                    console.log("search error");
                    console.log(error);
                }
            },
            'columns': [
                {data: 'title',
                    render: function (data, type, row, meta) {

                        let access = row.accessres;
                        var accessTxt = '<span class="green-dot" title="public"></span>&nbsp; ';
                        var accessStatus = false;
                        if (access == 'public' || access == 'öffentlich') {
                            accessTxt = '<span class="green-dot" title="public"></span>&nbsp; ';
                            accessStatus = true;
                        } else if (access == 'academic' || access == 'akademisch') {
                            accessTxt = '<span class="orange-dot" title="academic"></span>&nbsp; ';
                        } else if (access == 'restricted' || access == 'eingeschränkt') {
                            accessTxt = '<span class="red-dot" title="restricted"></span>&nbsp; ';
                        }
                        if (access === null) {
                            accessStatus = true;
                        }

                        var rdf = "";
                        if (row.acdhtype) {
                            let rdfTxt = row.acdhtype.replace('https://vocabs.acdh.oeaw.ac.at/schema#', 'acdh:');
                            rdf = '<i class="material-icons">today</i> <span class="res-prop-label">' + Drupal.t("Type") + ': </span> <span class="res-rdfType"><a id="archeHref" href="/browser/search/type=' + rdfTxt + '&payload=false/titleasc/10/1">' + rdfTxt + '</a></span><br>';

                        }

                        let payloadSearch = formtPayloadSearchResult(accessStatus, row.headline_desc, row.headline_binary);

                        let title = '<span class="res-title"><a href="/browser/detail/' + row.acdhid + '">' + data + '</a></span><br>';
                        let avdate = '<i class="material-icons">today</i> <span class="res-prop-label">' + Drupal.t("Available Date") + ':</span> <span class="res-prop-value">' + row.avdate + '</span>';

                        return accessTxt + title + rdf + avdate + payloadSearch;
                    }
                },
                {data: 'avdate', visible: false},
                {data: 'description', visible: false},
                {data: 'acdhid', visible: false},
                {data: 'cnt', visible: false},
                {data: 'headline_text', visible: false},
                {data: 'headline_desc', visible: false},
                {data: 'headline_binary', visible: false},
                {data: 'accessres', visible: false},
                {data: 'ids', visible: false},
                {data: 'pid', visible: false},
                {data: 'titleimage', visible: false},
                {data: 'image', width: "20%", render: function (data, type, row, meta) {
                        if (row.pid) {
                            let pid = row.pid.replace('https://', '');
                            return '<div class="dt-single-res-thumb text-center" style="min-width: 120px;">\n\
                                    <center><a href="https://arche-thumbnails.acdh.oeaw.ac.at/' + pid + '?width=600" data-lightbox="detail-titleimage-' + row.acdhid + '">\n\
                                        <img class="img-fluid bg-white" src="https://arche-thumbnails.acdh.oeaw.ac.at/' + pid + '?width=150">\n\
                                    </a></center>\n\
                                    </div>';
                        }
                        return '';
                    }
                }
            ],
            fnDrawCallback: function () {
                console.log('changed');
            },
            //oLanguage: {sProcessing: "<div id='search-table-loader-div'><div class='search-table-loader-bg'><div class='search-table-loader'></div></div></div>" }
            oLanguage: {sProcessing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span> '}
        });

        $("#sortBySearch").change(function () {
            var colIdx = $('#sortBySearch :selected').val();
            let id = colIdx.substring(0, 1);
            let order = colIdx.substring(2, 3);
            orderVal = 'asc';
            if (order > 0) {
                orderVal = 'desc';
            }
            searchTable.order([id, orderVal]).draw();
        });

    }

    // display the payload search results
    function formtPayloadSearchResult(accessStatus, headline_desc, headline_binary) {

        if (accessStatus === false) {
            return "";
        }
        var result = "";
        if (headline_desc !== "" || headline_binary !== "") {
            result += '<div class="res-property">\n\
                    <i class="material-icons">&#xe8a0;</i><span class="res-prop-label">' + Drupal.t("Search results") + ': </span></div>';

            if (headline_desc) {
                result += '<div class="res-property">\n\
                        <div class="container">\n\
                            <div class="row">\n\
                                <div class="col-lg-1">\n\
                                    &nbsp;\n\
                                </div>\n\
                                <div class="col-lg-11">\n\
                                    <span class="res-prop-label"><li>' + Drupal.t("Description") + ': </li></span>\n\
                                    <span class="res-prop-value"> ' + headline_desc + '</span>\n\
                                </div>\n\
                            </div>\n\
                        </div>\n\
                    </div>';
            }

            if (headline_binary) {
                result += '<div class="res-property">\n\
                        <div class="container">\n\
                            <div class="row">\n\
                                <div class="col-lg-1">\n\
                                    &nbsp;\n\
                                </div>\n\
                                <div class="col-lg-11">\n\
                                    <span class="res-prop-label"><li>' + Drupal.t("Binary Content") + ': </li></span>\n\
                                    <span class="res-prop-value">' + headline_binary + '</span>\n\
                                </div>\n\
                            </div>\n\
                        </div>\n\
                    </div>';
            }
        }

        return result;
    }

    //////////// Mateusz solution


    $(document).delegate("#arche-search-submit-btn", "click", function (e) {
        e.preventDefault();
        search();
    });



    var nmsp = [
        {prefix: 'https://vocabs.acdh.oeaw.ac.at/schema#', alias: 'acdh'},
        {prefix: 'http://www.w3.org/1999/02/22-rdf-syntax-ns#', alias: 'rdf'}
    ];
    function shorten(v) {
        for (var i = 0; i < nmsp.length; i++) {
            if (v.startsWith(nmsp[i].prefix)) {
                return nmsp[i].alias + ':' + v.substring(nmsp[i].prefix.length);
            }
        }
        return v;
    }
    var places = [];
    function findPlace(name) {
        window.clearTimeout(placeTimeout);
        $.ajax({
            method: 'get',
            url: 'https://secure.geonames.org/searchJSON',
            data: {
                'name': name,
                'fuzzy': 0.7,
                'maxRows': 10,
                'username': 'zozlak'
            },
            success: function (data) {
                places = data.geonames;
                var list = '';
                for (i = 0; i < places.length; i++) {
                    var place = places[i];
                    list += '<a href="#" class="place" data-id="' + i + '" onclick="choosePlace(this);">' + place.name + ' (' + place.fcodeName + ', ' + place.countryName + ')</a>';
                }
                $('#placesList').html(list);
            },
            error: onError
        });
        $('#error').text('');
        $('#placesList').html('');
        return false;
    }
    var token = 1;
    
    function search() {
        token++;
        var localToken = token;
        var param = {
            url: 'backend.php',
            method: 'post',
            data: {
                q: $('#q').val(),
                preferredLang: $('#preferredLang').val(),
                includeBinaries: $('#inBinary').is(':checked') ? 1 : 0,
                linkNamedEntities: $('#linkNamedEntities').is(':checked') ? 1 : 0,
                page: $('#page').val(),
                pageSize: $('#pageSize').val(),
                facets: {}
            }
        };
        
        $('input.facet:checked').each(function (n, facet) {
            var prop = $(facet).attr('data-value');
            var val = $(facet).val();
            if (!(prop in param.data.facets)) {
                param.data.facets[prop] = [];
            }
            param.data.facets[prop].push(val);
        });
        
        $('input.facet-min').each(function (n, facet) {
            var prop = $(facet).attr('data-value');
            var val = $(facet).val();
            if (!(prop in param.data.facets)) {
                param.data.facets[prop] = {};
            }
            param.data.facets[prop].min = val;
        });
        
        $('input.facet-max').each(function (n, facet) {
            var prop = $(facet).attr('data-value');
            var val = $(facet).val();
            if (!(prop in param.data.facets)) {
                param.data.facets[prop] = {};
            }
            param.data.facets[prop].max = val;
        });
        console.log("param: ");
        console.log(param);
        var t0 = new Date();
        param.success = function (x) {
            if (token === localToken) {
                showResults(x, param.data, t0);
            }
        };
        
        $('#wait').show();
        $.ajax(param);
    }

    function reset() {
        $('input.facet:checked').prop('checked', false);
        $('input.facet-min').val('');
        $('input.facet-max').val('');
    }

    function showResults(data, param, t0) {
        $('#wait').hide();
        t0 = (new Date() - t0) / 1000;

        var pages = $('#page').get(0);
        var pageCount = Math.ceil(data.totalCount / data.pageSize);
        $('#pageCount').text('/ ' + pageCount);
        pages.options.length = 0;
        for (var i = 0; i < pageCount; i++) {
            pages.add(new Option(i + 1, i));
        }
        $('#page').val(data.page);


        if (data.results.length > 0) {
            var facets = '<button type="button" class="btn btn-info" onclick="reset();">Reset filters</button>';
            $.each(data.facets, function (facet, fd) {
                facets += '<h5 class="mt-3">' + shorten(facet) + '</h5>';
                if (fd.continues) {
                    $.each(fd.values, function (value, count) {
                        facets += value + ' (' + count + ')<br/>';
                    });
                    var min = facet in param.facets && param.facets[facet].min || '';
                    var max = facet in param.facets && param.facets[facet].max || '';
                    facets += '<input class="facet-min w-25" type="text" value="' + min + '" data-value="' + facet + '"/> - <input class="facet-max w-25" type="text" value="' + max + '" data-value="' + facet + '"/>';
                } else {
                    $.each(fd.values, function (value, count) {
                        var checked = facet in param.facets && param.facets[facet].indexOf(value) >= 0 ? 'checked="checked"' : ''
                        facets += '<input class="facet" type="checkbox" value="' + value + '" data-value="' + facet + '" ' + checked + '/> ' + shorten(value) + ' (' + count + ')<br/>';
                    });
                }
            });
            $('#facets').html(facets);
        }

        var results = '';
        if (data.results.length > 0) {
            results = '<h5>Displaying results ' + (data.pageSize * data.page + 1) + ' - ' + Math.min((data.page + 1) * data.pageSize, data.totalCount) + ' from ' + data.totalCount + ' (' + t0 + ' s):</h5>';
        } else {
            results = '<h5>No results found</h5>';
        }
        $.each(data.results, function (k, result) {
            results += '<div class="row my-3">' +
                    '<div style="flex: 0 0 150px;">' +
                    '<img class="mr-2" src="https://arche-thumbnails.acdh.oeaw.ac.at/?width=150&height=150&id=' + encodeURIComponent(result.url) + '"/>' +
                    '</div><div>' +
                    '<a href="https://arche.acdh.oeaw.ac.at/browser/detail/' + result.id + '" taget="_blank"><h5>' + Object.values(result.title)[0] + '</h5></a>' +
                    getParents(result.parent || false, true) +
                    'Class: ' + shorten(result.class[0]) + '<br/>' +
                    'Available date: ' + result.availableDate + '<br/>' +
                    'Match score: ' + result.matchWeight + '<br/>' +
                    'Matches in:<div class="ml-5">';
            for (var j = 0; j < result.matchProperty.length; j++) {
                results += shorten(result.matchProperty[j]) + ': ' + result.matchHiglight[j] + '<br/>';
            }
            results += '</div></div></div><hr/>';
        });
        $('#results').html(results);
    }
    function getParents(parent, top) {
        if (parent === false) {
            return '';
        }
        parent = parent[0];
        var ret = getParents(parent.parent || false, false);
        ret += (ret !== '' ? ' &gt; ' : '') + '<a href="https://arche.acdh.oeaw.ac.at/browser/detail/' + parent.id + '">' + Object.values(parent.title)[0] + '</a>';
        if (top) {
            ret = 'In: ' + ret + '<br/>';
        }
        return ret;
    }


    function loadSearchBlock() {
        $.ajax({
            url: '/browser/search_form',
            type: "GET",
            success: function (data, status) {
                $('.old-search').html(data.form_html);
            },
            error: function (message) {
                $('.old-search').html(message);
            }
        });
    }

});



