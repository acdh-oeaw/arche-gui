jQuery(function ($) {

    $(document).ready(function () {


    });




    /* Root table query */
    var oTable = $('.root-table').DataTable({
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
            'url': "/browser/api/root/en",
            complete: function (response) {
                var order = 0;
                if (response.responseJSON.order == "desc") {
                    order = 1;
                }
                $('#sortBy').val(response.responseJSON.orderby + '-' + order);
            }
        },
        'columns': [
            {data: 'id', visible: false},
            {data: 'title',
                render: function (data, type, row, meta) {
                    return '<span class="res-title"><a href="/browser/oeaw_detail/' + row.id + '">' + data + '</a></span><br>\n\
                <i class="material-icons">today</i> <span class="res-prop-label">' + Drupal.t("Type") + ': </span> <span class="res-rdfType"><a id="archeHref" href="/browser/search/type=acdh:TopCollection&payload=false/titleasc/10/1">acdh:TopCollection</a></span><br>\n\
                <i class="material-icons">today</i> <span class="res-prop-label">' + Drupal.t("Available Date") + ':</span> <span class="res-prop-value">' + row.avdate + '</span>';
                }
            },
            {data: 'avdate', visible: false},
            {data: 'description', visible: false},
            {data: 'acdhid', visible: false},
            {data: 'sumcount', visible: false},
            {data: 'image', width: "20%", render: function (data, type, row, meta) {
                    let acdhid = row.acdhid.replace('https://', '');
                    return '<div class="dt-single-res-thumb">\n\
                            <center><a href="https://arche-thumbnails.acdh.oeaw.ac.at/' + acdhid + '?width=600" data-lightbox="detail-titleimage-' + row.id + '">\n\
                                <img class="img-responsive" src="https://arche-thumbnails.acdh.oeaw.ac.at/' + acdhid + '?width=150">\n\
                            </a></center>\n\
                            </div>';
                }
            }
        ],
        fnDrawCallback: function () {
            $(".root-table thead").remove();
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

        oTable.order([id, orderVal]).draw();
    });




});

