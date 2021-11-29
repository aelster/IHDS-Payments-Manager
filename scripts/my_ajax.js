var format = "Json";
var cellId;
$(document).ready(function() {
    $(".ajax").on("change",function() {
        if( typeof reTabulate === "function" ) {
            reTabulate();
        };
        var target = $(this).attr('id'); // Id of cell that changed
        var user_id = $("#user_id").val(); // User ID making the change
        if( $(this).type === "boolean" ) {
            var flag = $(this).val();
            var new_value = ! flag;
        } else {
            var new_value = $(this).val();
        }
        $.ajax({
            type: "POST",
            url: "ajax-update.php",
            dataType: format,
            data: {type:format, user_id:user_id, target:target, val:new_value}
        }).done(function(req,status,err) {
            if( req.refresh ) {
                setValue('func','users');
                addAction('Main');
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