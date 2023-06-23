jQuery(function ($) {

    "use strict";

    /********************** EVENTS *************************************/

    // let metaValueField = $("input[name='metavalue']").val().replace(/[^a-z0-9öüäéáűúőóüöíß:./-\s]/gi, '').replace(/[\s]/g, '+');
    $(document).delegate("#sks-form-front", "submit", function (e) {
        e.preventDefault();

        let searchParam = $('#q').val();
        $('#block-mainpagecontent').html('<div class="container">' +
                '<div class="row">' +
                '<div class="col-12 mt-5">' +
                '<img class="mx-auto d-block" src="/browser/modules/contrib/arche-gui/images/arche_logo_flip_47px.gif">' +
                ' </div>' +
                '</div>');
        window.location.href = '/browser/search/?q=' + searchParam;
    });

    function countSearchIn() {
        var count = $('.searchInElement').length;
        $(".searchOnlyInBtn").html('Search only in ( ' + count + ' ) ');
    }

    $(document).delegate(".remove_search_only_in", "click", function (e) {
        e.preventDefault();
        var id = $(this).attr("data-removeid");
        // #in17722
        $('#searchIn #in' + id).remove();
        countSearchIn();
    });

    $(document).delegate(".smartSearchInAdd", "click", function (e) {
        e.preventDefault();
        var id = $(this).attr("data-resourceid");
        if ($('#in' + id).length === 1) {
            return;
        }

        var element = $('#res' + id).clone();
        console.log('element');
        console.log(element);
        element.find('div:first-child').html('<a data-removeid="' + id + '" href="#" class="remove_search_only_in">Remove</a>');
        //element.find('div:last-child').children('div').remove();
        var btn = element.find('button');
        btn.text('-');
        btn.attr('onclick', '$(this).parent().parent().parent().remove();');
        element.attr('id', 'in' + id);
        element.attr('class', 'searchInElement');
        element.addClass('row');
        $('#searchIn').append(element);
        countSearchIn();
    });

    $(document).delegate(".resetSmartSearch", "click", function (e) {
        console.log('clicked');
        e.preventDefault();
        $('#block-smartsearchblock input[type="text"]').val('');
        $('#block-smartsearchblock input[type="search"]').val('');
        $('#block-smartsearchblock input[type="checkbox"]').prop('checked', false);
        $('#block-smartsearchblock textarea').val('');
        $('#block-smartsearchblock select').val('');
    });

    //main search block
    $(document).delegate(".smartsearch-btn", "click", function (e) {
        console.log("search clicked");
        $('.arche-smartsearch-page-div').show();
        $('#block-mainpagecontent').html('<div class="container">' +
                '<div class="row">' +
                '<div class="col-12 mt-5">' +
                '<img class="mx-auto d-block" src="/browser/modules/contrib/arche-gui/images/arche_logo_flip_47px.gif">' +
                ' </div>' +
                '</div>');
        search();

        e.preventDefault();
    });

    if (window.location.href.indexOf("browser/search/") >= 0) {
        let searchParams = new URLSearchParams(window.location.search);
        if (searchParams.has('q')) {
            $('#block-mainpagecontent').html('<div class="container">' +
                    '<div class="row">' +
                    '<div class="col-12 mt-5">' +
                    '<img class="mx-auto d-block" src="/browser/modules/contrib/arche-gui/images/arche_logo_flip_47px.gif">' +
                    ' </div>' +
                    '</div>');
            let param = searchParams.get('q');
            $('#q').val(param);
            //we have to wait 2 secs to download all facets
            setTimeout(
                    function ()
                    {
                        search();
                    }, 2000);

        }
    }

    //////////////// SMART SEARCH ///////////////////

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
    var geonamesAccount = 'zozlak';
    var spatialSelect;
    var bbox = '';


    function fetchFacet() {
        $.ajax({
            url: '/browser/api/searchDateFacet',
            success: function (data) {
                data = jQuery.parseJSON(data);

                $.each(data, function (k, v) {
                    var facet = '<div class="mt-2">' +
                            '<label class="mt-2 font-weight-bold" >' + v.label + '</label><br/>' +
                            '<input type="checkbox" class="distribution mt-2" value="1" data-value="' + k + '"/> show distribution<br/>' +
                            '<input type="checkbox" class="range mt-2" value="1" data-value="' + k + '"/> show range' +
                            '<div id="' + k + 'Values" class="dateValues"></div>' +
                            '<div class="row mt-2">' +
                            '<div class="col-lg-5"> <input class="facet-min w-100" type="number" data-value="' + k + '"/> </div>' +
                            '<div class="col-lg-1"> - </div>' +
                            '<div class="col-lg-5"><input class="facet-max w-100" type="number" data-value="' + k + '"/> </div>' +
                            '</div>'
                    '</div>'
                    '<hr/>';
                    $('#dateFacets').append(facet);
                });
            }
        });
    }

    $(document).ready(function () {

        fetchFacet();

        spatialSelect = new SlimSelect({
            select: '#spatialSelect',
            events: {
                search: function (phrase, curData) {
                    return new Promise((resolve, reject) => {
                        if ($('#spatialSource').val() === 'arche') {
                            fetch('https://arche-smartsearch.acdh-dev.oeaw.ac.at/places.php?q=' + encodeURIComponent(phrase))
                                    .then(function (response) {
                                        return response.json();
                                    })
                                    .then(function (data) {
                                        data = data.map(function (x) {
                                            return {
                                                text: x.label + ' (' + shorten(x.match_property) + ': ' + x.match_value + ')',
                                                value: x.id
                                            }
                                        });
                                        data.unshift({text: 'No filter', value: ''});

                                        resolve(data);
                                    });
                        } else {
                            fetch('https://secure.geonames.org/searchJSON?fuzzy=0.7&maxRows=10&username=' + encodeURIComponent(geonamesAccount) + '&name=' + encodeURIComponent(phrase))
                                    .then(function (response) {
                                        return response.json();
                                    })
                                    .then(function (data) {
                                        var options = data.geonames.map(function (x) {
                                            return {
                                                text: x.name + ' (' + x.fcodeName + ', ' + (x.countryName || '') + ')',
                                                value: x.geonameId
                                            };
                                        });
                                        options.unshift({text: 'No filter', value: ''});
                                        resolve(options);
                                    });
                        }
                    });
                },
                afterChange: function (value) {
                    bbox = '';
                    if (value[0].value !== '') {
                        $('#wait').show();
                        $.ajax({
                            method: 'GET',
                            url: 'https://secure.geonames.org/getJSON?username=' + encodeURIComponent(geonamesAccount) + '&geonameId=' + encodeURIComponent(value[0].value),
                            success: function (d) {
                                if (d.bbox || false) {
                                    d = d.bbox;
                                    bbox = 'POLYGON((' + d.west + ' ' + d.south + ', ' + d.west + ' ' + d.north + ', ' + d.east + ' ' + d.north + ', ' + d.east + ' ' + d.south + ',' + d.west + ' ' + d.south + '))';
                                } else {
                                    bbox = 'POINT( ' + d.lng + ' ' + d.lat + ')';
                                }
                                $('#linkNamedEntities').prop('checked', true);
                            },
                            error: function (xhr, error, code) {
                                $('#block-mainpagecontent').html(error);
                            }
                        });
                    }
                }
            }
        });
    });
    function getLangValue(data, prefLang) {
        prefLang = prefLang || 'en';
        return data[prefLang] || Object.values(data)[0];
    }
    var token = 1;

    function search(searchStr = "") {
        token++;
        var localToken = token;
        if (!searchStr) {
            searchStr = $('#q').val();
        }

        var param = {
            url: '/browser/api/smartsearch',
            method: 'get',
            data: {
                q: searchStr,
                preferredLang: $('#preferredLang').val(),
                includeBinaries: $('#inBinary').is(':checked') ? 1 : 0,
                linkNamedEntities: $('#linkNamedEntities').is(':checked') ? 1 : 0,
                page: $('#page').val(),
                pageSize: $('#pageSize').val(),
                facets: {},
                searchIn: []
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
            if (val !== "") {
                if (!(prop in param.data.facets)) {
                    param.data.facets[prop] = {};
                }
                param.data.facets[prop].min = val;
            }
        });

        $('input.facet-max').each(function (n, facet) {
            var prop = $(facet).attr('data-value');
            var val = $(facet).val();
            if (val !== "") {
                if (!(prop in param.data.facets)) {
                    param.data.facets[prop] = {};
                }
                param.data.facets[prop].max = val;
            }
        });

        $('input.range:checked').each(function (n, facet) {
            var prop = $(facet).attr('data-value');
            if (!(prop in param.data.facets)) {
                param.data.facets[prop] = {};
            }
            param.data.facets[prop].distribution = 1;
        });

        $('input.distribution:checked').each(function (n, facet) {
            var prop = $(facet).attr('data-value');
            if (!(prop in param.data.facets)) {
                param.data.facets[prop] = {};
            }
            param.data.facets[prop].distribution = (param.data.facets[prop].distribution || 0) + 2;
        });

        if ($('#searchInChb:checked').length === 1) {
            $('#searchIn > div').each(function (n, el) {
                param.data.searchIn.push($(el).attr('data-value'));
            });
        }

        if (bbox !== '') {
            param.data.facets['bbox'] = bbox;
        }

        var t0 = new Date();

        param.success = function (x) {
            if (token === localToken) {
                showResults(x, param.data, t0);
            }
        };
        param.fail = function (xhr, textStatus, errorThrown) {
            alert(xhr.responseText);
        };

        param.statusCode = function (response) {
            console.log(response);
        };

        param.error = function (xhr, status, error) {
            var err = eval("(" + xhr.responseText + ")");
            console.log(err.Message);
        };
        console.log("param: ");
        console.log(param);
        $.ajax(param);
    }

    function resetSearch() {
        $('input.facet:checked').prop('checked', false);
        $('input.facet-min').val('');
        $('input.facet-max').val('');
        $('#preferredLang').val('');
        spatialSelect.setData([{text: 'No filter', value: ''}]);
    }

    function showResults(data, param, t0) {
        t0 = (new Date() - t0) / 1000;
        data = jQuery.parseJSON(data);

        var pages = $('#page').get(0);
        var pageCount = Math.ceil(data.totalCount / data.pageSize);
        $('#pageCount').text('/ ' + pageCount);
        pages.options.length = 0;
        for (var i = 0; i < pageCount; i++) {
            pages.add(new Option(i + 1, i));
        }
        $('#page').val(data.page);


        $('div.dateValues').text('');
        if (data.results.length > 0) {
            $('input.facet-min').attr('placeholder', '');
            $('input.facet-max').attr('placeholder', '');
            var facets = '<div class="row"><div class="col-lg-12"><button type="button" class="btn btn-info w-100" onclick="resetSearch();">Reset filters</button></div></div><br/>';
            $.each(data.facets, function (n, fd) {
                var fdp = param.facets[fd.property] || {};
                if (fd.values.length > 0) {
                    var div = $(document.getElementById(fd.property + 'Values'));
                    var text = '';
                    if (fd.continues && fdp.distribution >= 2) {
                        $.each(fd.values, function (n, i) {
                            text += i.label + ': ' + i.count + '<br/>';
                        });
                    }
                    if (!fd.continues) {
                        $.each(fd.values, function (n, i) {
                            var checked = '';//fdp.indexOf(i.value) >= 0 ? 'checked="checked"' : ''
                            text += '<input class="facet mt-2" type="checkbox" value="' + i.value + '" data-value="' + fd.property + '" ' + checked + '/> ' + shorten(i.label) + ' (' + i.count + ')<br/>';
                        });
                    }
                    if (div.length === 0) {
                        if (fd.continues && fdp.distribution >= 2) {
                            text += '<input class="facet-min w-25" type="text" value="' + (fdp.min || '') + '" data-value="' + fd.property + '"/> - <input class="facet-max w-25" type="text" value="' + (fdp.max || '') + '" data-value="' + fd.property + '"/>';
                        }
                        facets += '<label class="mt-2 font-weight-bold">' + fd.label + '</label><br/>' + text + '<br/>';
                    } else {
                        div.html(text + '<br/>');
                    }
                }
                if (fdp.distribution === 1 || fdp.distribution === 3) {
                    $('input.facet-min[data-value="' + fd.property + '"]').attr('placeholder', fd.min || '');
                    $('input.facet-max[data-value="' + fd.property + '"]').attr('placeholder', fd.max || '');
                }
            });
            $('#facets').html(facets + '<hr/>');
        }


        var prefLang = $('#preferredLang').val();
        var results = '';
        results += '<div class="container">';
        results += '<div class="row">';
        results += '<div class="col-lg-12">';
        if (data.results.length > 0) {
            results += '<h5 class="font-weight-bold">Displaying results ' + (data.pageSize * data.page + 1) + ' - ' + Math.min((data.page + 1) * data.pageSize, data.totalCount) + ' from ' + data.totalCount + ' (' + t0 + ' s):</h5>';
        } else {
            results += '<h5 class="font-weight-bold">No results found</h5>';
        }

        $.each(data.results, function (k, result) {
            results += '<div class="row my-3" id="res' + result.id + '" data-value="' + result.id + '">' +
                    '<div class="col-lg-2">' +
                    '<a href="https://arche-thumbnails.acdh.oeaw.ac.at/?width=600&id=' + encodeURIComponent(result.url) + '" data-lightbox="detail-titleimage-' + result.id + '" style="border-bottom: none;"><img class="mr-2" src="https://arche-thumbnails.acdh.oeaw.ac.at/?width=150&height=150&id=' + encodeURIComponent(result.url) + '"/></a><br/>' +
                    '<button type="button" class="btn btn-info mt-4 btn-xs smartSearchInAdd" data-resourceid="' + result.id + '" style="white-space: normal;">Add to search only in</button> ' +
                    '</div>' +
                    '<div class="col-lg-10">' +
                    '<h5>' +
                    '<a href="https://arche.acdh.oeaw.ac.at/browser/oeaw_detail/' + result.id + '" taget="_blank">' + getLangValue(result.title, prefLang) + '</a>' +
                    '</h5>' +
                    getParents(result.parent || false, true, prefLang) +
                    'Class: ' + shorten(result.class[0]) + '<br/>' +
                    'Available date: ' + result.availableDate +
                    '<div>' +
                    'Match score: ' + result.matchWeight + '<br/>';
            if (result.matchProperty.length > 0) {
                results += 'Matches in:<div class="ml-5">';
                for (var j = 0; j < result.matchProperty.length; j++) {
                    if (result.matchHiglight && result.matchHiglight[j]) {
                        results += shorten(result.matchProperty[j] || '') + ': ' + result.matchHiglight[j] + '<br/>';
                    } else {
                        results += shorten(result.matchProperty[j] || '') + '<br/>';
                    }
                }
            }
            results += '</div></div></div></div></div></div></div><hr/>';
        });
        $('#block-mainpagecontent').html(results);
    }


    function getParents(parent, top, prefLang) {
        if (parent === false) {
            return '';
        }
        parent = parent[0];
        var ret = getParents(parent.parent || false, false, prefLang);
        ret += (ret !== '' ? ' &gt; ' : '') + '<a href="https://arche.acdh.oeaw.ac.at/browser/oeaw_detail/' + parent.id + '">' + getLangValue(parent.title) + '</a>';
        if (top) {
            ret = 'In: ' + ret + '<br/>';
        }
        return ret;
    }



});
