var plusImg = new Image();
plusImg.src = "images/plus.gif";
var minusImg = new Image();
minusImg.src = "images/minus.gif";
var debug_disabled = 0;
var debugWindow = null; // global variable
var debugWindowIndex = 0;
var myDebugWindows = [];

function _init() {
    createDebugWindow();
    debug('_init');
    hideAll();
}
function addAction(cmd) {
    var e = document.getElementById('action');
    if (!e)
        alert("can't find element: [action]");
    e.value = cmd;
    document.fMain.submit();
}
function addField(id) {
    var e = document.getElementById('fields');
    if (!e)
        alert("Can't find id: [fields]");
    if (!e.value) {
        e.value = id;
    } else {
        e.value += ',' + id;
    }
}
function addValue(id, val) {
    e = document.getElementById(id);
    if (!e)
        alert("Can't find id: [" + id + "]");
    if (!e.value) {
        e.value = val;
    } else {
        e.value += ',' + val;
    }
}
function checkEnter(e) { //e is event object passed from function invocation
    var characterCode; //literal character code will be stored in this variable

    if (e && e.which) { //if which property of event object is supported (NN4)
        e = e;
        characterCode = e.which; //character code is contained in NN4's which property
    } else {
        e = event;
        characterCode = e.keyCode; //character code is contained in IE's keyCode property
    }

    if (characterCode == 13) { //if generated character code is equal to ascii 13 (if enter key)
        document.fMain.action.submit(); //submit the form
        return false;
    } else {
        return true;
    }
}
function clearDebugWindow() {
    if (debug_disabled) {
        return;
    }
    if (window.top.debugWindow && !window.top.debugWindow.closed) {
        window.top.debugWindow.close();
        createDebugWindow();
    }
}
function closeDebugWindow() {
    if (debug_disabled) {
        return;
    }
    if (window.top.debugWindow && !window.top.debugWindow.closed) {
        window.top.debugWindow.close();
    }
}
function createDebugWindow(tag = 'xx') {
    if (debug_disabled) {
        return;
    }

    var windowName = '';
    if (tag == 'xx') {
        windowName = 'Debug Window';
    } else {
        windowName = 'Debug Window - ' + tag;
    }
    
    if( debugWindow == null || debugWindow.closed ) {
        debugWindow = window.open(
            "",
            "Debug",
            "left=0,top=0,width=900,height=700,scrollbars=yes,status=yes,resizable=yes"
            );
        debugWindowIndex++;
        myDebugWindows.push(debugWindow);
            
    } else {
        debugWindow.focus();
    }
    // open the document for writing
    debugWindow.document.open();
    debugWindow.document.write(
            '<HTML><HEAD><TITLE>' + windowName + '(' + debugWindowIndex + ')'
            + '</TITLE></HEAD><BODY><PRE>\n');
}
function debug(text) {
    var str;
    if (!debugWindow) {
        _init();
    }

    if (debugWindow && !debugWindow.closed) {
        ;
        str = '';
        for (var i = 0; i < arguments.length; i++)
        {
            str += arguments[i];
        }
        debugWindow.document.write(str + "\n");
    }
}
function doChallengeResponse() {
    var up = document.getElementById("userpass");
    if (up.value.length == 64) {
        document.getElementById("bypass").value = "1";
        document.fMain.response.value = up.value;
    } else
    {
        str = document.fMain.username.value.toLowerCase() + ":" +
                sha256_digest(up.value) + ":" + document.fMain.challenge.value;
        document.fMain.response.value = sha256_digest(str);
    }
    up.value = "";
    document.fMain.challenge.value = "";
    addAction('Login');
    return false;
}
function download_file(file) {
    var spl = file.split('\\');
    for (var i = 0; i < spl.length; i++) {
        debug('spl[' + i + ']=' + spl[i]);
    }
    debug('file:' + file);
    document.getElementById('file').value = file;
    document.getElementById('action').value = "View";
    document.getElementById('fMain').submit();
}
function DumpHtml() {
    var d = document;

    debug('------------------------------');
    debug('# forms: ' + d.forms.length);
    for (i = 0; i < d.forms.length; i++)
    {
        var f = d.forms[i];
        debug('form[' + i + '].name=' + f.name);
        debug('form[' + i + '].length = ' + f.length);
        for (j = 0; j < f.length; j++)
        {
            var e = f.elements[j];
            debug('element[' + j + '].name=' + e.name + ' (' + e.value + ')');
        }
    }
}
function getPassword(e) {
    var keynum, keychar, numcheck;
    if (window.event) // IE
    {
        keynum = e.keyCode;
    } else if (e.which) // Netscape/Firefox/Opera
    {
        keynum = e.which;
    }
    if (keynum == 13)
    {
        var f = document.getElementById('userpass');
        f.focus();
    }
}
function hideAll() {
    var divs = document.getElementsByTagName("div");
    for (var i = 0; i < divs.length; i++) {
        divs[i].style.display = 'none';
    }
}
function hideDebug() {
    if (window.top.debugWindow && !window.top.debugWindow.closed) {
        window.top.debugWindow.close();
        window.top.debugWindow = null;
    }
}
function hideId(id) {
    var id = document.getElementById(id);
    if(id) {
        id.style.display = 'none';
    }
}
function keyDown(e) {
    var keynum, keychar, numcheck;
    if (window.event) // IE
    {
        keynum = e.keyCode;
    } else if (e.which) // Netscape/Firefox/Opera
    {
        keynum = e.which;
    }
    if (keynum == 13)
    {
        doChallengeResponse();
    }
}
function mungepwd() {
    var p1 = document.getElementById("newpassword1");
    var p2 = document.getElementById("newpassword2");
    p1.value = sha256_digest(p1.value);
    p2.value = "";
}
function myConfirm(prompt) {
    var response = confirm(prompt);
    if (response) {
        addAction('update');
    } else {
        var id = this.event.currentTarget.id;
        var d = document.getElementById(id);
        if( d && d.type == 'checkbox') {
            d.checked = ! d.checked;
        }
    }
}
function pwdGen() {
    var x = new Number;
    var p1 = document.getElementById("newpwd");
    var p2 = document.getElementById("newpwdh");
    x = p1.value;
    if (x.valueOf() == '') {
        x = Math.random();
    }
    p2.value = sha256_digest(x.toString());
}
function scrollableTable() {
    var done = 0;
    var done_table = 0;
    var col_idx = 1;
//    var table_width = 0;
    var v, t, max_width, skip;
    var tables = document.getElementsByTagName('table');
    for( var tx = 0; tx < tables.length; tx++ ) {
        t = tables[tx];
        while (!done) {
            v = t.getElementsByClassName('col' + col_idx);
            if (v.length == 0) {
                done = 1;
                continue;
            }
            // Find the maximum width in Column [col_idx]
            max_width = 0;
            for (i = 0; i < v.length; i++) {
                if (v[i].clientWidth > max_width) {
                    if( col_idx == -11 ) {
                        alert( 'new Max Width for Column #' + col_idx + ' is ' + v[i].clientWidth + ' px' +
                                ', name: [' + v[i].outerText + ']');
                    }
                    max_width = v[i].clientWidth;
                }
            }
            // Set all in Column[col_idx] to max_width
            skip = 0;
            for (i = 0; i < v.length; i++) {
                v[i].style.width = max_width + 'px';
            }
            col_idx++;
        }
    }
}
function setFocus(id) {
    var e = document.getElementById(id);
    if (!e)
        alert("Can't find id " + id);
    e.focus();
}
function setHtml(id, val) {
    e = document.getElementById(id);
    if (!e)
        alert("Can't find id " + id);
    e.innerHTML = val;
}
function setValue(id, val) {
    e = document.getElementById(id);
    if(e) {
        e.value = val;
    } else {
        alert("Can't find id: [" + id + "]");
    }
}
function showHideDiv(e, divx) {
    var d = document.getElementById('hidden' + divx);
    var left = (e.clientX - window.innerWidth/8 ) + 'px';
    var top = (e.clientY + 20) + 'px';
    var y = e.clientY;
    if (!d.style.display || d.style.display === 'none') {
        var e = document.getElementsByClassName('hidden');
        for (i = 0; i < e.length; i++) {
            e[i].style.display = "none";
        }
        d.style.position = "absolute";
        d.style.left = left;
        d.style.top = top;
        d.style.display = "block";

    } else {
        d.style.display = "none";
    }
}
function sleep(milliseconds) {
    const date = Date.now();
    let currentDate = null;
    do {
        currentDate = Date.now();
    } while (currentDate - date < milliseconds);
}
function toggleBgRed(id) {
    var e = document.getElementById(id);
    if (!e)
        alert("Can't find id: " + id);
    if (e.style.backgroundColor == '#ff0000') {
        e.style.backgroundColor = '#ffffff';
    } else {
        e.style.backgroundColor = '#ff0000';
    }
}
function toggleDebug() {
    if (document.getElementById("debugOn").checked) {
        showDebug();
        debug("Check box checked, switched on debug");
        document.getElementById("checkboxLabel").innerHTML = "The debug window is <b>on</b>";
    } else {
        debug("Check box unchecked, switching off debug");
        hideDebug();
        document.getElementById("checkboxLabel").innerHTML = "The debug window is <b>off</b>";
    }
}
function toggleDetail(_tag) {
    var e, i;
    debug('toggleDetail(', _tag, ')');
    e = document.getElementsByTagName('div');
    for (i = 0; i < e.length; i++)
    {
        if (e[i].id.charAt(0) == 'd')
        {
            var s1 = e[i].style.display;
            if (e[i].id == _tag) {
                e[i].style.display = "block";
            } else {
                e[i].style.display = "none";
            }
            var s2 = e[i].style.display;
            if (s1 != s2) {
                debug(e[i].id, ' change from ', s1, ' to ', s2);
            }
        }
    }
}
function toggleEmail() {
    var e, f, i, new_state, str, id;
    if (arguments.length > 0)
    {
        e = document.getElementById('btn_all_email');
        if (e.value == 'All') {
            new_state = true;
            e.value = 'None';
        } else {
            new_state = false;
            e.value = 'All';
        }
        e = document.getElementsByTagName('input');
        for (i = 0; i < e.length; i++)
        {
            if (e[i].id.substr(0, 10) == 'btn_email_')
            {
                e[i].checked = new_state;
            }
        }
    } else
    {
        this.checked = !this.checked;
    }

    var addrs = new Array();
    e = document.getElementsByTagName('input');
    for (i = 0; i < e.length; i++)
    {
        if (e[i].id.substr(0, 10) == 'btn_email_' && e[i].checked)
        {
            id = e[i].id.substr(4);
            f = document.getElementById(id);
            addrs.push(f.firstChild.nodeValue);
        }
    }
    var e = document.getElementById('addr_list');
    e.value = addrs.join();
}
function toggleLevel(_levelId, _imgId) {
    var thisLevel = document.getElementById(_levelId);
    var thisImg = document.getElementById(_imgId);
    if (thisLevel.style.display == "none") {
        thisLevel.style.display = "block";
        if (thisImg)
            thisImg.src = minusImg.src;
//		debug( 'toggleLevel(' + _levelId + '):  display: none -> block' );
    } else
    {
        thisLevel.style.display = "none";
        if (thisImg)
            thisImg.src = plusImg.src;
//		debug( 'toggleLevel(' + _levelId + '):  display: block -> none' );
    }
}
function toggleVisOffOn(id1, id2) {
    var e = document.getElementById(id1);
    if (!e)
        alert("can't find id: " + id1);
    e.style.display = 'none';

    e = document.getElementById(id2);
    if (!e)
        alert("can't find id: " + id2);
    e.style.display = 'block';
}
function updateFormField(id, val) {
    var e = document.getElementById(id);
    if (!e)
        alert("Can't find FormField: [" + id + "]");
    if (!e.value) {
        e.value = val;
    } else {
        e.value += ',' + val;
    }
}
function verifypwd() {
    var p1 = document.getElementById("newpassword1");
    var p2 = document.getElementById("newpassword2");
    var btn = document.getElementById("userSettingsUpdate");
    var txt = document.getElementById("pwdval");
    if (p1.value != p2.value) {
        btn.disabled = true;
        btn.style.backgroundColor = '#ff0000';
        if (txt)
            txt.innerHTML = "** Passwords Don't Match **";
    } else {
        btn.disabled = false;
        btn.style.backgroundColor = '#90EE90';
        if (txt)
            txt.innerHTML = "";
    }
}
