<?php
session_start();

require_once( 'includes/config.php' );
include 'includes/globals.php';

$trace = 0;
$saveDebug = $gDebug;
$gDebug = $gDebugErrorLog;

if ($trace) {
    error_log("");
    error_log( "ajax-update: " . "-------------");
    foreach ($_POST as $key => $val) {
        error_log("ajax-update: ". "_POST[$key] = [$val]");
    }
}

$dataType = $_POST['type'];
list( $table, $field, $id ) = explode("__", $_POST['target']);
$val = trim(str_replace('$', '', $_POST['val']));
if ($field == "debug") {
    $query = "update $table set `$field` = `$field` ^ $val where id = $id";
} else if ($table == "sections") {
    if ($field == 'discounts') {
        list($sectionId, $codeId) = explode("_", $id);
        $stmt = DoQuery("select discounts from sections where id = $sectionId");
        list($discounts) = $stmt->fetch(PDO::FETCH_NUM);
        $tmp = array_filter(explode(',', $discounts));
        if ($val == 1) {
            $tmp[] = $codeId;
        } else {
            if (($index = array_search($codeId, $tmp) ) !== false) {
                unset($tmp[$index]);
            }
        }
        $str = implode(',', $tmp);
        $query = "update $table set `discounts` = '$str' where id = $sectionId";
    } else {
        $query = "update $table set `$field` = '$val' where id = $id";
    }
} else if( $table == 'donations' )  {
    $query = "update $table set `$field` = '$val' where id = $id";
    if( $field == "visible" ) {
        if( $_POST['section'] == 'kravmaga' ) {
            $query2 = "update kravmaga set visible = '$val' where donationId = $id";
            if( $trace)
                error_log($query2);
            DoQuery($query2);
        }
    }
} else {
    $query = "update $table set `$field` = '$val' where id = $id";
}
if ($trace)
    error_log($query);

DoQuery($query);

if ($trace)
    error_log("# rows updated: " . $gPDO_num_rows);

EventLogRecord([
    'type' => 'update',
    'user_id' => $_POST['user_id'],
    'item' => $query]);

$refresh = false;
if ($table == "access") {
    if ($field == "priv_id") {
        $refresh = true;
    }
} else if( $table == "donations" && $field == "visible" ) {
    $refresh = true;
}
$response_array = array(
    "status" => "success",
    "val" => "$val",
    "refresh" => "$refresh"
);

echo json_encode($response_array);

$gDebug = $saveDebug;
//if( $field == "amount" ) {
//    if( $dataType == "json" ) {
//        $answer = sprintf( "{ \"val\": \"\$ %s\" }", number_format($val,2));
//    } elseif( $dataType == "text" ) {
//        $answer = sprintf( "\$ %s",number_format($val,2));
//    }
//} elseif( $field == "frequency" ) {
//    $opts = [];
//    foreach( ['onetimetab','monthlytab'] as $opt ) {
//        $selected = ( $opt == $val ) ? "selected" : "";
//        $opts[] = "<option value='$opt' $selected>$opt</option>";
//    }
//    $answer = sprintf( "{\"val\": \"%s\" }", implode("",$opts));
//} else {
//    $answer = sprintf( "{\"val\": \"%s\" }", $val);
//}
//
//echo $answer;
