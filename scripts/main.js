/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var BulkOkAll = 0;
var IDLE_MAX_SECONDS = 20 * 60;
var IDLE_ALERT_THRESHOLD = 60;
var IDLE_TIME = 0;
var IDLE_ALERT_RESET = 0;
var idleTimer;

function clearId(id) {
    document.getElementById(id).innerHTML = "";
}

function copyEmailParts() {
    var v1, v2;
// Copy the Subject:
    v1 = document.getElementById('subject');
    v2 = document.getElementById('subject-div');
    v1.value = v2.innerHTML;

// Copy the Body:
    v1 = document.getElementById('body');
    v2 = document.getElementById('body-div');
    v1.value = v2.innerHTML;
}

function setDebug(val) {
    var e = document.getElementById('debug');
    if( val > 0 ) {
        e.value += ' (' + val + ')';
    }
}

function sidebarColor(mode = "office") {
    var e = document.getElementById("sidebar");
    e.className = mode;
}

function updateXX() {
    const regex = /xx/;
    debugger;
    var x = document.getElementById('col3');
    x.innerHTML.replace(regex, '23');
}

function resetIdleTimer() {
    IDLE_TIME = 0;
    if (IDLE_ALERT_RESET > 0) {
        var e = document.getElementById('IdleTime');
        e.innerHTML = '';
        e.style.backgroundColor = "white";
        e.style.color = "black";
        IDLE_ALERT_RESET = 0;
    }
}

function createIdleTimer() {
    var e = document.getElementsByTagName('body');
    e[0].addEventListener("mousemove", resetIdleTimer);
    e[0].addEventListener("click", resetIdleTimer);
    e[0].addEventListener("keypress", resetIdleTimer);

    idleTimer = setInterval('getIdleTime()', 1000);
}

function cancelIdleTimer() {
    clearInterval(idleTimer);
    var e = document.getElementsByTagName('body');
    e[0].removeEventListener("mousemove", resetIdleTimer);
    e[0].removeEventListener("click", resetIdleTimer);
    e[0].removeEventListener("keypress", resetIdleTimer);
}

function getIdleTime() {
    var _min, _sec, t, _nmin, _nsec;

    if (IDLE_TIME >= IDLE_MAX_SECONDS) {
        cancelIdleTimer();
        setValue('area', 'logout');
        addAction('update');

    } else {
        t = IDLE_MAX_SECONDS - IDLE_TIME;
        if (t <= IDLE_ALERT_THRESHOLD) {
            IDLE_ALERT_RESET = 1;
            _min = Math.floor(t / 60);
            _sec = t % 60;

            _nmin = (_min.toString().length == 1) ? '0' + _min : _min;
            _nsec = (_sec.toString().length == 1) ? '0' + _sec : _sec;

            var e = document.getElementById('IdleTime');
            e.innerHTML = '&nbsp;Timeout in: ' + _nmin + ':' + _nsec;
            var j = _sec % 2;
            //debugger;
            if (j == 0) {
                e.style.backgroundColor = "red";
                e.style.color = "white";
            } else {
                e.style.backgroundColor = "white";
                e.style.color = "black";
            }
        }
    }
    IDLE_TIME++;
}

var fileobj;
function upload_file(e,fid) {
    e.preventDefault();
    fileobj = e.dataTransfer.files[0];
    ajax_file_upload(fileobj,fid);
}
 
function file_explorer(fid) {
    document.getElementById('selectfile').click();
    document.getElementById('selectfile').onchange = function() {
        fileobj = document.getElementById('selectfile').files[0];
        ajax_file_upload(fileobj,fid);
    };
}
 
function ajax_file_upload(file_obj,fid) {
    if(file_obj != undefined) {
        var form_data = new FormData();                  
        form_data.append('FamilyId', fid);
        form_data.append('file', file_obj);
        $.ajax({
            type: 'POST',
            url: 'ajax.php',
            contentType: false,
            processData: false,
            data: form_data,
            success:function(response) {
                alert(response);
                $('#selectfile').val('');
            }
        });
    }
}

function paletteFontPlus() {
    var obj = document.getElementById('palette');
    var e = obj.getElementsByTagName('td');
    for( var i=0; i<e.length; i++ ) {
        if( i == 0 ) {
            var j = window.getComputedStyle(e[i]).fontSize.slice(0,-2);
            j++;
        }
        e[i].style.fontSize = j + 'px';
    }
    var e = obj.getElementsByTagName('p');
    for( var i=0; i<e.length; i++ ) {
        if( i == 0 ) {
            var j = window.getComputedStyle(e[i]).fontSize.slice(0,-2);
            j++;
        }
        e[i].style.fontSize = j + 'px';
    }
}

function paletteFontMinus() {
    var obj = document.getElementById('palette');
    var e = obj.getElementsByTagName('td');
    for( var i=0; i<e.length; i++ ) {
        if( i == 0 ) {
            var j = window.getComputedStyle(e[i]).fontSize.slice(0,-2);
            j = Math.max(j-1,5);
        }
        e[i].style.fontSize = j + 'px';
    }
    var e = obj.getElementsByTagName('p');
    for( var i=0; i<e.length; i++ ) {
        if( i == 0 ) {
            var j = window.getComputedStyle(e[i]).fontSize.slice(0,-2);
            j = Math.max(j-1,5);
        }
        e[i].style.fontSize = j + 'px';
    }
}