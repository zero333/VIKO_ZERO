// TableSort.js

var prevSortColumn = 0;
var prevSortOrder = "asc";

function filesizeInBytes(text) {
    // some languages use comma as fractional part separator
    text.replace(/,/, ".");

    var regex = /^([0-9.]+) +(B|KB|MB|GB|TB)$/i;
    var matches = text.match(regex);
    if ( matches !== null ) {
        var val = Number(matches[1]);
        switch (matches[2].toUpperCase()) {
            case "B":  return val;
            case "KB": return val*1024;
            case "MB": return val*1024*1024;
            case "GB": return val*1024*1024*1024;
            case "TB": return val*1024*1024*1024*1024;
            default: return 0;
        }
    }
    else {
        // invalid filesize, default to zero
        return 0;
    }
}

function getCellValue(row,cellIndex) {
    // get our column
    var col = row.getElementsByTagName("td")[cellIndex];

    var nodeValue;
    if (col.firstChild.nodeType==3) {
        nodeValue = col.firstChild.nodeValue;
    }
    else {
        nodeValue = col.firstChild.firstChild.nodeValue;
    }

    if (col.className=="nr") {
        // convert numeric column types to numbers
        return Number(nodeValue);
    }
    else if (col.className=="filesize") {
        // convert filesizes to number of bytes
        return filesizeInBytes(nodeValue);
    }
    else {
        // convert string columns to lower case
        return nodeValue.toLowerCase();
    }
}

function pairCompare(pair1, pair2) {
    var val1 = pair1.value;
    var val2 = pair2.value;

    if (val1 > val2) {
        return +1;
    }
    else if (val1 < val2) {
        return -1;
    }
    else {
        return 0;
    }
}

function makeRowValuePair(row, cellIndex) {
    return {
        "row": row,
        "value": getCellValue(row, cellIndex)
    };
}

function sortTable(table, cellIndex) {
    // get our table body
    var tbody = table.getElementsByTagName("tbody")[0];

    // get list of rows - these will be sorted
    var rows = tbody.getElementsByTagName("tr");

    // To reduce sorting time, calculate the values of rows,
    // that are going to be compared, up front.
    // Each row will be converted into object with two properties:
    // property "row" is the original row node,
    // property "value" is the calculated value of a cell to be compared
    var rowValuePairs = map(
        rows,
        function(row){ return makeRowValuePair(row, cellIndex); }
    );

    // when previosly sorted, just reverse without sorting
    // otherwise sort normally
    if (prevSortColumn == cellIndex) {
        rowValuePairs.reverse();

        if ( prevSortOrder == "asc" ) {
            prevSortOrder = "desc";
        }
        else {
            prevSortOrder = "asc";
        }
    }
    else {
        rowValuePairs.sort(pairCompare);
    }
    prevSortColumn = cellIndex;

    // add new rows
    each(
        rowValuePairs,
        function(pair){ tbody.appendChild(pair.row); }
    );

    // reset stripes
    makeOddEvenRows(tbody);

    return false;
}

// we assume, that th element has only one child, which is a text node
function headingToLink(th,cellIndex, table) {
    var link = document.createElement("a");

    // replace text with link
    var text = th.firstChild;
    th.replaceChild(link, text);

    // put text inside link instead
    link.appendChild(text);

    makeOnclickHandler = function(){
        var myCellIndex = cellIndex;
        var myTable = table;
        return function(){ return sortTable(myTable, myCellIndex); };
    };

    // set some link attributes
    link.href="#";
    link.onclick=makeOnclickHandler();
}

function containsTextNode(e) {
    // check that there is only one child node and that it's a text node
    return ( e.childNodes.length == 1 && e.firstChild.nodeType == 3 );
}

function initTable(table) {
    // get table head (there can only be one)
    var thead = table.getElementsByTagName("thead")[0];

    // get each individual heading cell
    var headings = thead.getElementsByTagName("th");

    // we are only interested in headings, that contain text
    headings = filter(headings, containsTextNode);

    // convert each heading to link
    for (var i=0; i<headings.length; i++) {
        headingToLink(headings[i],i,table);
    }
}

function makeOddEvenRows(tbody) {
    var rows = tbody.getElementsByTagName("tr");

    for (var rowIndex=0; rowIndex<rows.length; rowIndex++) {
        var tr = rows[rowIndex];
        var rowNumber = rowIndex+1;
        if ( rowNumber % 2 === 0 ) {
            tr.className = "even";
        }
        else {
            tr.className = "odd";
        }
    }
}

function initTableSort() {
    // init each sortable table
    var tables = getElementsByClass("sortable", document, "table");
    each(tables, initTable);
}

Handler.add(window, "load", initTableSort);








