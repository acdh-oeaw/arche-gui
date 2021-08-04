/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

jQuery(function ($) {
    var actualScale;
    
    function displayPdfViewer(scale) {
        actualScale = scale;
        //handle the promise
        window.addEventListener("unhandledrejection", function (promiseRejectionEvent) {
            // handle error here, for example log   
            $('.pdf-content-loading').hide();
            $('.pdf-toolbar').hide();
            $('.pdf-canvas').html(Drupal.t('PDF download is not possible, please check the binary!')).addClass('messages messages--error');
        });


        $(".pdf-container").pdfviewer({
            scale: 1,
            stopAtErrors: true,
            toolbar_template: '<div class="pdf-toolbar">' +
                    '<button id="pdf-prev" class="button button--primary js-form-submit form-submit">Previous</button>' +
                    '<span class="pdf-pager">Page: <span id="pdf-page-num"></span>/<span id="pdf-page-count"></span></span>' +
                    '<button id="pdf-next" class="button button--primary js-form-submit form-submit">Next</button>' +
                    '</div>',
            viewer_template: '<div class="pdf-canvas"> <div class="pdf-content-loading"> <div class="pdf-loader"></div> </div><canvas id="pdf-the-canvas"></canvas></div>',

            onDocumentLoaded: function () {
                console.log('onDocumentLoaded');
                var num = $(this).data('pdfviewer').pages();
                $(this).data('pdfviewer').autoFit();
                //alert('onDocumentLoaded:'+num);

            },
            onPrevPage: function () {
                //alert('onPrevPage'); 
                return true;
            },
            onNextPage: function () {
                //alert('onNextPage'); 
                return true;
            },
            onBeforeRenderPage: function (num) {
                //alert('onBeforeRenderPage'); 
                return true;
            },
            onRenderedPage: function (num) {
                //alert('onRenderedPage');
                $('.pdf-content-loading').hide();
            }
        });
    }
    $(document).ready(function () {

    });

    $(document).delegate("#pdfViewerBtn", "click", function (e) {
        e.preventDefault();
        $('#pdf-viewer-div').show();
        displayPdfViewer(2);
    });
    
    
    $(document).delegate("#zoominbutton", "click", function (e) {
        e.preventDefault();
        console.log('zoomin button pressed');
        displayPdfViewer(actualScale + 0.25);
    });

   

});