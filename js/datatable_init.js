var jq2 = jQuery;
jQuery.noConflict(true);
jq2(function( $ ) {
        var table = jq2('table.display').DataTable({
           "lengthMenu": [[20, 35, 50, -1], [20, 35, 50, "All"]]
        });
        
        jq2(".loader-div").hide();
        hidepopup();
        
        /** check the restriction for the dissemination services  START */
        
        jq2("#cancelLogin").click(function(e){
            e.preventDefault();
            hidepopup();
        });
    
        function showpopup()
        {
            $("#dissServLoginform").fadeIn();
            $("#dissServLoginform").css({"visibility":"visible","display":"block"});
        }

        function hidepopup()
        {
            $("#dissServLoginform").fadeOut();
            $("#dissServLoginform").css({"visibility":"hidden","display":"none"});
        }
            
        var accessRestriction = jq2('#accessRestriction').val();
        
        if(accessRestriction){
            if(accessRestriction.indexOf('public') == -1){
                jq2( ".dissServAhref" ).click(function(e) {
                    
                    let urlValue = jq2(this).attr("href");
                    let webUrl = window.location.origin + '/browser/';
                    
                    if(urlValue.indexOf(webUrl) > -1) {
                        window.location.replace(urlValue);
                    }
                    var xhr = new XMLHttpRequest();
                    let basic_auth = $("input#basic_auth").val();
                    
                    if(basic_auth){
                        $.ajax
                        ({
                            type: "GET",
                            url: urlValue,                            
                            xhr: function() {
                                return xhr;
                            },
                            beforeSend: function (xhr) {
                                xhr.setRequestHeader ("Authorization", basic_auth);
                            },
                            success: function (){
                                if(xhr.responseURL) {
                                    window.location.replace(xhr.responseURL);
                                }else{
                                    jq2( "#shibboleth_login_info" ).show().html(Drupal.t('Login error'));
                                }
                            },
                            error( xhr,status,error) {
                                jq2( "#shibboleth_login_info" ).show().html(Drupal.t('Login error:' + error));
                            }
                        });
                        return false;
                    }
                    
                    showpopup();
                    
                    jq2( "#dologin" ).click(function(ed) {
                        var username = $("input#username").val();
                        var password = $("input#password").val();
                        
                        if( username && password) {
                            ed.preventDefault();
                            
                            $.ajax
                            ({
                                type: "GET",
                                url: urlValue,
                                username: username,
                                password: password,
                                xhr: function() {
                                    return xhr;
                                },
                                success: function (){
                                    if(xhr.responseURL) {
                                        hidepopup();
                                        window.location.replace(xhr.responseURL);
                                    }else{
                                        jq2( "#loginErrorDiv" ).html(Drupal.t('Login error'));
                                    }
                                },
                                error( xhr,status,error) {
                                    jq2( "#loginErrorDiv" ).html(Drupal.t('Login error'));
                                }
                            });
                        }else{
                            jq2( "#loginErrorDiv" ).html(Drupal.t('Please provide your login credentials'));
                        }

                        ed.preventDefault();
                    });  
                    e.preventDefault();
                });  
            }
        }
        
        /** check the restriction for the dissemination services END */
        
        //the JS for the inverse table
        jq2( "#showInverse" ).click(function(e) {
            e.preventDefault();
            //show the table
            jq2('#inverseTableDiv').show("slow");
            //hide the button
            jq2('#showInverse').parent().hide("slow");
            //get the uri
            var uri = jq2('#showInverse').data('tableuri');
            //genereate the data
            jq2('table.inverseTable').DataTable({
                "ajax": {
                    "url": "/browser/oeaw_inverse_result/"+uri,
                    "data": function ( d ) {
                        d.limit = d.draw;
                    }
                }
            });
        });
        
        //the JS for the isMember table
        jq2( "#showIsMember" ).click(function(e) {
            e.preventDefault();
            //show the table
            jq2('#isMemberTableDiv').show("slow");
            //hide the button
            jq2('#showIsMember').parent().hide("slow");
            //get the uri            
            var url = jq2('#showIsMember').data('tableurl');    
            //genereate the data
            jq2('table.isMemberTable').DataTable({
                "ajax": {
                    "url": "/browser/oeaw_ismember_result/"+url,
                    "data": function ( d ) {
                        d.limit = d.draw;
                    }
                }
            });
        });
});