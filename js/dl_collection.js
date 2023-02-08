jQuery(function ($) {
    "use strict";

    var selectedItems = [];
    var disableChkUrlArray = [];
    var checked_ids = [];
    var unchecked_ids = [];

    var disableChkArray = [];
    var disableChkIDArray = [];
    var disableDirectoryIDArray = [];

    var resourceGroupsData = {};

    function bytesToSize(bytes, decimals = 2) {
        if (bytes === 0)
            return '0 Bytes';
        var k = 1024,
                dm = decimals || 2,
                sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
                i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
    

    function secondsTimeSpanToHMS(s) {
        var h = Math.floor(s / 3600); //Get whole hours
        s -= h * 3600;
        var m = Math.floor(s / 60); //Get remaining minutes
        s -= m * 60;
        return ' ' + h + ' ' + Drupal.t('hour(s)') + ' ' + (m < 10 ? '0' + m : m) + ' ' + Drupal.t('min(s)') + (s < 10 ? '0' + s : s) + ' ' + Drupal.t('sec(s)'); //zero padding on minutes and seconds
    }

    /*
     var tableCollection = $('table.collTable').DataTable({
     "lengthMenu": [[20, 35, 50, -1], [20, 35, 50, "All"]]
     });
     */

    function getActualuserRestriction() {
        var roles = drupalSettings.acdh_repo_gui.users.roles;
        var actualUserRestriction = 'public';

        if (roles !== '') {
            if (roles.includes('administrator')) {
                actualUserRestriction = 'admin';
            }
            if (roles.includes('academic')) {
                actualUserRestriction = 'academic';
            }
            if (roles.includes('restricted')) {
                actualUserRestriction = 'restricted';
            }
            if (roles.includes('anonymus')) {
                actualUserRestriction = 'public';
            }
        }
        if (drupalSettings.acdh_repo_gui.users.name === "shibboleth") {
            actualUserRestriction = 'academic';
        }
        return actualUserRestriction;
    }

    function generateCollectionTreeLazy(url, disabledUrls = [], username = "", password = "") {

        var actualUserRestriction = getActualuserRestriction();
        $('#collectionBrowser')
            .jstree({
                core: {
                    'check_callback': false,
                    'data': {
                        'url': function (node) {
                            if(node.id !== "#") {
                               url = node.id; 
                            }
                            return '/browser/api/get_collection_data_lazy/'+url+'/'+drupalSettings.language;
                        },
                        'data': function (node) {
                            return { 'id' : node.id }; 
                        },
                        'success': function (nodes) {
                        }
                    },
                    themes: {stripes: true},
                    error: function (jqXHR, textStatus, errorThrown) {
                        $('#collectionBrowser').html("<h3>Error: </h3><p>" + jqXHR.reason + "</p>");
                    }
                },
                checkbox: {
                    //keep_selected_style : true,
                    tie_selection: false,
                    whole_node: false
                },
                search: {
                    case_sensitive: false,
                    show_only_matches: true
                },
                plugins: ['checkbox', 'search']
            })//treeview before load the data to the ui
            .on("loaded.jstree", function (e, d) {
                var userAllowedToDL = false;
                $.each(d.instance._model.data, function (key, value) {
                    $.each(value.original, function (k, v) {
                        var resRestriction = "public";
                        if (k === 'userAllowedToDL') {
                            userAllowedToDL = checkActualDownloadStatus(v);                            
                        }
                        if (k === 'accessRestriction' && v !== null) {
                            resRestriction = setRestriction(v); 
                            if(checkResourceRestriction(resRestriction, actualUserRestriction) === true) {                                
                                userAllowedToDL === false;
                                fillDisableCheckBoxArrays(key, value, resRestriction);
                                $("#" + value.id).css('color', 'red');
                                uncheckAndDisableNode(value.id);
                            }
                        }                        
                        userAllowedToDL = false;
                    });                    
                    //if this is a directory and there is no child resources loaded, then we will block
                    //the checkbox, because the download will not work, because of the lazy load
                    disableDirectories(value);                    
                });
            });
    }
    
    function uncheckAndDisableNode(id) {
        $("#collectionBrowser").jstree("uncheck_node", id);
        $("#collectionBrowser").jstree().disable_node(id);
    }
    
    function fillDisableCheckBoxArrays(key, value, resRestriction) {
        disableChkArray.push(key + '_anchor');
        disableChkUrlArray.push(value.original.uri_dl);

        var obj = createResourceObject(value, resRestriction);
        //get one url for the permission levels
        if (!resourceGroupsData.hasOwnProperty(resRestriction)) {
            resourceGroupsData[resRestriction] = value.original.uri_dl;
        }
        disableChkIDArray.push(obj);
    }
    
    function disableDirectories(value) {
        if(value.icon === true && value.children.length === 0) {
            disableDirectoryIDArray.push(value.id);
            uncheckAndDisableNode(value.id);                           
        }
    }
    
    function createResourceObject(value, resRestriction) {
        return {"id": value.id, "url": value.original.uri_dl, "accessRestriction": resRestriction};
    }
    
    /**
     * Check the actual restriction
     * @param {type} data
     * @returns {String}
     */
    function setRestriction(data) {
        var result = data.split('/');
        var resRestriction = result.slice(-1)[0];
        if (!resRestriction) {
            resRestriction = "public";
        }
        return resRestriction;
    }
    
    function checkActualDownloadStatus(v) {
        if (v) {
            if (v === true) {
                return true;
            }
        }
        return false;
    }

    function checkResourceAccess(urls, username, password, callback) {       
        $("#loader-div").css("display", "block");

        let length = Object.keys(resourceGroupsData).length;
        var counter = 0;
        var result = [];
        $.each(urls, function (i, u) {
            $.when(
                $.ajax(u + '/metadata',
                    {
                        type: 'HEAD',
                        //username: username,
                        //password: password,
                        error: function (error) {
                            console.log(error);
                            callback(false);
                        },
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader("Authorization", "Basic " + btoa(username + ":" + password));
                        }
                    }
                )
            )
            .then(
                    function (data, textStatus, jqXHR) {
                        if (jqXHR.status === 200) {
                            //if the user/pwd was okay then we will remove this id 
                            //rfom the result
                            result.push(i);
                            counter++;
                            // last element reached so we will pass back the urls
                            if (counter === length) {
                                callback(result);
                            }
                        }
                    }
                );
        });
    }
    
    function createSelectedSizeMessage(cssClass, message) {
        $("#selected_files_size").html("<p class='"+cssClass+"'> " + message + "</p> ");                    
    }

    $(document).ready(function () {
        $('#selected_files_size_div').hide();
        $('#success_login_msg').hide();
        $('#error_login_msg').hide();
        let dlTime = $('#estDLTime').val();
        let formattedDlTime = secondsTimeSpanToHMS(dlTime);
        $('#dl_time').html(formattedDlTime);

        //var uid = Drupal.settings.currentUser;
        var roles = drupalSettings.acdh_repo_gui.users.roles;
        var actualUserRestriction = 'public';
        actualUserRestriction = getActualuserRestriction();

        var url = $('#repoid').val();
        var repoid = $('#repoid').val();

        if (!getCookie(url)) {
            //to generate the actual collection tree
            //window.setTimeout(generateCollection(url), 5000);
            window.setTimeout(generateCollectionTreeLazy(url), 5000);
        }

        /**  the collection download input field actions **/
        $("#search-input").keyup(function () {
            var searchString = $(this).val();
            $('#collectionBrowser').jstree('search', searchString);
        });

        /** the collection download jstree js  **/
        var sumSize = 0;

        //handle the node clicking to download the file
        $('#collectionBrowser').on("changed.jstree", function (node, data) {
            $('#selected_files_size_div').show();
            $('#dl_link').hide();
            $('#dl_link_txt').hide();

            if (data.selected.length === 1) {
                var selected = data.instance.get_node(data.selected[0]);
                //var hasChildren = !selected.state.loaded || selected.children.length > 0;
                //if we have a directory then do not open the fedora url
                if (data.node.original.dir === false) {
                    let id = data.instance.get_node(data.selected[0]).id;
                    //check the permissions for the file download
                    var resourceRestriction = data.instance.get_node(data.selected[0]).original.accessRestriction;

                    if (((resourceRestriction.search('public') === -1) && resourceRestriction.indexOf(actualUserRestriction) === -1)
                            || actualUserRestriction === 'admin') {
                        $('#not_enough_permission').hide();
                        window.location.href = data.instance.get_node(data.selected[0]).original.uri;
                    } else if (resourceRestriction.search('public') !== -1) {
                        $('#not_enough_permission').hide();
                        window.location.href = data.instance.get_node(data.selected[0]).original.uri;
                    } else {
                        alert(Drupal.t('You do not have access rights to get all of the resources'));
                    }
                }
            }
        })
        //the tree view before open functions
        .on("before_open.jstree", function (e, d) {
            $('#not_enough_permission').hide();
            //remove the actually opened dir from the disabled checkboxes array
            removeDirectoryFromDisabledCheckBoxArray(d);
            //add the possible child directories to the disabled checkboxes
            addChildDirectoriesToDisabledCheckBoxArray(d);
            if (disableChkArray.length > 0) {
                $.each(disableChkArray, function (key, value) {
                    $("#" + value).css('color', 'red');
                });
                $('#not_enough_permission').show();
            } 
        })
        //handle the checkboxes to download the selected files as a zip
        .on("check_node.jstree", function (node, data) {
            resourceCheckedOrUnchecked();
            sumSize = 0;
            
            if (disableChkArray.length > 0) {                
                $('#not_enough_permission').show();
            }            
            
            if (data.instance.get_checked(true)) {
                selectedItems = [];
                var actualResource = data.instance.get_checked(true);
                //if we have more than 4000 resources selected then drop an error message
                if (actualResource.length > 4000) { 
                    handleTooMuchSelectedResource(actualResource);                    
                }  else { 
                    //check here also the disables array
                    $.each(actualResource, function (i, res) {
                        if (res && res.icon !== true) {
                            var resourceRestriction = "public";
                            var enabled = false;
                            
                            if (res.original.hasOwnProperty("accessRestriction")) {
                                resourceRestriction = res.original.accessRestriction;
                            } 
                            
                            //check the rights
                            if(checkResourceRestriction(resourceRestriction, actualUserRestriction) === true) {
                                if (disableChkArray.length > 0) {
                                    $.each(disableChkArray, function (key, value) {
                                        $("#" + value).css('color', 'red');
                                        uncheckAndDisableNode(res.id);
                                    });
                                    $('#not_enough_permission').show();
                                } else {
                                    enabled = true;
                                    $("#collectionBrowser").jstree().enable_node(res.id);
                                }
                            } else {
                                enabled = true;
                                $("#collectionBrowser").jstree().enable_node(res.id);
                                $("#" + res.id).css('color', 'black');
                            }

                            if (res.original.binarysize && res.original.uri) {
                                // if( ((resourceRestriction == 'public') &&  resourceRestriction == actualUserRestriction) || actualUserRestriction == 'admin' ){
                                if (enabled === true) {
                                    selectedItems.push({id: res.id, size: res.original.binarysize, uri: res.original.uri, uri_dl: res.original.encodedUri, filename: res.original.filename, path: res.original.locationpath});
                                    sumSize = sumSize + Number(res.original.binarysize);
                                    showActualSelectedFilesAndSizes(sumSize, 6299999999, actualResource.length);                                    
                                }
                            }
                        } else {
                            sumSize = 0;
                        }
                    });
                }
                
                //disable the other directories
                if(disableDirectoryIDArray.length > 0) {
                    $.each(disableDirectoryIDArray, function (key, value) {
                        uncheckAndDisableNode(value);
                    });
                }
            }
        })
        .on("uncheck_node.jstree", function (node, data) {   
            resourceCheckedOrUnchecked();  
            var actualResource = createSelectedResourceObject(data.node.original);
            sumSize = 0;
            
            let allCheckedResource = data.instance.get_checked(true);
            if(allCheckedResource.length > 0) {
                //because we uncheck the directories also, we have to recalculate the sumsize value
                $.each(allCheckedResource, function (key, value) {
                    if(value.icon === "jstree-file" && value.original.binarysize) {
                        sumSize = sumSize + Number(value.original.binarysize);    
                    }
                });
                //remove the unchecked actual element
                if(actualResource.filename && actualResource.size > 0) {
                    sumSize = sumSize - Number(actualResource.size);
                }
            }
            showActualSelectedFilesAndSizes(sumSize, 6299999999, allCheckedResource.length);
        });
        hidepopup();

        //prepare the zip file
        $('#getCollectionData').on('click', function (e) {
            $("#loader-div").show();

            //disable the button after the click
            $(this).prop('disabled', true);
            var repoid = $('#repoid').val();
            e.preventDefault();
            var uriStr = "";
            //object for the file list
            var myObj = {};
            
            $.each(selectedItems, function (index, value) {
                uriStr += value.uri_dl + "__";
               
               if(value.path === null) {
                   value.path = "/data";
               }
                
                myObj[index] = createResultArray(value);
            });
            var username = $("input#username").val();
            var password = $("input#password").val();
            // Chrome 1 - 79
            //var isChrome = !!window.chrome && (!!window.chrome.webstore || !!window.chrome.runtime);
            //chrome has a problem with async false, this is why we need to
            //add the timeout, otherwise chrome will not display the loader.
            setTimeout(function () {
                $.ajax({
                    url: '/browser/api/dl_collection_binaries/' + repoid,
                    type: "POST",
                    async: false,
                    data: {jsonData: JSON.stringify(myObj), repoid: repoid, username: username, password: password},
                    timeout: 3600,
                    success: function (data, status) {
                        $('#dl_link_a').html('<a href="' + data + '" target="_blank">' + Drupal.t("Download Collection") + '</a>');
                        $('#dl_link').show();
                        $('#dl_link_txt').show();
                        $("#loader-div").delay(2000).fadeOut("fast");
                        $("#getCollectionDiv").hide();
                        return data;
                    },
                    error: function (xhr, status, error) {
                        $("#loader-div").delay(2000).fadeOut("fast");
                        createSelectedSizeMessage("size_text_red", Drupal.t('A server error has occurred... ' + status));
                    }
                });
            }, 10);
        });

        $('#loginToRestrictedResources').on('click', function (e) {
            showpopup();
            $('#dologin').on('click', function (ed) {
                $("#loader-div").css("display", "block");

                var username = $("input#username").val();
                var password = $("input#password").val();

                //checkResourceAccess(disableChkIDArray, username, password, function(newData) {
                checkResourceAccess(resourceGroupsData, username, password, function (newData) {

                    if (newData === false) {
                        $("#loader-div").delay(2000).fadeOut("fast");
                        $('#error_login_msg').show(0).delay(4000).fadeOut(1000).hide(0);
                        return false;
                    }

                    var newArray = [];
                    unchecked_ids = [];
                    var disabledArray = disableChkIDArray;
                    disableChkIDArray = [];
                    disableChkArray = [];
                    //if we have resources which are still not available for us
                    $.each(newData, function (i, u) {
                        $.each(disabledArray, function (ind, val) {
                            if (val.accessRestriction === u) {
                                //create the anchor ids 
                                var ahrefId = val.id + '_anchor';
                                //the objects
                                newArray.push(disabledArray[ind]);
                                unchecked_ids.push(val.id);                     
                            } else {
                                disableChkIDArray.push(disabledArray[ind]);
                                disableChkArray.push(val.id + '_anchor');
                            }
                        });
                        
                    });

                    checked_ids = [];

                    var checkedObj = $("#collectionBrowser").jstree("get_checked", true);

                    $.each(checkedObj, function (k, v) {
                        if (v.state.checked === true) {
                            checked_ids.push(this.id);
                            $("#" + this.id).css('color', 'black');
                            //remove the unchecked elements, because the jstree unchecked func not 
                            //working always as we expect.

                            checked_ids = checked_ids.filter(function (val) {
                                v.original.userAllowedToDL = true;
                                return unchecked_ids.indexOf(val) === -1;
                            });
                        }
                        $("#" + v.id).css('color', 'black');
                    });

                    $.each(newArray, function (k, v) {
                        $("#" + v.id + "_anchor").css('color', 'black');
                        $("#collectionBrowser").jstree().enable_checkbox(v.id + "_anchor");
                        $("#collectionBrowser").jstree().enable_node(v.id + "_anchor");
                    });
                    setCookie(repoid, disableChkIDArray);
                    $("#loader-div").delay(2000).fadeOut("fast");
                    $('#success_login_msg').show(0).delay(2000).fadeOut(1000).hide(0);

                });

                ed.preventDefault();
                $("#loader-div").delay(2000).fadeOut("fast");
                hidepopup();
            });
            e.preventDefault();
        });

        /** check the restriction for the dissemination services  START */

        $('#cancelLogin').on('click', function () {
            hidepopup();
        });
        
    });

    function handleTooMuchSelectedResource(actualResource) {
        $.each(actualResource, function (i, res) {
            $("#collectionBrowser").jstree("uncheck_node", res.id);
        });                    
        createSelectedSizeMessage("size_text_red", Drupal.t('You can select max 4000 files!') + "(" + actualResource.length + " " + Drupal.t('Files')+")");
        $("#getCollectionDiv").hide();
    }

    function resourceCheckedOrUnchecked() {
        $('#selected_files_size_div').show();
        $('#dl_link').hide();
        $('#dl_link_txt').hide();
        $('#getCollectionData').prop('disabled', false);
        $('#not_enough_permission').hide();
    }
    
    function createSelectedResourceObject(res) {
        var obj = {};
        obj.id = res.id;
        obj.size = res.binarysize;
        obj.uri = res.uri;
        obj.uri_dl = res.encodedUri;
        obj.filename = res.filename;
        obj.path = res.locationpath;
        return obj;
    }

    function showActualSelectedFilesAndSizes(sumSize, limit, actualResourceLength) {
        if (sumSize > limit) {
            createSelectedSizeMessage("size_text_red", bytesToSize(sumSize) + " (" + Drupal.t('Max. tar download limit is') + " 6 GB) (" + actualResourceLength + " " + Drupal.t('File(s)') + ")");
            $("#getCollectionDiv").hide();
        } else {
            createSelectedSizeMessage("size_text", bytesToSize(sumSize) + " (" + Drupal.t('Max. tar download limit is') + " 6 GB) (" + actualResourceLength + " " + Drupal.t('File(s)') + ")");
            $("#getCollectionDiv").show();
        }
    }

    function createResultArray(value) {
        var resArr = {};
        resArr['uri'] = value.uri;
        resArr['filename'] = value.filename;
        resArr['path'] = value.path;
        return resArr;
    }

    function removeDirectoryFromDisabledCheckBoxArray(d) {        
        disableDirectoryIDArray = disableDirectoryIDArray.filter(function(e) { return e !== d.node.original.id; });
        $("#collectionBrowser").jstree().enable_node(d.node.original.id);
    }
    
    function addChildDirectoriesToDisabledCheckBoxArray(d) {
        $.each(d.instance._model.data, function (key, value) {
            disableDirectories(value);
        });
    }

    function checkResourceRestriction(resourceRestriction, actualUserRestriction) {
        if (((resourceRestriction !== 'public') && resourceRestriction !== actualUserRestriction) && actualUserRestriction !== 'admin') {
            return true;
        }
        return false;
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
            while (c.charAt(0) === ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) === 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    function showpopup()
    {
        $("#dissServLoginform").fadeIn();
        $("#dissServLoginform").css({"visibility": "visible", "display": "block"});
    }

    function hidepopup()
    {
        $("#dissServLoginform").fadeOut();
        $("#dissServLoginform").css({"visibility": "hidden", "display": "none"});
    }
});