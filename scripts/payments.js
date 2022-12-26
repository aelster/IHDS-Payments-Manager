var section;

function del_text_load(id) {
    var e1 = document.getElementById('del_text_' + id );
    var e2 = document.getElementById('del_' + id );
    e2.innerHTML = e1.innerHTML;
}

function del_text_clear(id) {
    var e1 = document.getElementById('del_text_' + id );
    var e2 = document.getElementById('del_' + id );
    e2.innerHTML = "";
}

function deleteBoxState(event) {
    var tgt = event.currentTarget;
    var tmp = tgt.id.split('__');
    if( tgt.checked ) {
        document.getElementById('del_box_' + tmp[2]).classList.add("hidden");
    } else {
        document.getElementById('del_box_' + tmp[2]).classList.remove("hidden");        
    }
}