var format = "Json";
var cellId;
$(document).ready(function() {
    $(".ajax").on("change",function() {
        if( typeof reTabulate === "function" ) {
            reTabulate();
        };
        var target = $(this).attr('id'); // Id of cell that changed
        const words = target.split('__');
        var user_id = $("#user_id").val(); // User ID making the change
        var x1 = $(this).attr('type');
        var x3 = $(this).prop('checked');
        if( words[0] == 'donations' ) {
            if( x3 ) {
                del_text_clear(words[2]);
            } else {
                del_text_load(words[2]);
            }
        }
        var is_debug = target.indexOf('__debug__');
        if( x1 === "checkbox" && ( is_debug < 0 ) ) {  // The __debug__ value is a bit mask
            if( x3 ) {
                var new_value = 1;
            } else {
                var new_value = 0;
            }
        } else {
            var new_value = $(this).val();
        }
        $.ajax({
            type: "POST",
            url: "ajax-update.php",
            dataType: format,
            data: {type:format, user_id:user_id, target:target, val:new_value, section:section}
        }).done(function(req,status,err) {
            if( req.refresh ) {
                if( section == 'kravmaga' ) {
                    setValue('area', section);
                    setValue('func','show');
                    addAction('display');
                } else {
                    setValue('func','users');
                    addAction('display');
                }
            }
            ;
        }).fail(function(req,status,err) {
            $(cellId)
                    .css("background-color", "red")
            ;
            $("#statusBox")
                    .css("display", "block")
                    .html("Please fix the highlighted entry")
        })
    })
})