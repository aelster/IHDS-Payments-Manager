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