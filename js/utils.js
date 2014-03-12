/**
 * getElementsByClass function by Dustin Diaz
 * taken from http://www.dustindiaz.com/top-ten-javascript/
 *
 * retrieves all the elements in docyment that have classname searchClass.
 * Other parameters are optional:
 * node - the DOM node inside which to look for ( by default document )
 * tag - HTML element name to look for
 */
function getElementsByClass( searchClass, node, tag ) {
    var classElements = new Array();
    if ( node == null ) {
        node = document;
    }
    if ( tag == null ) {
        tag = '*';
    }
    var els = node.getElementsByTagName(tag);
    var elsLen = els.length;
    var pattern = new RegExp('(^|\\s)'+searchClass+'(\\s|$)');
    for (i = 0, j = 0; i < elsLen; i++) {
        if ( pattern.test(els[i].className) ) {
            classElements[j] = els[i];
            j++;
        }
    }
    return classElements;
}

/**
 * Generic map function
 *
 * Applies function fun to every element of array arr,
 * creating a new modified array and returning it.
 */
function map(arr, fun) {
    if (typeof fun != "function") {
        throw new TypeError();
    }

    var len = arr.length;
    var res = new Array(len);
    for (var i = 0; i < len; i++) {
        if (i in arr) {
            res[i] = fun(arr[i]);
        }
    }

    return res;
}

/**
 * Generic each function
 *
 * Just performes operation on every element,
 * without returning new array.
 */
function each(arr, fun) {
    if (typeof fun != "function") {
        throw new TypeError();
    }

    var len = arr.length;
    for (var i = 0; i < len; i++) {
        if (i in arr) {
            fun(arr[i]);
        }
    }
}

/**
 * Generic filter function
 *
 * Given an array and predicate, returns new array,
 * that contains only these elements for which the
 * predicate was true.
 */
function filter(arr, p) {
    if (typeof p != "function") {
        throw new TypeError();
    }

    var len = arr.length;
    var res = new Array();
    for (var i = 0; i < len; i++) {
        if (i in arr) {
            var val = arr[i];
            if ( p(val) ) {
                res.push(val);
            }
        }
    }

    return res;
}

/**
 * Measures how long function fun executes.
 * Returns the time in milliseconds.
 */
function benchmark(fun) {
    var startTime = new Date().getTime();
    fun();
    var endTime = new Date().getTime();
    return endTime - startTime;
}