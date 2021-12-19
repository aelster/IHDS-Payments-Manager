<?php

function addDonor() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    
    $id = $_POST['id'];

    $query = sprintf("select column_name,column_type from information_schema.columns
        where table_schema = '%s' and table_name = 'donations'", $gDbNames[$gDbControlId]);

    $stmt = DoQuery($query);
    $args = $vals = [];
    $i = 0;
    while (list( $fld, $xtype) = $stmt->fetch(PDO::FETCH_NUM)) {
        if ($fld == "id")
            continue;
        if ($fld == "society") {
            $i++;
            $args[$i] = "`$fld` = :v$i";
            $vals[":v$i"] = $_POST[$fld . "_$id"];
            continue;
        }
        if ($fld == "success") {
            $i++;
            $args[$i] = "`$fld` = :v$i";
            $vals[":v$i"] = 1;
            continue;
        }

        $j = strpos($xtype, '(');
        if ($j == 0) {
            $type = $xtype;
        } else {
            $type = substr($xtype, 0, $j);
        }

        switch ($type) {
            case "bigint":
            case "float":
            case "int":
            case "tinyint":
                $i++;
                $args[$i] = "`$fld` = :v$i";
                $vals[":v$i"] = empty($_POST[$fld . "_$id"]) ? 0 : $_POST[$fld . "_$id"];
                break;

            case "varchar":
                $i++;
                $args[$i] = "`$fld` = :v$i";
                $vals[":v$i"] = $_POST[$fld . "_$id"];
                break;
        }
    }
    $query = "insert into donations set " . implode(',', $args);
    DoQuery($query, $vals);

    $obj = [];
    $obj['type'] = 'create';
    $obj['user_id'] = $gUserId;
    $obj['item'] = "create new $gArea user record, id = " . $gPDO_lastInsertID;
    EventLogRecord($obj);

    $gAction = "display";
}

function addForm() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }

    echo "<form name=fMain id=fMain method=post action=\"$gSourceCode\">$gLF";

    $hidden = array();
    $hidden[] = 'action';   # what needs to be done or what was pressed
    $hidden[] = 'area';
    $hidden[] = 'fields';
    $hidden[] = 'from';
    $hidden[] = 'func';
    $hidden[] = 'id';
    $hidden[] = 'key';
    $hidden[] = 'mode';
    $hidden[] = 'user_id';
    $hidden[] = 'where';

    foreach ($hidden as $var) {
        $tag = MakeTag($var);
        echo "<input type=hidden $tag>$gLF";
    }
    define('FORM_OPEN', 1);
    if ($gTrace) {
        array_pop($gFunction);
    }
}

function addHtmlHeader() {
    include 'includes/globals.php';
    echo <<<EOT
<!DOCTYPE HTML>
<html>
        <head>
            <script type="text/javascript">var debug_disabled = 0;</script>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title>$gSiteName</title>
EOT;
    $styles = array();
    $styles[] = "css/Common.css";
    $styles[] = "css/main.css";

    $scripts = array();
    $scripts[] = "https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js";    
    $scripts[] = "scripts/Common.js";
    $scripts[] = "scripts/sorttable.js";
    $scripts[] = "scripts/my_ajax.js";

    echo <<<EOT
    <meta charset='utf-8'>
    <meta http-equiv='Cache-control' content='no-cache'>
    <link rel='shortcut icon' type='image/x-icon' href='assets/favicon.ico' />
EOT;

    $force = 1;

    if ($force) {
        $tag = rand(0, 1000);
        $str = "?dev=$tag";
    } else {
        $str = "";
    }
    foreach ($styles as $style) {
        printf("<link href=\"%s$str\" rel=\"stylesheet\" type=\"text/css\" />\n", $style);
    }

    foreach ($scripts as $script) {
        printf("<script type=\"text/javascript\" src=\"%s$str\"></script>\n", $script);
    }
    echo "</head>";
}

function addToSidebar($buttons) {
    include 'includes/globals.php';

    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    echo <<<EOT
    <script type="text/javascript">
        var btn;
        var sidebar = document.getElementById("sidebar");
        sidebar.innerHTML += '<hr>';
EOT;
    $i = 0;

    foreach ($buttons as $obj) {
        $i++;
        $class = "sidebar-btn";
        if (is_string($obj)) {
            continue;
        }
        $label = array_key_exists('label', $obj) ? $obj['label'] : ucfirst($obj['area']);

        $jsx = array();
        $jsx[] = "setValue('mode','$gMode')";
        $jsx[] = "setValue('area','" . $obj['area'] . "')";
        if (!empty($obj["js"])) {
            $jsx[] = $obj["js"];
        }
        $action = array_key_exists('action', $obj) ? $obj['action'] : 'display';
        if ($action == 'init') {
            $jsx[] = "myConfirm('$label')";
            $label = "Init";
        } else {
            $jsx[] = "addAction('$action')";
        }
        $js = join(';', $jsx);

        $bid = ($action == 'update') ? 'update' : "bid$i";

        echo <<<EOT
            btn = document.createElement("input");
            btn.setAttribute("type", "button");
            btn.id = "$bid";
            btn.setAttribute("class", "$class");
            btn.setAttribute("value", "$label");
EOT;
        if (array_key_exists('disabled', $obj)) {
            echo 'btn.setAttribute("disabled", "true");';
        }
        echo <<<EOT
            btn.setAttribute("onclick", "$js");
            sidebar.appendChild(btn);
EOT;
    }
    echo <<<EOT
        btn = document.createElement("input");
        btn.setAttribute("type", "button");
        btn.setAttribute("class", "sidebar-btn");
        btn.setAttribute("value", "Font +");
        btn.setAttribute("onclick", "paletteFontPlus()");
        sidebar.appendChild(btn);
        btn = document.createElement("input");
        btn.setAttribute("type", "button");
        btn.setAttribute("class", "sidebar-btn");
        btn.setAttribute("value", "Font -");
        btn.setAttribute("onclick", "paletteFontMinus()");
        sidebar.appendChild(btn);
    </script>
EOT;
    if ($gTrace) {
        array_pop($gFunction);
    }
}

function deleteDonor() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    $obj = [];
    $obj['type'] = 'update';
    $obj['user_id'] = $_POST['user_id'];

    $id = $_POST['id'];
    $stmt = DoQuery("select success from donations where id = $id");
    list( $val ) = $stmt->fetch(PDO::FETCH_NUM);
    if ($val == 1) { // This is a normal entry
        $query = "update donations set success = 2 where id = $id";
        DoQuery($query);
        $obj['item'] = $query;
        EventLogRecord($obj);
    } else { // This should only be visible from control and already deleted
        $query = "delete from donations where id = $id";
        DoQuery($query);
        $obj['item'] = $query;
        EventLogRecord($obj);
    }
    $gAction = "display";
}

function deleteMail() {
    include 'includes/globals.php';
    $gFunction[] = __FUNCTION__;
        $id = $gId;
        DoQuery("delete from mail where id = :id", [':id' => $id]);
    array_pop($gFunction);
}

function displayBanner() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }

    if ($gAction == 'logout')
        return;

    if ($user->is_logged_in()) {
        $jsx = [];
        $jsx[] = "addAction('logout')";
        $js = implode(';', $jsx);
        echo "<input type=button"
        . " id=button-logout"
        . " onclick=\"$js\""
        . " value=Logout>";

        if ($_SESSION['level'] >= $gAccessNameToLevel['control']) {
            $jsx = [];
            $jsx[] = "setValue('mode','control')";
            $jsx[] = "sidebarColor('control')";
            $jsx[] = "addAction('display')";
            $js = implode(';', $jsx);
            echo "<input type=button"
            . " id=button-control"
            . " class=control"
            . " onclick=\"$js\""
            . " value=Control>";

            if (empty($gBannerMode))
                $gBannerMode = "control";
        }

        if ($_SESSION['level'] >= $gAccessNameToLevel['admin']) {
            $jsx = [];
            $jsx[] = "setValue('mode','admin')";
            $jsx[] = "sidebarColor('admin')";
            $jsx[] = "addAction('display')";
            $js = implode(';', $jsx);
            echo "<input type=button"
            . " id=button-admin"
            . " class=admin"
            . " onclick=\"$js\""
            . " value=Admin>";
            if (empty($gBannerMode))
                $gBannerMode = "admin";
        }

        if ($_SESSION['level'] >= $gAccessNameToLevel['office']) {
            $jsx = [];
            $jsx[] = "setValue('mode','office')";
            $jsx[] = "sidebarColor('office')";
            $jsx[] = "addAction('display')";
            $js = implode(';', $jsx);
            echo "<input type=button"
            . " id=button-office"
            . " class=office"
            . " onclick=\"$js\""
            . " value=Office>";
            if (empty($gBannerMode))
                $gBannerMode = "office";
        }
    }
    echo "<div id=\"IdleTime\"></div>";

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function displayHome() {
    include 'includes/globals.php';

    echo "<input type=button onclick=\"addAction('rimon');\" value=\"Rimon Society\">";
    echo "&nbsp;";
    echo "<input type=button onclick=\"addAction('nachas');\" value=\"Nachas Society\">";
    echo "&nbsp;";
    echo "<input type=button onclick=\"addAction('all');\" value=\"All Society Donors\">";
}

function displayMail() {
    include 'includes/globals.php';
    
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }

    echo "<div class=center>";
    echo "<h2>Mail Controls</h2>";
//    echo "<span style='background-color: yellow; width: 200px; text-align: left; display: inline-block; font-size: 12pt;'>";
    echo "</span>";
    echo "</div>";
    echo "<input type=button value=Back onclick=\"addAction('main');\">";
        $jsx = [];
    $jsx[] = "setValue('from','" .  __FUNCTION__ . "')";
    $jsx[] = "setValue('func','new')";
    $jsx[] = "addAction('mail')";
    $js = implode(';', $jsx); 
    echo "&nbsp;";
    echo "<td class=c><input type=submit onclick=\"$js\" value=New></td>";

    echo "<br><br>";
    echo "<table class=usermanager>";

    echo "<thead>";
    echo "<tr>";
    echo "<th class=col1>Label</th>";
    echo "<th class=col2>Value</th>";
    echo "<th class=col3>Enabled</th>";
    echo "<th class=col5>Action</th>";
    echo "</tr>";
    echo "</thead>";

    echo "<tbody>";
    $stmt = DoQuery("select * from mail where lower(label) like '%email:%' order by label asc");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];
        $label = $row['label'];
        $value = $row['value'];

        if ($label == 'Email: Server') {
            echo "<tr>";
            echo "<td class=col1>$label</td>";
            echo "<td class=col2>";
            $ajax_id = "id=\"mail__value__{$id}\"";
            echo "<select class=\"'col2' ajax\" $ajax_id>";
            for( $mid = 0; $mid < count($gMailDB); $mid++  )  {
                $selected = ( $mid == $value ) ? "selected" : "";
                echo "<option value=$mid $selected>{$gMailDB[$mid]['Label']}</option>";
            }
            echo "</select>";
            echo "</td>";
            echo "<td>&nbsp;</td>";
            echo "<td>&nbsp;</td>";
            echo "</tr>";
        } else {
            echo "<tr>";
            echo "<td class=col1>$label</td>";
            $ajax_id = "id=\"mail__value__{$id}\"";
            echo "<td class=col2><input class=\"'col2' ajax\" size=60 $ajax_id value='" . $row['value'] . "'></td>";

            $tag = MakeTag("enabled_$id");
            $acts = array();
            $acts[] = "addField('$label|enabled|$id')";
            $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
            $acts[] = "setValue('mode','control')";
            $acts[] = "setValue('area','mail')";
            $acts[] = "setValue('func','update')";
            $acts[] = "setValue('id', '$id')";
            $acts[] = "setValue('key', '$label')";
            $acts[] = "addAction('update')";
            if( empty($row['enabled']) )  {
                $checked = "";
                $val = 1;
            } else {
                $checked = "checked";
                $val = 0;
            }
            $ajax_id = "id=\"mail__enabled__{$id}\"";
            $js = "";
            echo "<td class=box><input class=ajax type=\"checkbox\" $ajax_id $checked $js value=\"$val\"></td>\n";

            $acts = array();
            $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
            $acts[] = "setValue('area','mail')";
            $acts[] = "setValue('func','del')";
            $acts[] = "setValue('id', '$id')";
            $acts[] = "addAction('update')";
            printf("<td class='col5 c'><input type=button onClick=\"%s\" value='Del'></td>", join(';', $acts));

            echo "</tr>";
        }
    }

    $id = 0;

    echo "<tr>";

    $tag = MakeTag('label_' . $id);
    $js = "onChange=\"toggleBgRed('add');\" onClick=\"this.select();\"";
    echo "<td class=col1><input $tag type='text' size=15 $js value='-- enter label --'></td>";

    $tag = MakeTag('value_' . $id);
    $js = "onChange=\"addField('new|value|$id');toggleBgRed('add');\"";
    echo "<td class=col2><input $tag type='text' size=60 $js></td>";

    $tag = MakeTag('enabled_' . $id);
    $js = "onChange=\"addField('new|enabled|$id');toggleBgRed('add');\"";
    echo "<td class='col3 c'><input $tag type='checkbox' value=1 $js></td>";

    $tag = MakeTag('add');
    $acts = array();
    $acts[] = "addField('new|label|$id')";
    $acts[] = "addField('new|value|$id')";
    $acts[] = "addField('new|enabled|$id')";
    $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
    $acts[] = "setValue('mode','control')";
    $acts[] = "setValue('area','mail')";
    $acts[] = "setValue('func','add')";
    $acts[] = "addAction('update')";
    printf("<td class='col5 c'><input $tag type=button onClick=\"%s\" value=Add></td>", join(';', $acts));

    echo "</tr>";
    echo "</tbody>";
    echo "</table>";
    
    echo "<br><br>";

    echo "<h1>Email: Admin</h1>";
    echo "<ul class=mail-desc>";
    echo "<li>All emails are sent from this account</li>";
    echo "<li class=warn>If enabled, emails are sent to members</b></li>";
    echo "<li>If not enabled, emails are sent to Testing accounts</li>";
    echo "</ul>";

    echo "<br><br>";

    echo "<h1>Email: Default</h1>";
    echo  "<ul class=mail-desc>";
    echo "<li>This is the default mail account if nothing else is set up</li>";
    echo "</ul>";
    
    echo "<br><br>";

    echo "<h1>Email: Testing</h1>";
    echo  "<ul class=mail-desc>";
    echo "<li>If Admin is disabled, mail is sent to these accounts</li>";
    echo "<li>Multiple accounts can be created</li>";
    echo "</ul>";
    
    if ($gTrace) {
        array_pop($gFunction
        );
    }
}

function displayMisc() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    echo "<div class=center>";
    echo "<h2>Miscellaneous Controls</h2>";
    echo "</div>";


    echo "<br>";
//    echo "<table class=scrollable>";
    echo "<table>";

    echo "<thead>";
    echo "<tr>";
    echo "<th class=col1>Label</th>";
    echo "<th class=col2>Value</th>";
    echo "<th class=col3>Enabled</th>";
    echo "<th class=col4>Due By</th>";
    echo "<th class=col5>Action</th>";
    echo "</tr>";
    echo "</thead>";

    echo "<tbody>";
    $stmt = DoQuery("select * from misc where lower(label) not like '%email%' order by label asc");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];
        $label = $row['label'];

        echo "<tr>";
        echo "<td class=col1>$label</td>";

        $tag = MakeTag("value_$id");
        $js = "onChange=\"addField('$label|value|$id');toggleBgRed('update');\"";
        echo "<td class=col2><input class='col2' size=60 $tag $js value='" . $row['value'] . "'></td>";

        $tag = MakeTag("enabled_$id");
        $acts = array();
        $acts[] = "addField('$label|enabled|$id')";
        $acts[] = "setValue('from','" . __FUNCTION__ . "')";
        $acts[] = "setValue('area','misc')";
        $acts[] = "setValue('func','update')";
        $acts[] = "setValue('id', '$id')";
        $acts[] = "setValue('key', '$label')";
        $acts[] = "addAction('update')";
        $checked = empty($row['enabled']) ? "" : "checked";
        printf("<td class='col3 c'><input $tag type='checkbox' onchange=\"%s\" value=1 $js $checked></td>", implode(";", $acts));

        $tag = MakeTag("DueBy_$id");
        $js = "onChange=\"addField('$label|DueBy|$id');toggleBgRed('update');\"";
        $checked = empty($row['enabled']) ? "" : "checked";
        echo "<td class='col4 c'><input class='col4' size=3 $tag $js value='" . $row['DueBy'] . "'></td>";

        $acts = array();
        $acts[] = "setValue('from','MiscDisplay')";
        $acts[] = "setValue('area','misc')";
        $acts[] = "setValue('func','del')";
        $acts[] = "setValue('id', '$id')";
        $acts[] = "addAction('update')";
        printf("<td class='col5 c'><input type=button onClick=\"%s\" value='Del'></td>", join(';', $acts));

        echo "</tr>";
    }

    $id = 0;

    echo "<tr>";

    $tag = MakeTag('label_' . $id);
    $js = "onChange=\"toggleBgRed('add');\" onClick=\"this.select();\"";
    echo "<td class=col1><input $tag type='text' size=15 $js value='-- enter label --'></td>";

    $tag = MakeTag('value_' . $id);
    $js = "onChange=\"addField('new|value|$id');toggleBgRed('add');\"";
    echo "<td class=col2><input $tag type='text' size=60 $js></td>";

    $tag = MakeTag('enabled_' . $id);
    $js = "onChange=\"addField('new|enabled|$id');toggleBgRed('add');\"";
    echo "<td class='col3 c'><input $tag type='checkbox' value=1 $js></td>";

    $tag = MakeTag('DueBy_' . $id);
    $js = "onChange=\"addField('new|DueBy|$id');toggleBgRed('update');\"";
    echo "<td class='col4 c'><input class='col4' size=3 $tag $js value=0></td>";

    $tag = MakeTag('add');
    $acts = array();
    $acts[] = "addField('new|label|$id')";
    $acts[] = "addField('new|value|$id')";
    $acts[] = "addField('new|enabled|$id')";
    $acts[] = "addField('new|DueBy|$id')";
    $acts[] = "setValue('from','MiscDisplay')";
    $acts[] = "setValue('area','misc')";
    $acts[] = "setValue('func','add')";
    $acts[] = "addAction('update')";
    printf("<td class='col5 c'><input $tag type=button onClick=\"%s\" value=Add></td>", join(';', $acts));

    echo "</tr>";
    echo "</tbody>";
    echo "</table>";

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function displayPalette() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    $dpv_pre = "Begin";
    $dpv_phase = 4;
    $dpv_tag = "PaletteDisplay";
    if ($gDebug) {
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    switch ($gAction) {
        case "display":
            switch ($gArea) {
                case "all":
                    displayDonors($gArea);
                    break;

                case "carol":
                    displayDonors($gArea);
                    break;

                case "debug":
                    MyDebug("display");
                    break;

                case "eventLog":
                    EventLogDisplay();
                    break;

                case "mail":
                    UserMail('display');
                    break;

                case "misc":
                    displayMisc();
                    break;

                case "nachas":
                    displayDonors($gArea);
                    break;

                case "privileges":
                    UserManager("privileges");
                    break;

                case "rimon":
                    displayDonors($gArea);
                    break;

                case "society":
                    displayDonors();
                    break;

                case "source":
                    SourceDisplay();
                    break;

                case "users":
                    UserManager("control");
                    break;

                case "reports":
                    displayHome();
                    break;
            }
            break;

//        case "login":
//            $gFunc = "login";
//            UserManager("login");
//            break;

        case "logout":
            UserManager("logout");
            break;

        case "login":
            if ($gFunc == "getemail") {
                UserManager('forgot');
            } elseif ($gFunc == "welcome") {
                UserManager('welcome');
            } elseif ($gFunc == "reset") {
                UserManager('reset');
            } elseif ($gFunc == "change") {
                UserManager('welcome');
            } elseif( $gFunc == 'mail' ) {
                UserManager('mail');
            }
            break;

        case "welcome":
            UserManager("welcome");
            break;
    }

    if ($gDebug) {
        $dpv_pre = "End";
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }
    if ($gTrace) {
        array_pop($gFunction);
    }
}

function displaySidebar() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__ . " ($gBannerMode)";
        Logger();
    }
    if ($gAction == 'logout')
        return;

    if ($gDebug) {
        DumpPostVars(sprintf("displaySidebar: gAction: [%s], gMode: [%s], gArea: [%s],"
                        . " gFunc: [%s]", $gAction, $gMode, $gArea, $gFunc));
    }
    # bbl => button below the line
    #   label defaults to ucfirst(area)
    #   action defaults to sidebar

    if ($user->is_logged_in()) {
        $buttons = [];

        $skipActions = ['reset', 'start'];

        if (!array_key_exists($gAction, $skipActions)) {
            if ($gAction != "start") {
                if (!empty($gArea)) {
                    if (empty($gMode)) {
                        $gMode = array_key_exists($gArea, $gAreaToMode) ? $gAreaToMode[$gArea] : "";
                    }
                }
                if (empty($gMode))
                    $gMode = 'office';

                if (array_key_exists($gMode, $gModeToButtons)) {
                    $buttons = $gModeToButtons[$gMode];
                }
            }

            $bbl = array();

            if ($gAction != 'forgot' && $gAction != "edit") {
                if (!empty($buttons)) {
                    foreach ($buttons as $obj) {
                        $addBBL = 0;
                        if(! empty($gArea) && preg_match( "/$gArea/i", $obj['area'] ) ) {
                            $addBBL = 1;
                            $class = "sidebar-btn selected";
                        } else {
                            $class = "sidebar-btn";
                        }
                        if ($addBBL && array_key_exists('bbl', $obj)) {
                            $bbl = $obj['bbl'];
                        }

                        $label = array_key_exists('label', $obj) ? $obj['label'] : ucfirst($obj['area']);
                        $left = sprintf("<input type=button id=\"%s\" class=\"%s\" value=\"%s\"", $obj['area'], $class, $label);
                        $jsx = array();
                        $jsx[] = "setValue('mode','$gMode')";
                        $jsx[] = "setValue('area','" . $obj['area'] . "')";
                        $jsx[] = "setValue('func','show')";
                        $jsx[] = "setValue('where','sidebar')";

                        if (!empty($obj["js"])) {
                            $jsx[] = $obj["js"];
                        }
                        $action = array_key_exists('action', $obj) ? $obj['action'] : 'display';
                        $jsx[] = "addAction('$action')";
                        $js = join(';', $jsx);
                        echo $left . " onclick=\"$js\">";
                    }
                }
            }
            addToSidebar($bbl);
        }
    }
//    ob_flush();

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function displayDonors() {
    include 'includes/globals.php';

    $society = $_POST['area'];

    $control = ($gUserAccess == 'control');

    $quals = [];
    switch ($gArea) {
        case "carol":
        case "nachas":
        case "rimon":
            $quals[] = "society = \"$society\"";
            $quals[] = "success = 1";
            break;

        case "all":
            if (!$control) {
                $quals[] = "success = 1";
            }
            break;
    }
    
    $qual = empty($quals) ? "" : " where " . implode(" and ", $quals);
    
    if( $gArea == "all" ) {
        $stat = DoQuery( "select sum(amount) from donations where frequency = 'monthlytab'");
        list( $monthly ) = $stat->fetch(PDO::FETCH_NUM);
        $stat = DoQuery( "select sum(amount) from donations where frequency = 'onetimetab'");
        list( $oneTime ) = $stat->fetch(PDO::FETCH_NUM);
    
    } else {
        $stat = DoQuery( "select sum(amount) from donations" . $qual . " and frequency = 'monthlytab'");
        list( $monthly ) = $stat->fetch(PDO::FETCH_NUM);
        $stat = DoQuery( "select sum(amount) from donations" . $qual . " and frequency = 'onetimetab'");
        list( $oneTime ) = $stat->fetch(PDO::FETCH_NUM);
    }
    
    $query = "select * from donations" . $qual;
    $query .= " order by lastName asc";

    $fields = ["firstName", "lastName", "amount", "frequency", "address", "city", "state", "zip", "phone", "email"];
    if ($society == "all") {
        array_splice($fields, 0, 0, ["society", "success"]);
    }

    echo "<br>";
    echo "<div class=\"employees tight_table\">"
    . "<div class=\"status\" id=statusBox>-</div><br>"
    . "<input type=button onclick=\"addAction('$society');\" value=\"Download\">";
    
    echo "<br><br>";
    echo "One Time Total: \$" . number_format($oneTime,2);
    echo ",&nbsp;&nbsp;";
    echo "Monthly Totals: \$" . number_format($monthly, 2);
    echo "</div>";
    
    echo "<br>";
    echo "<br>";

    echo "<ul class=sort>";
    echo "<li>Click on a column header to sort, click again to reverse sort</li>";
    echo "<li>All fields can be edited</li>";
    echo "</ul>";

    echo "<br>";
    echo "<br>";

    echo "<table class=\"society sortable scrollable\">";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Act</th>";
    echo "<th>Date/Time</th>";
    foreach ($fields as $f) {
        printf("<th>%s</th>", ucfirst($f));
    }
    echo "</tr>\n";
    echo "</thead>";

    $sizes = [];
    $sizes['firstName'] = '20';
    $sizes['lastName'] = '20';
    $sizes['society'] = 6;
    $sizes['amount'] = '8';
    $sizes['address'] = '30';
    $sizes['city'] = '20';
    $sizes['state'] = '3';
    $sizes['phone'] = '10';
    $sizes['email'] = '30';

    echo "<tbody>";
    $stmt = DoQuery($query);
    $num = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $freq = ($row["frequency"] == "monthlytab") ? "monthly" : "onetime";
        echo "<tr class=\"$freq\">";
        $phpdate = strtotime($row['time']);
        $mysqldate = date('m/d/Y H:m', $phpdate);
        $id = $row['id'];

        $jsx = [];
        $jsx[] = "setValue('id',$id)";
        $jsx[] = "setValue('area','$society')";
        $jsx[] = "setValue('func','delete')";
        $str = sprintf( "Are you sure you want to delete the \$ %s donation from %s %s?",
                number_format($row['amount'],2), $row['firstName'], $row['lastName']);
        $jsx[] = "myConfirm('$str')";
        $js = sprintf("onclick=\"%s\"", join(';', $jsx));

        echo "<td><input type=button class=delete $js value=\"Del\"></td>";
        echo "<td>$mysqldate</td>";
        foreach ($fields as $f) {
            $ajax_id = "id=\"donations__{$f}__{$id}\"";
            $size = array_key_exists($f, $sizes) ? $sizes[$f] : 5;
            echo "<td class=\"sort\">";
            if ($f == "amount") {
                printf("<input type=text size=$size class=\"ajax\" $ajax_id value=\"\$ %s\" sorttable_customkey=\"%.2f\"></td>",
                        number_format($row[$f], 2), $row[$f]);
            } elseif ($f == "frequency") {
                echo "<select class=\"jq\" sorttable_customkey=\"{$row[$f]}\">";
                foreach (["onetimetab", "monthlytab"] as $opt) {
                    $selected = ( $row[$f] == $opt ) ? "selected" : "";
                    echo "<option value=\"$opt\" $selected>$opt</option>";
                }
            } elseif( $f == "success") {
                if( $row[$f] == 1 ) {
                    $s = "Active";
                } elseif( $row[$f] == 2 ) {
                    $s = "Inactive";
                }
                printf("<input type=text size=$size class=\"ajax\" $ajax_id value=\"%s\" sorttable_customkey=\"%s\"></td>",
                        $s, $row[$f]);
            } else {
                printf("<input type=text size=$size class=\"ajax\" $ajax_id value=\"%s\" sorttable_customkey=\"%s\"></td>",
                        $row[$f], strtolower($row[$f]));
            }
            echo "</td>";
        }
        echo "</tr>\n";
    }
    echo "</tbody>";
    echo "<tfoot>";
    $id = 0;
    $jsx = [];
    $jsx[] = "setValue('id',$id)";
    $jsx[] = "setValue('area','$society')";
    $jsx[] = "setValue('func','add')";
    $jsx[] = "addAction('update')";
    $js = sprintf("onclick=\"%s\"", join(';', $jsx));
    echo "<tr>";
    echo "<td class=c><input type=button class=add value=Add $js></td>";
    echo "<td>&nbsp;</td>";
    foreach ($fields as $f) {
        $row[$f] = "";
        $tag = MakeTag(implode("_", [$f, $id]));
        $size = array_key_exists($f, $sizes) ? $sizes[$f] : 5;
        echo "<td>";
        if ($f == "amount") {
            printf("<input $tag type=text size=$size class=\"r\" placeholder=\"??\"></td>" );
        } elseif ($f == "frequency") {
            echo "<select $tag>";
            $selected = "selected";
            foreach (["onetimetab", "monthlytab"] as $opt) {
                echo "<option value=\"$opt\" $selected>$opt</option>";
                $selected = "";
            }
        } elseif( $gArea == "all" && $f == "society" ) {
            echo "<select $tag>";
            $selected = "";
            foreach (["nachas", "rimon","carol"] as $opt) {
                echo "<option value=\"$opt\">$opt</option>";
                $selected = "selected";
            }
        } else {
            printf("<input type=text $tag size=$size value='%s' placeholder=\"??\"></td>",
                    $row[$f], $row[$f]);
        }
        echo "</td>";
    }
    $missingFields = [
        'method' => 'manual',
        'schedEndDate' => '0000-00-00',
        'schedFrequency' => 'Monthly',
        'schedStartDate' => date("%Y-%m-%d"),
        'schedNumPayments' => 12,
        'untilCancelled' => 0 ];
    foreach( $missingFields as $f => $v ) {
        $tag = MakeTag(implode("_", [$f, $id]));
        echo "<input type=hidden $tag value=\"$v\"></input>";
    }
    if( $society != "all" ) {
        $tag = MakeTag(implode("_", ['society', $id]));
        echo "<input type=hidden $tag value=\"$gArea\"></input>";
    }
    echo "</tr>";
    echo "</tfoot>";
    echo "</table>";
}

function displaySite() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }

    if ($gAction == 'logout')
        return;

    $uname = ($user->is_logged_in() ) ? " - $gUserName" : "";
    $mode = ($gTestModeEnabled) ? " - <span class=mail-test>Mail Safe</span>" : " - <span class=mail-live>** Mail Live **</span>";

    echo "IHDS Societies Manager (<span id=site-prod>{$gSiteName}{$uname}{$mode}</span>): ";

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function dumpCSV() {
    include 'includes/globals.php';

    $society = func_get_arg(0);
    
    if(func_num_args() == 1 ) { # Manual Download
        $file = "Donor Report - $society.csv";
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $file . '";');
        $fh = fopen('php://output', 'w');
        $mode = "manual";
        
    } else {
        $file = "$gSiteDir/tmp/Donor-Report-$society.csv";
        $fh = fopen($file, "w");
        $mode = "cron";
    }

    $quals = [];
    $quals[] = "success = 1";
    if ($society == "rimon")
        $quals[] = "society = \"$society\"";
    if ($society == "nachas")
        $quals[] = "society = \"$society\"";
    if ($society == "carol")
        $quals[] = "society = \"$society\"";

    $query = "select * from donations where " . implode(" and ", $quals);

    $stmt = DoQuery($query);
    $num = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $num++;
        if ($num == 1) {
            $fields = [];
            foreach ($row as $key => $val) {
                $fields[] = $key;
            }
            fputcsv($fh, $fields, ',');
        }
        $values = [];
        foreach ($row as $key => $val) {
            $values[] = $val;
        }
        fputcsv($fh, $values, ',');
    }
    fclose($fh);
    
    if( $mode == "manual" ) {
        exit();
    } else {
        $temp = DoQuery( "select value from mail where label = 'Email: Nightly'");
        $dist_list = array();
        while( list( $name ) = $temp->fetch(PDO::FETCH_NUM) ) {
            $tmp = explode(",", $name);
            $dist_list[] = $tmp[0];
        }
        $dist = implode(" ", $dist_list);
        $sh_file =  "$gSiteDir/tmp/cron-email.sh";
        $fh = fopen($sh_file,"w");
        fputs($fh, "#!/bin/bash -x\n");
        fputs($fh, "cd $gSiteDir/tmp\n");
        fputs($fh, "echo | mailx -a $file -s \"Donor Database\" $dist\n");
        fclose($fh);
        chmod($sh_file, 0700);
        exec($sh_file,$output,$retval);
        #unlink($sh_file);
        if( $retval) {
            error_log(print_r($output,true) );
        }
    }
}

function initialize() {
    include 'includes/globals.php';
    $gDb = $gPDO[$gDbControlId]['inst'];

    $gAction = array_key_exists('action', $_POST) ? $_POST["action"] : "welcome";

    $req = $_SERVER['QUERY_STRING'];
    if (!empty($req)) {
        $tmp = parse_str($req, $qs);
        if (array_key_exists('action', $qs) && $qs['action'] == 'password' &&
                array_key_exists('func', $qs) && $qs['func'] == 'reset') {
            $gAction = 'password';
            $gFunc = 'reset';
            $gFrom = 'email';
            $gResetKey = $qs['key'];
        } elseif (!array_key_exists('XDEBUG_SESSION_START', $qs)) {
            UserManager('logout');
            exit;
        }
    }

    $proto = ( array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == "on" ) ? "https" : "http";
    $gSourceCode = sprintf("%s://%s%s", $proto, $_SERVER['SERVER_NAME'], $_SERVER['SCRIPT_NAME']);
    $gFunction = array();


    $tmp = ['action', 'area', 'from', 'func', 'mode', 'where'];
    foreach ($tmp as $name) {
        $gn = 'g' . ucfirst($name);
        if (!isset($$gn)) {
            $$gn = array_key_exists($name, $_POST) ? $_POST[$name] : "";
        }
    }

    $gAccessNameToLevel = array();
    $gAccessNameEnabled = array();
    $gAccessLevels = array();
    $stmt = DoQuery('select * from privileges order by level desc');
    if ($stmt->rowCount() == 0) {
        $query = "insert into privileges set name = :name, level = :level, enabled = :enabled";
        DoQuery($query, [':name' => 'control', ':level' => 500, ':enabled' => 1]);
        DoQuery($query, [':name' => 'admin', ':level' => 400, ':enabled' => 0]);
        DoQuery($query, [':name' => 'office', ':level' => 300, ':enabled' => 0]);
        $stmt = DoQuery('select * from privileges order by level desc');
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gAccessNameToId[$row['name']] = $row['id'];
        $gAccessNameToLevel[$row['name']] = $row['level'];
        $gAccessNameEnabled[$row['name']] = $row['enabled'];
        $gAccessLevelEnabled[$row['level']] = $row['enabled'];
        $gAccessLevels[] = $row['name'];
    }

    loadMailSettings();

    $stmt = DoQuery("select value from misc where label = 'Site_Name'");
    if ($gPDO_num_rows) {
        list($gSiteName) = $stmt->fetch(PDO::FETCH_NUM);
    }

    $gModeToButtons = $gAreaToMode = [];
//=====================================    
    $mode = 'control';
    $buttons = [];
    $buttons[] = ['area' => 'source', 'js' => "setValue('func','display')",
        'bbl' => [['area' => 'source', 'label' => 'Refresh', 'js' => "setValue('func','display')"]]];
    $buttons[] = ['area' => 'backup', 'action' => 'backup'];
    $buttons[] = ['area' => 'eventLog', 'label' => 'Event Log'];
    $buttons[] = ['area' => 'mail',
        'bbl' => [['area' => 'mail', 'action' => 'update', 'label' => 'Update', 'js' => "setValue('func','update')"]]];
    $buttons[] = ['area' => 'misc',
        'bbl' => [['area' => 'misc', 'action' => 'update', 'label' => 'Update', 'js' => "setValue('func','update')"]]];
    $buttons[] = ['area' => 'users',
        'bbl' => [['area' => 'users', 'action' => 'update', 'label' => 'Update', 'js' => "setValue('func','update')"]]];
    $buttons[] = ['area' => 'privileges',
        'bbl' => [['area' => 'privileges', 'action' => 'update', 'label' => 'Update', 'js' => "setValue('func','update')"]]];
//    $buttons[] = ['area' => 'debug', 'label' => 'Debug', 'js' => "setValue('func','display')"];
    $buttons[] = ['area' => 'special', 'label' => 'Special', 'js' => "setValue('func','special')"];

    $gModeToButtons[$mode] = $buttons;
    foreach ($buttons as $obj) {
        $gAreaToMode[$obj['area']] = $mode;
    }
//=====================================    
    $mode = 'admin';
    $buttons = array();
    /*
      $buttons[] = ['area' => 'fees'];
      $buttons[] = ['area' => 'financials',
      'bbl' => [
      ['area' => 'financials', 'action' => 'display', 'label' => 'Reset Filters', 'js' => "setValue('func','reset')"]
      ]];
      $buttons[] = ['area' => 'discounts'];
      $buttons[] = ['area' => 'events', 'label' => 'Event Log',
      'bbl' => [['area' => 'events', 'action' => 'init', 'label' => 'Are you sure?', 'js' => "setValue('func','confirm')"]]];
      $buttons[] = ['area' => 'message'];
      $buttons[] = ['area' => 'statistics'];
     */
    $gModeToButtons[$mode] = $buttons;
    foreach ($buttons as $obj) {
        $gAreaToMode[$obj['area']] = $mode;
    }
//=====================================    
    $mode = 'office';
    $buttons = array();
    $buttons[] = ['area' => 'carol', 'js' => "setValue('func','show'),setValue('area','carol')"];
    $buttons[] = ['area' => 'rimon', 'js' => "setValue('func','show'),setValue('area','rimon')"];
    $buttons[] = ['area' => 'nachas', 'js' => "setValue('func','show'),setValue('area','nachas')"];
    $buttons[] = ['area' => 'all', 'js' => "setValue('func','show'),setValue('area','all')"];

    $gModeToButtons[$mode] = $buttons;
    foreach ($buttons as $obj) {
        $gAreaToMode[$obj['area']] = $mode;
    }
}

function loadMailSettings() {
    include 'includes/globals.php';

    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    $gMailAdmin = $gMailDefault = $gMailTesting = [];
    $query = "select label, `value`, enabled from mail where lower(label) like '%email:%'";

    $stmt = DoQuery($query);
    if ($gPDO_num_rows == 0) {
        DoQuery("insert into mail (label, value, enabled) values ('Email Server','',0)");
        DoQuery("insert into mail (label, value, enabled) values ('Email: Default','andy.elster@gmail.com, Andy Elster',1)");
    }

    $gMailLive = 0;
    $stmt = DoQuery($query);
    while (list( $label, $value, $enabled ) = $stmt->fetch(PDO::FETCH_NUM)) {
        $tmp = preg_split("/,/", $value, NULL, PREG_SPLIT_NO_EMPTY);
        $j = count($tmp);
        if ($j == 1) {
            $email = $name = $tmp[0];
        } elseif ($j == 2) {
            $email = $tmp[0];
            $name = $tmp[1];
        }
        if (stripos($label, "admin") !== false) {
            $gMailAdmin[] = ['email' => "$email", 'name' => "$name"];

            if( $enabled && ! $gProduction) {
                DoQuery("update mail set enabled = 0 where label = 'Email: Admin'"); # Don't let me send out live emails from home
                echo "<script type=\"text/javascript\">alert('WARNING: Non-Production Machine: gMailLive forced to off');</script>";
                $enabled = false;
            }
            $gMailLive = $enabled;
        } elseif (stripos($label, "default") !== false) {
            $gMailDefault[] = ['email' => "$email", 'name' => "$name"];
        } elseif (stripos($label, "backup") !== false) {
            $gMailBackup[] = ['email' => "$email", 'name' => "$name"];
        } elseif ($enabled && stripos($label, "testing") !== false) {
            $gMailTesting[] = ['email' => "$email", 'name' => "$name"];
        } elseif (stripos($label, "server") !== false) {
            $gMailServer = $gMailDB[$value];
        }
    }

    if (count($gMailAdmin) == 0) {
        $gMailAdmin = $gMailDefault;
    }
    if (count($gMailTesting) == 0) {
        $gMailTesting = $gMailDefault;
    }

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function phase1() {     # Phase1 is for pre-output actions that would interfere with PDF production
    include 'includes/globals.php';

    $action = array_key_exists('action', $_POST) ? $_POST['action'] : "home";
    if ($action == "rimon" || $action == "nachas" || $action == "carol" || $action == "all") {
        dumpCSV($action);
        exit();
    }
    $dpv_pre = "Begin";
    $dpv_phase = 1;
    $dpv_tag = "pre-html";

    addHtmlHeader();

    if ($gDebug) {
        Logger('****************************************************************************');
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    if ($gDebug & $gDebugWindow) {
        echo "<script type='text/javascript'>\n";
        $tag = ($gDreamweaver) ? "Dreamweaver" : "";
        echo "createDebugWindow('$tag');\n";
        echo "var d = new Date();\n";
        echo "debug('--- Non-Production. Start of run @ ' + d + ' ---')\n";
        echo "</script>\n";
    }

    addForm();

    if ($gAction == 'login' && $gFunc == 'verify') {
        Logger("user NOT logged in, verifying password");
        UserManager('verify');
    }

    if( $gAction == "backup" ) {
        BackupMySql();
    }
    
    $val = 0;
    if ($user->is_logged_in()) {
        Logger("user logged in");
        UserManager('load', $_SESSION['userid']);
        $saveDb = $gDb;
        $gDb = $gPDO[$gDbControlId]['inst'];
        $stmt = DoQuery( "select priv_id from access where id = $gUserId");
        list($val) = $stmt->fetch(PDO::FETCH_NUM);
        $stmt = DoQuery( "select name from privileges where id = $val");
        list($gUserAccess) = $stmt->fetch(PDO::FETCH_NUM);
        $gDb = $saveDb;
        echo "<script type=\"text/javascript\">createIdleTimer();</script>";   
    } else {
        Logger("user not logged in");
    }
//    $val = 6;

    logger("Phase1: gUserId: [$gUserId]");
    logger("Phase1: val : [$val]");

    if ($gDebug) {
        $dpv_pre = "End";
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function phase2() { # updates
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }

    $dpv_pre = "Begin";
    $dpv_phase = 2;
    $dpv_tag = "pre-html";

    if ($gDebug) {
        Logger('****************************************************************************');
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    switch ($gAction) {
        case "add":
            addDonor();
            break;

        case "reset":
            if ($gFunc == "change") {
                UserManager('reset');
                $gFunc = "welcome";
            }
            break;

        case "update":
            switch ($gArea) {
                case "debug":
                    MyDebug("update");
                    $gAction = "display";
                    break;

                case "mail":
                    updateMail();
                    $gAction = "display";
                    break;

                case "misc":
                    updateMisc();
                    $gAction = "display";
                    break;

                case "all":
                case "nachas":
                case "rimon":
                case "carol":
                    if( $gFunc == 'delete') {
                        deleteDonor();
                    } elseif( $gFunc == 'add') {
                        addDonor();
                    }
                    $gAction = "display";
                    break;

                case "users":
                    if( $gFunc == 'reset') {
                        UserManager('mail');
                    } else {
                        UserManager('update');
                    }
                    $gAction = "display";
                    break;
            }

            break;
    }

    if ($gDebug) {
        $dpv_pre = "End";
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function phase3() { # display
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    $dpv_pre = "Begin";
    $dpv_phase = 3;
    $dpv_tag = "prePaletteDisplay";

    if ($gDebug) {
        Logger('****************************************************************************');
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    if ($gAction == "password") {
        if ($gFunc == "send") {
            UserManager("forgot");
        }
    }
    if ($gDebug) {
        $dpv_pre = "End";
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function xxresetMail() {
    include 'includes/globals.php';
    include 'local_mailer.php';

    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    $gMailAdmin = $gMailDefault = $gMailTesting = [];
    $query = "select label, `value`, enabled, DueBy from misc where lower(label) like '%email:%'";

    $stmt = DoQuery($query);
    if ($gPDO_num_rows == 0) {
        DoQuery("insert into misc (label, value, enabled) values ('Email Server','',0)");
        DoQuery("insert into misc (label, value, enabled) values ('Email: Default','andy.elster@gmail.com, Andy Elster',1)");
        DoQuery("insert into misc (label, value, enabled) values ('First Payment','',0)");
        DoQuery("insert into misc (label, value, enabled) values ('Materials & Supplies Due Date','',0)");
        DoQuery("insert into misc (label, value, enabled) values ('Forms Due Date','',0)");
    }
    if (!$gProduction) {
        DoQuery("update misc set enabled = 0 where label = 'Email: Admin'");
        DoQuery("update misc set value = 'Dev' where label = 'Site_Name'");
    }

    $gTestModeEnabled = 1;
    $stmt = DoQuery($query);
    while (list( $label, $value, $enabled, $dueBy ) = $stmt->fetch(PDO::FETCH_NUM)) {
        $tmp = preg_split("/,/", $value, NULL, PREG_SPLIT_NO_EMPTY);
        $j = count($tmp);
        if ($j == 1) {
            $email = $name = $tmp[0];
        } elseif ($j == 2) {
            $email = $tmp[0];
            $name = $tmp[1];
        }
        if (stripos($label, "admin") !== false) {
            $gMailAdmin[] = ['email' => "$email", 'name' => "$name"];
            $gTestModeEnabled = !$enabled;
        } elseif (stripos($label, "default") !== false) {
            $gMailDefault[] = ['email' => "$email", 'name' => "$name"];
        } elseif (stripos($label, "backup") !== false) {
            $gMailBackup[] = ['email' => "$email", 'name' => "$name"];
        } elseif ($enabled && stripos($label, "testing") !== false) {
            $gMailTesting[] = ['email' => "$email", 'name' => "$name"];
        } elseif (stripos($label, "server") !== false) {
            $gMailServer = $gMailDB[$dueBy];
        }
    }

    if (count($gMailAdmin) == 0) {
        $gMailAdmin = $gMailDefault;
    }
    if (count($gMailTesting) == 0) {
        $gMailTesting = $gMailDefault;
    }

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function selectDB() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        if (defined('DB_OPEN')) {
            Logger('fy select (DB_OPEN defined):');
        } else {
            Logger('fy select (DB_OPEN not defined - first call):');
        }
    }
    for ($i = 0; $i < count($gPDO); $i++) {
        $tmp = [];
        $tmp[] = $gPDO[$i]['host'];
        $tmp[] = 'dbname=' . $gPDO[$i]['dbname'];
        $tmp[] = 'charset=' . $gPDO[$i]['charset'];
        $dsn = implode(';', $tmp);
        $user = $gPDO[$i]['user'];
        $pass = $gPDO[$i]['pass'];
        $attr = $gPDO[$i]['attr'];
        $gDbNames[$i] = $gPDO[$i]['dbname'];
        try {
            //create PDO connection
            if ($gProduction) {
                $attr[PDO::ATTR_ERRMODE] = PDO::ERRMODE_SILENT;
            } else {
                $attr[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
            }
            $gPDO[$i]['inst'] = new PDO($dsn, $user, $pass, $attr);
        } catch (PDOException $e) {
            echo "failed to open DB #$i<br>";
            //show error
            echo '<p class="bg-danger">' . $e->getMessage() . '</p>';
            $gDbControl = NULL;
            throw $e;
        }
    }
    $gDb = $gPDO[$gDbControlId]['inst'];

    initialize();
}

function updateMail() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    if ($gFunc == 'add') {
        $v = preg_split('/,/', $_POST['fields'], NULL, PREG_SPLIT_NO_EMPTY);
        $flds = array_unique($v);
        $qx = [];
        $args = [];
        $i = 0;
        $ok = 1;
        foreach ($flds as $fld) {
            list( $label, $colName, $id ) = preg_split('/\|/', $fld);
            $var = implode("_", [$colName, $id]);
            if (array_key_exists($var, $_POST) && !empty($_POST[$var])) {
                $val = $_POST[$var];
                if (stripos($val, "email: admin") !== false) {
                    DoQuery("select id from mail where lower(label) like \"%email: admin%\"");
                    if ($gPDO_num_rows > 0)
                        $ok = 0;
                }
                if (stripos($val, "email: default") !== false) {
                    DoQuery("select id from mail where lower(label) like \"%email: default%\"");
                    if ($gPDO_num_rows > 0)
                        $ok = 0;
                }
                $qx[] = sprintf("`%s` = :v%d", $colName, $i);
                $args[":v$i"] = $val;
                $i++;
            }
        }
        $query = "insert into mail set " . join(',', $qx);
        if ($ok) {
            Logger("query: [$query]");
            Logger("args: [" . print_r($args, true) . "]");
            DoQuery($query, $args);
        }
    } elseif ($gFunc == 'del') {
        $id = $_POST['id'];
        DoQuery("delete from mail where id = :id", [':id' => $id]);
    } elseif ($gFunc == 'update') {
        $v = preg_split('/,/', $_POST['fields'], NULL, PREG_SPLIT_NO_EMPTY);
        $flds = array_unique($v);
        foreach ($flds as $fld) {
            $args = [];
            list( $label, $colName, $id ) = preg_split('/\|/', $fld);
            $query = "update mail set " . sprintf("%s = :v1", $colName);
            $query .= " where id = :v2";
            $var = implode("_", [$colName, $id]);
            $newVal = array_key_exists($var, $_POST) ? $_POST[$var] : 0;
            $args[":v1"] = $newVal;
            $args[":v2"] = $id;
            DoQuery($query, $args);
        }
    }
    loadMailSettings();
    if ($gTrace) {
        array_pop($gFunction);
    }
}
