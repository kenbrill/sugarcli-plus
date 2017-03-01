<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>Package Manager+</title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>

    <style type="text/css">
        BODY,
        HTML {
            padding: 0px;
            margin: 0px;
        }

        BODY {
            font-family: Verdana, Arial, Helvetica, sans-serif;
            font-size: 11px;
            background: #EEE;
            padding: 15px;
        }

        H1 {
            font-family: Arial, serif;
            font-size: 20px;
            font-weight: normal;
        }

        H2 {
            font-family: Arial, serif;
            font-size: 16px;
            font-weight: normal;
            margin: 0px 0px 10px 0px;
        }

        .tree {
            float: left;
            margin: 15px;
        }

        .fileList {
            float: left;
            margin: 15px;
        }

        .demo {
            width: 400px;
            height: 300px;
            border-top: solid 1px #BBB;
            border-left: solid 1px #BBB;
            border-bottom: solid 1px #FFF;
            border-right: solid 1px #FFF;
            background: #FFF;
            overflow: scroll;
            padding: 5px;
        }

        thead th {
            font-family: 'Patua One', cursive;
            font-size: 16px;
            font-weight: 400;
            color: #000;
            @include text-shadow(1 px 1 px 0 px rgba(100, 100, 100, 0.5));
            text-align: left;
            padding: 20px;
        }

    </style>


    <link href="packageManager/jqueryFileTree.css" rel="stylesheet" type="text/css" media="screen"/>
    <link href="packageManager/jQuery_toastMessage/resources/css/jquery.toastmessage.css" rel="stylesheet" type="text/css" media="screen"/>

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.2.2/css/buttons.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/scroller/1.4.2/css/scroller.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/select/1.2.0/css/select.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <script type="text/javascript" src="https://code.jquery.com/jquery-2.2.3.min.js"></script>
    <script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/scroller/1.4.2/js/dataTables.scroller.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/select/1.2.0/js/dataTables.select.min.js"></script>
    <script src="packageManager/jquery.easing.js" type="text/javascript"></script>
    <script src="packageManager/jqueryFileTree.js" type="text/javascript"></script>
    <script src="packageManager/js/relationships.js"></script>
    <script src="packageManager/jQuery_toastMessage/javascript/jquery.toastmessage.js"></script>
</head>

<body>
<div id="tabs">
    <ul>
        <li><a href="#tabs-1">Files</a></li>
        <li><a href="#tabs-2">Relationships</a></li>
        <li><a href="#tabs-3">Custom Fields</a></li>
        <li><a href="#tabs-4">ACL</a></li>
        <li><a href="#tabs-5">Logic Hooks</a></li>
        <li><a href="#tabs-6">Review & Build</a></li>
    </ul>
    <div id="tabs-1">
<div class="container">
    <table border="1">
        <tr>
            <td valign="top">
                <div class="tree">
                    <h2>Pick Files</h2>
                    <select name="instance" style="width: 412px;" onChange="changeInstance(this)">
                        <?php
                        $dirs = array_filter(glob('/var/www/html/*'), 'is_dir');
                        foreach ($dirs as $possibleInstance) {
                            if (file_exists($possibleInstance . DIRECTORY_SEPARATOR . 'sugar_version.php')) {
                                $name = basename($possibleInstance);
                                echo "<option value='{$possibleInstance}'>{$name}</option><br>";
                            }
                        }
                        ?>
                    </select>
                    <div id="packageManagerFilePicker" class="demo"></div>
                </div>
            </td>
            <td valign="top">
                <div class="fileList">
                    <h2>Included files:&nbsp;<span id="counterDisplay">0</span></h2>
                    <select id="mySelect" multiple size="22" style="width: 600px;" ondblclick="removeSelect()">
                    </select><br>
                    <center>
                        <button name="Remove" onClick="removeSelect()">Remove Selected</button>
                        <button name="Truncate" onClick="removeAll()">Remove All</button>
                    </center>
                </div>
            </td>
        </tr>
    </table>
</div>
    </div>
    <div id="tabs-2">
    <div id="Relationship_div">
    </div>
    </div>
    <div id="tabs-3">
    <div id="CustomFields_div">
    </div>
    </div>
    <div id="tabs-4">
    <div id="ACL_div">
    </div>
    </div>
    <div id="tabs-5">
        <div id="Reports_div">
        </div>
    </div>
    <div id="tabs-5">
        <div id="Logic_div">
        </div>
    </div>
    <div id="tabs-6">
        <div id="Review">
        </div>
    </div>
</div>
</body>

</html>
<script type="application/javascript">
    var counter = 0;
    var myTimeOut;
    var selectList = [];
    function updateCounter() {
        document.getElementById("counterDisplay").innerHTML = counter;
    }

    function changeInstance(sel) {
        path = sel.value + '/';
        $('#packageManagerFilePicker').fileTree({
            root: path,
            script: 'packageManager/connector.php',
            folderEvent: 'dblclick',
            expandSpeed: 750,
            collapseSpeed: 750,
            multiFolder: false,
            expandEasing: 'easeOutBounce',
            collapseEasing: 'easeOutBounce',
            loadMessage: 'Loading...'
        }, function (file) {
            addElement(file);
        });
        fillInRelationships(path);
        fillInCustomFields(path);
        fillInACL(path);
        myTimeOut = setTimeout('addDataTable()',1000);
    }

    function fillInRelationships(path) {
        $.post( "packageManager/getRelationships.php", { path: path })
            .done(function( data ) {
                $('#Relationship_div').html(data);
            });
    }
    function fillInCustomFields(path) {
        $.post( "packageManager/getCustomFields.php", { path: path })
            .done(function( data ) {
                $('#CustomFields_div').html(data);
            });
    }
    function fillInACL(path) {
        $.post( "packageManager/getACL.php", { path: path })
            .done(function( data ) {
                $('#ACL_div').html(data);
            });
    }

    function removeAll() {
        var x = confirm("Are you sure?");
        if (x)
            $("#mySelect").empty();
    }

    function addElement(file) {
        var duplicate = false;
        for (i = 0; i < document.getElementById("mySelect").length; ++i) {
            if (document.getElementById("mySelect").options[i].value == file) {
                duplicate = true;
            }
        }
        if (duplicate == false) {
            $(document).ready(function () {
                $('#mySelect').append('<option>' + file + '</option>');
            })
            counter++;
            updateCounter();
            sortSelect();
        }
    }

    function removeSelect() {
        $("#mySelect option:selected").remove();
        counter--;
        updateCounter();
    }

    function addList(list) {
        listArray = list.split('|');
        for (var i = 0; i < listArray.length; i++) {
            file = listArray[i];
            addElement(file);
        }
    }

    function sortSelect() {
        var tmpAry = new Array();
        var selElem = document.getElementById('mySelect');
        for (var i = 0; i < selElem.options.length; i++) {
            tmpAry[i] = new Array();
            tmpAry[i][0] = selElem.options[i].text;
            tmpAry[i][1] = selElem.options[i].value;
        }
        tmpAry.sort();
        while (selElem.options.length > 0) {
            selElem.options[0] = null;
        }
        for (var i = 0; i < tmpAry.length; i++) {
            var op = new Option(tmpAry[i][0], tmpAry[i][1]);
            selElem.options[i] = op;
        }

        //check for possible invalid files
        $('#mySelect > option').each(function () {
            fileName = $(this).text();
            parts = fileName.split('/');
            //alert(parts[8]);
            if (parts[5] == 'cache' ||
                parts[5] == '.idea' ||
                (parts[5] == 'custom' && parts[8] == 'Ext' && parts[6] != 'Extension') ||
                parts[6] == 'blowfish' ||
                parts[6] == 'history' ||
                parts[7] == 'Ext') {
                $(this).css('color', 'red');
            }
        });

        return;
    }

    $(document).ready(function () {
        $( function() {
            $( "#tabs" ).tabs({
                active: 0
            });
        } );
        $().toastmessage('showToast', {
            text     : 'Loading data..',
            stayTime: 	3000,
            sticky   : false,
            position : 'top-right',
            type     : 'success',
            close    : function () {console.log("Closed Startup message...");}
           });
        fillInRelationships('/var/www/html/gfs');
        fillInCustomFields('/var/www/html/gfs');
        fillInACL('/var/www/html/gfs');
        myTimeOut = setTimeout('addDataTable()',1000);
        $('#packageManagerFilePicker').fileTree({
            root: '/var/www/html/gfs/',
            script: 'packageManager/connector.php',
            folderEvent: 'dblclick',
            expandSpeed: 750,
            collapseSpeed: 750,
            multiFolder: false,
            expandEasing: 'easeOutBounce',
            collapseEasing: 'easeOutBounce',
            loadMessage: 'Loading...'
        }, function (file) {
            addElement(file);
        });
    });

    function addDataTable() {
        clearTimeout(myTimeOut);
        addRelationshipTable();

        var table2 = $('#customfield_table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'selectNone',
                'selectAll'
            ],
            select: {
                style: 'multi'
            }
        });
        $('#customfield_table tbody').on('click', 'tr', function () {
            var data = table2.row( this ).data();
            alert( 'You clicked on '+data[0]+'\'s row' );
        } );

        var table3 = $('#ACL_table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'selectNone',
                'selectAll'
            ],
            select: {
                style: 'multi'
            }
        });
        $('#ACL_table tbody').on('click', 'tr', function () {
            var data = table3.row( this ).data();
            alert( 'You clicked on '+data[0]+'\'s row' );
        } );

    }
</script>

