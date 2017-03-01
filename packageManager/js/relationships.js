function checkRelationships(data) {
    var relName = data[0];
    var relLength = relName.length;
    var removed = 0;
    $("#mySelect option").each(function (index,element) {
        console.log(index);
        console.log(element.value);
        console.log(element.text);
//        var fileName = $(this).val().split(/[\\/]/).pop();
//        if (fileName.substr(0, relLength) == relName) {
        //Remove the relationship metadata
        if(strstr(fileName,relName+'MetaData.php')!=false) {
            console.log('Removed: ' + $(this).val());
            $("#mySelect option[value='"+element.value+"']").remove();
            removed++;
            counter--;
            updateCounter();
        }
    });
    if (removed > 0) {
        return true;
    } else {
        return false;
    }
}

function addRelationshipTable() {
    var table1 = $('#relationship_table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'selectNone',
            'selectAll'
        ],
        select: {
            style: 'multi'
        }
    });
    $('#relationship_table tbody').on('click', 'tr', function () {
        var id = table1.row( this ).index();
        //Add remove from the selected list
        var index = $.inArray(id, selectList);
        var data = table1.row( this ).data();
        if ($(this).hasClass('selected')) {
            selectList.push( id );
            console.log('add');
            console.log(selectList);
            if(checkRelationships(data)) {
                $().toastmessage('showToast', {
                    text     : 'Removed relationship files from installdefs',
                    stayTime: 	3000,
                    sticky   : false,
                    position : 'top-right',
                    type     : 'warning',
                    close    : function () {console.log("Closed Relationship message...");}
                });
            }
        } else {
            selectList.splice( id, 1 );
            console.log('remove');
            console.log(selectList);
        }
    } );
}

function strstr(haystack, needle, bool) {
    // Finds first occurrence of a string within another
    //
    // version: 1103.1210
    // discuss at: http://phpjs.org/functions/strstr    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: strstr(‘Kevin van Zonneveld’, ‘van’);
    // *     returns 1: ‘van Zonneveld’    // *     example 2: strstr(‘Kevin van Zonneveld’, ‘van’, true);
    // *     returns 2: ‘Kevin ‘
    // *     example 3: strstr(‘name@example.com’, ‘@’);
    // *     returns 3: ‘@example.com’
    // *     example 4: strstr(‘name@example.com’, ‘@’, true);    // *     returns 4: ‘name’
    var pos = 0;

    haystack += "";
    pos = haystack.indexOf(needle); if (pos == -1) {
        return false;
    } else {
        if (bool) {
            return haystack.substr(0, pos);
        } else {
            return haystack.slice(pos);
        }
    }
}